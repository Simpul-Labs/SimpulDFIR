<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\LiveLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function store(Request $request)
    {
        $token = $request->header('Authorization');
        if (empty($token)) {
            return response()->json(['detail' => 'Not authenticated'], 401);
        }

        $agent = Agent::where('auth_token', $token)->first();
        if (!$agent) {
            return response()->json(['detail' => 'Invalid or revoked token'], 401);
        }

        $agent->last_seen = now();
        $agent->is_online = true;
        
        $ip = $request->ip();
        if ($ip && ($agent->ip_address === '0.0.0.0' || empty($agent->ip_address))) {
            $agent->ip_address = $ip;
        }
        $agent->save();

        $logData = $request->all();
        
        $log = new LiveLog();
        $log->agent_id = $agent->id;
        $log->source_ip = $logData['source_ip'] ?? $ip;
        $log->log_message = $logData['log_message'] ?? '';
        $log->threat_level = $logData['threat_level'] ?? 'INFO';
        $log->save();

        return response()->json(['status' => 'ok']);
    }

    public function index(Request $request)
    {
        $limit = $request->query('limit', 50);
        $logs = LiveLog::with('agent:id,hostname,ip_address')
            ->orderBy('timestamp', 'desc')
            ->take($limit)
            ->get();
            
        return response()->json($logs);
    }
}
