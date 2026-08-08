from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, desc
from app.core.database import get_db
from app.models.live_log import LiveLog
from app.models.agent import Agent
from app.schemas.live_log import LiveLogCreate, LiveLogResponse
from typing import List, Optional
import uuid

router = APIRouter()

@router.post("/push", status_code=status.HTTP_201_CREATED)
async def push_logs(
    logs_in: List[LiveLogCreate],
    db: AsyncSession = Depends(get_db)
):
    """
    Receives JSON logs from agents. Auto-registers agent if hostname is new.
    """
    for log_data in logs_in:
        # Find or create agent by hostname
        result = await db.execute(select(Agent).where(Agent.hostname == log_data.hostname))
        agent = result.scalars().first()
        
        if not agent:
            agent = Agent(
                hostname=log_data.hostname,
                ip_address=log_data.source_ip or "0.0.0.0",
                auth_token=str(uuid.uuid4()), # Generate dummy token for now
                is_online=True
            )
            db.add(agent)
            await db.flush() # flush to get agent.id

        new_log = LiveLog(
            agent_id=agent.id,
            timestamp=log_data.timestamp,
            source_ip=log_data.source_ip,
            log_message=log_data.log_message,
            threat_level=log_data.threat_level
        )
        db.add(new_log)
    await db.commit()
    return {"status": "success", "processed_logs": len(logs_in)}

@router.get("/recent", response_model=List[LiveLogResponse])
async def get_recent_logs(agent_id: Optional[str] = None, limit: int = 50, db: AsyncSession = Depends(get_db)):
    """
    Get the most recent logs for the UI, optionally filtered by agent_id.
    """
    query = select(LiveLog).order_by(desc(LiveLog.timestamp)).limit(limit)
    if agent_id:
        query = query.where(LiveLog.agent_id == agent_id)
        
    result = await db.execute(query)
    logs = result.scalars().all()
    # Reverse to show chronological order in UI
    return list(reversed(logs))
