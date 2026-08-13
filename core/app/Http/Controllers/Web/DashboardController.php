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
        return view('pages.dashboard');
    }

    public function cyberops()
    {
        return view('pages.dashboard');
    }

    public function forensics()
    {
        return view('pages.dashboard');
    }

    public function status()
    {
        // Fetch real Linux system metrics
        $cpu = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
        
        $meminfo = @file_get_contents("/proc/meminfo");
        $ramTotal = 16.0;
        $ramUsed = 7.2;
        $ramPercent = 45.1;
        if ($meminfo) {
            preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $total);
            preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $avail);
            if (isset($total[1]) && isset($avail[1])) {
                $ramTotal = $total[1] / 1024 / 1024;
                $ramAvail = $avail[1] / 1024 / 1024;
                $ramUsed = $ramTotal - $ramAvail;
                $ramPercent = $ramTotal > 0 ? ($ramUsed / $ramTotal) * 100 : 0;
            }
        }

        $diskTotal = @disk_total_space("/") ? disk_total_space("/") / 1024 / 1024 / 1024 : 500.0;
        $diskFree = @disk_free_space("/") ? disk_free_space("/") / 1024 / 1024 / 1024 : 350.0;
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = $diskTotal > 0 ? ($diskUsed / $diskTotal) * 100 : 0;

        $cpuCount = (int)@shell_exec("nproc");
        if ($cpuCount <= 0) $cpuCount = 8;
        
        $cpuPhys = (int)@shell_exec("lscpu -p | egrep -v '^#' | sort -u -t, -k 2,2 | wc -l");
        if ($cpuPhys <= 0) $cpuPhys = 4;

        return response()->json([
            'cpu' => round($cpu, 1),
            'ram' => round($ramPercent, 1),
            'ram_used_gb' => round($ramUsed, 1),
            'ram_total_gb' => round($ramTotal, 1),
            'disk' => round($diskPercent, 1),
            'disk_used_gb' => round($diskUsed, 1),
            'disk_total_gb' => round($diskTotal, 1),
            'cpu_count' => $cpuCount,
            'cpu_count_phys' => $cpuPhys,
            'hostname' => gethostname(),
            'username' => get_current_user(),
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'time' => Carbon::now()->format('H:i:s'),
            'ntp_sync' => true,
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

    public function generateForensicReport(Request $request, $id)
    {
        $agent = Agent::where('id', $id)->orWhere('hostname', $id)->first();
        if (!$agent) return response()->json(['error' => 'Agent not found'], 404);

        $logs = $request->input('logs', []);
        
        $hasSystemState = in_array('System State Snapshot', $logs);
        $hasAuthLog = false;
        $hasNginxLog = false;
        
        foreach($logs as $l) {
            if (strpos($l, 'auth.log') !== false) $hasAuthLog = true;
            if (strpos($l, 'nginx') !== false) $hasNginxLog = true;
        }

        // Create initial report row with a generated UUID
        $report = \App\Models\ForensicReport::create([
            'agent_id' => $agent->id,
            'status' => 'COMPLETED', 
            'hash' => hash('sha256', (string) Str::uuid()),
            'pdf_data' => 'generating...'
        ]);

        // Render HTML using Blade
        $html = view('reports.forensic_pdf', compact('agent', 'report', 'logs', 'hasSystemState', 'hasAuthLog', 'hasNginxLog'))->render();
        
        // Ensure storage directory exists
        if (!\Illuminate\Support\Facades\Storage::exists('forensics')) {
            \Illuminate\Support\Facades\Storage::makeDirectory('forensics');
        }

        // Save HTML
        \Illuminate\Support\Facades\Storage::put("forensics/{$report->id}.html", $html);

        $report->update(['pdf_data' => "forensics/{$report->id}.html"]);

        return response()->json($report);
    }

    public function downloadPdf($id)
    {
        $report = \App\Models\ForensicReport::find($id);
        if (!$report) return response()->json(['error' => 'Report not found'], 404);

        $path = "forensics/{$report->id}.pdf";
        if (!\Illuminate\Support\Facades\Storage::exists($path)) {
            return response()->json(['error' => 'PDF not found on server'], 404);
        }

        return \Illuminate\Support\Facades\Storage::download($path, "{$report->id}_NIST_Forensic_Report.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function downloadHtml($id)
    {
        $report = \App\Models\ForensicReport::find($id);
        if (!$report) return response()->json(['error' => 'Report not found'], 404);

        $path = "forensics/{$report->id}.html";
        if (!\Illuminate\Support\Facades\Storage::exists($path)) {
            return response()->json(['error' => 'HTML not found on server'], 404);
        }

        $cleanFilename = "Laporan_Forensik_NIST_" . substr($report->id, 0, 8) . ".html";

        return \Illuminate\Support\Facades\Storage::download($path, $cleanFilename, [
            'Content-Type' => 'text/html',
        ]);
    }
}
