from sqlalchemy import Column, String, DateTime, ForeignKey, Text
from datetime import datetime, timezone
from app.core.database import Base
import uuid

class LiveLog(Base):
    __tablename__ = "live_logs"

    id = Column(String, primary_key=True, default=lambda: str(uuid.uuid4()), index=True)
    agent_id = Column(String, ForeignKey("agents.id", ondelete="CASCADE"), nullable=False)
    timestamp = Column(DateTime(timezone=True), default=lambda: datetime.now(timezone.utc))
    source_ip = Column(String, index=True, nullable=True)
    log_message = Column(Text, nullable=False)
    threat_level = Column(String, nullable=True) # e.g., INFO, WARN, CRITICAL
