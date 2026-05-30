#!/bin/bash
# 🚀 WEBEXA DEPLOYMENT - STEP BY STEP GUIDE
# Lisez ce fichier avant de faire quoi que ce soit!

cat << 'EOF'

╔════════════════════════════════════════════════════════════════════════════╗
║                                                                            ║
║           ✅ WEBEXA - PRÊT POUR LE DÉPLOIEMENT VPS                       ║
║                                                                            ║
║   Votre application est 100% préparée pour le déploiement!               ║
║                                                                            ║
╚════════════════════════════════════════════════════════════════════════════╝


📌 QUOI FAIRE MAINTENANT?
═══════════════════════════════════════════════════════════════════════════

Il y a 3 chemins possibles:


🚀 CHEMIN 1: DÉPLOIEMENT RAPIDE (RECOMMANDÉ) ⭐
═══════════════════════════════════════════════════════════════════════════

Exécutez simplement:

    chmod +x quick-deploy.sh
    ./quick-deploy.sh

Et répondez aux questions:
  - Adresse IP VPS
  - Nom d'utilisateur SSH (défaut: root)
  - Domaines (défaut: webexa.fr, webexa.online)

Le script fait TOUT automatiquement!


📖 CHEMIN 2: D'ABORD LIRE (POUR COMPRENDRE) 
═══════════════════════════════════════════════════════════════════════════

Lisez d'abord en français:

    cat LISEZMOI_DEPLOYMENT.md

Puis:

    ./quick-deploy.sh


🔧 CHEMIN 3: DÉPLOIEMENT MANUEL (AVANCÉ)
═══════════════════════════════════════════════════════════════════════════

Lisez les instructions détaillées:

    cat DEPLOYMENT_GUIDE.md

Et suivez étape par étape (pour apprendre ou déboguer)


═══════════════════════════════════════════════════════════════════════════

📋 FICHIERS CRÉÉS POUR VOUS
═══════════════════════════════════════════════════════════════════════════

📚 GUIDES (Lire ça):
  • LISEZMOI_DEPLOYMENT.md ........ En français!
  • DEPLOYMENT_SUMMARY.md ......... Résumé complet
  • DEPLOYMENT_README.md .......... Guide détaillé
  • CHEAT_SHEET.md ............... Commandes rapides

🚀 SCRIPTS (Exécuter ça):
  • quick-deploy.sh .............. ⭐ LE SCRIPT PRINCIPAL!
  • setup-vps.sh ................. Configuration VPS
  • verify-deployment.sh .......... Vérification après
  • deploy.sh .................... Alternative

⚙️  CONFIGS (Pour avancés):
  • nginx-webexa.fr.conf
  • nginx-webexa.online.conf
  • webexa-api.service


═══════════════════════════════════════════════════════════════════════════

⏱️ COMBIEN DE TEMPS?
═══════════════════════════════════════════════════════════════════════════

  • Lecture du guide: 5 minutes
  • Exécution du script: 10-15 minutes
  • Configuration .env: 5 minutes
  ─────────────────────────────
  • TOTAL: ~35 minutes
  
  (+ 24 heures pour la propagation DNS)


═══════════════════════════════════════════════════════════════════════════

🎯 CE QUE VOUS OBTIENDREZ
═══════════════════════════════════════════════════════════════════════════

✅ webexa.fr ................ Votre CRM/ERP
✅ webexa.online ............ Votre API Python (Agents IA)
✅ HTTPS/SSL ................ Certificats Let's Encrypt
✅ Auto-redémarrage ......... Si un service plante
✅ Monitoring ............... Logs et diagnostic
✅ Sécurité ................. Headers, restrictions


═══════════════════════════════════════════════════════════════════════════

✅ PRÉ-REQUIS (À AVOIR AVANT)
═══════════════════════════════════════════════════════════════════════════

  ☐ IP de votre VPS
  ☐ Accès SSH au VPS
  ☐ Domaines enregistrés (webexa.fr, webexa.online)
  ☐ Base de données (MySQL/Oracle)
  ☐ Clés API (OpenAI, Anthropic, etc.)


═══════════════════════════════════════════════════════════════════════════

🚀 DÉMARRAGE RAPIDE (COPY-PASTE)
═══════════════════════════════════════════════════════════════════════════

1. Rendez les scripts exécutables:
   
   chmod +x quick-deploy.sh setup-vps.sh deploy.sh verify-deployment.sh

2. Lancez le déploiement:
   
   ./quick-deploy.sh

3. Répondez aux questions du script
4. Attendez 10-15 minutes
5. Configurez .env sur le VPS
6. Configurez DNS chez votre registraire
7. C'est fait! 🎉


═══════════════════════════════════════════════════════════════════════════

❓ SI VOUS AVEZ DES QUESTIONS
═══════════════════════════════════════════════════════════════════════════

  • Avant de commencer? Lire: LISEZMOI_DEPLOYMENT.md
  • Pendant le déploiement? Suivez le prompt du script
  • Après le déploiement? Lire: CHEAT_SHEET.md
  • Problème? Lire: DEPLOYMENT_README.md (section Troubleshooting)
  • En détail? Lire: DEPLOYMENT_GUIDE.md


═══════════════════════════════════════════════════════════════════════════

💡 CONSEIL: Lisez d'abord LISEZMOI_DEPLOYMENT.md (5 min)
   Ça vous donnera la vue d'ensemble avant de lancer le script.


═══════════════════════════════════════════════════════════════════════════

🎉 VOUS ÊTES 100% PRÊT! 
   Lancez simplement: ./quick-deploy.sh

   Bonne chance! 🚀

═══════════════════════════════════════════════════════════════════════════

EOF

echo ""
echo "📌 PROCHAINE ÉTAPE: Lire LISEZMOI_DEPLOYMENT.md ou lancer quick-deploy.sh"
echo ""
