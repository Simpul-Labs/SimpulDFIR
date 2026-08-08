from fastapi import APIRouter, Depends
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, desc
from app.core.database import get_db
from app.models.agent import Agent
from app.schemas.agent import AgentResponse
from typing import List

router = APIRouter()

@router.get("/", response_model=List[AgentResponse])
async def get_all_agents(db: AsyncSession = Depends(get_db)):
    """
    Get list of all registered agents.
    """
    result = await db.execute(
        select(Agent).order_by(desc(Agent.last_seen))
    )
    agents = result.scalars().all()
    return agents
