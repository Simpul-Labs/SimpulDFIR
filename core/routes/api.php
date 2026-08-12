<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\LogController;

Route::prefix('v1')->group(function () {
    // Agent Endpoints (For Go Agent)
    Route::get('/agents/install.sh', [AgentController::class, 'installScript']);
    Route::get('/agents/download', [AgentController::class, 'download']);
    Route::post('/agents/{agent_id}/metrics', [AgentController::class, 'storeMetrics']);
    Route::post('/agents/{agent_id}/connections', [AgentController::class, 'storeConnections']);
    
    // Logs endpoints (For Go Agent)
    Route::post('/logs/push', [LogController::class, 'store']);
});
