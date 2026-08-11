from pydantic import BaseModel

from typing import Optional

class AgentMetrics(BaseModel):
    cpu: float
    ram: float
    ram_total: Optional[float] = 0
    disk: float
    net_in: float
    net_out: float
