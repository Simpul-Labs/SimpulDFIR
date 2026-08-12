<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // JSON endpoints used by the dashboard Javascript
    Route::get('/api/web/system/status', [DashboardController::class, 'status']);
    Route::get('/api/web/agents', [DashboardController::class, 'agents']);
    Route::delete('/api/web/agents/{id}', [DashboardController::class, 'destroyAgent']);
    Route::post('/api/web/agents/token', [DashboardController::class, 'generateToken']);
    Route::get('/api/web/agents/{id}/metrics', [DashboardController::class, 'getMetrics']);
    Route::get('/api/web/agents/{id}/connections', [DashboardController::class, 'getAgentConnections']);
    Route::get('/api/web/logs/recent', [DashboardController::class, 'recentLogs']);
    Route::post('/api/web/agents/{id}/forensics/generate', [DashboardController::class, 'generateForensicReport']);
    Route::get('/api/web/agents/{id}/forensics/reports', [DashboardController::class, 'getForensicReports']);
});
