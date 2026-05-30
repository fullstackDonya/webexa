"""
Service LLM Mock pour les tests et développement
"""
from typing import Dict, Any
import random


class LLMMockService:
    """Service d'IA simulé pour les tests"""
    
    def __init__(self):
        self.provider = "mock"
        self.model = "mock-gpt-4"
    
    def analyze_email(self, subject: str, body: str, sender: str) -> Dict[str, Any]:
        """Analyser un email (version simulée)"""
        
        # Détection basique de mots-clés
        body_lower = (subject + " " + body).lower()
        
        # Déterminer le type
        if any(word in body_lower for word in ["devis", "tarif", "prix", "coût"]):
            email_type = "nouveau_prospect"
            priority = "haute"
        elif any(word in body_lower for word in ["problème", "bug", "erreur", "aide"]):
            email_type = "support"
            priority = "haute"
        elif any(word in body_lower for word in ["suivi", "relance", "rappel"]):
            email_type = "relance"
            priority = "moyenne"
        elif "@" in body_lower and "spam" not in body_lower:
            email_type = "client_existant"
            priority = "moyenne"
        else:
            email_type = "client_existant"
            priority = "basse"
        
        # Déterminer le sentiment
        if any(word in body_lower for word in ["urgent", "rapidement", "vite", "asap"]):
            sentiment = "urgent"
        elif any(word in body_lower for word in ["merci", "super", "excellent", "parfait"]):
            sentiment = "positif"
        elif any(word in body_lower for word in ["problème", "déçu", "mécontent", "mauvais"]):
            sentiment = "negatif"
        else:
            sentiment = "neutre"
        
        # Extraire des informations
        extracted_info = {
            "nom": sender.split("@")[0].title(),
            "email": sender,
            "telephone": None,
            "besoin": self._extract_need(body_lower),
            "budget": self._extract_budget(body_lower),
            "urgence": sentiment == "urgent"
        }
        
        # Générer un résumé
        summary = self._generate_summary(email_type, subject, extracted_info)
        
        # Action suggérée
        if email_type == "nouveau_prospect":
            suggested_action = "Répondre avec devis personnalisé"
        elif email_type == "support":
            suggested_action = "Transférer au support technique"
        elif email_type == "relance":
            suggested_action = "Envoyer une réponse de suivi"
        else:
            suggested_action = "Archiver et suivre"
        
        return {
            "type": email_type,
            "priority": priority,
            "sentiment": sentiment,
            "summary": summary,
            "extracted_info": extracted_info,
            "suggested_action": suggested_action,
            "confidence": random.uniform(0.75, 0.95)
        }
    
    def _extract_need(self, text: str) -> str:
        """Extraire le besoin principal"""
        if "site web" in text or "site internet" in text:
            return "Création de site web"
        elif "application" in text or "app" in text:
            return "Développement d'application"
        elif "crm" in text:
            return "Solution CRM"
        elif "erp" in text:
            return "Solution ERP"
        elif "marketing" in text:
            return "Services marketing"
        elif "support" in text or "aide" in text:
            return "Support technique"
        else:
            return "Demande d'information générale"
    
    def _extract_budget(self, text: str) -> str:
        """Extraire le budget si mentionné"""
        if any(word in text for word in ["€", "euro", "budget"]):
            if any(word in text for word in ["5000", "10000", "10k"]):
                return "5000-10000€"
            elif "15000" in text or "15k" in text:
                return "10000-15000€"
            else:
                return "À discuter"
        return "Non mentionné"
    
    def _generate_summary(self, email_type: str, subject: str, info: Dict) -> str:
        """Générer un résumé de l'email"""
        summaries = {
            "nouveau_prospect": f"Nouveau prospect intéressé par {info['besoin']}. Demande {subject.lower()}.",
            "support": f"Demande de support concernant: {subject}",
            "relance": f"Message de suivi: {subject}",
            "client_existant": f"Message client: {subject}",
            "spam": f"Message suspect: {subject}"
        }
        return summaries.get(email_type, f"Email reçu: {subject}")
    
    def generate_response(self, context: str) -> str:
        """Générer une réponse suggérée"""
        return f"""Bonjour,

Merci pour votre message. Nous avons bien pris en compte votre demande.

Un membre de notre équipe reviendra vers vous dans les plus brefs délais avec une réponse personnalisée.

Cordialement,
L'équipe Webitech

---
[Réponse générée automatiquement - À personnaliser]
"""
    
    def score_lead(self, lead_data: Dict[str, Any]) -> Dict[str, Any]:
        """Scorer un lead (version simulée)"""
        score = 50  # Score de base
        factors_detail = []
        
        # Facteurs qui augmentent le score
        if lead_data.get("has_budget") or lead_data.get("budget"):
            score += 20
            factors_detail.append("Budget identifié (+20)")
        
        if lead_data.get("urgence") or lead_data.get("urgence_text"):
            score += 15
            factors_detail.append("Demande urgente (+15)")
        
        if lead_data.get("interaction_count", 0) > 3 or lead_data.get("email_count", 0) > 2:
            score += 10
            factors_detail.append("Bon engagement (+10)")
        
        if lead_data.get("email_opened"):
            score += 5
            factors_detail.append("Emails ouverts (+5)")
        
        # Bonus pour les nouveaux leads (moins de 7 jours)
        if lead_data.get("days_since_creation", 999) < 7:
            score += 5
            factors_detail.append("Lead récent (+5)")
        
        # Catégorie (selon les valeurs de l'enum dans la base)
        if score >= 80:
            category = "chaud"
            recommendation = "Contacter immédiatement, forte probabilité de conversion"
            recommended_actions = [
                "Appeler dans les 2 heures",
                "Préparer une proposition commerciale personnalisée",
                "Planifier une démo/réunion"
            ]
        elif score >= 60:
            category = "tiede"
            recommendation = "Suivre activement, bon potentiel"
            recommended_actions = [
                "Envoyer un email de suivi sous 24h",
                "Proposer un rendez-vous téléphonique",
                "Partager du contenu pertinent"
            ]
        elif score >= 40:
            category = "froid"
            recommendation = "Nurturing à long terme, surveiller l'engagement"
            recommended_actions = [
                "Ajouter à une campagne de nurturing",
                "Envoyer du contenu éducatif mensuel"
            ]
        else:
            category = "mort"
            recommendation = "Très faible probabilité de conversion"
            recommended_actions = [
                "Archiver ou relancer dans 6 mois"
            ]
        
        # Construire le raisonnement
        reasoning = f"Score: {min(100, score)}/100. "
        if factors_detail:
            reasoning += "Facteurs positifs: " + ", ".join(factors_detail) + ". "
        reasoning += recommendation
        
        return {
            "score": min(100, score),
            "category": category,
            "reasoning": reasoning,
            "recommended_actions": recommended_actions,
            "factors": {
                "budget": lead_data.get("has_budget", False),
                "urgence": lead_data.get("urgence", False),
                "engagement": lead_data.get("interaction_count", 0) > 3 or lead_data.get("email_count", 0) > 2,
                "email_opened": lead_data.get("email_opened", False),
                "recent": lead_data.get("days_since_creation", 999) < 7
            },
            "confidence": random.uniform(0.75, 0.95)
        }


# Instance globale
llm_mock = LLMMockService()
