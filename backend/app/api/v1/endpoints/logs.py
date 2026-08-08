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
    """
    Receives JSON logs from agents.
    """
    # TODO: Bulk insert LiveLog records into the database
    return {"status": "success", "processed_logs": len(logs_in)}
