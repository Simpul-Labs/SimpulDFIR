<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\LiveLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LogController extends Controller
{
    public function store(Request $request)
    {
        $logsData = $request->all();
        if (empty($logsData) || !is_array($logsData)) {
            return response()->json(['detail' => 'Invalid payload'], 400);
        }

        $header = $request->header('Authorization');
        if (empty($header) || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['detail' => 'Not authenticated'], 401);
        }
        
        $token = substr($header, 7);
        $agent = Agent::where('auth_token', $token)->first();

        // If agent doesn't exist but has a pending token from install script
        if (!$agent) {
            $pending = Cache::get("pending_token_{$token}");
            if ($pending) {
                // Get hostname from the first log payload
                $hostname = $logsData[0]['hostname'] ?? 'unknown-' . substr($token, 0, 8);
                $ip = $request->ip();

                $agent = new Agent();
                $agent->hostname = $hostname;
                $agent->ip_address = $pending['target_ip'] ?? $ip;
                $agent->auth_token = $token;
                $agent->is_online = true;
                $agent->save();

                Cache::forget("pending_token_{$token}");
            } else {
                // To maintain direct compatibility with old Python backend for dev-token-123
                // Auto-register if token is dev-token-123 or if we want to allow legacy agents
                if ($token === 'dev-token-123') {
                    $hostname = $logsData[0]['hostname'] ?? 'dev-agent';
                    $agent = Agent::where('hostname', $hostname)->first();
                    if (!$agent) {
                        $agent = new Agent();
                        $agent->hostname = $hostname;
                        $agent->ip_address = $request->ip();
                        $agent->auth_token = $token;
                        $agent->is_online = true;
                        $agent->save();
                    }
                } else {
                    return response()->json(['detail' => 'Invalid or revoked token'], 401);
                }
            }
        }

        $agent->last_seen = now();
        $agent->is_online = true;
        
        $ip = $request->ip();
        if ($ip && ($agent->ip_address === '0.0.0.0' || empty($agent->ip_address))) {
            $agent->ip_address = $ip;
        }
        $agent->save();

        foreach ($logsData as $logData) {
            if (!is_array($logData)) continue;

            $log = new LiveLog();
            $log->agent_id = $agent->id;
            $log->source_ip = $logData['source_ip'] ?? $ip;
            $log->log_message = $logData['log_message'] ?? '';
            $log->threat_level = $logData['threat_level'] ?? 'INFO';
            // Use agent's timestamp if provided, else current DB time
            if (isset($logData['timestamp'])) {
                $log->timestamp = $logData['timestamp'];
            }
            $log->save();
        }

        return response()->json(['status' => 'ok', 'processed_logs' => count($logsData)]);
    }

    public function index(Request $request)
    {
        $limit = $request->query('limit', 50);
        $agentId = $request->query('agent_id');

        $query = LiveLog::with('agent:id,hostname,ip_address')
            ->orderBy('timestamp', 'desc')
            ->take($limit);
            
        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        $logs = $query->get();
            
        return response()->json($logs);
    }
}
