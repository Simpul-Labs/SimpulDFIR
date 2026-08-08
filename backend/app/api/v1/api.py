from fastapi import APIRouter
from app.api.v1.endpoints import auth, logs, commands, forensic, system

api_router = APIRouter()

api_router.include_router(auth.router, prefix="/auth", tags=["Authentication"])
api_router.include_router(logs.router, prefix="/logs", tags=["Logs"])
api_router.include_router(commands.router, prefix="/commands", tags=["Commands"])
api_router.include_router(forensic.router, prefix="/forensic", tags=["Forensic"])
api_router.include_router(system.router, prefix="/system", tags=["System"])
