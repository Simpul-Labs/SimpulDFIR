from pydantic import BaseModel

from typing import Optional

class AgentMetrics(BaseModel):
    cpu: float
    cpu_count: Optional[int] = 1
    ram: float
    ram_used_gb: Optional[float] = 0
    ram_total: Optional[float] = 0
    disk: float
    net_in: float
    net_out: float
