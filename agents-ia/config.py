"""
Configuration centrale du système d'agents IA
"""
import os
from pathlib import Path
from dotenv import load_dotenv

# Charger les variables d'environnement
load_dotenv()

# Chemins
BASE_DIR = Path(__file__).resolve().parent
DATA_DIR = BASE_DIR / "data"
LOGS_DIR = BASE_DIR / "logs"

# Créer les dossiers s'ils n'existent pas
DATA_DIR.mkdir(exist_ok=True)
LOGS_DIR.mkdir(exist_ok=True)

# Database
DB_CONFIG = {
    "host": os.getenv("DB_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_PORT", 8889)),
    "database": os.getenv("DB_NAME", "webitech"),
    "user": os.getenv("DB_USER", "root"),
    "password": os.getenv("DB_PASSWORD", "root"),
}

# API
API_CONFIG = {
    "host": os.getenv("API_HOST", "0.0.0.0"),
    "port": int(os.getenv("API_PORT", 8000)),
    "secret_key": os.getenv("API_SECRET_KEY", "dev-secret-key-change-me"),
    "reload": os.getenv("ENV", "development") == "development",
}

# LLM
LLM_CONFIG = {
    "provider": os.getenv("LLM_PROVIDER", "openai"),
    "openai_key": os.getenv("OPENAI_API_KEY"),
    "anthropic_key": os.getenv("ANTHROPIC_API_KEY"),
    "model": os.getenv("LLM_MODEL", "gpt-4o"),
    "temperature": float(os.getenv("LLM_TEMPERATURE", "0.7")),
    "max_tokens": int(os.getenv("LLM_MAX_TOKENS", "1000")),
    "demo_mode": os.getenv("LLM_DEMO_MODE", "true").lower() == "true",  # Mode démo sans consommer de crédits
}

# Vector Database
VECTOR_DB_PATH = os.getenv("VECTOR_DB_PATH", str(DATA_DIR / "chromadb"))

# Redis
REDIS_URL = os.getenv("REDIS_URL", "redis://localhost:6379/0")

# Logging
LOG_CONFIG = {
    "level": os.getenv("LOG_LEVEL", "INFO"),
    "file": os.getenv("LOG_FILE", str(LOGS_DIR / "agents.log")),
}

# Agents Status
AGENTS_ENABLED = {
    "inbox": os.getenv("INBOX_AGENT_ENABLED", "true").lower() == "true",
    "whatsapp": os.getenv("WHATSAPP_AGENT_ENABLED", "false").lower() == "true",
    "lead": os.getenv("LEAD_AGENT_ENABLED", "true").lower() == "true",
    "campaign": os.getenv("CAMPAIGN_AGENT_ENABLED", "false").lower() == "true",
    "mission": os.getenv("MISSION_AGENT_ENABLED", "false").lower() == "true",
    "scheduler": os.getenv("SCHEDULER_AGENT_ENABLED", "false").lower() == "true",
    "executor": os.getenv("EXECUTOR_AGENT_ENABLED", "false").lower() == "true",
}

# Automation Mode
AUTOMATION_MODE = os.getenv("AUTOMATION_MODE", "assisted")  # assisted, semi-auto, autonomous

# Email Processing
EMAIL_CONFIG = {
    "batch_size": int(os.getenv("EMAIL_BATCH_SIZE", 10)),
    "process_interval": int(os.getenv("EMAIL_PROCESS_INTERVAL", 60)),
}
