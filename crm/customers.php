<?php
include 'includes/customers.php';

// Calculer les KPIs
$total_clients = count($customers);
$active_clients_count = count(array_filter($customers, function($c) { return $c['status'] === 'client'; }));
$prospect_count = count(array_filter($customers, function($c) { return $c['status'] === 'prospect'; }));
$total_revenue = array_sum(array_column($customers, 'annual_revenue'));
$avg_revenue_calculated = $total_clients > 0 ? $total_revenue / $total_clients : 0;
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
    <link href="assets/css/customers.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        <div class="main-content">
            <div class="container-fluid">

                <!-- En-tête -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-user-tie"></i> Gestion des Clients
                    </h1>
                    <div class="btn-group">
                        <a href="customers-export.php" class="btn btn-outline-primary">
                            <i class="fas fa-file-export"></i> Exporter
                        </a>
                        <a href="customers-add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveau Client
                        </a>
                    </div>
                </div>

                <!-- KPIs - Analytics -->
                <div class="kpi-container">
                    <div class="kpi-card info">
                        <div class="kpi-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="kpi-value" id="total-customers"><?php echo $total_clients; ?></div>
                        <p class="kpi-label">Total Clients</p>
                    </div>

                    <div class="kpi-card success">
                        <div class="kpi-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="kpi-value" id="active-customers"><?php echo $active_clients_count; ?></div>
                        <p class="kpi-label">Clients Actifs</p>
                    </div>

                    <div class="kpi-card warning">
                        <div class="kpi-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="kpi-value" id="prospect-count"><?php echo $prospect_count; ?></div>
                        <p class="kpi-label">Prospects</p>
                    </div>

                    <div class="kpi-card primary">
                        <div class="kpi-icon">
                            <i class="fas fa-euro-sign"></i>
                        </div>
                        <div class="kpi-value" id="avg-revenue">€<?php echo number_format($avg_revenue_calculated, 0); ?></div>
                        <p class="kpi-label">Revenus Moyens</p>
                    </div>
                </div>

                <!-- Filtres et Recherche -->
                <div class="filters-section">
                    <h6 class="filters-title">
                        <i class="fas fa-filter"></i> Filtres et Recherche
                    </h6>
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search-customer">Rechercher</label>
                            <input type="text" id="search-customer" class="form-control" placeholder="Nom, email, téléphone...">
                        </div>

                        <div class="filter-group">
                            <label for="filter-status">Statut</label>
                            <select id="filter-status" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="client">Client</option>
                                <option value="prospect">Prospect</option>
                                <option value="partner">Partenaire</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="filter-type">Type</label>
                            <select id="filter-type" class="form-select">
                                <option value="">Tous les types</option>
                                <?php
                                $type_options = array_unique(array_filter(array_map(function($c) { return $c['industry']; }, $customers)));
                                foreach ($type_options as $type):
                                ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button class="btn btn-primary" id="apply-filters">
                            <i class="fas fa-search"></i> Appliquer
                        </button>
                        <button class="btn btn-outline-primary" id="reset-filters">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- Tableau des Clients -->
                <div class="customers-table-container">
                    <div class="customers-table-header">
                        <h5>Liste des Clients</h5>
                        <span id="customers-count" class="badge bg-primary"><?php echo $total_clients; ?> clients</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="customersTable">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Type</th>
                                        <th>Revenus</th>
                                        <th>Statut</th>
                                        <th>Dernière activité</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="customers-list">
                                <?php foreach ($customers as $customer): ?>
                                    <tr data-customer-id="<?php echo intval($customer['id']); ?>">
                                        <td class="editable" data-field="name"><?php echo htmlspecialchars((string)($customer['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="editable" data-field="email"><?php echo htmlspecialchars((string)($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="editable" data-field="phone"><?php echo htmlspecialchars((string)($customer['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="readonly"><?php echo htmlspecialchars((string)($customer['industry'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="readonly">€<?php echo $customer['annual_revenue'] ? number_format($customer['annual_revenue'], 2, ',', ' ') : '0'; ?></td>
                                        <td class="readonly">
                                            <?php
                                            $badge_class = 'badge-secondary';
                                            switch ($customer['status']) {
                                                case 'client': $badge_class = 'badge-success'; break;
                                                case 'prospect': $badge_class = 'badge-info'; break;
                                                case 'partner': $badge_class = 'badge-primary'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars((string)($customer['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td class="readonly">
                                            <?php
                                            echo isset($customer['updated_at']) ? date('d/m/Y', strtotime($customer['updated_at'])) : '';
                                            ?>
                                        </td>
                                        <td class="readonly">
                                            <div class="actions-group">
                                                <a href="customers-view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info" title="Voir"><i class="fas fa-eye"></i></a>
                                                <a href="customers-edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-warning" title="Éditer"><i class="fas fa-edit"></i></a>
                                                <a href="customers-delete.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer ce client ?');"><i class="fas fa-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.3; display: block;"></i>
                                            <p>Aucun client trouvé.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicateurs d'édition -->
    <div class="editing-indicator" id="editingIndicator">
        <i class="fas fa-edit"></i> Éditez la cellule...
    </div>
    <div class="save-indicator" id="saveIndicator">
        <i class="fas fa-check"></i> Sauvegardé !
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Filtrer les clients
    function filterCustomers() {
        const searchText = document.getElementById('search-customer').value.toLowerCase();
        const filterStatus = document.getElementById('filter-status').value;
        const filterType = document.getElementById('filter-type').value;
        
        const rows = document.querySelectorAll('#customers-list tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            if (row.querySelector('td') === null) return;
            
            const name = row.cells[0]?.textContent.toLowerCase() || '';
            const email = row.cells[1]?.textContent.toLowerCase() || '';
            const type = row.cells[3]?.textContent || '';
            const status = row.cells[5]?.textContent.trim() || '';
            
            let matchSearch = !searchText || name.includes(searchText) || email.includes(searchText);
            let matchStatus = !filterStatus || status.includes(filterStatus);
            let matchType = !filterType || type.includes(filterType);
            
            if (matchSearch && matchStatus && matchType) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Mettre à jour le compteur
        const header = document.querySelector('.customers-table-header span');
        if (header) {
            header.textContent = visibleCount + ' client' + (visibleCount !== 1 ? 's' : '');
        }
    }

    // Réinitialiser les filtres
    function resetFilters() {
        document.getElementById('search-customer').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-type').value = '';
        filterCustomers();
    }

    // Ajouter les écouteurs d'événements
    document.getElementById('search-customer').addEventListener('keyup', filterCustomers);
    document.getElementById('filter-status').addEventListener('change', filterCustomers);
    document.getElementById('filter-type').addEventListener('change', filterCustomers);
    document.getElementById('apply-filters').addEventListener('click', filterCustomers);
    document.getElementById('reset-filters').addEventListener('click', resetFilters);

    // =============================================
    // ÉDITION INLINE - Clients
    // =============================================
    
    const editingIndicator = document.getElementById('editingIndicator');
    const saveIndicator = document.getElementById('saveIndicator');
    let editingCell = null;

    // Double-clic pour éditer
    document.querySelectorAll('tbody td.editable').forEach(cell => {
        cell.addEventListener('dblclick', function() {
            if (editingCell) return;
            
            editingCell = this;
            const currentValue = this.textContent.trim();
            const field = this.dataset.field;
            
            this.classList.add('editing');
            editingIndicator.classList.add('show');
            
            const input = document.createElement('input');
            input.type = field === 'email' ? 'email' : field === 'phone' ? 'tel' : 'text';
            input.value = currentValue;
            input.className = 'form-control';
            
            this.innerHTML = '';
            this.appendChild(input);
            input.focus();
            input.select();
            
            const saveEdit = async () => {
                const newValue = input.value.trim();
                const row = this.closest('tr');
                const customerId = row.dataset.customerId;
                
                editingIndicator.classList.remove('show');
                this.classList.remove('editing');
                
                if (newValue !== currentValue) {
                    try {
                        const formData = new FormData();
                        formData.append('customer_id', customerId);
                        formData.append('field', field);
                        formData.append('value', newValue);
                        
                        const response = await fetch('api/update-customer-inline.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            this.textContent = newValue;
                            saveIndicator.classList.add('show');
                            setTimeout(() => {
                                saveIndicator.classList.remove('show');
                            }, 2000);
                        } else {
                            alert('Erreur: ' + result.message);
                            this.textContent = currentValue;
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        alert('Erreur lors de la sauvegarde');
                        this.textContent = currentValue;
                    }
                } else {
                    this.textContent = currentValue;
                }
                
                editingCell = null;
            };
            
            input.addEventListener('blur', saveEdit);
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveEdit();
                }
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    editingIndicator.classList.remove('show');
                    this.classList.remove('editing');
                    this.textContent = currentValue;
                    editingCell = null;
                }
            });
        });
    });
    </script>