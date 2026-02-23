<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AssetController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssetRequirementController;
use App\Http\Controllers\TaskController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('assets', AssetController::class);

    // 🚧 RUTAS FUTURAS (déjalas comentadas hasta crear los controladores)
    Route::get('/assets/{asset}/requirements/{requirement}', [AssetRequirementController::class, 'show'])
        ->name('assets.requirements.show');

    Route::get('/requirements/{requirement}/tasks', [TaskController::class, 'index'])
        ->name('requirements.tasks.index');
});

require __DIR__ . '/auth.php';