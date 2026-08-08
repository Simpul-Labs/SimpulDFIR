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
    """
    Receives a static log archive and its SHA-256 hash from the agent.
    """
    # TODO: Handle file upload, verify hash, and save record in ForensicCase
    return {
        "status": "success",
        "case_number": case_number,
        "filename": file.filename,
        "message": "Archive uploaded securely and hash verified."
    }
