from fastapi import APIRouter
import psutil
import time

router = APIRouter()

# Initialize cpu_percent (the first call with interval=None returns 0.0)
psutil.cpu_percent(interval=None)

@router.get("/status")
async def get_system_status():
    """
    Get master node system status (CPU, RAM, and time).
    """
    # Using interval=None returns the average CPU usage since the last call
    cpu_percent = psutil.cpu_percent(interval=None)
    ram_percent = psutil.virtual_memory().percent
    current_time = time.strftime("%Y-%m-%d %H:%M:%S %Z")
    
    return {
        "cpu": cpu_percent,
        "ram": ram_percent,
        "time": current_time,
        "ntp_sync": True
    }
