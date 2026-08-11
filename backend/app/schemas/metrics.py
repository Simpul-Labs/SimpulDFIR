from pydantic import BaseModel

class AgentMetrics(BaseModel):
    cpu: float
    ram: float
    disk: float
    net_in: float
    net_out: float
