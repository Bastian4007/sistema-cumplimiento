<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class TaskDocumentController extends Controller
{
    public function index(Task $task, Request $request)
    {
        // ✅ Seguridad multiempresa por la relación Task -> Requirement -> Asset
        abort_if($task->requirement->company_id !== $request->user()->company_id, 403);

        $task->load(['documents.uploader']);

        return view('tasks.documents', [
            'task' => $task,
            'requirement' => $task->requirement,
            'asset' => $task->requirement->asset,
        ]);
    }

    public function store(Task $task, Request $request)
    {
        abort_if($task->requirement->company_id !== $request->user()->company_id, 403);

        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB
        ]);

        $path = $request->file('file')->store(
            'task-documents/'.$task->id,
            'public'
        );

        TaskDocument::create([
            'task_id' => $task->id,
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Documento subido.');
    }

    public function download(TaskDocument $document, Request $request)
    {
        // cargar task y validar empresa
        $document->load('task.requirement');

        abort_if($document->task->requirement->company_id !== $request->user()->company_id, 403);

        return Storage::disk('public')->download($document->file_path);
    }

    public function destroy(TaskDocument $document, Request $request)
    {
        $document->load('task.requirement');

        abort_if($document->task->requirement->company_id !== $request->user()->company_id, 403);

        // (opcional) Solo operativo
        abort_if(!$request->user()->isOperative(), 403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('status', 'Documento eliminado.');
    }

   public function preview(Task $task, TaskDocument $document)
    {
        // ✅ el documento debe pertenecer a la task
        abort_unless($document->task_id === $task->id, 404);

        // ✅ validar company por requirement (porque task no tiene company_id)
        $companyId = $task->requirement?->company_id;
        abort_unless($companyId && $companyId === auth()->user()->company_id, 403);

        $path = $document->file_path;

        abort_unless(Storage::disk('public')->exists($path), 404);

        $mime = Storage::disk('public')->mimeType($path) ?? 'application/octet-stream';

        $allowed = [
            'application/pdf',
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/webp',
            'image/gif',
        ];

        if (!in_array($mime, $allowed, true)) {
            return back()->withErrors([
                'preview' => 'Este tipo de archivo no se puede previsualizar. Descárgalo en su lugar.',
            ]);
        }

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}