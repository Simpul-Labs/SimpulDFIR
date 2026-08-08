from pydantic_settings import BaseSettings

class Settings(BaseSettings):
    PROJECT_NAME: str = "Simpul DFIR Master Node"
    API_V1_STR: str = "/api/v1"
    DATABASE_URL: str = "postgresql+asyncpg://postgres:password@localhost:5432/simpul_dfir"

    class Config:
        case_sensitive = True

settings = Settings()
