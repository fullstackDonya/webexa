<?php
include 'includes/verify_subscriptions.php';
require_once __DIR__ . '/includes/env.php';

$page_title = "Chat Assistant - CRM Webitech";
$customer_id = $_SESSION['customer_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">

    <style>
        .chat-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .chat-box {
            height: 600px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background: #f8f9fa;
            padding: 20px;
        }
        .message {
            margin-bottom: 20px;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message.user {
            text-align: right;
        }
        .message.assistant {
            text-align: left;
        }
        .message-bubble {
            display: inline-block;
            max-width: 70%;
            padding: 15px 20px;
            border-radius: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .message.user .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 5px;
        }
        .message.assistant .message-bubble {
            background: white;
            color: #333;
            border-bottom-left-radius: 5px;
        }
        .message-meta {
            font-size: 11px;
            color: #6c757d;
            margin-top: 5px;
        }
        .typing-indicator {
            display: none;
        }
        .typing-indicator.active {
            display: inline-block;
            background: white;
            padding: 15px 20px;
            border-radius: 20px;
        }
        .typing-indicator span {
            height: 10px;
            width: 10px;
            background: #999;
            border-radius: 50%;
            display: inline-block;
            margin: 0 2px;
            animation: typing 1.4s infinite;
        }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
        .quick-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .quick-question-btn {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .quick-question-btn:hover {
            background: #667eea;
            color: white;
        }
        .chat-input-container {
            position: relative;
        }
        .chat-input {
            border-radius: 25px;
            padding-right: 60px;
        }
        .send-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 50%;
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .send-btn:hover {
            opacity: 0.9;
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
        }
        .knowledge-tag {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="wrapper">

        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">
                <div class="chat-container">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>
                            <i class="fas fa-robot text-primary"></i> Chat Assistant IA
                        </h1>
                        <div>
                            <button class="btn btn-outline-secondary btn-sm" onclick="clearChat()">
                                <i class="fas fa-trash"></i> Effacer
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="exportChat()">
                                <i class="fas fa-download"></i> Exporter
                            </button>
                        </div>
                    </div>

                    <!-- Welcome Card -->
                    <div class="welcome-card">
                        <h3><i class="fas fa-comments"></i> Bonjour ! Comment puis-je vous aider ?</h3>
                        <p class="mb-3">Je suis votre assistant virtuel CRM. Je peux répondre à toutes vos questions sur :</p>
                        <div>
                            <span class="knowledge-tag"><i class="fas fa-users"></i> Contacts & Leads</span>
                            <span class="knowledge-tag"><i class="fas fa-envelope"></i> Email OAuth</span>
                            <span class="knowledge-tag"><i class="fab fa-whatsapp"></i> WhatsApp Business</span>
                            <span class="knowledge-tag"><i class="fas fa-bullhorn"></i> Campagnes</span>
                            <span class="knowledge-tag"><i class="fas fa-robot"></i> Automations</span>
                            <span class="knowledge-tag"><i class="fas fa-chart-bar"></i> Analytics</span>
                            <span class="knowledge-tag"><i class="fas fa-cog"></i> Configuration</span>
                            <span class="knowledge-tag"><i class="fas fa-question-circle"></i> Support Technique</span>
                        </div>
                    </div>

                    <!-- Quick Questions -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-bolt"></i> Questions Rapides
                            </h6>
                            <div class="quick-questions">
                                <button class="quick-question-btn" onclick="askQuestion('Comment connecter mon Gmail au CRM ?')">
                                    📧 Connecter Gmail
                                </button>
                                <button class="quick-question-btn" onclick="askQuestion('Comment créer une campagne WhatsApp ?')">
                                    💬 Campagne WhatsApp
                                </button>
                                <button class="quick-question-btn" onclick="askQuestion('Comment importer mes contacts ?')">
                                    📥 Importer contacts
                                </button>
                                <button class="quick-question-btn" onclick="askQuestion('Quelle est la différence entre un lead et un contact ?')">
                                    🎯 Lead vs Contact
                                </button>
                                <button class="quick-question-btn" onclick="askQuestion('Comment créer une automation ?')">
                                    🤖 Créer automation
                                </button>
                                <button class="quick-question-btn" onclick="askQuestion('Comment voir mes statistiques de ventes ?')">
                                    📊 Stats ventes
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Box -->
                    <div class="chat-box" id="chatBox">
                        <!-- Messages seront ajoutés ici dynamiquement -->
                    </div>

                    <!-- Typing Indicator -->
                    <div class="typing-indicator" id="typingIndicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                    <!-- Input -->
                    <div class="card mt-3">
                        <div class="card-body">
                            <form id="chatForm" onsubmit="sendMessage(event)">
                                <div class="chat-input-container">
                                    <input 
                                        type="text" 
                                        class="form-control chat-input" 
                                        id="messageInput"
                                        placeholder="Posez votre question sur le CRM..."
                                        autocomplete="off"
                                        required
                                    >
                                    <button type="submit" class="send-btn">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                            <small class="text-muted">
                                <i class="fas fa-lightbulb"></i> Astuce : Soyez précis dans vos questions pour obtenir les meilleures réponses.
                            </small>
                        </div>
                    </div>

                    <!-- Links -->
                    <div class="text-center mt-4">
                        <a href="documentation-commerciaux.php" class="btn btn-outline-primary btn-sm me-2">
                            <i class="fas fa-book"></i> Documentation Commerciaux
                        </a>
                        <a href="documentation-clients.php" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-book-open"></i> Documentation Clients
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    let chatHistory = [];

    // Charger l'historique depuis localStorage
    function loadChatHistory() {
        const saved = localStorage.getItem('chatHistory');
        if (saved) {
            chatHistory = JSON.parse(saved);
            chatHistory.forEach(msg => {
                displayMessage(msg.text, msg.type, false);
            });
        }
    }

    // Sauvegarder l'historique
    function saveChatHistory() {
        localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
    }

    // Afficher un message
    function displayMessage(text, type, save = true) {
        const chatBox = document.getElementById('chatBox');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        
        const now = new Date();
        const time = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        
        messageDiv.innerHTML = `
            <div class="message-bubble">
                ${text}
            </div>
            <div class="message-meta">${time}</div>
        `;
        
        chatBox.appendChild(messageDiv);
        chatBox.scrollTop = chatBox.scrollHeight;

        if (save) {
            chatHistory.push({ text, type, time });
            saveChatHistory();
        }
    }

    // Envoyer un message
    async function sendMessage(event) {
        event.preventDefault();
        
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message) return;

        // Afficher le message utilisateur
        displayMessage(message, 'user');
        input.value = '';

        // Afficher l'indicateur de saisie
        const typingIndicator = document.getElementById('typingIndicator');
        typingIndicator.classList.add('active');

        try {
            // Appeler l'API
            const response = await fetch('api/chat-assistant.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    message: message,
                    history: chatHistory.slice(-10) // 10 derniers messages pour contexte
                })
            });

            const data = await response.json();
            
            // Masquer l'indicateur
            typingIndicator.classList.remove('active');

            if (data.success) {
                // Afficher la réponse de l'assistant
                displayMessage(data.response, 'assistant');
            } else {
                displayMessage('❌ Erreur : ' + (data.message || 'Impossible de traiter votre demande'), 'assistant');
            }
        } catch (error) {
            console.error('Error:', error);
            typingIndicator.classList.remove('active');
            displayMessage('❌ Erreur de connexion. Veuillez réessayer.', 'assistant');
        }
    }

    // Question rapide
    function askQuestion(question) {
        document.getElementById('messageInput').value = question;
        document.getElementById('chatForm').dispatchEvent(new Event('submit'));
    }

    // Effacer le chat
    function clearChat() {
        if (confirm('⚠️ Effacer tout l\'historique du chat ?')) {
            chatHistory = [];
            localStorage.removeItem('chatHistory');
            document.getElementById('chatBox').innerHTML = '';
        }
    }

    // Exporter le chat
    function exportChat() {
        if (chatHistory.length === 0) {
            alert('Aucun message à exporter.');
            return;
        }

        let text = '=== Historique Chat CRM Webitech ===\n\n';
        chatHistory.forEach(msg => {
            const label = msg.type === 'user' ? 'Vous' : 'Assistant';
            text += `[${msg.time}] ${label}: ${msg.text}\n\n`;
        });

        const blob = new Blob([text], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `chat-crm-${new Date().toISOString().split('T')[0]}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    }

    // Au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        loadChatHistory();
        
        // Focus sur l'input
        document.getElementById('messageInput').focus();
    });
    </script>
</body>
</html>
