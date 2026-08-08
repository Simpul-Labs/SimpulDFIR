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
