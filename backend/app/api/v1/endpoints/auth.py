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
    """
    Registers a new agent and returns the agent details along with an auth token.
    """
    # TODO: Implement token generation and DB insertion logic
    return {
        "id": uuid.uuid4(),
        "hostname": agent_in.hostname,
        "ip_address": agent_in.ip_address,
        "is_online": True,
        "last_seen": datetime.now(timezone.utc)
    }
