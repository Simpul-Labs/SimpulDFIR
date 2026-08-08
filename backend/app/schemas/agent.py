from pydantic import BaseModel, ConfigDict
from datetime import datetime
from typing import Optional

class AgentBase(BaseModel):
    hostname: str
    ip_address: str

class AgentCreate(AgentBase):
    pass

class AgentUpdate(BaseModel):
    is_online: Optional[bool] = None
    last_seen: Optional[datetime] = None

class AgentResponse(AgentBase):
    id: str
    is_online: bool
    last_seen: datetime
    
    model_config = ConfigDict(from_attributes=True)
