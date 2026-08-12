<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class AgentController extends Controller
{
    public function index()
    {
        return response()->json(Agent::orderBy('last_seen', 'desc')->get());
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
        $installCmd = "curl -sSf http://{$host}/api/v1/agents/install.sh?token={$token} | bash";

        return response()->json([
            'token' => $token,
            'target_ip' => $targetIp,
            'install_command' => $installCmd
        ]);
    }

    public function download()
    {
        $path = public_path('simpul-agent');
        if (!file_exists($path)) {
            return response()->json(['detail' => 'Agent binary not found.'], 404);
        }
        return response()->download($path, 'simpul-agent');
    }

    public function installScript(Request $request)
    {
        $token = $request->query('token', '$(cat /proc/sys/kernel/random/uuid)');
        $host = $request->header('host', 'localhost:8000');
        $masterUrl = "http://{$host}";

        $script = <<<EOT
#!/bin/bash
set -e

# Simpul DFIR - Secure One-Touch Agent Installation
MASTER_URL="{$masterUrl}"
BIN_PATH="/usr/local/bin/simpul-agent"
SERVICE_PATH="/etc/systemd/system/simpul-agent.service"

echo "========================================="
echo " Installing Simpul DFIR Agent"
echo " Master Node: \$MASTER_URL"
echo "========================================="

echo "[1/4] Stopping existing service and downloading agent binary..."
systemctl stop simpul-agent 2>/dev/null || true
if ! curl -fsSL -o /tmp/simpul-agent \$MASTER_URL/api/v1/agents/download; then
    echo "ERROR: Failed to download agent binary from \$MASTER_URL"
    echo "       Make sure the Master Node is running and the binary is built."
    exit 1
fi
mv /tmp/simpul-agent \$BIN_PATH
chmod +x \$BIN_PATH

echo "[2/4] Applying Secret Authentication Token..."
TOKEN="{$token}"

echo "[3/4] Creating systemd service..."
cat <<EOF > \$SERVICE_PATH
[Unit]
Description=Simpul DFIR Agent
After=network.target

[Service]
Type=simple
ExecStart=\$BIN_PATH
Restart=on-failure
Environment="MASTER_NODE_URL=\$MASTER_URL/api/v1/logs/push"
Environment="AGENT_AUTH_TOKEN=\$TOKEN"

[Install]
WantedBy=multi-user.target
EOF

echo "[4/4] Starting and enabling agent service..."
systemctl daemon-reload
systemctl enable simpul-agent
systemctl restart simpul-agent

echo "========================================="
echo " Agent successfully installed and running!"
echo " It will automatically register with the Master Node."
echo " Check status with: systemctl status simpul-agent"
echo "========================================="
EOT;

        return response($script)->header('Content-Type', 'text/plain');
    }

    public function destroy($id)
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

    public function storeMetrics(Request $request, $id)
    {
        $metrics = $request->all();
        
        $agent = Agent::where('id', $id)->orWhere('hostname', $id)->first();
        if (!$agent) {
            return response()->json(['detail' => 'Agent not found'], 404);
        }

        $ip = $request->ip();
        $agent->last_seen = now();
        $agent->is_online = true;
        if ($ip && ($agent->ip_address === '0.0.0.0' || empty($agent->ip_address))) {
            $agent->ip_address = $ip;
        }
        $agent->save();

        Cache::put("metrics_{$id}", $metrics, now()->addMinutes(5));

        return response()->json(['status' => 'ok']);
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

    public function storeConnections(Request $request, $id)
    {
        $agent = Agent::where('id', $id)->orWhere('hostname', $id)->first();
        if (!$agent) {
            return response()->json(['detail' => 'Agent not found'], 404);
        }

        $connections = $request->all();
        
        // Basic sync approach: delete old and insert new. 
        // In a high volume production env, you might update existing ones instead.
        \App\Models\ActiveConnection::where('agent_id', $agent->id)->delete();
        
        $insertData = [];
        $now = now();
        foreach ($connections as $conn) {
            $insertData[] = [
                'agent_id' => $agent->id,
                'proto' => $conn['proto'] ?? 'tcp',
                'local_address' => $conn['local_address'] ?? 'unknown',
                'state' => $conn['state'] ?? 'LISTEN',
                'pid_program' => $conn['pid_program'] ?? 'unknown',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        if (!empty($insertData)) {
            \App\Models\ActiveConnection::insert($insertData);
        }

        $agent->last_seen = now();
        $agent->is_online = true;
        $agent->save();

        return response()->json(['status' => 'ok']);
    }
}
