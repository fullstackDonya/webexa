"""
Inbox Agent - Analyse des emails entrants
"""
from typing import Dict, Any, Optional
from database import db, AgentLog, AgentAction
from llm_service import llm
from datetime import datetime


class InboxAgent:
    """Agent d'analyse des emails"""
    
    def __init__(self):
        self.name = "inbox_agent"
    
    def analyze_email(
        self, 
        email_id: int,
        subject: str,
        body: str,
        sender: str,
        customer_id: int
    ) -> Dict[str, Any]:
        """Analyser un email entrant"""
        
        # Log de l'entrée
        input_data = {
            "email_id": email_id,
            "subject": subject,
            "sender": sender,
            "body_length": len(body)
        }
        
        try:
            # 1. Analyse IA de l'email
            analysis = llm.analyze_email(subject, body, sender)
            
            # 2. Rechercher le contact dans la base
            contact = self._find_contact(sender, customer_id)
            
            # 3. Créer ou mettre à jour le lead si nécessaire
            lead_id = None
            if analysis["type"] == "nouveau_prospect":
                lead_id = self._create_lead_from_email(
                    email_id,
                    analysis,
                    sender,
                    customer_id
                )
            elif contact:
                # Mettre à jour le lead existant
                lead_id = self._update_lead_from_email(
                    contact["id"],
                    analysis,
                    customer_id
                )
            
            # 4. Proposer une action
            action_data = {
                "email_id": email_id,
                "analysis": analysis,
                "contact_id": contact["id"] if contact else None,
                "lead_id": lead_id,
                "suggested_response": None
            }
            
            # Si demande de réponse
            if analysis.get("suggested_action", "").lower().startswith("répondre"):
                context = f"""
Email de: {sender}
Sujet: {subject}
Type: {analysis['type']}
Besoin: {analysis['extracted_info'].get('besoin', 'Non spécifié')}
Sentiment: {analysis['sentiment']}
"""
                suggested_response = llm.generate_response(context)
                action_data["suggested_response"] = suggested_response
            
            # 5. Créer l'action proposée
            action_id = AgentAction.create(
                agent_name=self.name,
                action_type="email_response" if action_data["suggested_response"] else "email_processed",
                target_type="email",
                target_id=email_id,
                data=action_data,
                status="pending",
                customer_id=customer_id
            )
            
            # 6. Mettre à jour l'email dans la base
            self._update_email_analysis(email_id, analysis, action_id)
            
            # Log de succès
            output_data = {
                "analysis": analysis,
                "action_id": action_id,
                "lead_id": lead_id,
                "contact_found": contact is not None
            }
            
            AgentLog.create(
                agent_name=self.name,
                action="analyze_email",
                input_data=input_data,
                output_data=output_data,
                status="success",
                customer_id=customer_id
            )
            
            return {
                "success": True,
                "email_id": email_id,
                "analysis": analysis,
                "action_id": action_id,
                "lead_id": lead_id,
                "suggested_response": action_data.get("suggested_response")
            }
            
        except Exception as e:
            # Log d'erreur
            AgentLog.create(
                agent_name=self.name,
                action="analyze_email",
                input_data=input_data,
                output_data={},
                status="error",
                error_message=str(e),
                customer_id=customer_id
            )
            
            return {
                "success": False,
                "error": str(e)
            }
    
    def _find_contact(self, email: str, customer_id: int) -> Optional[Dict]:
        """Rechercher un contact par email"""
        # Note: la table contacts n'a pas customer_id, seulement company_id
        # On recherche juste par email pour trouver le contact
        query = """
            SELECT * FROM contacts 
            WHERE email = %s
            LIMIT 1
        """
        return db.execute_one(query, (email,))
    
    def _create_lead_from_email(
        self,
        email_id: int,
        analysis: Dict,
        sender: str,
        customer_id: int
    ) -> Optional[int]:
        """Créer un nouveau lead depuis un email"""
        extracted = analysis.get("extracted_info", {})
        
        # Vérifier si un lead existe déjà pour ce contact
        existing = db.execute_one(
            "SELECT id FROM leads WHERE email = %s AND (customer_id = %s OR customer_id IS NULL)",
            (sender, customer_id)
        )
        
        if existing:
            return existing["id"]
        
        # Créer le lead
        query = """
            INSERT INTO leads 
            (customer_id, first_name, last_name, email, phone, source, status, notes, interest, created_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())
        """
        
        nom_complet = extracted.get("nom", sender.split("@")[0])
        parts = nom_complet.split(" ", 1)
        first_name = parts[0]
        last_name = parts[1] if len(parts) > 1 else ""
        
        notes = f"Lead créé automatiquement depuis email #{email_id}\n"
        notes += f"Besoin: {extracted.get('besoin', 'Non spécifié')}\n"
        notes += f"Budget: {extracted.get('budget', 'Non mentionné')}\n"
        notes += f"Urgence: {extracted.get('urgence', 'Non mentionnée')}\n"
        notes += f"Résumé: {analysis['summary']}"
        
        interest = extracted.get('besoin', 'Demande générale')
        
        return db.insert(query, (
            customer_id,
            first_name,
            last_name,
            sender,
            extracted.get("telephone"),
            "website",
            "new",
            notes,
            interest
        ))
    
    def _update_lead_from_email(
        self,
        contact_id: int,
        analysis: Dict,
        customer_id: int
    ) -> Optional[int]:
        """Mettre à jour un lead existant"""
        # Rechercher le lead associé au contact
        lead = db.execute_one(
            """SELECT id FROM leads 
               WHERE customer_id = %s 
               AND (contact_id = %s OR email = (SELECT email FROM contacts WHERE id = %s))
               LIMIT 1""",
            (customer_id, contact_id, contact_id)
        )
        
        if lead:
            # Mettre à jour les notes du lead
            query = """
                UPDATE leads 
                SET notes = CONCAT(notes, %s),
                    updated_at = NOW()
                WHERE id = %s
            """
            
            note = f"\n\n[{datetime.now().strftime('%Y-%m-%d %H:%M')}] Email reçu - {analysis['summary']}"
            db.update(query, (note, lead["id"]))
            
            return lead["id"]
        
        return None
    
    def _update_email_analysis(self, email_id: int, analysis: Dict, action_id: int):
        """Mettre à jour l'email avec l'analyse"""
        import json
        
        query = """
            UPDATE emails 
            SET 
                ai_analyzed = 1,
                ai_summary = %s,
                ai_type = %s,
                ai_priority = %s,
                ai_sentiment = %s,
                ai_action_id = %s
            WHERE id = %s
        """
        
        db.update(query, (
            analysis["summary"],
            analysis["type"],
            analysis["priority"],
            analysis["sentiment"],
            action_id,
            email_id
        ))
    
    def process_pending_emails(self, customer_id: int, limit: int = 10) -> Dict[str, Any]:
        """Traiter les emails en attente d'analyse"""
        
        # Récupérer les emails non analysés
        query = """
            SELECT id, subject, body_text, from_email, customer_id
            FROM emails 
            WHERE customer_id = %s 
            AND (ai_analyzed IS NULL OR ai_analyzed = 0)
            AND mailbox = 'INBOX'
            ORDER BY sent_at DESC
            LIMIT %s
        """
        
        emails = db.execute(query, (customer_id, limit))
        
        results = []
        for email in emails:
            result = self.analyze_email(
                email_id=email["id"],
                subject=email["subject"],
                body=email["body_text"] or "",
                sender=email["from_email"],
                customer_id=email["customer_id"]
            )
            results.append(result)
        
        return {
            "processed": len(results),
            "results": results
        }


# Instance globale
inbox_agent = InboxAgent()
