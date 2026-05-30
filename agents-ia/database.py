"""
Base de données et modèles
"""
import pymysql
from typing import Optional, Dict, Any, List
from datetime import datetime
from config import DB_CONFIG


class Database:
    """Gestionnaire de connexion à la base de données MySQL"""
    
    def __init__(self, config: Dict = None):
        self.config = config or DB_CONFIG
        self.connection = None
    
    def connect(self):
        """Établir la connexion"""
        if not self.connection or not self.connection.open:
            self.connection = pymysql.connect(
                host=self.config["host"],
                port=self.config["port"],
                user=self.config["user"],
                password=self.config["password"],
                database=self.config["database"],
                charset='utf8mb4',
                cursorclass=pymysql.cursors.DictCursor,
                autocommit=False
            )
        return self.connection
    
    def execute(self, query: str, params: tuple = None) -> List[Dict]:
        """Exécuter une requête SELECT"""
        conn = self.connect()
        try:
            with conn.cursor() as cursor:
                cursor.execute(query, params or ())
                return cursor.fetchall()
        except Exception as e:
            print(f"Database error: {e}")
            raise
    
    def execute_one(self, query: str, params: tuple = None) -> Optional[Dict]:
        """Exécuter une requête SELECT et retourner 1 résultat"""
        results = self.execute(query, params)
        return results[0] if results else None
    
    def insert(self, query: str, params: tuple = None) -> int:
        """Exécuter un INSERT et retourner l'ID"""
        conn = self.connect()
        try:
            with conn.cursor() as cursor:
                cursor.execute(query, params or ())
                conn.commit()
                return cursor.lastrowid
        except Exception as e:
            conn.rollback()
            print(f"Database error: {e}")
            raise
    
    def update(self, query: str, params: tuple = None) -> int:
        """Exécuter un UPDATE"""
        conn = self.connect()
        try:
            with conn.cursor() as cursor:
                affected = cursor.execute(query, params or ())
                conn.commit()
                return affected
        except Exception as e:
            conn.rollback()
            print(f"Database error: {e}")
            raise
    
    def close(self):
        """Fermer la connexion"""
        if self.connection and self.connection.open:
            self.connection.close()


# Instance globale
db = Database()

# Note: CRM et ERP partagent la même base de données 'webitech'
# Les tables CRM ont le préfixe 'crm_' ou pas de préfixe (emails, whatsapp_messages, etc.)
# Les tables ERP ont le préfixe 'erp_' (erp_shifts, erp_employees, erp_payrolls, etc.)


class AgentLog:
    """Modèle pour les logs des agents"""
    
    @staticmethod
    def create(
        agent_name: str,
        action: str,
        input_data: Dict[str, Any],
        output_data: Dict[str, Any],
        status: str = "success",
        error_message: Optional[str] = None,
        customer_id: Optional[int] = None
    ) -> int:
        """Créer un log"""
        import json
        
        query = """
            INSERT INTO agent_logs 
            (agent_name, action, input_data, output_data, status, error_message, customer_id, created_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, NOW())
        """
        
        return db.insert(query, (
            agent_name,
            action,
            json.dumps(input_data, ensure_ascii=False),
            json.dumps(output_data, ensure_ascii=False),
            status,
            error_message,
            customer_id
        ))
    
    @staticmethod
    def get_recent(agent_name: Optional[str] = None, limit: int = 50) -> List[Dict]:
        """Récupérer les logs récents"""
        if agent_name:
            query = """
                SELECT * FROM agent_logs 
                WHERE agent_name = %s 
                ORDER BY created_at DESC 
                LIMIT %s
            """
            return db.execute(query, (agent_name, limit))
        else:
            query = """
                SELECT * FROM agent_logs 
                ORDER BY created_at DESC 
                LIMIT %s
            """
            return db.execute(query, (limit,))


class AgentAction:
    """Modèle pour les actions proposées/exécutées par les agents"""
    
    @staticmethod
    def create(
        agent_name: str,
        action_type: str,
        target_type: str,
        target_id: int,
        data: Dict[str, Any],
        status: str = "pending",
        customer_id: Optional[int] = None
    ) -> int:
        """Créer une action"""
        import json
        
        query = """
            INSERT INTO agent_actions 
            (agent_name, action_type, target_type, target_id, data, status, customer_id, created_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, NOW())
        """
        
        return db.insert(query, (
            agent_name,
            action_type,
            target_type,
            target_id,
            json.dumps(data, ensure_ascii=False),
            status,
            customer_id
        ))
    
    @staticmethod
    def get_pending(customer_id: Optional[int] = None) -> List[Dict]:
        """Récupérer les actions en attente"""
        if customer_id:
            query = """
                SELECT * FROM agent_actions 
                WHERE status = 'pending' AND customer_id = %s
                ORDER BY created_at ASC
            """
            return db.execute(query, (customer_id,))
        else:
            query = """
                SELECT * FROM agent_actions 
                WHERE status = 'pending'
                ORDER BY created_at ASC
            """
            return db.execute(query)
    
    @staticmethod
    def update_status(action_id: int, status: str, result: Optional[Dict] = None) -> int:
        """Mettre à jour le statut d'une action"""
        import json
        
        if result:
            query = """
                UPDATE agent_actions 
                SET status = %s, result = %s, executed_at = NOW()
                WHERE id = %s
            """
            return db.update(query, (status, json.dumps(result, ensure_ascii=False), action_id))
        else:
            query = """
                UPDATE agent_actions 
                SET status = %s, executed_at = NOW()
                WHERE id = %s
            """
            return db.update(query, (status, action_id))
