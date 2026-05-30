<?php

include __DIR__ . '/includes/reports.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ERP - Rapports</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    .reports-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .reports-header h1 {
      margin: 0;
      font-size: 1.875rem;
      font-weight: 700;
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .filter-container {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.5);
      margin-bottom: 2rem;
    }
    .filter-form {
      display: flex;
      gap: 1rem;
      align-items: flex-end;
      flex-wrap: wrap;
    }
    .filter-field {
      flex: 1;
      min-width: 200px;
      display: flex;
      flex-direction: column;
    }
    .filter-field label {
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.5rem;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .filter-field input {
      padding: 0.875rem 1rem;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      font-size: 0.875rem;
      transition: all 0.3s;
    }
    .filter-field input:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
    .stat-card.payrolls .stat-icon {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }
    .stat-card.cost .stat-icon {
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
    .table-container {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.5);
      margin-bottom: 2rem;
    }
    .table-container h2 {
      margin: 0 0 1.5rem 0;
      font-size: 1.25rem;
      font-weight: 700;
      color: #111827;
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
    .modern-table tbody td.right {
      text-align: right;
      font-weight: 600;
    }
    .period-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.25rem 0.75rem;
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: #1e40af;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
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
    .actions-container {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.5);
    }
    .actions-container h2 {
      margin: 0 0 1rem 0;
      font-size: 1.25rem;
      font-weight: 700;
      color: #111827;
    }
    .actions-container p {
      color: #6b7280;
      margin-bottom: 1rem;
      font-size: 0.875rem;
    }
    .actions-grid {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }
  </style>
</head>
<body>
    <?php include 'erp_nav.php'; ?>
    
  <div class="container">
    <div class="reports-header">
      <h1>
        <i class="fas fa-chart-bar"></i> Rapports 
        <?php if ($customer_id): ?>
          <span style="font-size: 0.875rem; color: #6b7280; font-weight: 400;">Client #<?= $customer_id ?></span>
        <?php endif; ?>
      </h1>
    </div>

    <!-- Filtres -->
    <div class="filter-container">
      <form method="get" class="filter-form">
        <input type="hidden" name="action" value="list">
        <div class="filter-field">
          <label><i class="fas fa-calendar"></i> Période</label>
          <input name="period" placeholder="YYYY-MM" value="<?= htmlspecialchars($period) ?>" pattern="\d{4}-\d{2}">
        </div>
        <button class="btn btn-primary" type="submit">
          <i class="fas fa-filter"></i> Filtrer
        </button>
        <div style="margin-left: auto; display: flex; gap: 0.5rem;">
          <a class="btn btn-ghost" href="reports.php?action=export_payrolls<?= $period ? '&period=' . urlencode($period) : '' ?>">
            <i class="fas fa-file-csv"></i> Export Fiches
          </a>
          <a class="btn btn-ghost" href="reports.php?action=export_employees">
            <i class="fas fa-file-csv"></i> Export Employés
          </a>
        </div>
      </form>
    </div>

    <!-- Statistiques -->
    <div class="stats-container">
      <div class="stat-card payrolls">
        <div class="stat-icon">
          <i class="fas fa-file-invoice"></i>
        </div>
        <div class="stat-label">Nombre de fiches</div>
        <div class="stat-value"><?= (int)($summary['cnt'] ?? 0) ?></div>
      </div>
      <div class="stat-card cost">
        <div class="stat-icon">
          <i class="fas fa-euro-sign"></i>
        </div>
        <div class="stat-label">Coût total (net + charges)</div>
        <div class="stat-value"><?= number_format((float)($summary['total_cost'] ?? 0), 0, ',', ' ') ?> €</div>
      </div>
      <?php if ($period): ?>
      <div class="stat-card" style="display: flex; align-items: center; justify-content: center;">
        <div>
          <div class="stat-label" style="text-align: center;">Période affichée</div>
          <div style="text-align: center; margin-top: 0.5rem;">
            <span class="period-badge" style="font-size: 1rem; padding: 0.5rem 1rem;">
              <i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($period) ?>
            </span>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Tableau des fiches de paie -->
    <div class="table-container">
      <h2><i class="fas fa-list"></i> Fiches de paie</h2>
      <?php if (empty($payrolls)): ?>
        <div class="empty-state">
          <i class="fas fa-file-invoice-dollar"></i>
          <h3>Aucune fiche trouvée</h3>
          <p>Aucune fiche de paie pour la période sélectionnée</p>
        </div>
      <?php else: ?>
        <table class="modern-table">
          <thead>
            <tr>
              <th>Employé</th>
              <th>Période</th>
              <th>Brut</th>
              <th>Charges salarié</th>
              <th>Charges employeur</th>
              <th>Net</th>
              <th>Créé le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payrolls as $p): ?>
              <tr>
                <td>
                  <div style="font-weight: 600; color: #3b82f6;">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($p['last_name'] . ' ' . $p['first_name']) ?>
                  </div>
                </td>
                <td>
                  <span class="period-badge">
                    <i class="fas fa-calendar"></i> <?= htmlspecialchars($p['period']) ?>
                  </span>
                </td>
                <td class="right"><?= number_format((float)$p['gross_salary'],2,',',' ') ?> €</td>
                <td class="right" style="color: #dc2626;"><?= number_format((float)$p['employee_contrib'],2,',',' ') ?> €</td>
                <td class="right" style="color: #f59e0b;"><?= number_format((float)$p['employer_contrib'],2,',',' ') ?> €</td>
                <td class="right" style="color: #059669; font-weight: 700; font-size: 1rem;">
                  <?= number_format((float)$p['net_pay'],2,',',' ') ?> €
                </td>
                <td>
                  <i class="far fa-clock"></i> <?= date('d/m/Y H:i', strtotime($p['created_at'] ?? 'now')) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- Actions rapides -->
    <div class="actions-container">
      <h2><i class="fas fa-bolt"></i> Export rapide / Utilitaires</h2>
      <p>Téléchargez les CSV des employés ou des fiches de paie pour archivage ou import dans un tableur.</p>
      <div class="actions-grid">
        <a class="btn btn-primary" href="reports.php?action=export_payrolls<?= $period ? '&period=' . urlencode($period) : '' ?>">
          <i class="fas fa-file-csv"></i> Exporter fiches (CSV)
        </a>
        <a class="btn btn-ghost" href="reports.php?action=export_employees">
          <i class="fas fa-users"></i> Exporter employés (CSV)
        </a>
      </div>
    </div>
  </div>
</body>
</html>
