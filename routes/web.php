<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetRequirementController;
use App\Http\Controllers\RequirementTaskController;
use App\Http\Controllers\TaskDocumentController;
use App\Http\Controllers\ComplianceDashboardController;
use App\Http\Controllers\AssetRequirementDocumentController;
use App\Http\Controllers\RequirementAuditLogController;
use App\Http\Controllers\RequirementHistoryController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [ComplianceDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Assets CRUD
    Route::resource('assets', AssetController::class);

    // Asset activation
    Route::patch('assets/{asset}/deactivate', [AssetController::class, 'deactivate'])->name('assets.deactivate');
    Route::patch('assets/{asset}/activate', [AssetController::class, 'activate'])->name('assets.activate');

    // Requirements (nested under asset)
    Route::get('assets/{asset}/requirements/{requirement}', [AssetRequirementController::class, 'show'])
        ->name('assets.requirements.show');

    Route::patch('assets/{asset}/requirements/{requirement}/complete', [AssetRequirementController::class, 'complete'])
        ->name('assets.requirements.complete');

    // Requirement official documents (nested under asset + requirement)
    // ✅ Cambié {document} a {requirementDocument} para evitar binding equivocado
    Route::get('assets/{asset}/requirements/{requirement}/documents', [AssetRequirementDocumentController::class, 'index'])
        ->name('assets.requirements.documents.index');

    Route::post('assets/{asset}/requirements/{requirement}/documents', [AssetRequirementDocumentController::class, 'store'])
        ->name('assets.requirements.documents.store');

    Route::get('assets/{asset}/requirements/{requirement}/documents/{document}/preview', [AssetRequirementDocumentController::class, 'preview'])
        ->name('assets.requirements.documents.preview');

    Route::get('assets/{asset}/requirements/{requirement}/documents/{document}/download', [AssetRequirementDocumentController::class, 'download'])
        ->name('assets.requirements.documents.download');

    Route::delete('assets/{asset}/requirements/{requirement}/documents/{document}', [AssetRequirementDocumentController::class, 'destroy'])
        ->name('assets.requirements.documents.destroy');

    // Tasks for a requirement (separate controller)
    Route::get('requirements/{requirement}/tasks/create', [RequirementTaskController::class, 'create'])
        ->name('requirements.tasks.create');

    Route::post('requirements/{requirement}/tasks', [RequirementTaskController::class, 'store'])
        ->name('requirements.tasks.store');

    Route::get('requirements/{requirement}/tasks/{task}/edit', [RequirementTaskController::class, 'edit'])
        ->name('requirements.tasks.edit');

    Route::put('requirements/{requirement}/tasks/{task}', [RequirementTaskController::class, 'update'])
        ->name('requirements.tasks.update');

    Route::delete('requirements/{requirement}/tasks/{task}', [RequirementTaskController::class, 'destroy'])
        ->name('requirements.tasks.destroy');

    Route::patch('requirements/{requirement}/tasks/{task}/complete', [RequirementTaskController::class, 'complete'])
        ->name('requirements.tasks.complete');

    Route::patch('requirements/{requirement}/tasks/{task}/reopen', [RequirementTaskController::class, 'reopen'])
        ->name('requirements.tasks.reopen');

    // Task documents
    Route::get('tasks/{task}/documents', [TaskDocumentController::class, 'index'])
        ->name('tasks.documents.index');

    Route::post('tasks/{task}/documents', [TaskDocumentController::class, 'store'])
        ->name('tasks.documents.store');

    Route::get('tasks/{task}/documents/{document}/preview', [TaskDocumentController::class, 'preview'])
        ->name('tasks.documents.preview');

    Route::get('documents/{document}/download', [TaskDocumentController::class, 'download'])
        ->name('documents.download');

    Route::delete('documents/{document}', [TaskDocumentController::class, 'destroy'])
        ->name('documents.destroy');

    Route::get(
        'assets/{asset}/requirements/{requirement}/audit-logs',
        [RequirementAuditLogController::class, 'index']
    )->name('assets.requirements.audit-logs');

    Route::get(
        'assets/{asset}/requirements/{requirement}/history',
        [RequirementHistoryController::class, 'index']
    )->name('assets.requirements.history');

    Route::get(
        'assets/{asset}/requirements/{requirement}/tasks/{task}/history',
        [RequirementHistoryController::class, 'task']
    )->name('assets.requirements.tasks.history');
});

require __DIR__ . '/auth.php';