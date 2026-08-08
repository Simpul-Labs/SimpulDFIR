from fastapi import APIRouter
import psutil
from datetime import datetime, timezone

router = APIRouter()

@router.get("/status")
async def get_system_status():
    """
    Get master node system status (CPU and time).
    """
    cpu_percent = psutil.cpu_percent(interval=0.1)
    current_time = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S UTC")
    
    return {
        "cpu": cpu_percent,
        "time": current_time,
        "ntp_sync": True
    }
