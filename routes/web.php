<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ComplianceDashboardController;

Route::get('/me/compliance-dashboard', [ComplianceDashboardController::class, 'me']);

Route::get('/', function () {
    return view('welcome');
});
