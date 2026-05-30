<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Vérification de l'authentification
if (!isAuthenticated()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer le customer_id
$stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$customer_id = $stmt->fetchColumn();

// Récupérer le customer
$customer = null;
if ($customer_id) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupérer les modules activés
$stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = 'enabled_modules'");
$stmt->execute([$user_id]);
$modulesJson = $stmt->fetchColumn();
$enabledModules = $modulesJson ? json_decode($modulesJson, true) : ['crm'];

// Récupérer les infos user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer la company si elle existe
$companyInfo = null;
if ($customer_id) {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE customer_id = ? LIMIT 1");
    $stmt->execute([$customer_id]);
    $companyInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - CRM Intelligent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --secondary: #764ba2;
            --success: #10b981;
        }

        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }

        .settings-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .settings-header p {
            opacity: 0.9;
            font-size: 1.1rem;
            margin: 0;
        }

        .settings-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .section-description {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #6b7280;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .module-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .module-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .module-card.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .module-card .checkmark {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .module-card.selected .checkmark {
            background: var(--primary);
            border-color: var(--primary);
        }

        .module-card.selected .checkmark i {
            color: white;
            font-size: 0.7rem;
        }

        .module-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .module-icon i {
            font-size: 1.3rem;
            color: white;
        }

        .module-name {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .module-desc {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .success-message {
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            color: #065f46;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .success-message.show {
            display: flex;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .settings-container {
                padding: 15px;
            }

            .settings-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/topbar.php'; ?>
    
    <div class="d-flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="content flex-grow-1">
            <div class="settings-container">
                <div class="settings-header">
                    <h1><i class="fas fa-cog"></i> Paramètres</h1>
                    <p>Gérez vos informations personnelles, votre entreprise et vos préférences</p>
                </div>

                <div id="successMessage" class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <span>Paramètres enregistrés avec succès !</span>
                </div>

                <!-- Section Profil -->
                <div class="settings-section">
                    <div class="section-title">
                        <i class="fas fa-user-circle"></i>
                        Profil Personnel
                    </div>
                    <p class="section-description">Vos informations personnelles et de contact</p>

                    <form id="profileForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($userInfo['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($userInfo['last_name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($customer['email'] ?? $userInfo['email'] ?? '') ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Poste/Fonction</label>
                                <input type="text" class="form-control" name="position" value="<?= htmlspecialchars($customer['position'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Adresse</label>
                            <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($customer['address'] ?? '') ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Ville</label>
                                <input type="text" class="form-control" name="city" value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Code Postal</label>
                                <input type="text" class="form-control" name="postal_code" value="<?= htmlspecialchars($customer['postal_code'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer le Profil
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Section Entreprise -->
                <div class="settings-section">
                    <div class="section-title">
                        <i class="fas fa-building"></i>
                        Entreprise
                    </div>
                    <p class="section-description">Informations sur votre organisation</p>

                    <form id="companyForm">
                        <div class="form-group">
                            <label class="form-label">Nom de l'entreprise</label>
                            <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($companyInfo['name'] ?? $customer['name'] ?? '') ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">SIRET/SIREN</label>
                                <input type="text" class="form-control" name="siret" value="<?= htmlspecialchars($companyInfo['siret'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">TVA Intracommunautaire</label>
                                <input type="text" class="form-control" name="vat_number" value="<?= htmlspecialchars($companyInfo['vat_number'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Site Web</label>
                            <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($companyInfo['website'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Secteur d'activité</label>
                            <select class="form-control" name="industry">
                                <option value="">Sélectionnez un secteur</option>
                                <option value="technology" <?= ($companyInfo['industry'] ?? '') === 'technology' ? 'selected' : '' ?>>Technologies</option>
                                <option value="finance" <?= ($companyInfo['industry'] ?? '') === 'finance' ? 'selected' : '' ?>>Finance</option>
                                <option value="healthcare" <?= ($companyInfo['industry'] ?? '') === 'healthcare' ? 'selected' : '' ?>>Santé</option>
                                <option value="retail" <?= ($companyInfo['industry'] ?? '') === 'retail' ? 'selected' : '' ?>>Commerce</option>
                                <option value="manufacturing" <?= ($companyInfo['industry'] ?? '') === 'manufacturing' ? 'selected' : '' ?>>Industrie</option>
                                <option value="services" <?= ($companyInfo['industry'] ?? '') === 'services' ? 'selected' : '' ?>>Services</option>
                                <option value="education" <?= ($companyInfo['industry'] ?? '') === 'education' ? 'selected' : '' ?>>Éducation</option>
                                <option value="other" <?= ($companyInfo['industry'] ?? '') === 'other' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nombre d'employés</label>
                                <select class="form-control" name="employee_count">
                                    <option value="">Sélectionnez</option>
                                    <option value="1-10" <?= ($companyInfo['employee_count'] ?? '') === '1-10' ? 'selected' : '' ?>>1-10</option>
                                    <option value="11-50" <?= ($companyInfo['employee_count'] ?? '') === '11-50' ? 'selected' : '' ?>>11-50</option>
                                    <option value="51-200" <?= ($companyInfo['employee_count'] ?? '') === '51-200' ? 'selected' : '' ?>>51-200</option>
                                    <option value="201-500" <?= ($companyInfo['employee_count'] ?? '') === '201-500' ? 'selected' : '' ?>>201-500</option>
                                    <option value="501+" <?= ($companyInfo['employee_count'] ?? '') === '501+' ? 'selected' : '' ?>>500+</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Chiffre d'affaires annuel</label>
                                <select class="form-control" name="annual_revenue">
                                    <option value="">Sélectionnez</option>
                                    <option value="0-100k" <?= ($companyInfo['annual_revenue'] ?? '') === '0-100k' ? 'selected' : '' ?>>0 - 100K €</option>
                                    <option value="100k-500k" <?= ($companyInfo['annual_revenue'] ?? '') === '100k-500k' ? 'selected' : '' ?>>100K - 500K €</option>
                                    <option value="500k-1m" <?= ($companyInfo['annual_revenue'] ?? '') === '500k-1m' ? 'selected' : '' ?>>500K - 1M €</option>
                                    <option value="1m-5m" <?= ($companyInfo['annual_revenue'] ?? '') === '1m-5m' ? 'selected' : '' ?>>1M - 5M €</option>
                                    <option value="5m+" <?= ($companyInfo['annual_revenue'] ?? '') === '5m+' ? 'selected' : '' ?>>5M+ €</option>
                                </select>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer l'Entreprise
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Section Modules -->
                <div class="settings-section">
                    <div class="section-title">
                        <i class="fas fa-puzzle-piece"></i>
                        Modules Activés
                    </div>
                    <p class="section-description">Choisissez les outils que vous souhaitez utiliser</p>

                    <div class="module-grid">
                        <div class="module-card <?= in_array('crm', $enabledModules) ? 'selected' : '' ?>" data-module="crm">
                            <div class="checkmark"><i class="fas fa-check"></i></div>
                            <div class="module-icon"><i class="fas fa-users"></i></div>
                            <div class="module-name">CRM</div>
                            <div class="module-desc">Gestion clients</div>
                        </div>

                        <div class="module-card <?= in_array('erp', $enabledModules) ? 'selected' : '' ?>" data-module="erp">
                            <div class="checkmark"></div>
                            <div class="module-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="module-name">ERP</div>
                            <div class="module-desc">Ressources</div>
                        </div>

                        <div class="module-card <?= in_array('projects', $enabledModules) ? 'selected' : '' ?>" data-module="projects">
                            <div class="checkmark"></div>
                            <div class="module-icon"><i class="fas fa-tasks"></i></div>
                            <div class="module-name">Projets</div>
                            <div class="module-desc">Gestion projets</div>
                        </div>

                        <div class="module-card <?= in_array('marketing', $enabledModules) ? 'selected' : '' ?>" data-module="marketing">
                            <div class="checkmark"></div>
                            <div class="module-icon"><i class="fas fa-bullhorn"></i></div>
                            <div class="module-name">Marketing</div>
                            <div class="module-desc">Campagnes</div>
                        </div>

                        <div class="module-card <?= in_array('support', $enabledModules) ? 'selected' : '' ?>" data-module="support">
                            <div class="checkmark"></div>
                            <div class="module-icon"><i class="fas fa-headset"></i></div>
                            <div class="module-name">Support</div>
                            <div class="module-desc">Service client</div>
                        </div>

                        <div class="module-card <?= in_array('analytics', $enabledModules) ? 'selected' : '' ?>" data-module="analytics">
                            <div class="checkmark"></div>
                            <div class="module-icon"><i class="fas fa-chart-pie"></i></div>
                            <div class="module-name">Analytics</div>
                            <div class="module-desc">Rapports IA</div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary" onclick="saveModules()">
                            <i class="fas fa-save"></i> Enregistrer les Modules
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedModules = <?= json_encode($enabledModules) ?>;

        // Module selection
        document.querySelectorAll('.module-card').forEach(card => {
            card.addEventListener('click', function() {
                const module = this.dataset.module;
                
                if (module === 'crm') {
                    return; // CRM toujours requis
                }
                
                this.classList.toggle('selected');
                
                if (this.classList.contains('selected')) {
                    if (!selectedModules.includes(module)) {
                        selectedModules.push(module);
                    }
                } else {
                    selectedModules = selectedModules.filter(m => m !== module);
                }
            });
        });

        // Save modules
        async function saveModules() {
            try {
                const response = await fetch('api/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_modules',
                        modules: selectedModules
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    showSuccess();
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Erreur lors de la sauvegarde');
            }
        }

        // Save profile
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('api/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_profile',
                        data: data
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    showSuccess();
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Erreur lors de la sauvegarde');
            }
        });

        // Save company
        document.getElementById('companyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('api/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_company',
                        data: data
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    showSuccess();
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Erreur lors de la sauvegarde');
            }
        });

        function showSuccess() {
            const msg = document.getElementById('successMessage');
            msg.classList.add('show');
            setTimeout(() => msg.classList.remove('show'), 3000);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>
