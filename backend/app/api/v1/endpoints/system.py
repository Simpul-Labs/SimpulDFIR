from fastapi import APIRouter
import psutil
import time

router = APIRouter()

@router.get("/status")
async def get_system_status():
    """
    Get master node system status (CPU and time).
    """
    cpu_percent = psutil.cpu_percent(interval=0.1)
    current_time = time.strftime("%Y-%m-%d %H:%M:%S %Z")
    
    return {
        "cpu": cpu_percent,
        "time": current_time,
        "ntp_sync": True
    }
