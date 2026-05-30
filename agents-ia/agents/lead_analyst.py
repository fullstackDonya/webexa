"""
Lead Analyst Agent - Scoring et qualification des leads
"""
from typing import Dict, Any, List
from database import db, AgentLog, AgentAction
from llm_service import llm
from datetime import datetime, timedelta


class LeadAnalystAgent:
    """Agent d'analyse et scoring des leads"""
    
    def __init__(self):
        self.name = "lead_analyst_agent"
    
    def score_lead(self, lead_id: int, customer_id: int) -> Dict[str, Any]:
        """Scorer un lead"""
        
        input_data = {"lead_id": lead_id}
        
        try:
            # 1. Récupérer les données du lead
            lead = self._get_lead_data(lead_id, customer_id)
            
            if not lead:
                return {"success": False, "error": "Lead not found"}
            
            # 2. Analyser avec l'IA
            score_result = llm.score_lead(lead)
            
            # 3. Mettre à jour le score dans la base
            self._update_lead_score(lead_id, score_result)
            
            # 4. Créer des actions recommandées
            action_ids = []
            for action in score_result.get("recommended_actions", []):
                action_id = AgentAction.create(
                    agent_name=self.name,
                    action_type="lead_action",
                    target_type="lead",
                    target_id=lead_id,
                    data={
                        "action": action,
                        "score": score_result["score"],
                        "category": score_result["category"]
                    },
                    status="pending",
                    customer_id=customer_id
                )
                action_ids.append(action_id)
            
            # Log
            AgentLog.create(
                agent_name=self.name,
                action="score_lead",
                input_data=input_data,
                output_data={
                    "score": score_result["score"],
                    "category": score_result["category"],
                    "actions_created": len(action_ids)
                },
                status="success",
                customer_id=customer_id
            )
            
            return {
                "success": True,
                "lead_id": lead_id,
                "score": score_result["score"],
                "category": score_result["category"],
                "reasoning": score_result["reasoning"],
                "recommended_actions": score_result["recommended_actions"],
                "action_ids": action_ids
            }
            
        except Exception as e:
            AgentLog.create(
                agent_name=self.name,
                action="score_lead",
                input_data=input_data,
                output_data={},
                status="error",
                error_message=str(e),
                customer_id=customer_id
            )
            
            return {"success": False, "error": str(e)}
    
    def _get_lead_data(self, lead_id: int, customer_id: int) -> Dict[str, Any]:
        """Récupérer toutes les données d'un lead"""
        from datetime import datetime
        
        # Lead de base
        lead = db.execute_one(
            "SELECT * FROM leads WHERE id = %s AND customer_id = %s",
            (lead_id, customer_id)
        )
        
        if not lead:
            return None
        
        # Historique des interactions (optionnel - table peut ne pas exister)
        interactions = []
        try:
            interactions = db.execute(
                """SELECT type, created_at FROM interactions 
                   WHERE lead_id = %s ORDER BY created_at DESC LIMIT 10""",
                (lead_id,)
            )
        except:
            # Table interactions n'existe pas encore
            pass
        
        # Emails échangés
        emails = []
        if lead.get("email"):
            try:
                emails = db.execute(
                    """SELECT subject, from_email, sent_at FROM emails 
                       WHERE customer_id = %s 
                       AND (from_email = %s OR to_email = %s)
                       ORDER BY sent_at DESC LIMIT 5""",
                    (customer_id, lead.get("email", ""), lead.get("email", ""))
                )
            except:
                # Erreur lors de la récupération des emails
                pass
        
        # Enrichir les données
        lead["interactions"] = interactions
        lead["emails"] = emails
        lead["interaction_count"] = len(interactions)
        lead["email_count"] = len(emails)
        lead["email_opened"] = False  # TODO: implémenter le tracking
        
        # Calculer l'ancienneté
        if lead.get("created_at"):
            created = lead["created_at"]
            if isinstance(created, str):
                from datetime import datetime
                try:
                    created = datetime.fromisoformat(created.replace('Z', '+00:00'))
                except:
                    created = datetime.now()
            lead["days_since_creation"] = (datetime.now() - created).days
        else:
            lead["days_since_creation"] = 0
        
        # Ajouter le budget si disponible
        if lead.get("budget"):
            lead["has_budget"] = True
        else:
            # Extraire le budget des notes si mentionné
            notes = lead.get("notes", "") or lead.get("interest", "")
            if "budget" in notes.lower() or "€" in notes or "euro" in notes.lower():
                lead["has_budget"] = True
            else:
                lead["has_budget"] = False
        
        # Déterminer l'urgence depuis les notes
        notes_text = (lead.get("notes", "") or "") + " " + (lead.get("interest", "") or "")
        lead["urgence"] = "urgent" in notes_text.lower() or "rapidement" in notes_text.lower()
        
        return lead
    
    def _update_lead_score(self, lead_id: int, score_result: Dict):
        """Mettre à jour le score du lead"""
        query = """
            UPDATE leads 
            SET score = %s,
                score_category = %s,
                score_reasoning = %s,
                score_updated_at = NOW()
            WHERE id = %s
        """
        
        db.update(query, (
            score_result["score"],
            score_result["category"],
            score_result["reasoning"],
            lead_id
        ))
    
    def analyze_all_leads(self, customer_id: int) -> Dict[str, Any]:
        """Analyser tous les leads d'un customer"""
        
        # Récupérer tous les leads actifs
        leads = db.execute(
            """SELECT id FROM leads 
               WHERE customer_id = %s 
               AND status NOT IN ('lost', 'won')
               ORDER BY created_at DESC""",
            (customer_id,)
        )
        
        results = []
        for lead in leads:
            result = self.score_lead(lead["id"], customer_id)
            results.append(result)
        
        return {
            "analyzed": len(results),
            "results": results
        }
    
    def get_hot_leads(self, customer_id: int, limit: int = 10) -> List[Dict]:
        """Récupérer les leads chauds"""
        query = """
            SELECT *
            FROM leads
            WHERE customer_id = %s
            AND score >= 70
            AND status NOT IN ('lost', 'converted')
            ORDER BY score DESC, updated_at DESC
            LIMIT %s
        """
        
        return db.execute(query, (customer_id, limit))
    
    def detect_cold_leads(self, customer_id: int, days: int = 30) -> List[Dict]:
        """Détecter les leads froids (sans interaction depuis X jours)"""
        query = """
            SELECT *
            FROM leads
            WHERE customer_id = %s
            AND status NOT IN ('lost', 'converted')
            AND updated_at < DATE_SUB(NOW(), INTERVAL %s DAY)
            ORDER BY updated_at ASC
        """
        
        return db.execute(query, (customer_id, days))


# Instance globale
lead_analyst = LeadAnalystAgent()
