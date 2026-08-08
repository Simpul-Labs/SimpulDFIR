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
