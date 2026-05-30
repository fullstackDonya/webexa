"""
Système d'agents IA pour CRM - Package principal
Version: 1.0.0
Phase: 1 (Base IA)
"""

__version__ = "1.0.0"
__author__ = "Webitech CRM Team"
__phase__ = "Phase 1 - Base IA"

# Imports principaux
from .config import API_CONFIG, DB_CONFIG, LLM_CONFIG, AGENTS_ENABLED, AUTOMATION_MODE
from .database import db, AgentLog, AgentAction
from .llm_service import llm
from .agents import inbox_agent, lead_analyst

__all__ = [
    # Configuration
    "API_CONFIG",
    "DB_CONFIG",
    "LLM_CONFIG",
    "AGENTS_ENABLED",
    "AUTOMATION_MODE",
    
    # Database
    "db",
    "AgentLog",
    "AgentAction",
    
    # Services
    "llm",
    
    # Agents
    "inbox_agent",
    "lead_analyst",
]
