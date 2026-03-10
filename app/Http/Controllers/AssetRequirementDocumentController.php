<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\AssetRequirementDocument;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Concerns\ValidatesCompany;
use App\Enums\RequirementStatus;
use App\Enums\TaskStatus;
use Carbon\Carbon;

class AssetRequirementDocumentController extends Controller
{
    use ValidatesCompany;

    private function disk()
    {
        return Storage::disk('private');
    }

    public function index(Asset $asset, AssetRequirement $requirement)
    {
        $this->assertRequirementBelongsToAsset($asset, $requirement);
        $this->assertSameCompany($asset);

        $assetInactive = method_exists($asset, 'isInactive')
            ? $asset->isInactive()
            : ($asset->status === 'inactive');

        $requirement->load([
            'template',
            'documents.uploader',
        ]);

        $navContext = [
            'asset' => $asset,
            'requirement' => $requirement,
            'task' => null,
            'documentSection' => true,
            'documentOwner' => 'requirement',
        ];

        return view('requirements.documents', [
            'asset' => $asset,
            'requirement' => $requirement,
            'assetInactive' => $assetInactive,
            'navContext' => $navContext,
        ]);
    }

    public function store(Request $request, Asset $asset, AssetRequirement $requirement)
    {
        $this->assertRequirementBelongsToAsset($asset, $requirement);
        $this->assertSameCompany($asset);

        if (method_exists($asset, 'isInactive') ? $asset->isInactive() : ($asset->status === 'inactive')) {
            return back()->with('error', 'El activo está desactivado. No puedes subir documentación oficial.');
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['required', 'date', 'after:today'],
        ], [
            'expires_at.required' => 'La fecha de vencimiento es obligatoria.',
            'expires_at.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
        ]);

        // 1) Si ya existe un documento oficial, borrarlo (archivo + registro)
        $existing = AssetRequirementDocument::where('asset_requirement_id', $requirement->id)
            ->latest()
            ->first();

        if ($existing) {
            if ($this->disk()->exists($existing->file_path)) {
                $this->disk()->delete($existing->file_path);
            }
            $existing->delete();
        }

        // 2) Guardar el nuevo archivo en PRIVATE
        $file = $request->file('file');

        $path = $file->store(
            "companies/{$asset->company_id}/requirements/{$requirement->id}",
            'private'
        );

        // 3) Guardar metadata del documento
        AssetRequirementDocument::create([
            'asset_requirement_id' => $requirement->id,
            'company_id' => $asset->company_id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
            'issued_at' => $data['issued_at'] ?? null,
            'expires_at' => $data['expires_at'],
        ]);

        // 4) Actualizar vigencia del requerimiento y marcarlo como completado
        $requirement->update([
            'issued_at' => $data['issued_at'] ?? null,
            'expires_at' => $data['expires_at'],
            'status' => RequirementStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        // 5) Crear tarea automática de renovación
        $this->createRenewalTask($asset, $requirement, $data['expires_at']);

        return back()->with('success', 'Documento oficial subido correctamente y tarea de renovación generada.');
    }

    public function download(Asset $asset, AssetRequirement $requirement, AssetRequirementDocument $document)
    {
        $this->assertRequirementBelongsToAsset($asset, $requirement);
        $this->assertSameCompany($asset);
        $this->assertDocumentBelongsToRequirement($document, $requirement);

        if (!$this->disk()->exists($document->file_path)) {
            abort(404);
        }

        return $this->disk()->download(
            $document->file_path,
            $document->original_name ?? basename($document->file_path)
        );
    }

    public function preview(Asset $asset, AssetRequirement $requirement, AssetRequirementDocument $document)
    {
        $this->assertRequirementBelongsToAsset($asset, $requirement);
        $this->assertSameCompany($asset);
        $this->assertDocumentBelongsToRequirement($document, $requirement);

        if (!$this->disk()->exists($document->file_path)) {
            abort(404);
        }

        $fullPath = $this->disk()->path($document->file_path);

        $mime = $document->mime_type ?: (file_exists($fullPath) ? mime_content_type($fullPath) : null);

        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!$mime || !in_array($mime, $allowed, true)) {
            abort(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'Preview not supported');
        }

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $this->safeFilename($document->original_name ?? 'document') . '"',
        ]);
    }

    public function destroy(Asset $asset, AssetRequirement $requirement, AssetRequirementDocument $document)
    {
        $this->assertRequirementBelongsToAsset($asset, $requirement);
        $this->assertSameCompany($asset);
        $this->assertDocumentBelongsToRequirement($document, $requirement);

        if (method_exists($asset, 'isInactive') ? $asset->isInactive() : ($asset->status === 'inactive')) {
            abort(403, 'Asset inactive');
        }

        if ($this->disk()->exists($document->file_path)) {
            $this->disk()->delete($document->file_path);
        }

        $document->delete();

        $requirement->refresh();

        $remaining = $requirement->documents()->count();

        if ($remaining === 0) {
            $requirement->update([
                'status' => RequirementStatus::IN_PROGRESS,
                'completed_at' => null,
                'issued_at' => null,
                'expires_at' => null,
            ]);
        }

        return back()->with('status', 'Documento eliminado.');
    }

    private function createRenewalTask(Asset $asset, AssetRequirement $requirement, string $expiresAt): void
    {
        $requirementName = $requirement->template?->name ?? $requirement->type ?? 'Requerimiento';
        $title = 'Renovar ' . $requirementName;

        // 60 días antes del vencimiento
        $dueDate = Carbon::parse($expiresAt)->subDays(60);

        if ($dueDate->isPast()) {
            $dueDate = Carbon::today()->addDay();
        }

        $alreadyExists = Task::query()
            ->where('asset_requirement_id', $requirement->id)
            ->where('title', $title)
            ->whereNull('completed_at')
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $task = Task::create([
            'asset_requirement_id' => $requirement->id,
            'title' => $title,
            'description' => 'Renovación automática del documento oficial del requerimiento.',
            'due_date' => $dueDate->toDateString(),
            'requires_document' => true,
            'status' => TaskStatus::PENDING,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        if ($asset->responsible_user_id) {
            $task->users()->sync([$asset->responsible_user_id]);
        }
    }

    private function assertRequirementBelongsToAsset(Asset $asset, AssetRequirement $requirement): void
    {
        if ((int) $requirement->asset_id !== (int) $asset->id) {
            abort(404);
        }
    }

    private function assertDocumentBelongsToRequirement(AssetRequirementDocument $document, AssetRequirement $requirement): void
    {
        if ((int) $document->asset_requirement_id !== (int) $requirement->id) {
            abort(404);
        }
    }

    private function safeFilename(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9\.\-\_\s]/', '', $name) ?: 'document';
    }
}