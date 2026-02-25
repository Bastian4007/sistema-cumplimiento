<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AssetController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssetRequirementController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\RequirementTaskController;
use App\Http\Controllers\TaskDocumentController;
use App\Http\Controllers\ComplianceDashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [ComplianceDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('assets', AssetController::class);
    Route::get('/assets/{asset}/requirements/{requirement}', [AssetRequirementController::class, 'show'])
        ->name('assets.requirements.show');

    Route::patch('assets/{asset}/requirements/{requirement}/complete', [AssetRequirementController::class, 'complete'])
        ->name('assets.requirements.complete');

    Route::get('/requirements/{requirement}/tasks/create', [RequirementTaskController::class, 'create'])
        ->name('requirements.tasks.create');

    Route::post('/requirements/{requirement}/tasks', [RequirementTaskController::class, 'store'])
        ->name('requirements.tasks.store');

    Route::get('/requirements/{requirement}/tasks/{task}/edit', [RequirementTaskController::class, 'edit'])
        ->name('requirements.tasks.edit');

    Route::put('/requirements/{requirement}/tasks/{task}', [RequirementTaskController::class, 'update'])
        ->name('requirements.tasks.update');

    Route::delete('/requirements/{requirement}/tasks/{task}', [RequirementTaskController::class, 'destroy'])
        ->name('requirements.tasks.destroy');

    Route::patch('/requirements/{requirement}/tasks/{task}/complete', [RequirementTaskController::class, 'complete'])
        ->name('requirements.tasks.complete');

    Route::patch('/requirements/{requirement}/tasks/{task}/reopen', [RequirementTaskController::class, 'reopen'])
        ->name('requirements.tasks.reopen');

    Route::get('/tasks/{task}/documents', [TaskDocumentController::class, 'index'])
    ->name('tasks.documents.index');

    Route::post('/tasks/{task}/documents', [TaskDocumentController::class, 'store'])
        ->name('tasks.documents.store');

    Route::get('/documents/{document}/download', [TaskDocumentController::class, 'download'])
        ->name('documents.download');

    Route::delete('/documents/{document}', [TaskDocumentController::class, 'destroy'])
        ->name('documents.destroy');
    
    Route::get('tasks/{task}/documents/{document}/preview', [TaskDocumentController::class, 'preview'])
        ->name('tasks.documents.preview');

    Route::patch('assets/{asset}/deactivate', [AssetController::class, 'deactivate'])
    ->name('assets.deactivate');

    Route::patch('assets/{asset}/activate', [AssetController::class, 'activate'])
        ->name('assets.activate');
});

require __DIR__ . '/auth.php';