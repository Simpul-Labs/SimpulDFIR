from fastapi import APIRouter
import psutil
import platform
import time
import os

router = APIRouter()

# Initialize cpu_percent (the first call with interval=None returns 0.0)
psutil.cpu_percent(interval=None)

# Cache system specs (they don't change at runtime)
_cpu_count = psutil.cpu_count(logical=True)
_cpu_count_phys = psutil.cpu_count(logical=False)
_ram_total_gb = round(psutil.virtual_memory().total / (1024**3), 1)
_disk_total_gb = round(psutil.disk_usage('/').total / (1024**3), 1)
_hostname = platform.node() or os.environ.get("HOSTNAME", "master-node")
_os_info = f"{platform.system()} {platform.release()}"

@router.get("/status")
async def get_system_status():
    """
    Get master node system status (CPU, RAM, disk, and time) with hardware specs.
    """
    # Using interval=None returns the average CPU usage since the last call
    cpu_percent = psutil.cpu_percent(interval=None)
    ram = psutil.virtual_memory()
    disk = psutil.disk_usage('/')
    current_time = time.strftime("%Y-%m-%d %H:%M:%S %Z")
    
    return {
        "cpu": cpu_percent,
        "cpu_count": _cpu_count,
        "cpu_count_phys": _cpu_count_phys,
        "ram": ram.percent,
        "ram_used_gb": round(ram.used / (1024**3), 1),
        "ram_total_gb": _ram_total_gb,
        "disk": disk.percent,
        "disk_used_gb": round(disk.used / (1024**3), 1),
        "disk_total_gb": _disk_total_gb,
        "hostname": _hostname,
        "os": _os_info,
        "time": current_time,
        "ntp_sync": True
    }
