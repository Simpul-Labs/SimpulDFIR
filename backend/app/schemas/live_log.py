from pydantic import BaseModel, ConfigDict
from datetime import datetime
from typing import Optional

class LiveLogBase(BaseModel):
    source_ip: Optional[str] = None
    log_message: str
    threat_level: Optional[str] = None

class LiveLogCreate(LiveLogBase):
    hostname: str
    timestamp: Optional[datetime] = None

class LiveLogResponse(LiveLogBase):
    id: str
    agent_id: str
    timestamp: datetime
    
    model_config = ConfigDict(from_attributes=True)
