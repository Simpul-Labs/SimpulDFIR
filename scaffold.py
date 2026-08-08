import os
from pathlib import Path

base_dir = Path("d:/Simpul-DFIR/backend")
files = {
    "app/core/config.py": """\
from pydantic_settings import BaseSettings

class Settings(BaseSettings):
    PROJECT_NAME: str = "Simpul DFIR Master Node"
    API_V1_STR: str = "/api/v1"
    DATABASE_URL: str = "postgresql+asyncpg://postgres:password@localhost:5432/simpul_dfir"

    class Config:
        case_sensitive = True

settings = Settings()
""",
    "app/core/database.py": """\
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import declarative_base, sessionmaker
from app.core.config import settings
from typing import AsyncGenerator

engine = create_async_engine(settings.DATABASE_URL, echo=True, future=True)
AsyncSessionLocal = sessionmaker(
    bind=engine, class_=AsyncSession, expire_on_commit=False
)
Base = declarative_base()

async def get_db() -> AsyncGenerator[AsyncSession, None]:
    async with AsyncSessionLocal() as session:
        yield session
""",
    "app/models/__init__.py": """\
from .agent import Agent
from .live_log import LiveLog
from .forensic_case import ForensicCase
""",
    "app/models/agent.py": """\
from sqlalchemy import Column, String, Boolean, DateTime
from sqlalchemy.dialects.postgresql import UUID
import uuid
from datetime import datetime, timezone
from app.core.database import Base

class Agent(Base):
    __tablename__ = "agents"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4, index=True)
    hostname = Column(String, index=True, nullable=False)
    ip_address = Column(String, nullable=False)
    auth_token = Column(String, unique=True, index=True, nullable=False)
    is_online = Column(Boolean, default=False)
    last_seen = Column(DateTime(timezone=True), default=lambda: datetime.now(timezone.utc))
""",
    "app/models/live_log.py": """\
from sqlalchemy import Column, String, DateTime, ForeignKey, Text
from sqlalchemy.dialects.postgresql import UUID
from datetime import datetime, timezone
from app.core.database import Base
import uuid

class LiveLog(Base):
    __tablename__ = "live_logs"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4, index=True)
    agent_id = Column(UUID(as_uuid=True), ForeignKey("agents.id", ondelete="CASCADE"), nullable=False)
    timestamp = Column(DateTime(timezone=True), default=lambda: datetime.now(timezone.utc))
    source_ip = Column(String, index=True, nullable=True)
    log_message = Column(Text, nullable=False)
    threat_level = Column(String, nullable=True) # e.g., INFO, WARN, CRITICAL
""",
    "app/models/forensic_case.py": """\
from sqlalchemy import Column, String, DateTime, ForeignKey
from sqlalchemy.dialects.postgresql import UUID
from datetime import datetime, timezone
from app.core.database import Base
import uuid

class ForensicCase(Base):
    __tablename__ = "forensic_cases"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4, index=True)
    agent_id = Column(UUID(as_uuid=True), ForeignKey("agents.id", ondelete="CASCADE"), nullable=False)
    case_number = Column(String, unique=True, index=True, nullable=False)
    original_file = Column(String, nullable=False)
    sha256_hash = Column(String, nullable=False)
    report_path = Column(String, nullable=True)
    created_at = Column(DateTime(timezone=True), default=lambda: datetime.now(timezone.utc))
""",
    "app/schemas/__init__.py": """\
from .agent import AgentCreate, AgentResponse, AgentUpdate
from .live_log import LiveLogCreate, LiveLogResponse
from .forensic_case import ForensicCaseCreate, ForensicCaseResponse
""",
    "app/schemas/agent.py": """\
from pydantic import BaseModel, ConfigDict
from uuid import UUID
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
    id: UUID
    is_online: bool
    last_seen: datetime
    
    model_config = ConfigDict(from_attributes=True)
""",
    "app/schemas/live_log.py": """\
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
""",
    "app/schemas/forensic_case.py": """\
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
""",
    "app/api/v1/endpoints/auth.py": """\
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.ext.asyncio import AsyncSession
from app.core.database import get_db
from app.schemas.agent import AgentCreate, AgentResponse
import uuid
from datetime import datetime, timezone

router = APIRouter()

@router.post("/register", response_model=AgentResponse, status_code=status.HTTP_201_CREATED)
async def register_agent(
    agent_in: AgentCreate,
    db: AsyncSession = Depends(get_db)
):
    \"\"\"
    Registers a new agent and returns the agent details along with an auth token.
    \"\"\"
    # TODO: Implement token generation and DB insertion logic
    return {
        "id": uuid.uuid4(),
        "hostname": agent_in.hostname,
        "ip_address": agent_in.ip_address,
        "is_online": True,
        "last_seen": datetime.now(timezone.utc)
    }
""",
    "app/api/v1/endpoints/logs.py": """\
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.ext.asyncio import AsyncSession
from app.core.database import get_db
from app.schemas.live_log import LiveLogCreate
from typing import List

router = APIRouter()

@router.post("/push", status_code=status.HTTP_201_CREATED)
async def push_logs(
    logs_in: List[LiveLogCreate],
    db: AsyncSession = Depends(get_db)
    # TODO: token verification dependency to get current_agent
):
    \"\"\"
    Receives JSON logs from agents.
    \"\"\"
    # TODO: Bulk insert LiveLog records into the database
    return {"status": "success", "processed_logs": len(logs_in)}
""",
    "app/api/v1/endpoints/commands.py": """\
from fastapi import APIRouter, Depends
from sqlalchemy.ext.asyncio import AsyncSession
from app.core.database import get_db
from typing import List

router = APIRouter()

@router.get("/poll")
async def poll_commands(
    db: AsyncSession = Depends(get_db)
    # TODO: token verification dependency to get current_agent
):
    \"\"\"
    Agent checks for pending commands (e.g., iptables BLOCK).
    \"\"\"
    # TODO: Fetch pending commands for this agent
    return [
        {"command_type": "BLOCK_IP", "target_ip": "114.119.160.20", "duration_seconds": 3600}
    ]
""",
    "app/api/v1/endpoints/forensic.py": """\
from fastapi import APIRouter, Depends, UploadFile, File, Form, HTTPException, status
from sqlalchemy.ext.asyncio import AsyncSession
from app.core.database import get_db

router = APIRouter()

@router.post("/upload", status_code=status.HTTP_201_CREATED)
async def upload_forensic_archive(
    case_number: str = Form(...),
    sha256_hash: str = Form(...),
    file: UploadFile = File(...),
    db: AsyncSession = Depends(get_db)
    # TODO: token verification dependency
):
    \"\"\"
    Receives a static log archive and its SHA-256 hash from the agent.
    \"\"\"
    # TODO: Handle file upload, verify hash, and save record in ForensicCase
    return {
        "status": "success",
        "case_number": case_number,
        "filename": file.filename,
        "message": "Archive uploaded securely and hash verified."
    }
""",
    "app/api/v1/api.py": """\
from fastapi import APIRouter
from app.api.v1.endpoints import auth, logs, commands, forensic

api_router = APIRouter()

api_router.include_router(auth.router, prefix="/auth", tags=["Authentication"])
api_router.include_router(logs.router, prefix="/logs", tags=["Logs"])
api_router.include_router(commands.router, prefix="/commands", tags=["Commands"])
api_router.include_router(forensic.router, prefix="/forensic", tags=["Forensic"])
""",
    "app/main.py": """\
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.api.v1.api import api_router
from app.core.config import settings

app = FastAPI(
    title=settings.PROJECT_NAME,
    description="Master Node API for Simpul DFIR",
    version="1.0.0"
)

# CORS Middleware (configure appropriately for production)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(api_router, prefix=settings.API_V1_STR)

@app.get("/health", tags=["Health"])
async def health_check():
    return {"status": "healthy"}
""",
    "requirements.txt": """\
fastapi
uvicorn[standard]
sqlalchemy[asyncio]
asyncpg
pydantic
pydantic-settings
python-multipart
alembic
"""
}

for rel_path, content in files.items():
    p = base_dir / rel_path
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(content, encoding='utf-8')

print(f"Scaffolded {len(files)} files successfully at {base_dir}")
