from pydantic_settings import BaseSettings

class Settings(BaseSettings):
    PROJECT_NAME: str = "Simpul DFIR Master Node"
    API_V1_STR: str = "/api/v1"
    DATABASE_URL: str = "sqlite+aiosqlite:///./simpul_dfir.db"

    class Config:
        case_sensitive = True

settings = Settings()
