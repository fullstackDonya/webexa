"""
Service LLM pour l'analyse de texte
"""
from typing import Dict, List, Optional, Any
from config import LLM_CONFIG
import json


class LLMService:
    """Service d'interaction avec le LLM"""
    
    def __init__(self):
        self.demo_mode = LLM_CONFIG.get("demo_mode", False)
        self.provider = LLM_CONFIG["provider"]
        self.model = LLM_CONFIG["model"]
        self.temperature = LLM_CONFIG["temperature"]
        self.max_tokens = LLM_CONFIG["max_tokens"]
        
        # Mode démo : utiliser le mock
        if self.demo_mode:
            from llm_mock import llm_mock
            self.mock = llm_mock
            print("⚠️  MODE DÉMO ACTIVÉ - Utilisation du LLM simulé (pas de consommation d'API)")
        elif self.provider == "openai":
            from openai import OpenAI
            self.client = OpenAI(api_key=LLM_CONFIG["openai_key"])
        elif self.provider == "anthropic":
            from anthropic import Anthropic
            self.client = Anthropic(api_key=LLM_CONFIG["anthropic_key"])
        else:
            raise ValueError(f"Provider {self.provider} not supported")
    
    def chat(
        self, 
        messages: List[Dict[str, str]], 
        system: Optional[str] = None,
        json_mode: bool = False
    ) -> str:
        """Envoyer une requête au LLM"""
        try:
            if self.provider == "openai":
                return self._chat_openai(messages, system, json_mode)
            elif self.provider == "anthropic":
                return self._chat_anthropic(messages, system)
        except Exception as e:
            raise Exception(f"LLM Error: {str(e)}")
    
    def _chat_openai(
        self, 
        messages: List[Dict[str, str]], 
        system: Optional[str],
        json_mode: bool
    ) -> str:
        """Chat avec OpenAI"""
        formatted_messages = []
        
        if system:
            formatted_messages.append({"role": "system", "content": system})
        
        formatted_messages.extend(messages)
        
        kwargs = {
            "model": self.model,
            "messages": formatted_messages,
            "temperature": self.temperature,
            "max_tokens": self.max_tokens,
        }
        
        if json_mode:
            kwargs["response_format"] = {"type": "json_object"}
        
        response = self.client.chat.completions.create(**kwargs)
        return response.choices[0].message.content
    
    def _chat_anthropic(
        self, 
        messages: List[Dict[str, str]],
        system: Optional[str]
    ) -> str:
        """Chat avec Anthropic"""
        kwargs = {
            "model": self.model,
            "max_tokens": self.max_tokens,
            "temperature": self.temperature,
            "messages": messages,
        }
        
        if system:
            kwargs["system"] = system
        
        response = self.client.messages.create(**kwargs)
        return response.content[0].text
    
    def analyze_email(self, subject: str, body: str, sender: str) -> Dict[str, Any]:
        """Analyser un email"""
        # Mode démo : utiliser le mock
        if self.demo_mode:
            return self.mock.analyze_email(subject, body, sender)
        
        system = """Tu es un assistant IA qui analyse les emails pour un CRM.
Tu dois extraire les informations clés et classifier l'email.
Réponds UNIQUEMENT en JSON valide."""
        
        prompt = f"""Analyse cet email et retourne un JSON avec cette structure :
{{
    "type": "nouveau_prospect | relance | client_existant | support | spam",
    "priority": "haute | moyenne | basse",
    "sentiment": "positif | neutre | negatif | urgent",
    "summary": "résumé en 1-2 phrases",
    "extracted_info": {{
        "nom": "si trouvé",
        "telephone": "si trouvé",
        "besoin": "description du besoin",
        "budget": "si mentionné",
        "urgence": "si mentionnée"
    }},
    "suggested_action": "action recommandée",
    "confidence": 0.0 à 1.0
}}

EMAIL:
De: {sender}
Sujet: {subject}
Corps: {body}
"""
        
        messages = [{"role": "user", "content": prompt}]
        response = self.chat(messages, system=system, json_mode=True)
        
        try:
            return json.loads(response)
        except json.JSONDecodeError:
            # Fallback si le JSON est invalide
            return {
                "type": "unknown",
                "priority": "moyenne",
                "sentiment": "neutre",
                "summary": response[:200],
                "extracted_info": {},
                "suggested_action": "Vérifier manuellement",
                "confidence": 0.5
            }
    
    def score_lead(self, lead_data: Dict[str, Any]) -> Dict[str, Any]:
        """Scorer un lead"""
        # Mode démo : utiliser le mock
        if self.demo_mode:
            return self.mock.score_lead(lead_data)
        
        system = """Tu es un expert en qualification de leads commerciaux.
Analyse les données du lead et attribue un score de 0 à 100.
Réponds UNIQUEMENT en JSON valide."""
        
        prompt = f"""Analyse ce lead et retourne un JSON avec cette structure :
{{
    "score": 0-100,
    "category": "chaud | tiede | froid | mort",
    "reasoning": "explication du score",
    "strengths": ["point fort 1", "point fort 2"],
    "weaknesses": ["point faible 1"],
    "recommended_actions": ["action 1", "action 2"],
    "next_contact_delay": "immédiat | 1-3 jours | 1 semaine | 1 mois"
}}

LEAD DATA:
{json.dumps(lead_data, indent=2, ensure_ascii=False)}
"""
        
        messages = [{"role": "user", "content": prompt}]
        response = self.chat(messages, system=system, json_mode=True)
        
        try:
            return json.loads(response)
        except json.JSONDecodeError:
            return {
                "score": 50,
                "category": "tiede",
                "reasoning": "Analyse automatique non disponible",
                "strengths": [],
                "weaknesses": [],
                "recommended_actions": ["Vérifier manuellement"],
                "next_contact_delay": "1 semaine"
            }
    
    def generate_response(self, context: str, tone: str = "professionnel") -> str:
        """Générer une réponse email"""
        # Mode démo : utiliser le mock
        if self.demo_mode:
            return self.mock.generate_response(context)
        
        system = f"""Tu es un assistant commercial professionnel.
Génère une réponse email en français, avec un ton {tone}.
Sois concis, clair et orienté solution."""
        
        prompt = f"""Génère une réponse email appropriée pour ce contexte :

{context}

La réponse doit :
- Être courtoise et professionnelle
- Répondre aux points clés
- Inclure un appel à l'action si pertinent
- Faire maximum 150 mots
"""
        
        messages = [{"role": "user", "content": prompt}]
        return self.chat(messages, system=system)


# Instance globale
llm = LLMService()
