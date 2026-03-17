<?php

namespace App\Services\Requirements;

use App\Models\AssetRequirement;
use App\Models\AssetRequirementDocument;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\RequirementStatus;
use App\Enums\TaskStatus;

class UploadOfficialDocumentService
{
    public function handle(
        AssetRequirement $requirement,
        UploadedFile $file,
        ?string $issuedAt = null,
        ?string $expiresAt = null,
        ?string $notes = null
    ): AssetRequirementDocument {
        return DB::transaction(function () use ($requirement, $file, $issuedAt, $expiresAt, $notes) {
            $requirement = AssetRequirement::query()
                ->whereKey($requirement->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentDocument = AssetRequirementDocument::query()
                ->where('asset_requirement_id', $requirement->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            $nextVersion = (int) AssetRequirementDocument::query()
                ->where('asset_requirement_id', $requirement->id)
                ->max('version_number');

            $nextVersion++;

            $directory = "companies/{$requirement->company_id}/requirements/{$requirement->id}/official-documents";
            $filename = now()->format('Ymd_His') . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs($directory, $filename, 'private');

            $newDocument = AssetRequirementDocument::create([
                'company_id' => $requirement->company_id,
                'asset_requirement_id' => $requirement->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'uploaded_at' => now(),
                'is_current' => true,
                'status' => 'active',
                'version_number' => $nextVersion,
                'notes' => $notes,
            ]);

            if ($currentDocument) {
                $currentDocument->update([
                    'is_current' => false,
                    'status' => 'replaced',
                    'replaced_by_document_id' => $newDocument->id,
                ]);
            }

            AssetRequirement::where('id', $requirement->id)->update([
                'status' => RequirementStatus::COMPLETED,
                'completed_at' => now(),
                'issued_at' => $newDocument->issued_at,
                'expires_at' => $newDocument->expires_at,
                'current_document_id' => $newDocument->id,
            ]);

            $this->completeOpenRenewalTask($requirement);

            if ($newDocument->expires_at) {
                $this->createNextRenewalTask($requirement, $newDocument);
            }

            return $newDocument;
        });
    }

    protected function completeOpenRenewalTask(AssetRequirement $requirement): void
    {
        $task = $requirement->tasks()
            ->where('type', Task::TYPE_RENEWAL)
            ->whereIn('status', [
                TaskStatus::PENDING,
                TaskStatus::IN_PROGRESS,
            ])
            ->orderBy('id')
            ->first();

        if (! $task) {
            return;
        }

        $task->update([
            'status' => TaskStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    protected function createNextRenewalTask(
        AssetRequirement $requirement,
        AssetRequirementDocument $document
    ): void {
        if (! $document->expires_at) {
            return;
        }

        $dueDate = Carbon::parse($document->expires_at)->subDays(60);

        $alreadyExists = $requirement->tasks()
            ->where('type', Task::TYPE_RENEWAL)
            ->whereDate('due_date', $dueDate->toDateString())
            ->whereIn('status', [
                TaskStatus::PENDING,
                TaskStatus::IN_PROGRESS,
            ])
            ->exists();
            
        if ($alreadyExists) {
            return;
        }

        $title = $requirement->template?->name ?? $requirement->type;
        $responsibleUserId = $requirement->asset?->responsible_user_id;

        $task = Task::create([
            'asset_requirement_id' => $requirement->id,
            'title' => 'Renovar ' . $title .' ' . $dueDate->year,
            'description' => 'Renovación automática generada al registrar una nueva versión del documento oficial.',
            'type' => Task::TYPE_RENEWAL,
            'status' => TaskStatus::PENDING,
            'due_date' => $dueDate->toDateString(),
            'requires_document' => false,
        ]);

        if ($responsibleUserId) {
            $task->users()->sync([$responsibleUserId]);
        }
    }
}