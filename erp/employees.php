<?php

include __DIR__ . '/includes/employees.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ERP - Employés</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    .employees-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .employees-header h1 {
      margin: 0;
      font-size: 1.875rem;
      font-weight: 700;
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .employees-stats {
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
    .stat-card.total .stat-icon {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }
    .stat-card.active .stat-icon {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }
    .stat-card.salary .stat-icon {
      background: linear-gradient(135deg, #f59e0b, #d97706);
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
    .form-field select {
      padding: 0.875rem 1rem;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      font-size: 0.875rem;
      transition: all 0.3s;
      background: white;
    }
    .form-field input:focus,
    .form-field select:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .form-actions {
      display: flex;
      gap: 1rem;
      margin-top: 1.5rem;
    }
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
    .employee-name {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-weight: 600;
      color: #3b82f6;
    }
    .employee-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.875rem;
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .status-badge.active {
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      color: #065f46;
    }
    .status-badge.inactive {
      background: linear-gradient(135deg, #fee2e2, #fecaca);
      color: #991b1b;
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
    .pagination {
      display: flex;
      gap: 0.5rem;
      justify-content: center;
      margin-top: 1.5rem;
    }
    .pagination .page-link {
      padding: 0.5rem 1rem;
      border-radius: 8px;
      border: 1px solid #e5e7eb;
      background: white;
      color: #374151;
      text-decoration: none;
      transition: all 0.2s;
    }
    .pagination .page-link:hover {
      background: #f3f4f6;
      border-color: #3b82f6;
    }
    .pagination .page-link.active {
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      color: white;
      border-color: transparent;
    }
  </style>
</head>
<body>
   <?php include 'erp_nav.php'; ?>

  <div class="container">
    <div class="employees-header">
      <h1><i class="fas fa-users"></i> Gestion des Employés</h1>
      <div style="display: flex; gap: 0.5rem;">
        <?php if (!$current): ?>
          <a class="btn btn-ghost" href="employees.php?action=export<?= $q ? '&q=' . urlencode($q) : '' ?>">
            <i class="fas fa-download"></i> Exporter
          </a>
        <?php endif; ?>
        <a class="btn btn-primary" href="employees.php?action=add">
          <i class="fas fa-user-plus"></i> Nouvel Employé
        </a>
      </div>
    </div>

    <?php if (!$current): ?>
    <div class="employees-stats">
      <div class="stat-card total">
        <div class="stat-icon">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-label">Nombre d'employés</div>
        <div class="stat-value" id="totalEmployees"><?= count($employees) ?></div>
      </div>
      <div class="stat-card active">
        <div class="stat-icon">
          <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-label">Employés actifs</div>
        <div class="stat-value" id="activeEmployees">
          <?= count(array_filter($employees, fn($e) => ($e['status'] ?? 'active') === 'active')) ?>
        </div>
      </div>
      <div class="stat-card salary">
        <div class="stat-icon">
          <i class="fas fa-euro-sign"></i>
        </div>
        <div class="stat-label">Masse salariale mensuelle</div>
        <div class="stat-value" id="totalSalary">
          <?= number_format(array_sum(array_map(fn($e) => (float)($e['base_salary'] ?? 0), $employees)), 0, ',', ' ') ?> €
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="form-container">
      <h2><i class="fas fa-<?= $current ? 'edit' : 'user-plus' ?>"></i> <?= $current ? 'Modifier' : 'Ajouter' ?> un employé</h2>
      <form method="post">
        <?php if ($current): ?>
          <input type="hidden" name="id" value="<?= htmlspecialchars((string)$current['id']) ?>">
        <?php endif; ?>
        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-user"></i> Prénom *</label>
            <input name="first_name" required value="<?= htmlspecialchars($current['first_name'] ?? '') ?>" placeholder="Jean">
          </div>
          <div class="form-field">
            <label><i class="fas fa-user"></i> Nom *</label>
            <input name="last_name" required value="<?= htmlspecialchars($current['last_name'] ?? '') ?>" placeholder="Dupont">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-envelope"></i> Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($current['email'] ?? '') ?>" placeholder="jean.dupont@entreprise.fr">
          </div>
          <div class="form-field">
            <label><i class="fas fa-calendar"></i> Date d'embauche</label>
            <input type="date" name="hire_date" value="<?= htmlspecialchars($current['hire_date'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-euro-sign"></i> Salaire de base (mensuel brut)</label>
            <input type="number" step="0.01" name="base_salary" value="<?= htmlspecialchars($current['base_salary'] ?? '') ?>" placeholder="2500.00">
          </div>
          <div class="form-field">
            <label><i class="fas fa-briefcase"></i> Poste</label>
            <input name="job_title" value="<?= htmlspecialchars($current['job_title'] ?? '') ?>" placeholder="Développeur">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-building"></i> Département</label>
            <input name="department" value="<?= htmlspecialchars($current['department'] ?? '') ?>" placeholder="IT">
          </div>
          <div class="form-field">
            <label><i class="fas fa-file-contract"></i> Type de contrat</label>
            <select name="contract_type">
              <?php foreach (['CDI','CDD','Freelance','Stage','Alternance'] as $ct): ?>
                <option value="<?= $ct ?>" <?= (($current['contract_type'] ?? '') === $ct) ? 'selected' : '' ?>><?= $ct ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-field">
          <label><i class="fas fa-toggle-on"></i> Statut</label>
          <select name="status">
            <?php foreach (['active' => 'Actif', 'inactive' => 'Inactif'] as $val => $label): ?>
              <option value="<?= $val ?>" <?= (($current['status'] ?? 'active') === $val) ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-actions">
          <button class="btn btn-primary" type="submit">
            <i class="fas fa-save"></i> Enregistrer
          </button>
          <?php if ($current): ?>
            <a class="btn btn-ghost" href="employees.php">
              <i class="fas fa-times"></i> Annuler
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <?php if (!$current): ?>
    <div class="table-container">
      <div class="table-header">
        <h2><i class="fas fa-list"></i> Liste des employés</h2>
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Rechercher un employé..." value="<?= htmlspecialchars($q) ?>">
        </div>
      </div>
      
      <?php if (count($employees) > 0): ?>
      <table class="modern-table">
        <thead>
          <tr>
            <th>Employé</th>
            <th>Email</th>
            <th>Date d'embauche</th>
            <th>Salaire</th>
            <th>Poste</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="employeesTableBody">
          <?php foreach ($employees as $e): ?>
            <tr>
              <td>
                <div class="employee-name">
                  <div class="employee-avatar">
                    <?= strtoupper(substr($e['first_name'], 0, 1) . substr($e['last_name'], 0, 1)) ?>
                  </div>
                  <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($e['last_name'] . ' ' . $e['first_name']) ?></div>
                    <div style="font-size: 0.75rem; color: #6b7280;"><?= htmlspecialchars($e['department'] ?? 'N/A') ?></div>
                  </div>
                </div>
              </td>
              <td>
                <?php if (!empty($e['email'])): ?>
                  <a href="mailto:<?= htmlspecialchars($e['email']) ?>" style="color: #3b82f6; text-decoration: none;">
                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($e['email']) ?>
                  </a>
                <?php else: ?>
                  <span style="color: #9ca3af;">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($e['hire_date'])): ?>
                  <i class="far fa-calendar"></i> <?= date('d/m/Y', strtotime($e['hire_date'])) ?>
                <?php else: ?>
                  <span style="color: #9ca3af;">—</span>
                <?php endif; ?>
              </td>
              <td style="font-weight: 700; color: #059669;">
                <?= number_format((float)$e['base_salary'], 0, ',', ' ') ?> €
              </td>
              <td>
                <span style="color: #6b7280;">
                  <i class="fas fa-briefcase"></i> <?= htmlspecialchars($e['job_title'] ?? 'N/A') ?>
                </span>
              </td>
              <td>
                <span class="status-badge <?= ($e['status'] ?? 'active') === 'active' ? 'active' : 'inactive' ?>">
                  <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                  <?= ($e['status'] ?? 'active') === 'active' ? 'Actif' : 'Inactif' ?>
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <a href="employees.php?action=edit&id=<?= (int)$e['id'] ?>" class="btn-icon edit" title="Modifier">
                    <i class="fas fa-edit"></i>
                  </a>
                  <form method="post" style="display:inline; margin: 0;" onsubmit="return confirm('Supprimer cet employé ?')">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
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
        <i class="fas fa-users"></i>
        <h3>Aucun employé trouvé</h3>
        <p>Commencez par ajouter votre premier employé</p>
      </div>
      <?php endif; ?>

      <?php if (!empty($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a class="page-link <?= $p === $page ? 'active' : '' ?>" href="employees.php?page=<?= $p ?>&q=<?= urlencode($q) ?>"><?= $p ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <script>
    document.getElementById('searchInput')?.addEventListener('input', function(e) {
      const searchTerm = e.target.value.toLowerCase();
      const rows = document.querySelectorAll('#employeesTableBody tr');
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
