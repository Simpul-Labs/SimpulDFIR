from fastapi import APIRouter, HTTPException, status, Depends
from pydantic import BaseModel
import secrets
import hashlib

router = APIRouter()

# In-memory storage for admin credentials (dev/initial version)
# Hashes the password using SHA-256 for basic security
class AuthState:
    username = "admin"
    password_hash = hashlib.sha256("admin".encode()).hexdigest()
    active_tokens = set()

state = AuthState()

class LoginRequest(BaseModel):
    username: str
    password: str

class ChangePasswordRequest(BaseModel):
    old_password: str
    new_password: str

class LoginResponse(BaseModel):
    access_token: str
    token_type: str = "bearer"
    username: str

@router.post("/login", response_model=LoginResponse)
async def login(req: LoginRequest):
    input_hash = hashlib.sha256(req.password.encode()).hexdigest()
    
    if req.username != state.username or input_hash != state.password_hash:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid username or password"
        )
    
    token = secrets.token_hex(32)
    state.active_tokens.add(token)
    
    return {
        "access_token": token,
        "token_type": "bearer",
        "username": state.username
    }

@router.post("/change-password")
async def change_password(req: ChangePasswordRequest):
    old_hash = hashlib.sha256(req.old_password.encode()).hexdigest()
    
    if old_hash != state.password_hash:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Incorrect old password"
        )
    
    if len(req.new_password) < 4:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="New password must be at least 4 characters long"
        )
        
    state.password_hash = hashlib.sha256(req.new_password.encode()).hexdigest()
    return {"message": "Password changed successfully"}
