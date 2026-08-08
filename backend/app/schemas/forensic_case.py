from pydantic import BaseModel, ConfigDict
from uuid import UUID
from datetime import datetime
from typing import Optional

class ForensicCaseBase(BaseModel):
    case_number: str
    original_file: str
    sha256_hash: str

class ForensicCaseCreate(ForensicCaseBase):
    pass

class ForensicCaseResponse(ForensicCaseBase):
    id: UUID
    agent_id: UUID
    report_path: Optional[str] = None
    created_at: datetime
    
    model_config = ConfigDict(from_attributes=True)
