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
    """
    Agent checks for pending commands (e.g., iptables BLOCK).
    """
    # TODO: Fetch pending commands for this agent
    return [
        {"command_type": "BLOCK_IP", "target_ip": "114.119.160.20", "duration_seconds": 3600}
    ]
