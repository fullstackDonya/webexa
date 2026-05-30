<?php

include __DIR__ . '/includes/companies.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ERP - Sociétés</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    /* Companies Page Styles */
    .companies-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .companies-header h1 {
      margin: 0;
      font-size: 1.875rem;
      font-weight: 700;
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .companies-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.5);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 30px rgba(59, 130, 246, 0.2);
    }

    .stat-card .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 1rem;
    }

    .stat-card.companies .stat-icon {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }

    .stat-card .stat-label {
      font-size: 0.875rem;
      color: #6b7280;
      margin-bottom: 0.5rem;
    }

    .stat-card .stat-value {
      font-size: 1.875rem;
      font-weight: 700;
      color: #111827;
    }

    /* Form Container */
    .form-container {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.5);
      margin-bottom: 2rem;
    }

    .form-container h2 {
      margin: 0 0 1.5rem 0;
      font-size: 1.25rem;
      font-weight: 700;
      color: #111827;
    }

    .form-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .form-field {
      display: flex;
      flex-direction: column;
    }

    .form-field label {
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.5rem;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .form-field input,
    .form-field textarea {
      padding: 0.875rem 1rem;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      font-size: 0.875rem;
      transition: all 0.3s;
      background: white;
    }

    .form-field input:focus,
    .form-field textarea:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-field textarea {
      resize: vertical;
      min-height: 120px;
      font-family: inherit;
    }

    .form-actions {
      display: flex;
      gap: 1rem;
      justify-content: flex-start;
      margin-top: 1.5rem;
    }

    /* Table Container */
    .table-container {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .table-header h2 {
      margin: 0;
      font-size: 1.25rem;
      font-weight: 700;
      color: #111827;
    }

    .search-box {
      position: relative;
      flex: 1;
      max-width: 400px;
    }

    .search-box input {
      width: 100%;
      padding: 0.75rem 1rem 0.75rem 2.75rem;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      font-size: 0.875rem;
      transition: all 0.3s;
    }

    .search-box input:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .search-box i {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af;
    }

    /* Modern Table */
    .modern-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }

    .modern-table thead {
      background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    }

    .modern-table thead th {
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      font-size: 0.875rem;
      color: #374151;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border-bottom: 2px solid #e5e7eb;
    }

    .modern-table thead th:first-child {
      border-radius: 12px 0 0 0;
    }

    .modern-table thead th:last-child {
      border-radius: 0 12px 0 0;
    }

    .modern-table tbody tr {
      transition: all 0.2s;
      border-bottom: 1px solid #f3f4f6;
    }

    .modern-table tbody tr:hover {
      background: linear-gradient(90deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));
      transform: scale(1.01);
    }

    .modern-table tbody td {
      padding: 1rem;
      font-size: 0.875rem;
      color: #1f2937;
    }

    .company-name {
      font-weight: 600;
      color: #3b82f6;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .info-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.25rem 0.75rem;
      background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
      color: #374151;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 500;
    }

    .action-buttons {
      display: flex;
      gap: 0.5rem;
    }

    .btn-icon {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.875rem;
    }

    .btn-icon.edit {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: #1e40af;
    }

    .btn-icon.edit:hover {
      background: linear-gradient(135deg, #bfdbfe, #93c5fd);
      transform: scale(1.1);
    }

    .btn-icon.delete {
      background: linear-gradient(135deg, #fef2f2, #fee2e2);
      color: #dc2626;
    }

    .btn-icon.delete:hover {
      background: linear-gradient(135deg, #fee2e2, #fecaca);
      transform: scale(1.1);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: #6b7280;
    }

    .empty-state i {
      font-size: 4rem;
      color: #d1d5db;
      margin-bottom: 1rem;
    }

    .empty-state h3 {
      font-size: 1.25rem;
      color: #374151;
      margin-bottom: 0.5rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .companies-stats {
        grid-template-columns: 1fr;
      }
      
      .form-row {
        grid-template-columns: 1fr;
      }
      
      .modern-table {
        font-size: 0.75rem;
      }
      
      .modern-table thead th,
      .modern-table tbody td {
        padding: 0.75rem 0.5rem;
      }
    }
  </style>
</head>
<body>
   <?php include 'erp_nav.php'; ?>

  <div class="container">
    <div class="companies-header">
      <h1><i class="fas fa-building"></i> Gestion des Sociétés</h1>
      <?php if (!$current): ?>
        <a href="companies.php?action=export" class="btn btn-ghost">
          <i class="fas fa-download"></i> Exporter CSV
        </a>
      <?php endif; ?>
    </div>

    <?php if (!$current): ?>
    <!-- Stats Cards -->
    <div class="companies-stats">
      <div class="stat-card companies">
        <div class="stat-icon">
          <i class="fas fa-building"></i>
        </div>
        <div class="stat-label">Nombre de sociétés</div>
        <div class="stat-value" id="companiesCount"><?= count($companies) ?></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="form-container">
      <h2><i class="fas fa-<?= $current ? 'edit' : 'plus-circle' ?>"></i> <?= $current ? 'Modifier' : 'Ajouter' ?> une société</h2>
      <form method="post">
        <?php if ($current): ?>
          <input type="hidden" name="id" value="<?= htmlspecialchars((string)$current['id']) ?>">
        <?php endif; ?>
        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-building"></i> Raison sociale *</label>
            <input name="name" required value="<?= htmlspecialchars($current['name'] ?? '') ?>" placeholder="Nom de la société">
          </div>
          <div class="form-field">
            <label><i class="fas fa-id-card"></i> SIRET</label>
            <input name="siret" value="<?= htmlspecialchars($current['siret'] ?? '') ?>" placeholder="123 456 789 00012">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-industry"></i> Code NAF</label>
            <input name="naf" value="<?= htmlspecialchars($current['naf'] ?? '') ?>" placeholder="6201Z">
          </div>
          <div class="form-field">
            <label><i class="fas fa-phone"></i> Téléphone</label>
            <input name="phone" value="<?= htmlspecialchars($current['phone'] ?? '') ?>" placeholder="+33 1 23 45 67 89">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-map-marker-alt"></i> Adresse</label>
            <input name="address_line1" value="<?= htmlspecialchars($current['address_line1'] ?? '') ?>" placeholder="123 rue de la République">
          </div>
          <div class="form-field">
            <label><i class="fas fa-map-marker-alt"></i> Complément d'adresse</label>
            <input name="address_line2" value="<?= htmlspecialchars($current['address_line2'] ?? '') ?>" placeholder="Bâtiment A, 2ème étage">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-envelope"></i> Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($current['email'] ?? '') ?>" placeholder="contact@entreprise.fr">
          </div>
        </div>

        <div class="form-field">
          <label><i class="fas fa-sticky-note"></i> Notes internes</label>
          <textarea name="notes" placeholder="Informations complémentaires..."><?= htmlspecialchars($current['notes'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Enregistrer
          </button>
          <?php if ($current): ?>
            <a href="companies.php" class="btn btn-ghost">
              <i class="fas fa-times"></i> Annuler
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <?php if (!$current): ?>
    <!-- Table -->
    <div class="table-container">
      <div class="table-header">
        <h2><i class="fas fa-list"></i> Liste des sociétés</h2>
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Rechercher une société...">
        </div>
      </div>
      
      <div class="table-wrapper">
        <?php if (count($companies) > 0): ?>
        <table class="modern-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>SIRET</th>
              <th>NAF</th>
              <th>Téléphone</th>
              <th>Email</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="companiesTableBody">
            <?php foreach ($companies as $c): ?>
              <tr>
                <td>
                  <div class="company-name">
                    <i class="fas fa-building"></i>
                    <?= htmlspecialchars($c['name']) ?>
                  </div>
                </td>
                <td>
                  <?php if (!empty($c['siret'])): ?>
                    <span class="info-badge">
                      <i class="fas fa-id-card"></i>
                      <?= htmlspecialchars($c['siret']) ?>
                    </span>
                  <?php else: ?>
                    <span style="color: #9ca3af;">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($c['naf'])): ?>
                    <span class="info-badge">
                      <i class="fas fa-industry"></i>
                      <?= htmlspecialchars($c['naf']) ?>
                    </span>
                  <?php else: ?>
                    <span style="color: #9ca3af;">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($c['phone'])): ?>
                    <a href="tel:<?= htmlspecialchars($c['phone']) ?>" style="color: #3b82f6; text-decoration: none;">
                      <i class="fas fa-phone"></i> <?= htmlspecialchars($c['phone']) ?>
                    </a>
                  <?php else: ?>
                    <span style="color: #9ca3af;">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($c['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($c['email']) ?>" style="color: #3b82f6; text-decoration: none;">
                      <i class="fas fa-envelope"></i> <?= htmlspecialchars($c['email']) ?>
                    </a>
                  <?php else: ?>
                    <span style="color: #9ca3af;">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action-buttons">
                    <a href="companies.php?action=edit&id=<?= (int)$c['id'] ?>" class="btn-icon edit" title="Modifier">
                      <i class="fas fa-edit"></i>
                    </a>
                    <form method="post" style="display:inline; margin: 0;" onsubmit="return confirm('Supprimer cette société ?')">
                      <input type="hidden" name="_method" value="DELETE">
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <button type="submit" class="btn-icon delete" title="Supprimer">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-building"></i>
          <h3>Aucune société enregistrée</h3>
          <p>Commencez par ajouter votre première société</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <script>
    // Search functionality
    document.getElementById('searchInput')?.addEventListener('input', function(e) {
      const searchTerm = e.target.value.toLowerCase();
      const rows = document.querySelectorAll('#companiesTableBody tr');
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
