from pydantic import BaseModel, ConfigDict
from uuid import UUID
from datetime import datetime
from typing import Optional

class LiveLogBase(BaseModel):
    source_ip: Optional[str] = None
    log_message: str
    threat_level: Optional[str] = None

class LiveLogCreate(LiveLogBase):
    timestamp: Optional[datetime] = None

class LiveLogResponse(LiveLogBase):
    id: UUID
    agent_id: UUID
    timestamp: datetime
    
    model_config = ConfigDict(from_attributes=True)
