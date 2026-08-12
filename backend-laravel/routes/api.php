<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\LogController;

Route::prefix('v1')->group(function () {
    
    // System endpoints
    Route::get('/system/status', [SystemController::class, 'status']);
    
    // Agent endpoints
    Route::get('/agents', [AgentController::class, 'index']);
    Route::post('/agents/token', [AgentController::class, 'generateToken']);
    Route::get('/agents/install.sh', [AgentController::class, 'installScript']);
    Route::get('/agents/download', [AgentController::class, 'download']);
    Route::delete('/agents/{agent_id}', [AgentController::class, 'destroy']);
    
    // Agent metrics
    Route::post('/agents/{agent_id}/metrics', [AgentController::class, 'storeMetrics']);
    Route::get('/agents/{agent_id}/metrics', [AgentController::class, 'getMetrics']);
    
    // Logs endpoints
    Route::post('/logs/push', [LogController::class, 'store']);
    Route::get('/logs/live', [LogController::class, 'index']);
});
