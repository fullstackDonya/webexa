"""
Assistant Agent - Surveillance et gestion automatique des tâches quotidiennes
Cet agent surveille :
- Les nouveaux emails
- Les messages WhatsApp
- Les tâches à effectuer
- Les alertes et actions
Et crée automatiquement les notifications appropriées
"""
from typing import Dict, Any, List, Optional
from datetime import datetime, timedelta
from database import db, AgentLog, AgentAction
from llm_service import llm
import json


class AssistantAgent:
    """Agent assistant pour la surveillance et gestion automatique"""
    
    def __init__(self):
        self.name = "assistant_agent"
        self.notification_types = {
            "email_new": {"icon": "fa-envelope", "color": "info", "priority": "medium"},
            "whatsapp_new": {"icon": "fa-whatsapp", "color": "success", "priority": "medium"},
            "task_due": {"icon": "fa-tasks", "color": "warning", "priority": "high"},
            "task_overdue": {"icon": "fa-exclamation-triangle", "color": "danger", "priority": "urgent"},
            "call_reminder": {"icon": "fa-phone", "color": "primary", "priority": "high"},
            "shift_reminder": {"icon": "fa-clock", "color": "primary", "priority": "high"},
            "lead_hot": {"icon": "fa-fire", "color": "danger", "priority": "urgent"},
            "integration_error": {"icon": "fa-exclamation-circle", "color": "danger", "priority": "urgent"},
            "integration_sync": {"icon": "fa-sync", "color": "info", "priority": "low"},
        }
    
    def run_daily_check(self, customer_id: int) -> Dict[str, Any]:
        """Vérification quotidienne complète"""
        
        input_data = {
            "customer_id": customer_id,
            "check_time": datetime.now().isoformat(),
            "check_type": "daily"
        }
        
        try:
            results = {
                "new_emails": 0,
                "new_whatsapp": 0,
                "tasks_due": 0,
                "tasks_overdue": 0,
                "notifications_created": 0,
                "integrations_synced": 0,
                "errors": []
            }
            
            # 1. Vérifier les nouveaux emails
            email_check = self._check_new_emails(customer_id)
            results["new_emails"] = email_check["count"]
            results["notifications_created"] += email_check["notifications"]
            
            # 2. Vérifier les messages WhatsApp
            whatsapp_check = self._check_new_whatsapp(customer_id)
            results["new_whatsapp"] = whatsapp_check["count"]
            results["notifications_created"] += whatsapp_check["notifications"]
            
            # 3. Vérifier les tâches à faire aujourd'hui
            tasks_check = self._check_tasks_due(customer_id)
            results["tasks_due"] = tasks_check["due_count"]
            results["tasks_overdue"] = tasks_check["overdue_count"]
            results["notifications_created"] += tasks_check["notifications"]
            
            # 4. Vérifier les leads chauds nécessitant une action
            leads_check = self._check_hot_leads(customer_id)
            results["notifications_created"] += leads_check["notifications"]
            
            # 5. Synchroniser les intégrations
            sync_check = self._synchronize_integrations(customer_id)
            results["integrations_synced"] = sync_check["synced"]
            results["errors"].extend(sync_check["errors"])
            
            # 6. Vérifier les rappels d'appels
            calls_check = self._check_call_reminders(customer_id)
            results["notifications_created"] += calls_check["notifications"]
            
            # 7. Vérifier les shifts ERP à venir
            shifts_check = self._check_upcoming_shifts(customer_id)
            results["shifts_today"] = shifts_check["count"]
            results["notifications_created"] += shifts_check["notifications"]
            
            # Log de succès
            AgentLog.create(
                agent_name=self.name,
                action="daily_check",
                input_data=input_data,
                output_data=results,
                status="success",
                customer_id=customer_id
            )
            
            return {
                "success": True,
                "results": results,
                "message": f"Vérification quotidienne terminée : {results['notifications_created']} notifications créées"
            }
            
        except Exception as e:
            error_msg = str(e)
            AgentLog.create(
                agent_name=self.name,
                action="daily_check",
                input_data=input_data,
                output_data={},
                status="error",
                error_message=error_msg,
                customer_id=customer_id
            )
            return {"success": False, "error": error_msg}
    
    def _check_new_emails(self, customer_id: int) -> Dict[str, Any]:
        """Vérifier les nouveaux emails non lus"""
        try:
            # Récupérer les emails des dernières 24h non traités
            query = """
                SELECT id, subject, from_address, email_date, is_read, priority
                FROM emails
                WHERE customer_id = %s
                AND email_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND is_read = 0
                ORDER BY email_date DESC
            """
            new_emails = db.execute(query, (customer_id,))
            
            notifications_created = 0
            
            for email in new_emails:
                # Créer une notification pour chaque email non lu important
                if email.get('priority') in ['high', 'urgent'] or self._is_important_email(email):
                    self._create_notification(
                        customer_id=customer_id,
                        type="email_new",
                        title=f"📧 Nouvel email important",
                        message=f"De: {email['from_address']} - {email['subject'][:50]}...",
                        link=f"email-inbox.php?id={email['id']}",
                        priority="high" if email.get('priority') == 'urgent' else "medium"
                    )
                    notifications_created += 1
            
            return {"count": len(new_emails), "notifications": notifications_created}
            
        except Exception as e:
            print(f"Erreur vérification emails: {e}")
            return {"count": 0, "notifications": 0}
    
    def _check_new_whatsapp(self, customer_id: int) -> Dict[str, Any]:
        """Vérifier les nouveaux messages WhatsApp"""
        try:
            # Vérifier si l'intégration WhatsApp est active
            integration = db.execute_one(
                "SELECT * FROM integrations WHERE customer_id = %s AND integration_type = 'whatsapp' AND is_active = 1",
                (customer_id,)
            )
            
            if not integration:
                return {"count": 0, "notifications": 0}
            
            # Récupérer les messages WhatsApp non lus
            query = """
                SELECT id, from_phone, contact_name, content, created_at
                FROM whatsapp_messages
                WHERE customer_id = %s
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND is_read = 0
                ORDER BY created_at DESC
            """
            new_messages = db.execute(query, (customer_id,))
            
            notifications_created = 0
            
            for msg in new_messages:
                sender_name = msg.get('contact_name') or msg.get('from_phone')
                self._create_notification(
                    customer_id=customer_id,
                    type="whatsapp_new",
                    title=f"💬 Nouveau message WhatsApp",
                    message=f"De: {sender_name} - {msg['content'][:50]}...",
                    link=f"whatsapp.php?id={msg['id']}",
                    priority="medium"
                )
                notifications_created += 1
            
            return {"count": len(new_messages), "notifications": notifications_created}
            
        except Exception as e:
            print(f"Erreur vérification WhatsApp: {e}")
            return {"count": 0, "notifications": 0}
    
    def _check_tasks_due(self, customer_id: int) -> Dict[str, Any]:
        """Vérifier les tâches à faire aujourd'hui et en retard"""
        try:
            # Tâches dues aujourd'hui
            today_tasks = db.execute("""
                SELECT id, title, description, due_date, assigned_to
                FROM tasks
                WHERE customer_id = %s
                AND DATE(due_date) = CURDATE()
                AND status != 'completed'
                ORDER BY due_date ASC
            """, (customer_id,))
            
            # Tâches en retard
            overdue_tasks = db.execute("""
                SELECT id, title, description, due_date, assigned_to
                FROM tasks
                WHERE customer_id = %s
                AND due_date < NOW()
                AND status != 'completed'
                ORDER BY due_date ASC
            """, (customer_id,))
            
            notifications_created = 0
            
            # Notifications pour tâches du jour
            for task in today_tasks:
                self._create_notification(
                    customer_id=customer_id,
                    type="task_due",
                    title=f"📋 Tâche à effectuer aujourd'hui",
                    message=f"{task['title']} - Échéance: {task['due_date'].strftime('%H:%M')}",
                    link=f"tasks.php?id={task['id']}",
                    priority="high"
                )
                notifications_created += 1
            
            # Notifications pour tâches en retard
            for task in overdue_tasks:
                days_late = (datetime.now() - task['due_date']).days
                self._create_notification(
                    customer_id=customer_id,
                    type="task_overdue",
                    title=f"⚠️ Tâche en retard ({days_late} jours)",
                    message=f"{task['title']} - Devait être fait le {task['due_date'].strftime('%d/%m/%Y')}",
                    link=f"tasks.php?id={task['id']}",
                    priority="urgent"
                )
                notifications_created += 1
            
            return {
                "due_count": len(today_tasks),
                "overdue_count": len(overdue_tasks),
                "notifications": notifications_created
            }
            
        except Exception as e:
            print(f"Erreur vérification tâches: {e}")
            return {"due_count": 0, "overdue_count": 0, "notifications": 0}
    
    def _check_hot_leads(self, customer_id: int) -> Dict[str, Any]:
        """Vérifier les leads chauds nécessitant une action"""
        try:
            # Leads avec score élevé non contactés récemment
            hot_leads = db.execute("""
                SELECT l.id, l.first_name, l.last_name, l.company_name, l.ai_score,
                       l.last_contact_date, l.status
                FROM crm_leads l
                WHERE l.customer_id = %s
                AND l.ai_score >= 80
                AND l.status NOT IN ('converted', 'lost')
                AND (l.last_contact_date IS NULL OR l.last_contact_date < DATE_SUB(NOW(), INTERVAL 3 DAY))
                ORDER BY l.ai_score DESC
                LIMIT 10
            """, (customer_id,))
            
            notifications_created = 0
            
            for lead in hot_leads:
                name = f"{lead.get('first_name', '')} {lead.get('last_name', '')}".strip() or lead.get('company_name', 'Lead inconnu')
                days_since_contact = (datetime.now() - lead['last_contact_date']).days if lead['last_contact_date'] else 999
                
                self._create_notification(
                    customer_id=customer_id,
                    type="lead_hot",
                    title=f"🔥 Lead chaud à contacter",
                    message=f"{name} - Score: {lead['ai_score']}/100 - Non contacté depuis {days_since_contact} jours",
                    link=f"leads-view.php?id={lead['id']}",
                    priority="urgent"
                )
                notifications_created += 1
            
            return {"count": len(hot_leads), "notifications": notifications_created}
            
        except Exception as e:
            print(f"Erreur vérification leads: {e}")
            return {"count": 0, "notifications": 0}
    
    def _check_call_reminders(self, customer_id: int) -> Dict[str, Any]:
        """Vérifier les rappels d'appels à effectuer"""
        try:
            # Appels programmés pour aujourd'hui
            call_reminders = db.execute("""
                SELECT id, contact_name, phone, notes, scheduled_time
                FROM call_reminders
                WHERE customer_id = %s
                AND DATE(scheduled_time) = CURDATE()
                AND status = 'pending'
                ORDER BY scheduled_time ASC
            """, (customer_id,))
            
            notifications_created = 0
            
            for reminder in call_reminders:
                self._create_notification(
                    customer_id=customer_id,
                    type="call_reminder",
                    title=f"📞 Rappel d'appel",
                    message=f"Appeler {reminder['contact_name']} à {reminder['scheduled_time'].strftime('%H:%M')}",
                    link=f"calls.php?id={reminder['id']}",
                    priority="high"
                )
                notifications_created += 1
            
            return {"count": len(call_reminders), "notifications": notifications_created}
            
        except Exception as e:
            print(f"Erreur vérification rappels: {e}")
            return {"count": 0, "notifications": 0}
    
    def _check_upcoming_shifts(self, customer_id: int) -> Dict[str, Any]:
        """Vérifier les shifts ERP à venir (aujourd'hui et demain)"""
        try:
            # Récupérer les shifts des 24 prochaines heures
            shifts = db.execute("""
                SELECT s.id, s.start_datetime, s.end_datetime, s.role, s.notes,
                       e.first_name, e.last_name, e.job_title
                FROM erp_shifts s
                JOIN erp_employees e ON s.employee_id = e.id
                WHERE s.customer_id = %s
                AND s.start_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
                ORDER BY s.start_datetime ASC
            """, (customer_id,))
            
            notifications_created = 0
            
            for shift in shifts:
                employee_name = f"{shift['first_name']} {shift['last_name']}"
                shift_time = shift['start_datetime'].strftime('%H:%M')
                role = shift['role'] or shift['job_title']
                
                # Créer une notification pour les shifts commençant dans moins de 2h
                time_diff = (shift['start_datetime'] - datetime.now()).total_seconds() / 3600
                
                if time_diff <= 2:  # Moins de 2 heures
                    self._create_notification(
                        customer_id=customer_id,
                        type="shift_reminder",
                        title=f"🕐 Shift à venir",
                        message=f"{employee_name} ({role}) - Début: {shift_time}",
                        link=f"../erp/shifts.php?id={shift['id']}",
                        priority="high" if time_diff <= 0.5 else "medium"
                    )
                    notifications_created += 1
            
            return {"count": len(shifts), "notifications": notifications_created}
            
        except Exception as e:
            print(f"Erreur vérification shifts: {e}")
            return {"count": 0, "notifications": 0}
    
    def _synchronize_integrations(self, customer_id: int) -> Dict[str, Any]:
        """Synchroniser toutes les intégrations actives"""
        try:
            # Récupérer toutes les intégrations actives
            integrations = db.execute("""
                SELECT id, integration_type, name, last_sync, config
                FROM integrations
                WHERE customer_id = %s
                AND is_active = 1
            """, (customer_id,))
            
            synced = 0
            errors = []
            
            for integration in integrations:
                try:
                    # Synchronisation selon le type
                    sync_result = self._sync_integration(integration, customer_id)
                    
                    if sync_result["success"]:
                        # Mettre à jour le dernier sync
                        db.update("""
                            UPDATE integrations
                            SET last_sync = NOW(), 
                                sync_status = 'success',
                                error_message = NULL
                            WHERE id = %s
                        """, (integration['id'],))
                        
                        synced += 1
                        
                        # Créer notification de succès
                        if sync_result.get("new_items", 0) > 0:
                            self._create_notification(
                                customer_id=customer_id,
                                type="integration_sync",
                                title=f"✅ Synchronisation {integration['name']}",
                                message=f"{sync_result['new_items']} nouveaux éléments synchronisés",
                                link="integrations.php",
                                priority="low"
                            )
                    else:
                        raise Exception(sync_result.get("error", "Erreur inconnue"))
                        
                except Exception as e:
                    error_msg = str(e)
                    errors.append({
                        "integration": integration['name'],
                        "error": error_msg
                    })
                    
                    # Mettre à jour le statut d'erreur
                    db.update("""
                        UPDATE integrations
                        SET sync_status = 'error',
                            error_message = %s
                        WHERE id = %s
                    """, (error_msg, integration['id']))
                    
                    # Créer notification d'erreur
                    self._create_notification(
                        customer_id=customer_id,
                        type="integration_error",
                        title=f"❌ Erreur {integration['name']}",
                        message=f"Échec de synchronisation : {error_msg[:100]}",
                        link="integrations.php",
                        priority="urgent"
                    )
            
            return {"synced": synced, "errors": errors}
            
        except Exception as e:
            print(f"Erreur synchronisation intégrations: {e}")
            return {"synced": 0, "errors": [{"integration": "global", "error": str(e)}]}
    
    def _sync_integration(self, integration: Dict, customer_id: int) -> Dict[str, Any]:
        """Synchroniser une intégration spécifique"""
        integration_type = integration['integration_type']
        
        # Dispatcher selon le type
        if integration_type == 'email':
            return self._sync_email_integration(integration, customer_id)
        elif integration_type == 'whatsapp':
            return self._sync_whatsapp_integration(integration, customer_id)
        elif integration_type == 'calendar':
            return self._sync_calendar_integration(integration, customer_id)
        elif integration_type == 'sms':
            return self._sync_sms_integration(integration, customer_id)
        else:
            return {"success": False, "error": f"Type d'intégration non supporté: {integration_type}"}
    
    def _sync_email_integration(self, integration: Dict, customer_id: int) -> Dict[str, Any]:
        """Synchroniser les emails (IMAP/POP3)"""
        # TODO: Implémenter la synchronisation IMAP
        # Pour l'instant, simuler le succès
        return {"success": True, "new_items": 0}
    
    def _sync_whatsapp_integration(self, integration: Dict, customer_id: int) -> Dict[str, Any]:
        """Synchroniser WhatsApp (via API)"""
        # TODO: Implémenter l'API WhatsApp Business
        # Pour l'instant, simuler le succès
        return {"success": True, "new_items": 0}
    
    def _sync_calendar_integration(self, integration: Dict, customer_id: int) -> Dict[str, Any]:
        """Synchroniser le calendrier (Google Calendar, Outlook, etc.)"""
        # TODO: Implémenter la synchronisation calendrier
        return {"success": True, "new_items": 0}
    
    def _sync_sms_integration(self, integration: Dict, customer_id: int) -> Dict[str, Any]:
        """Synchroniser les SMS (Twilio, etc.)"""
        # TODO: Implémenter la synchronisation SMS
        return {"success": True, "new_items": 0}
    
    def _is_important_email(self, email: Dict) -> bool:
        """Déterminer si un email est important"""
        # Logique simple pour détecter les emails importants
        subject_lower = email.get('subject', '').lower()
        sender_lower = email.get('from_address', '').lower()
        
        important_keywords = [
            'urgent', 'important', 'asap', 'devis', 'commande', 
            'facture', 'paiement', 'deadline', 'échéance'
        ]
        
        return any(keyword in subject_lower for keyword in important_keywords)
    
    def _create_notification(
        self,
        customer_id: int,
        type: str,
        title: str,
        message: str,
        link: str = None,
        priority: str = "medium"
    ) -> int:
        """Créer une notification dans la base de données"""
        try:
            notification_config = self.notification_types.get(type, {
                "icon": "fa-bell",
                "color": "primary",
                "priority": "medium"
            })
            
            query = """
                INSERT INTO crm_notifications 
                (customer_id, type, title, message, icon, color, link, priority, is_read)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 0)
            """
            
            notification_id = db.insert(query, (
                customer_id,
                type,
                title,
                message,
                notification_config["icon"],
                notification_config["color"],
                link,
                priority
            ))
            
            return notification_id
            
        except Exception as e:
            print(f"Erreur création notification: {e}")
            return 0


# Instance globale pour utilisation facile
assistant = AssistantAgent()
