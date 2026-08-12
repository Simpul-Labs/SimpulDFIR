<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\LiveLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function status()
    {
        return response()->json([
            'ntp_sync' => true,
            'system_time' => Carbon::now()->toIso8601String(),
            'cpu' => 5.2,
            'ram' => 45.1,
            'disk' => 30.5,
            'ram_used_gb' => 7.2,
            'ram_total_gb' => 16.0
        ]);
    }

    public function agents()
    {
        return response()->json(Agent::orderBy('last_seen', 'desc')->get());
    }

    public function destroyAgent($id)
    {
        $agent = Agent::where('id', $id)->orWhere('hostname', $id)->orWhere('ip_address', $id)->first();
        if ($agent) {
            Cache::forget("metrics_{$agent->id}");
            Cache::forget("metrics_{$agent->hostname}");
            $agent->delete();
            return response()->noContent();
        }
        return response()->json(['detail' => 'Agent not found'], 404);
    }

    public function generateToken(Request $request)
    {
        $targetIp = $request->input('target_ip');
        $token = 'sec_' . Str::random(32);
        
        Cache::put("pending_token_{$token}", [
            'target_ip' => $targetIp,
            'created_at' => now()
        ], now()->addHours(1));

        $host = $request->header('host', 'localhost:8000');
        // Install script remains an API endpoint because agents don't have session auth
        $installCmd = "curl -sSf http://{$host}/api/v1/agents/install.sh?token={$token} | bash";

        return response()->json([
            'token' => $token,
            'target_ip' => $targetIp,
            'install_command' => $installCmd
        ]);
    }

    public function getMetrics($id)
    {
        $metrics = Cache::get("metrics_{$id}", [
            'cpu' => 0,
            'ram' => 0,
            'disk' => 0,
            'net_in' => 0,
            'net_out' => 0,
            'cpuCount' => 0,
            'os' => 'Unknown',
            'uptime' => 0
        ]);
        return response()->json($metrics);
    }

    public function recentLogs(Request $request)
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

    public function getAgentConnections($id)
    {
        $agent = Agent::where('id', $id)->orWhere('hostname', $id)->first();
        if (!$agent) return response()->json([], 404);

        $connections = \App\Models\ActiveConnection::where('agent_id', $agent->id)->orderBy('updated_at', 'desc')->get();
        return response()->json($connections);
    }

    public function getForensicReports($id)
    {
        $agent = Agent::where('id', $id)->orWhere('hostname', $id)->first();
        if (!$agent) return response()->json([], 404);

        $reports = \App\Models\ForensicReport::where('agent_id', $agent->id)->orderBy('created_at', 'desc')->get();
        return response()->json($reports);
    }

    public function generateForensicReport($id)
    {
        $agent = Agent::where('id', $id)->orWhere('hostname', $id)->first();
        if (!$agent) return response()->json(['error' => 'Agent not found'], 404);

        $report = \App\Models\ForensicReport::create([
            'agent_id' => $agent->id,
            'status' => 'COMPLETED', // Simulating instant generation for now
            'hash' => hash('sha256', (string) Str::uuid()),
            'pdf_data' => 'dummy_path.pdf'
        ]);

        return response()->json($report);
    }
}
