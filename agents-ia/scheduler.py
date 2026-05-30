"""
Scheduler - Planificateur de tâches automatiques
Lance l'assistant agent à intervalles réguliers
"""
import schedule
import time
from datetime import datetime
from agents.assistant_agent import assistant
from database import db
import logging

# Configuration du logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('logs/scheduler.log'),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger(__name__)


class Scheduler:
    """Planificateur de tâches automatiques"""
    
    def __init__(self):
        self.running = False
    
    def run_daily_checks_all_customers(self):
        """Exécuter les vérifications pour tous les clients"""
        logger.info("=== Début des vérifications quotidiennes ===")
        
        try:
            # Récupérer tous les clients actifs
            customers = db.execute("""
                SELECT id, company_name, email
                FROM customers
                WHERE status = 'active'
            """)
            
            logger.info(f"Nombre de clients à vérifier: {len(customers)}")
            
            for customer in customers:
                try:
                    logger.info(f"Vérification client: {customer['id']} - {customer.get('company_name', 'N/A')}")
                    
                    # Lancer la vérification quotidienne
                    result = assistant.run_daily_check(customer['id'])
                    
                    if result['success']:
                        logger.info(f"✓ Client {customer['id']}: {result['message']}")
                    else:
                        logger.error(f"✗ Client {customer['id']}: {result.get('error', 'Erreur inconnue')}")
                        
                except Exception as e:
                    logger.error(f"Erreur pour client {customer['id']}: {e}")
                    continue
            
            logger.info("=== Vérifications quotidiennes terminées ===")
            
        except Exception as e:
            logger.error(f"Erreur lors des vérifications: {e}")
    
    def run_hourly_email_check(self):
        """Vérifier les emails toutes les heures"""
        logger.info("=== Vérification horaire des emails ===")
        
        try:
            customers = db.execute("SELECT id FROM customers WHERE status = 'active'")
            
            for customer in customers:
                result = assistant._check_new_emails(customer['id'])
                if result['count'] > 0:
                    logger.info(f"Client {customer['id']}: {result['count']} nouveaux emails, {result['notifications']} notifications")
                    
        except Exception as e:
            logger.error(f"Erreur vérification emails: {e}")
    
    def run_integration_sync(self):
        """Synchroniser les intégrations toutes les 30 minutes"""
        logger.info("=== Synchronisation des intégrations ===")
        
        try:
            customers = db.execute("SELECT id FROM customers WHERE status = 'active'")
            
            for customer in customers:
                result = assistant._synchronize_integrations(customer['id'])
                if result['synced'] > 0:
                    logger.info(f"Client {customer['id']}: {result['synced']} intégrations synchronisées")
                if result['errors']:
                    logger.warning(f"Client {customer['id']}: {len(result['errors'])} erreurs de sync")
                    
        except Exception as e:
            logger.error(f"Erreur synchronisation: {e}")
    
    def start(self):
        """Démarrer le scheduler"""
        logger.info("🚀 Démarrage du scheduler...")
        
        # Planifier les tâches
        # Vérification complète quotidienne à 8h00
        schedule.every().day.at("08:00").do(self.run_daily_checks_all_customers)
        
        # Vérification des emails toutes les heures
        schedule.every().hour.do(self.run_hourly_email_check)
        
        # Synchronisation des intégrations toutes les 30 minutes
        schedule.every(30).minutes.do(self.run_integration_sync)
        
        # Pour les tests : vérification immédiate au démarrage
        logger.info("Exécution de la première vérification...")
        self.run_daily_checks_all_customers()
        
        self.running = True
        logger.info("✓ Scheduler démarré avec succès")
        logger.info("- Vérification quotidienne: 08:00")
        logger.info("- Vérification emails: Toutes les heures")
        logger.info("- Synchronisation intégrations: Toutes les 30 minutes")
        
        # Boucle principale
        try:
            while self.running:
                schedule.run_pending()
                time.sleep(60)  # Vérifier toutes les minutes
                
        except KeyboardInterrupt:
            logger.info("Arrêt du scheduler...")
            self.stop()
    
    def stop(self):
        """Arrêter le scheduler"""
        self.running = False
        logger.info("✓ Scheduler arrêté")


def main():
    """Point d'entrée principal"""
    scheduler = Scheduler()
    scheduler.start()


if __name__ == "__main__":
    main()
