from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, desc
from app.core.database import get_db
from app.models.live_log import LiveLog
from app.schemas.live_log import LiveLogCreate, LiveLogResponse
from typing import List

router = APIRouter()

@router.post("/push", status_code=status.HTTP_201_CREATED)
async def push_logs(
    logs_in: List[LiveLogCreate],
    db: AsyncSession = Depends(get_db)
):
    """
    Receives JSON logs from agents.
    """
    for log_data in logs_in:
        new_log = LiveLog(
            agent_id=log_data.agent_id,
            timestamp=log_data.timestamp,
            severity=log_data.severity,
            source=log_data.source,
            message=log_data.message,
            raw_data=log_data.raw_data
        )
        db.add(new_log)
    await db.commit()
    return {"status": "success", "processed_logs": len(logs_in)}

@router.get("/recent", response_model=List[LiveLogResponse])
async def get_recent_logs(limit: int = 50, db: AsyncSession = Depends(get_db)):
    """
    Get the most recent logs for the UI.
    """
    result = await db.execute(
        select(LiveLog).order_by(desc(LiveLog.timestamp)).limit(limit)
    )
    logs = result.scalars().all()
    # Reverse to show chronological order in UI
    return list(reversed(logs))
