<?php
/**
 * Base de templates d'emails pour les campagnes
 * Permet de générer automatiquement des emails professionnels
 */

function get_email_templates() {
    return [
        // === LEAD NURTURING ===
        'lead_welcome' => [
            'name' => 'Bienvenue - Lead',
            'category' => 'lead_nurturing',
            'subject' => 'Bienvenue {FIRST_NAME} ! 👋',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Merci de votre intérêt pour nos services ! Nous sommes ravi de vous accueillir dans notre communauté.</p>

<p>Nous nous engageons à vous fournir les meilleures solutions adaptées à vos besoins. N'hésitez pas à explorer nos offres ou à nous contacter si vous avez des questions.</p>

<p>À bientôt,<br>
L'équipe</p>
HTML
        ],
        
        'lead_followup_1' => [
            'name' => 'Suivi 1 - 3 jours après',
            'category' => 'lead_nurturing',
            'subject' => 'Avez-vous eu l\'occasion de vérifier ?',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Nous espérons que vous avez pu consulter nos services. Nous aimerions connaître votre avis !</p>

<p>Avez-vous des questions ? Notre équipe est là pour vous aider à trouver la solution parfaite.</p>

<p>Cordialement,<br>
L'équipe Commerciale</p>
HTML
        ],

        'lead_followup_2' => [
            'name' => 'Suivi 2 - 7 jours après',
            'category' => 'lead_nurturing',
            'subject' => 'Une offre spéciale pour vous',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Nous avons remarqué que vous n'avez pas encore pris de décision. Nous aimerions vous faire bénéficier d'une offre exclusive !</p>

<p><strong>Offre limitée :</strong> 20% de réduction pour les nouveaux clients cette semaine.</p>

<p>Contactez-nous dès maintenant pour en savoir plus.</p>

<p>Cordialement,<br>
L'équipe</p>
HTML
        ],

        // === SALES FOCUSED ===
        'product_demo' => [
            'name' => 'Demande de Démo Produit',
            'category' => 'sales',
            'subject' => 'Réservez votre démo gratuite - {FIRST_NAME}',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Découvrez notre solution en action lors d'une démo personnalisée (15-20 minutes).</p>

<p><strong>Avantages :</strong></p>
<ul>
    <li>Découverte complète de la plateforme</li>
    <li>Cas d'usage adaptés à votre secteur</li>
    <li>Réponses à toutes vos questions</li>
</ul>

<p><strong>Réservez maintenant :</strong> [Lien de réservation]</p>

<p>Cordialement,<br>
L'équipe Commercial</p>
HTML
        ],

        'pricing_inquiry' => [
            'name' => 'Demande de Tarification',
            'category' => 'sales',
            'subject' => 'Nos tarifs adaptés à votre budget',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Merci de votre intérêt pour nos services. Nous avons des forfaits flexibles adaptés à tous les budgets.</p>

<p><strong>Nos plans :</strong></p>
<ul>
    <li><strong>Starter :</strong> 29€/mois - Parfait pour débuter</li>
    <li><strong>Pro :</strong> 79€/mois - Pour les petites équipes</li>
    <li><strong>Enterprise :</strong> Tarif personnalisé - Pour les grandes organisations</li>
</ul>

<p>Découvrez quelle offre correspond le mieux à vos besoins.</p>

<p>Cordialement,<br>
L'équipe Commercial</p>
HTML
        ],

        'trial_reminder' => [
            'name' => 'Rappel - Essai Gratuit',
            'category' => 'sales',
            'subject' => 'Votre essai gratuit expire bientôt !',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Vous avez lancé votre essai gratuit il y a 14 jours. Nous espérons que la plateforme vous plaît !</p>

<p><strong>Avant que votre essai n'expire :</strong></p>
<ul>
    <li>Testez toutes les fonctionnalités premium</li>
    <li>Intégrez vos outils existants</li>
    <li>Invitez votre équipe</li>
</ul>

<p><strong>Prêt à commencer ?</strong> [Activer votre compte]</p>

<p>Cordialement,<br>
L'équipe</p>
HTML
        ],

        'abandoned_cart' => [
            'name' => 'Panier Abandonné',
            'category' => 'sales',
            'subject' => 'Votre panier vous attend, {FIRST_NAME} 🛒',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Nous avons remarqué que vous avez laissé des articles dans votre panier.</p>

<p><strong>Articles en attente :</strong></p>
<ul>
    <li>[Produit 1 - 29€]</li>
    <li>[Produit 2 - 49€]</li>
</ul>

<p><strong>Total : 78€</strong></p>

<p><strong>Offre spéciale :</strong> Complétez votre achat aujourd'hui et bénéficiez de 15% de réduction avec le code <strong>FINISH15</strong></p>

<p><strong>Finaliser la commande :</strong> [Retour au panier]</p>

<p>Questions ? Notre équipe est là pour vous aider !</p>

<p>Cordialement,<br>
L'équipe Commercial</p>
HTML
        ],

        // === CUSTOMER SUCCESS ===
        'onboarding' => [
            'name' => 'Onboarding - Bienvenue Client',
            'category' => 'customer_success',
            'subject' => 'Bienvenue {FIRST_NAME} ! Commençons ensemble 🚀',
            'body' => <<<'HTML'
<p>Bienvenue {FIRST_NAME},</p>

<p>Nous sommes heureux de vous avoir à bord ! Pour démarrer du bon pied, voici votre plan d'action :</p>

<p><strong>Étape 1 - Aujourd'hui :</strong> Configurez votre profil (2 min)</p>
<p><strong>Étape 2 - Demain :</strong> Invitez votre équipe (5 min)</p>
<p><strong>Étape 3 - Cette semaine :</strong> Importez vos données (15 min)</p>

<p>Notre équipe est disponible pour vous aider à chaque étape. N'hésitez pas à poser vos questions !</p>

<p>Bienvenue,<br>
L'équipe Success</p>
HTML
        ],

        'feature_announcement' => [
            'name' => 'Annonce - Nouvelle Fonctionnalité',
            'category' => 'customer_success',
            'subject' => '✨ Découvrez notre nouvelle fonctionnalité',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Nous sommes ravi d'annoncer le lancement d'une fonctionnalité très demandée par nos utilisateurs !</p>

<p><strong>Tableau de Bord Analytique Avancé :</strong></p>
<ul>
    <li>Visualisations personnalisables</li>
    <li>Rapports automatisés en temps réel</li>
    <li>Exportation multi-format</li>
</ul>

<p><strong>Découvrez-la dès maintenant :</strong> [Lien vers la nouvelle fonctionnalité]</p>

<p>Cordialement,<br>
L'équipe Produit</p>
HTML
        ],

        'win_back' => [
            'name' => 'Win-Back - Nous vous manquez',
            'category' => 'customer_success',
            'subject' => 'Nous vous manquez, {FIRST_NAME} ! 💙',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Nous avons remarqué que vous n'utilisez plus notre service depuis quelques temps. Nous aimerions savoir pourquoi !</p>

<p><strong>Avez-vous rencontré un problème ?</strong> Notre équipe support est là pour vous aider.</p>

<p><strong>Offre de retour :</strong> 50% de réduction le premier mois si vous réactivez votre compte cette semaine.</p>

<p>Nous serions ravis de vous revoir !</p>

<p>Cordialement,<br>
L'équipe</p>
HTML
        ],

        // === EVENT & PROMOTIONAL ===
        'event_invitation' => [
            'name' => 'Invitation Événement',
            'category' => 'promotion',
            'subject' => '🎉 Vous êtes invité à notre événement exclusif',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Nous avons le plaisir de vous inviter à notre événement exclusif !</p>

<p><strong>Événement :</strong> Webinaire - Optimisez votre stratégie marketing<br>
<strong>Date :</strong> 25 février 2026 à 15h00 (GMT+1)<br>
<strong>Durée :</strong> 60 minutes<br>
<strong>Intervenants :</strong> Experts du secteur</p>

<p><strong>Au programme :</strong></p>
<ul>
    <li>Tendances 2026 en marketing digital</li>
    <li>Cas d'usage concrets</li>
    <li>Q&A en direct avec les experts</li>
</ul>

<p><strong>Réservez maintenant :</strong> [Lien de réservation]</p>

<p>À bientôt,<br>
L'équipe</p>
HTML
        ],

        'seasonal_promotion' => [
            'name' => 'Promotion Saisonnière',
            'category' => 'promotion',
            'subject' => '⚡ Soldes février - Jusqu\'à -40%',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>C'est notre grand événement de février ! Ne manquez pas nos réductions exceptionnelles.</p>

<p><strong>🔥 Offres limitées :</strong></p>
<ul>
    <li>Abonnement annuel : -30% (seulement 99€ au lieu de 149€)</li>
    <li>Pack Professionnel : -40% (seulement 1 490€ au lieu de 2 490€)</li>
    <li>Support prioritaire gratuit (1 an)</li>
</ul>

<p><strong>⏰ Offre valable jusqu'au 28 février 2026</strong></p>

<p><strong>Profitez-en maintenant :</strong> [Lien vers les offres]</p>

<p>Cordialement,<br>
L'équipe</p>
HTML
        ],

        'referral_program' => [
            'name' => 'Programme de Parrainage',
            'category' => 'promotion',
            'subject' => 'Gagnez 100€ en parrainant vos amis',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Nous avons créé un programme de parrainage génial pour vous remercier de votre fidélité !</p>

<p><strong>Comment ça marche :</strong></p>
<ol>
    <li>Invitez un ami via votre lien de parrainage personnalisé</li>
    <li>Il s'inscrit et active un plan payant</li>
    <li>Vous recevez 100€ de crédit (et lui aussi !)</li>
</ol>

<p><strong>Pas de limite !</strong> Vous pouvez parrainer autant de personnes que vous le souhaitez.</p>

<p><strong>Commencez maintenant :</strong> [Accès au programme]</p>

<p>Cordialement,<br>
L'équipe</p>
HTML
        ],

        // === PARTNERSHIP ===
        'partnership_proposal' => [
            'name' => 'Proposition de Partenariat',
            'category' => 'partnership',
            'subject' => 'Proposition de Partenariat Stratégique',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Nous voyons un excellent potentiel de collaboration entre nos organisations.</p>

<p><strong>Opportunités de partenariat :</strong></p>
<ul>
    <li>Intégration réciproque des produits</li>
    <li>Programme d'affiliation conjoint</li>
    <li>Co-marketing et events</li>
    <li>Webinaires conjoints</li>
</ul>

<p>Nous serions ravis de discuter des détails avec vous. Êtes-vous disponible pour un appel cette semaine ?</p>

<p>Cordialement,<br>
L'équipe Partenariats</p>
HTML
        ],

        // === TECH & SOFTWARE SALES ===
        'tech_cold_outreach' => [
            'name' => 'Prospection Tech - Premier Contact',
            'category' => 'tech_sales',
            'subject' => '{COMPANY} - Optimisez votre infrastructure digitale',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Je me permets de vous contacter concernant la transformation digitale de {COMPANY}.</p>

<p>Nous accompagnons des entreprises de votre secteur dans :</p>
<ul>
    <li><strong>Développement d'applications métier</strong> - Solutions sur-mesure adaptées à vos processus</li>
    <li><strong>Sites web performants</strong> - E-commerce, vitrines, portails clients</li>
    <li><strong>Modernisation logicielle</strong> - Migration vers le cloud, optimisation legacy</li>
</ul>

<p><strong>Nos clients récents :</strong> [Nom Client 1], [Nom Client 2], [Nom Client 3]</p>

<p>Seriez-vous disponible pour un échange de 15 minutes afin d'identifier vos besoins ?</p>

<p>Cordialement,<br>
{SIGNATURE}</p>
HTML
        ],

        'erp_demo_invitation' => [
            'name' => 'Invitation Démo ERP',
            'category' => 'tech_sales',
            'subject' => 'Démo personnalisée - ERP nouvelle génération pour {COMPANY}',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Vous cherchez à unifier vos processus de gestion ? Notre solution ERP est conçue pour les entreprises en croissance comme {COMPANY}.</p>

<p><strong>Modules disponibles :</strong></p>
<ul>
    <li>📊 <strong>Gestion Financière</strong> - Comptabilité, trésorerie, budget</li>
    <li>📦 <strong>Supply Chain</strong> - Stocks, achats, logistique</li>
    <li>👥 <strong>Ressources Humaines</strong> - Paie, congés, recrutement</li>
    <li>🛒 <strong>Ventes & CRM</strong> - Pipeline commercial, facturation</li>
    <li>📈 <strong>Business Intelligence</strong> - Tableaux de bord temps réel</li>
</ul>

<p><strong>✨ Avantages clés :</strong></p>
<ul>
    <li>✅ Déploiement rapide (4-6 semaines)</li>
    <li>✅ Interface intuitive - Formation 2 jours</li>
    <li>✅ Cloud sécurisé avec garantie 99,9% uptime</li>
    <li>✅ Intégrations natives (comptables, banques, e-commerce)</li>
</ul>

<p><strong>Réservez votre démo personnalisée :</strong> [Lien calendrier]</p>

<p>Découvrez comment gagner 20h/semaine sur vos processus administratifs.</p>

<p>Cordialement,<br>
{SIGNATURE}<br>
Expert Solutions ERP</p>
HTML
        ],

        'crm_demo_invitation' => [
            'name' => 'Invitation Démo CRM',
            'category' => 'tech_sales',
            'subject' => 'CRM Intelligent - Doublez vos conversions, {FIRST_NAME}',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Perdez-vous des opportunités commerciales par manque de suivi ? Notre CRM intelligent résout ce problème.</p>

<p><strong>🎯 Fonctionnalités clés :</strong></p>
<ul>
    <li><strong>Pipeline visuel</strong> - Gérez vos opportunités par glisser-déposer</li>
    <li><strong>Lead scoring IA</strong> - Priorisez automatiquement vos meilleurs prospects</li>
    <li><strong>Automatisations marketing</strong> - Emails, SMS, relances automatiques</li>
    <li><strong>Analytics avancés</strong> - Prévisions de ventes, ROI campagnes</li>
    <li><strong>Mobile-first</strong> - Toute votre relation client dans votre poche</li>
</ul>

<p><strong>📊 Résultats clients moyens :</strong></p>
<ul>
    <li>+45% de taux de conversion</li>
    <li>-60% de temps administratif</li>
    <li>+30% de revenus la première année</li>
</ul>

<p><strong>🚀 Démo live 20 minutes :</strong> Nous configurons un environnement avec VOS données réelles.</p>

<p><strong>Choisissez votre créneau :</strong> [Lien calendrier]</p>

<p>PS : Offre spéciale jusqu'au {DATE} - 3 mois offerts pour tout abonnement annuel.</p>

<p>Cordialement,<br>
{SIGNATURE}<br>
Consultant CRM</p>
HTML
        ],

        'website_development_proposal' => [
            'name' => 'Proposition Développement Site Web',
            'category' => 'tech_sales',
            'subject' => 'Projet Site Web - Devis personnalisé pour {COMPANY}',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Suite à notre échange, voici notre proposition pour votre projet de site web.</p>

<p><strong>🎨 Types de sites que nous développons :</strong></p>
<ul>
    <li><strong>Site Vitrine Premium</strong> - 5-8 pages, design sur-mesure, SEO optimisé (à partir de 2 500€)</li>
    <li><strong>E-commerce Performant</strong> - Boutique en ligne, paiement sécurisé, gestion stocks (à partir de 5 500€)</li>
    <li><strong>Application Web Métier</strong> - Outil personnalisé pour vos processus internes (sur devis)</li>
    <li><strong>Portail Client</strong> - Espace membre, tableau de bord, documents (à partir de 4 000€)</li>
</ul>

<p><strong>✨ Inclus dans tous nos projets :</strong></p>
<ul>
    <li>✅ Design responsive (mobile, tablette, desktop)</li>
    <li>✅ Optimisation SEO complète</li>
    <li>✅ Performance (vitesse chargement < 2 sec)</li>
    <li>✅ Sécurité SSL, RGPD compliant</li>
    <li>✅ Formation à l'administration</li>
    <li>✅ Maintenance 3 mois offerts</li>
</ul>

<p><strong>🛠️ Technologies utilisées :</strong> React, Next.js, WordPress, Laravel selon vos besoins.</p>

<p><strong>📅 Planning type :</strong></p>
<ul>
    <li>Semaine 1-2 : Conception maquettes</li>
    <li>Semaine 3-6 : Développement</li>
    <li>Semaine 7 : Tests & corrections</li>
    <li>Semaine 8 : Formation & mise en ligne</li>
</ul>

<p><strong>Prêt à démarrer ?</strong> [Prendre rendez-vous pour affiner le devis]</p>

<p>Cordialement,<br>
{SIGNATURE}<br>
Chef de Projet Web</p>
HTML
        ],

        'software_followup' => [
            'name' => 'Suivi Post-Démo Logiciel',
            'category' => 'tech_sales',
            'subject' => 'Suite à notre démo - Questions sur notre solution ?',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Merci d'avoir assisté à notre démonstration hier. J'espère qu'elle a répondu à vos attentes !</p>

<p><strong>📋 Récapitulatif de ce que nous avons vu :</strong></p>
<ul>
    <li>Fonctionnalités principales adaptées à {COMPANY}</li>
    <li>Processus d'implémentation (4-6 semaines)</li>
    <li>Tarification personnalisée</li>
</ul>

<p><strong>❓ Questions fréquentes après une démo :</strong></p>

<p><strong>Q: Combien de temps pour être opérationnel ?</strong><br>
R: Configuration initiale en 1 semaine, formation équipe en 2 jours, pleine autonomie en 1 mois.</p>

<p><strong>Q: Quid de nos données actuelles ?</strong><br>
R: Migration complète incluse. Nous importons vos données depuis Excel, CSV ou votre système actuel.</p>

<p><strong>Q: Support technique ?</strong><br>
R: Support illimité par chat/email. Hotline prioritaire en option. SLA garanti.</p>

<p><strong>🎁 Prochaines étapes :</strong></p>
<ol>
    <li>Essai gratuit 14 jours avec vos vraies données</li>
    <li>Session de configuration avec notre expert</li>
    <li>Validation technique par votre DSI si besoin</li>
</ol>

<p><strong>Souhaitez-vous démarrer l'essai cette semaine ?</strong></p>

<p>Je reste disponible pour toute question.</p>

<p>Cordialement,<br>
{SIGNATURE}</p>
HTML
        ],

        'tech_case_study' => [
            'name' => 'Étude de Cas Client Tech',
            'category' => 'tech_sales',
            'subject' => 'Comment [Client] a augmenté sa productivité de 40%',
            'body' => <<<'HTML'
<p>Bonjour {FIRST_NAME},</p>

<p>Je souhaitais partager avec vous un cas concret qui pourrait vous inspirer.</p>

<p><strong>📊 Étude de cas : [Nom Client - Secteur similaire au vôtre]</strong></p>

<p><strong>Situation initiale :</strong></p>
<ul>
    <li>❌ Processus manuels chronophages</li>
    <li>❌ Données dispersées (Excel, emails, papier)</li>
    <li>❌ Erreurs de saisie fréquentes</li>
    <li>❌ Pas de vision globale en temps réel</li>
</ul>

<p><strong>Solution déployée :</strong></p>
<ul>
    <li>✅ [Nom de votre logiciel] avec modules personnalisés</li>
    <li>✅ Automatisation de 80% des tâches répétitives</li>
    <li>✅ Tableaux de bord temps réel</li>
    <li>✅ Formation équipe complète</li>
</ul>

<p><strong>📈 Résultats après 6 mois :</strong></p>
<ul>
    <li>🚀 +40% de productivité équipe</li>
    <li>💰 Économie estimée : 50 000€/an</li>
    <li>⏱️ Gain de temps : 15h/semaine</li>
    <li>📉 Réduction erreurs de 75%</li>
    <li>😊 Satisfaction employés : 9,2/10</li>
</ul>

<p><strong>"Nous aurions dû le faire il y a 2 ans. Le ROI a été atteint en 4 mois."</strong><br>
- [Prénom NOM, Fonction, Entreprise]</p>

<p><strong>Télécharger l'étude de cas complète :</strong> [Lien PDF]</p>

<p>Souhaitez-vous qu'on évalue ensemble le potentiel pour {COMPANY} ?</p>

<p>Cordialement,<br>
{SIGNATURE}</p>
HTML
        ],

        // === WHATSAPP TEMPLATES ===
        'whatsapp_welcome' => [
            'name' => 'WhatsApp - Bienvenue',
            'category' => 'whatsapp',
            'channel' => 'whatsapp',
            'subject' => 'Bienvenue {FIRST_NAME} ! 👋',
            'body' => <<<'TEXT'
👋 Bonjour {FIRST_NAME} !

Bienvenue chez Webitech ! 🎉

Nous sommes ravis de vous compter parmi nous.

📌 Prochaines étapes :
• Découvrir nos services
• Échanger sur vos besoins
• Obtenir un devis personnalisé

💬 Une question ? Répondez à ce message !

L'équipe Webitech
🌐 webitech.fr
TEXT
        ],

        'whatsapp_followup' => [
            'name' => 'WhatsApp - Relance Prospect',
            'category' => 'whatsapp',
            'channel' => 'whatsapp',
            'subject' => 'Toujours intéressé ?',
            'body' => <<<'TEXT'
Bonjour {FIRST_NAME} 👋

J'espère que vous allez bien !

Suite à notre dernier échange concernant {COMPANY}, je voulais savoir où vous en êtes ? 🤔

Nous avons :
✅ Solutions adaptées à votre secteur
✅ Devis en 24h
✅ Démo gratuite disponible

📞 Êtes-vous disponible pour en discuter cette semaine ?

Répondez simplement par OUI et je vous rappelle ! 😊

{SIGNATURE}
TEXT
        ],

        'whatsapp_promo' => [
            'name' => 'WhatsApp - Offre Spéciale',
            'category' => 'whatsapp',
            'channel' => 'whatsapp',
            'subject' => '🔥 Offre limitée',
            'body' => <<<'TEXT'
🔥 OFFRE EXCLUSIVE {FIRST_NAME} !

-30% sur tous nos services jusqu'au {DATE} ! ⏰

Ce qui est inclus :
💻 Développement web/app
📱 Solutions mobiles
🎨 Design professionnel
🔧 Maintenance 6 mois offerte

💰 Économisez jusqu'à 3 000€ !

👉 Répondez "DEVIS" pour recevoir votre proposition personnalisée

Offre valable 48h ⚡

Webitech - Votre partenaire digital
🌐 webitech.fr | ☎️ 07 59 81 00 51
TEXT
        ],

        'whatsapp_appointment' => [
            'name' => 'WhatsApp - Rappel RDV',
            'category' => 'whatsapp',
            'channel' => 'whatsapp',
            'subject' => 'Rappel RDV demain',
            'body' => <<<'TEXT'
📅 Bonjour {FIRST_NAME},

Petit rappel pour notre rendez-vous :

📍 Date : [JJ/MM/AAAA à HH:MM]
💻 Type : [Visio/Présentiel/Téléphone]
⏱️ Durée : 30 minutes

Au programme :
• Présentation de notre solution
• Analyse de vos besoins
• Proposition tarifaire

✅ Confirmez par OUI
❌ Reportez en répondant REPORT

À demain ! 😊

{SIGNATURE}
TEXT
        ],

        'whatsapp_demo' => [
            'name' => 'WhatsApp - Invitation Démo',
            'category' => 'whatsapp',
            'channel' => 'whatsapp',
            'subject' => 'Démo gratuite disponible',
            'body' => <<<'TEXT'
🎯 {FIRST_NAME}, votre démo gratuite vous attend !

Découvrez comment notre solution peut transformer {COMPANY} :

✨ Ce que vous verrez :
• Tableau de bord en temps réel
• Automatisations intelligentes
• Gain de temps immédiat
• ROI mesurable

⏰ Durée : 20 minutes
📱 100% en ligne

📅 Créneaux disponibles :
1️⃣ Lundi 14h
2️⃣ Mardi 10h
3️⃣ Mercredi 16h

👉 Répondez avec le numéro qui vous convient !

Webitech - Solutions digitales
🌐 webitech.fr
TEXT
        ],

        'whatsapp_thanks' => [
            'name' => 'WhatsApp - Remerciement',
            'category' => 'whatsapp',
            'channel' => 'whatsapp',
            'subject' => 'Merci !',
            'body' => <<<'TEXT'
🙏 Merci {FIRST_NAME} !

Votre confiance nous honore ! 🎉

Nous avons bien reçu votre demande et notre équipe la traite en priorité.

📋 Prochaines étapes :
1️⃣ Analyse détaillée (24h)
2️⃣ Proposition personnalisée
3️⃣ Échange avec notre expert

📞 Besoin d'infos ?
Répondez à ce message ou appelez-nous :
☎️ 07 59 81 00 51

À très vite ! 😊

L'équipe Webitech
🌐 webitech.fr
TEXT
        ],

        'whatsapp_event' => [
            'name' => 'WhatsApp - Invitation Événement',
            'category' => 'whatsapp',
            'channel' => 'whatsapp',
            'subject' => '🎉 Événement exclusif',
            'body' => <<<'TEXT'
🎉 Invitation VIP pour {FIRST_NAME} !

Webinaire gratuit :
"Comment digitaliser votre entreprise en 2026"

📅 Le [DATE] à [HEURE]
⏱️ Durée : 1h
🎁 Support PDF offert

💡 Programme :
• Tendances digitales 2026
• Outils indispensables
• Cas clients concrets
• Session Q&A live

👥 Places limitées à 50 participants !

✅ Répondez "JE PARTICIPE" pour réserver votre place

À bientôt ! 🚀

Webitech
🌐 webitech.fr
TEXT
        ],
    ];
}

/**
 * Obtenir les templates par catégorie
 */
function get_templates_by_category($category = null) {
    $templates = get_email_templates();
    
    if ($category === null) {
        return $templates;
    }
    
    return array_filter($templates, function($t) use ($category) {
        return $t['category'] === $category;
    });
}

/**
 * Obtenir les catégories disponibles
 */
function get_template_categories() {
    return [
        'lead_nurturing' => '🌱 Lead Nurturing',
        'sales' => '💰 Sales',
        'customer_success' => '⭐ Customer Success',
        'promotion' => '🎉 Promotion',
        'partnership' => '🤝 Partenariat',
        'tech_sales' => '💻 Tech & Software'
    ];
}

/**
 * Obtenir un template spécifique
 */
function get_template($template_id) {
    $templates = get_email_templates();
    return $templates[$template_id] ?? null;
}

/**
 * Générer la signature email avec les infos du user + footer Webitech
 */
function generate_email_signature($user_data = null) {
    // Récupérer les infos du user depuis la session si disponible
    $first_name = $_SESSION['first_name'] ?? 'Équipe';
    $last_name = $_SESSION['last_name'] ?? 'Webitech';
    $user_email = $_SESSION['email'] ?? 'contact@webitech.fr';
    $user_phone = $_SESSION['phone'] ?? '+33 7 59 81 00 51';
    
    // Override avec user_data si fourni
    if ($user_data) {
        $first_name = $user_data['first_name'] ?? $first_name;
        $last_name = $user_data['last_name'] ?? $last_name;
        $user_email = $user_data['email'] ?? $user_email;
        $user_phone = $user_data['phone'] ?? $user_phone;
    }
    
    return <<<HTML
<p>Cordialement,<br>
<strong>$first_name $last_name</strong></p>

<hr style="border: none; border-top: 2px solid #0066cc; margin: 20px 0;">

<table style="font-family: Arial, sans-serif; font-size: 13px; color: #333;">
    <tr>
        <td style="padding-right: 20px; vertical-align: top;">
            <img src="../logo.png" alt="Webitech" style="max-width: 150px; height: auto;">
        </td>
        <td style="vertical-align: top;">
            <p style="margin: 0 0 5px 0; font-weight: bold; color: #0066cc; font-size: 14px;">Webitech</p>
            <p style="margin: 0 0 3px 0;">Conseil en Transformation Digitale</p>
            <p style="margin: 10px 0 3px 0;">
                <strong>$first_name $last_name</strong>
            </p>
            <p style="margin: 0 0 3px 0;">
                📧 <a href="mailto:$user_email" style="color: #0066cc; text-decoration: none;">$user_email</a>
            </p>
            <p style="margin: 0 0 3px 0;">
                📱 <a href="tel:$user_phone" style="color: #0066cc; text-decoration: none;">$user_phone</a>
            </p>
            <p style="margin: 0 0 3px 0;">
                🌐 <a href="https://webitech.fr" style="color: #0066cc; text-decoration: none;">https://webitech.fr</a>
            </p>
            <p style="margin: 0 0 3px 0;">
                💼 <a href="https://www.linkedin.com/company/webitech" style="color: #0066cc; text-decoration: none;">LinkedIn</a>
            </p>
            <p style="margin: 10px 0 0 0; font-size: 11px; color: #666;">
                📞 Standard : <a href="tel:+33759810051" style="color: #666; text-decoration: none;">+33 7 59 81 00 51</a><br>
                ✉️ Contact : <a href="mailto:contact@webitech.fr" style="color: #666; text-decoration: none;">contact@webitech.fr</a>
            </p>
        </td>
    </tr>
</table>

<p style="font-size: 11px; color: #999; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
    Ce message et toutes les pièces jointes sont confidentiels et établis à l'intention exclusive de ses destinataires. 
    Si vous avez reçu ce message par erreur, merci d'en avertir immédiatement l'expéditeur et de le détruire.
</p>
HTML;
}

/**
 * Générer automatiquement le contenu avec personnalisation
 */
function generate_email_content($template_id, $lead_data = [], $user_data = null) {
    $template = get_template($template_id);
    
    if (!$template) {
        return false;
    }
    
    // Générer la signature
    $signature = generate_email_signature($user_data);
    
    // Variables de personnalisation
    $replacements = [
        '{FIRST_NAME}' => $lead_data['first_name'] ?? 'Client',
        '{LAST_NAME}' => $lead_data['last_name'] ?? '',
        '{EMAIL}' => $lead_data['email'] ?? '',
        '{COMPANY}' => $lead_data['company_name'] ?? 'votre entreprise',
        '{DATE}' => date('d/m/Y'),
        '{YEAR}' => date('Y'),
        '{SIGNATURE}' => $signature,
    ];
    
    return [
        'subject' => strtr($template['subject'], $replacements),
        'body' => strtr($template['body'], $replacements),
        'template_id' => $template_id
    ];
}

?>
