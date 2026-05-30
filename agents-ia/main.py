"""
API FastAPI pour les agents IA
"""
from fastapi import FastAPI, HTTPException, Depends, Header
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List, Dict, Any
from datetime import datetime

from config import API_CONFIG, AGENTS_ENABLED
from database import db, AgentLog, AgentAction
from agents import inbox_agent, lead_analyst

# Création de l'application FastAPI
app = FastAPI(
    title="CRM AI Agents API",
    description="API pour les agents IA du CRM",
    version="1.0.0"
)

# CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # À restreindre en production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ========== Modèles Pydantic ==========

class EmailAnalysisRequest(BaseModel):
    email_id: int
    subject: str
    body: str
    sender: str
    customer_id: int


class LeadScoreRequest(BaseModel):
    lead_id: int
    customer_id: int


class ProcessEmailsRequest(BaseModel):
    customer_id: int
    limit: Optional[int] = 10


class ActionUpdateRequest(BaseModel):
    action_id: int
    status: str  # approved, rejected, executed
    result: Optional[Dict[str, Any]] = None


# ========== Authentification ==========

async def verify_api_key(x_api_key: Optional[str] = Header(None)):
    """Vérifier la clé API"""
    if not x_api_key or x_api_key != API_CONFIG["secret_key"]:
        raise HTTPException(status_code=401, detail="Invalid API key")
    return x_api_key


# ========== Routes de santé ==========

@app.get("/")
async def root():
    """Route de base"""
    return {
        "service": "CRM AI Agents",
        "version": "1.0.0",
        "status": "operational",
        "agents_enabled": AGENTS_ENABLED
    }


@app.get("/health")
async def health_check():
    """Vérification de santé"""
    try:
        # Tester la connexion DB
        db.execute("SELECT 1")
        db_status = "connected"
    except Exception as e:
        db_status = f"error: {str(e)}"
    
    return {
        "status": "healthy",
        "database": db_status,
        "agents": AGENTS_ENABLED,
        "timestamp": datetime.now().isoformat()
    }


# ========== Routes Inbox Agent ==========

@app.post("/api/inbox/analyze")
async def analyze_email(
    request: EmailAnalysisRequest,
    api_key: str = Depends(verify_api_key)
):
    """Analyser un email"""
    if not AGENTS_ENABLED["inbox"]:
        raise HTTPException(status_code=503, detail="Inbox agent is disabled")
    
    result = inbox_agent.analyze_email(
        email_id=request.email_id,
        subject=request.subject,
        body=request.body,
        sender=request.sender,
        customer_id=request.customer_id
    )
    
    if not result["success"]:
        raise HTTPException(status_code=500, detail=result.get("error"))
    
    return result


@app.post("/api/inbox/process-pending")
async def process_pending_emails(
    request: ProcessEmailsRequest,
    api_key: str = Depends(verify_api_key)
):
    """Traiter les emails en attente"""
    if not AGENTS_ENABLED["inbox"]:
        raise HTTPException(status_code=503, detail="Inbox agent is disabled")
    
    result = inbox_agent.process_pending_emails(
        customer_id=request.customer_id,
        limit=request.limit
    )
    
    return result


# ========== Routes Lead Analyst ==========

@app.post("/api/leads/score")
async def score_lead(
    request: LeadScoreRequest,
    api_key: str = Depends(verify_api_key)
):
    """Scorer un lead"""
    if not AGENTS_ENABLED["lead"]:
        raise HTTPException(status_code=503, detail="Lead analyst agent is disabled")
    
    result = lead_analyst.score_lead(
        lead_id=request.lead_id,
        customer_id=request.customer_id
    )
    
    if not result["success"]:
        raise HTTPException(status_code=500, detail=result.get("error"))
    
    return result


@app.get("/api/leads/hot/{customer_id}")
async def get_hot_leads(
    customer_id: int,
    limit: int = 10,
    api_key: str = Depends(verify_api_key)
):
    """Récupérer les leads chauds"""
    if not AGENTS_ENABLED["lead"]:
        raise HTTPException(status_code=503, detail="Lead analyst agent is disabled")
    
    leads = lead_analyst.get_hot_leads(customer_id, limit)
    
    return {
        "customer_id": customer_id,
        "count": len(leads),
        "leads": leads
    }


@app.get("/api/leads/cold/{customer_id}")
async def get_cold_leads(
    customer_id: int,
    days: int = 30,
    api_key: str = Depends(verify_api_key)
):
    """Détecter les leads froids"""
    if not AGENTS_ENABLED["lead"]:
        raise HTTPException(status_code=503, detail="Lead analyst agent is disabled")
    
    leads = lead_analyst.detect_cold_leads(customer_id, days)
    
    return {
        "customer_id": customer_id,
        "days_threshold": days,
        "count": len(leads),
        "leads": leads
    }


# ========== Routes Actions ==========

@app.get("/api/actions/pending/{customer_id}")
async def get_pending_actions(
    customer_id: int,
    api_key: str = Depends(verify_api_key)
):
    """Récupérer les actions en attente"""
    actions = AgentAction.get_pending(customer_id)
    
    return {
        "customer_id": customer_id,
        "count": len(actions),
        "actions": actions
    }


@app.post("/api/actions/update")
async def update_action(
    request: ActionUpdateRequest,
    api_key: str = Depends(verify_api_key)
):
    """Mettre à jour le statut d'une action"""
    affected = AgentAction.update_status(
        action_id=request.action_id,
        status=request.status,
        result=request.result
    )
    
    if affected == 0:
        raise HTTPException(status_code=404, detail="Action not found")
    
    return {
        "success": True,
        "action_id": request.action_id,
        "status": request.status
    }


# ========== Routes Logs ==========

@app.get("/api/logs")
async def get_logs(
    agent_name: Optional[str] = None,
    limit: int = 50,
    api_key: str = Depends(verify_api_key)
):
    """Récupérer les logs des agents"""
    logs = AgentLog.get_recent(agent_name, limit)
    
    return {
        "agent": agent_name or "all",
        "count": len(logs),
        "logs": logs
    }


# ========== Gestion des erreurs ==========

@app.exception_handler(Exception)
async def global_exception_handler(request, exc):
    """Gestionnaire d'erreurs global"""
    return {
        "error": str(exc),
        "type": type(exc).__name__,
        "timestamp": datetime.now().isoformat()
    }


# ========== Démarrage ==========

if __name__ == "__main__":
    import uvicorn
    
    print("=" * 60)
    print("🤖 CRM AI AGENTS API")
    print("=" * 60)
    print(f"Host: {API_CONFIG['host']}:{API_CONFIG['port']}")
    print(f"Agents enabled: {AGENTS_ENABLED}")
    print("=" * 60)
    
    uvicorn.run(
        "main:app",
        host=API_CONFIG["host"],
        port=API_CONFIG["port"],
        reload=API_CONFIG["reload"]
    )
