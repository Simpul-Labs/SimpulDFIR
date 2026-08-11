from fastapi import APIRouter, Depends, Request, HTTPException
from fastapi.responses import FileResponse, PlainTextResponse
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, desc
from app.core.database import get_db
from app.models.agent import Agent
from app.schemas.agent import AgentResponse
from app.schemas.metrics import AgentMetrics
from typing import List, Dict
import os

router = APIRouter()

# In-memory store for real-time metrics
ACTIVE_METRICS: Dict[str, AgentMetrics] = {}


@router.get("/", response_model=List[AgentResponse])
async def get_all_agents(db: AsyncSession = Depends(get_db)):
    """
    Get list of all registered agents.
    """
    result = await db.execute(
        select(Agent).order_by(desc(Agent.last_seen))
    )
    agents = result.scalars().all()
    return agents

@router.get("/download")
async def download_agent():
    """
    Serve the compiled agent binary.
    """
    agent_path = "/app/static/simpul-agent"
    if not os.path.exists(agent_path):
        raise HTTPException(status_code=404, detail="Agent binary not found. Master node might be improperly built.")
    return FileResponse(agent_path, media_type="application/octet-stream", filename="simpul-agent")

@router.get("/install.sh")
async def get_install_script(request: Request):
    """
    Dynamically generates the bash installation script.
    """
    # Detect Master IP dynamically from headers
    host = request.headers.get("host")
    if not host:
        host = "localhost:8000"
        
    master_url = f"http://{host}"
    
    script = f"""#!/bin/bash
set -e

# Simpul DFIR - Zero Touch Agent Installation
MASTER_URL="{master_url}"
BIN_PATH="/usr/local/bin/simpul-agent"
SERVICE_PATH="/etc/systemd/system/simpul-agent.service"

echo "========================================="
echo " Installing Simpul DFIR Agent"
echo " Master Node: $MASTER_URL"
echo "========================================="

echo "[1/4] Stopping existing service and downloading agent binary..."
systemctl stop simpul-agent 2>/dev/null || true
curl -sSL -o /tmp/simpul-agent $MASTER_URL/api/v1/agents/download
mv /tmp/simpul-agent $BIN_PATH
chmod +x $BIN_PATH

echo "[2/4] Generating Auth Token..."
TOKEN=$(cat /proc/sys/kernel/random/uuid)

echo "[3/4] Creating systemd service..."
cat <<EOF > $SERVICE_PATH
[Unit]
Description=Simpul DFIR Agent
After=network.target

[Service]
Type=simple
ExecStart=$BIN_PATH
Restart=on-failure
Environment="MASTER_NODE_URL=$MASTER_URL/api/v1/logs/push"
Environment="AGENT_AUTH_TOKEN=$TOKEN"

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
"""
    return PlainTextResponse(script)

@router.delete("/{agent_id}", status_code=204)
async def delete_agent(agent_id: str, db: AsyncSession = Depends(get_db)):
    """
    Deletes an agent from the Master Node database.
    """
    result = await db.execute(select(Agent).where(Agent.id == agent_id))
    agent = result.scalars().first()
    if not agent:
        raise HTTPException(status_code=404, detail="Agent not found")
        
    await db.delete(agent)
    await db.commit()
    return None

@router.post("/{agent_id}/metrics")
async def post_metrics(agent_id: str, metrics: AgentMetrics):
    """
    Receive real-time system metrics from an agent.
    """
    ACTIVE_METRICS[agent_id] = metrics
    return {"status": "ok"}

@router.get("/{agent_id}/metrics", response_model=AgentMetrics)
async def get_metrics(agent_id: str):
    """
    Get the latest real-time system metrics for a specific agent.
    """
    metrics = ACTIVE_METRICS.get(agent_id)
    if not metrics:
        # Return fallback zeros if no data received yet
        return AgentMetrics(cpu=0, ram=0, disk=0, net_in=0, net_out=0)
    return metrics

