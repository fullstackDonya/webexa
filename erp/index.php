<?php

include __DIR__ . '/includes/dashboard.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ERP - Tableau de bord</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    .stats-grid{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
      gap:24px;
      margin:32px 0;
    }
    .stat-card{
      background:rgba(255,255,255,0.85);
      backdrop-filter:blur(20px);
      border:1px solid rgba(255,255,255,0.3);
      padding:28px;
      border-radius:20px;
      box-shadow:0 10px 40px rgba(16,24,40,0.08);
      transition:all 0.4s cubic-bezier(0.4,0,0.2,1);
      position:relative;
      overflow:hidden;
    }
    .stat-card::before{
      content:'';
      position:absolute;
      top:0;left:0;right:0;
      height:5px;
      background:var(--accent-gradient);
      transform:scaleX(0);
      transition:transform 0.4s;
    }
    .stat-card:hover{
      transform:translateY(-8px) scale(1.02);
      box-shadow:0 20px 60px rgba(16,24,40,0.15);
    }
    .stat-card:hover::before{transform:scaleX(1)}
    .stat-icon{
      width:56px;
      height:56px;
      border-radius:14px;
      background:var(--accent-gradient);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:28px;
      color:#fff;
      margin-bottom:20px;
      box-shadow:0 8px 20px rgba(59,130,246,0.3);
    }
    .stat-value{
      font-size:40px;
      font-weight:800;
      background:var(--accent-gradient);
      -webkit-background-clip:text;
      -webkit-text-fill-color:transparent;
      margin:12px 0;
    }
    .stat-label{
      font-size:14px;
      color:#64748b;
      font-weight:600;
      text-transform:uppercase;
      letter-spacing:0.5px;
    }
    .stat-detail{
      color:#94a3b8;
      font-size:13px;
      margin-top:12px;
      padding-top:12px;
      border-top:1px solid #e2e8f0;
    }
    .section-header{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin:32px 0 20px;
    }
    .section-header h2{
      font-size:24px;
      font-weight:800;
      color:#0f172a;
      display:flex;
      align-items:center;
      gap:12px;
    }
    .quick-actions{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
      gap:16px;
      margin:24px 0;
    }
    .quick-action{
      background:rgba(255,255,255,0.9);
      backdrop-filter:blur(20px);
      padding:20px;
      border-radius:16px;
      text-align:center;
      transition:all 0.3s;
      border:2px solid transparent;
      cursor:pointer;
    }
    .quick-action:hover{
      border-color:#3b82f6;
      transform:translateY(-4px);
      box-shadow:0 12px 30px rgba(59,130,246,0.2);
    }
    .quick-action i{
      font-size:32px;
      color:#3b82f6;
      margin-bottom:12px;
    }
    .quick-action span{
      display:block;
      font-weight:600;
      color:#0f172a;
    }
  </style>
</head>
<body>
  
  <?php include 'erp_nav.php'; ?>
<main class="main-content fade-in">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px">
    <div>
      <h1 style="margin-bottom:8px"><i class="fas fa-chart-line"></i> Tableau de bord ERP</h1>
      <p style="color:#64748b;font-size:14px">Vue d'ensemble de votre entreprise en temps réel</p>
    </div>
    <div style="display:flex;gap:12px">
      <button class="btn btn-ghost" onclick="location.reload()">
        <i class="fas fa-sync"></i> Actualiser
      </button>
      <button class="btn btn-primary" onclick="window.location.href='../crm/index.php'">
        <i class="fas fa-link"></i> Accès CRM
      </button>
    </div>
  </div>

  <!-- KPIs Modernes -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-users"></i></div>
      <div class="stat-label">Total Employés</div>
      <div class="stat-value"><?= htmlspecialchars((string)$totalEmployees) ?></div>
      <div class="stat-detail">
        <i class="fas fa-check-circle" style="color:#10b981"></i> 
        <?= htmlspecialchars((string)$activeEmployees) ?> actifs
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%)">
        <i class="fas fa-euro-sign"></i>
      </div>
      <div class="stat-label">Salaire Moyen</div>
      <div class="stat-value" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        <?= number_format((float)$avgSalary, 0, ',', ' ') ?>€
      </div>
      <div class="stat-detail">Période: <?= htmlspecialchars($currentPeriod) ?></div>
    </div>

    <div class="stat-card">
      <div class="stat-icon" style="background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%)">
        <i class="fas fa-file-invoice-dollar"></i>
      </div>
      <div class="stat-label">Fiches de Paie</div>
      <div class="stat-value" style="background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        <?= htmlspecialchars((string)$payrollsThisMonth) ?>
      </div>
      <div class="stat-detail">
        Coût total: <?= number_format((float)$totalPayrollCost, 0, ',', ' ') ?>€
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon" style="background:linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)">
        <i class="fas fa-rocket"></i>
      </div>
      <div class="stat-label">Actions Rapides</div>
      <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px">
        <a href="missions.php" class="btn btn-ghost" style="font-size:13px;padding:8px 12px">
          <i class="fas fa-tasks"></i> Missions
        </a>
        <a href="employees.php" class="btn btn-ghost" style="font-size:13px;padding:8px 12px">
          <i class="fas fa-user-plus"></i> Personnel
        </a>
      </div>
    </div>
  </div>

  <!-- Actions Rapides -->
  <div class="section-header">
    <h2><i class="fas fa-bolt"></i> Actions Rapides</h2>
  </div>
  <div class="quick-actions">
    <a href="employees.php" class="quick-action">
      <i class="fas fa-user-plus"></i>
      <span>Ajouter Employé</span>
    </a>
    <a href="missions.php?action=add" class="quick-action">
      <i class="fas fa-tasks"></i>
      <span>Nouvelle Mission</span>
    </a>
    <a href="payroll.php" class="quick-action">
      <i class="fas fa-file-invoice"></i>
      <span>Fiche de Paie</span>
    </a>
    <a href="reports.php" class="quick-action">
      <i class="fas fa-chart-bar"></i>
      <span>Rapports</span>
    </a>
  </div>

  <!-- Sections de données -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:24px;margin-top:32px">
    <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:24px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08)">
      <div class="section-header" style="margin:0 0 20px">
        <h2 style="font-size:18px"><i class="fas fa-user-check"></i> Nouvelles Embauches</h2>
      </div>
      <?php if (count($recentHires) === 0): ?>
        <p style="text-align:center;color:#94a3b8;padding:20px">
          <i class="fas fa-inbox" style="font-size:40px;opacity:0.3;margin-bottom:12px"></i><br>
          Aucune embauche récente
        </p>
      <?php else: ?>
        <table class="table-compact">
          <thead><tr><th>Employé</th><th>Poste</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($recentHires as $r): ?>
              <tr>
                <td><strong><?= htmlspecialchars($r['last_name'] . ' ' . $r['first_name']) ?></strong></td>
                <td><?= htmlspecialchars($r['job_title']) ?></td>
                <td><?= date('d/m/Y', strtotime($r['hire_date'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:24px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08)">
      <div class="section-header" style="margin:0 0 20px">
        <h2 style="font-size:18px"><i class="fas fa-money-check-alt"></i> Dernières Fiches</h2>
      </div>
      <?php if (count($recentPayrolls) === 0): ?>
        <p style="text-align:center;color:#94a3b8;padding:20px">
          <i class="fas fa-file-invoice" style="font-size:40px;opacity:0.3;margin-bottom:12px"></i><br>
          Aucune fiche générée
        </p>
      <?php else: ?>
        <table class="table-compact">
          <thead><tr><th>Employé</th><th>Période</th><th>Net</th></tr></thead>
          <tbody>
            <?php foreach ($recentPayrolls as $p): ?>
              <tr>
                <td><strong><?= htmlspecialchars($p['last_name'] . ' ' . $p['first_name']) ?></strong></td>
                <td><?= htmlspecialchars($p['period']) ?></td>
                <td style="color:#10b981;font-weight:700"><?= number_format((float)$p['net_pay'], 2, ',', ' ') ?>€</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Auto-génération paies -->
  <div style="background:linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(139,92,246,0.1) 100%);padding:24px;border-radius:16px;margin:32px 0;border:2px solid rgba(59,130,246,0.2)">
    <h3 style="margin:0 0 12px;display:flex;align-items:center;gap:12px">
      <i class="fas fa-magic" style="color:#3b82f6"></i>
      Génération Automatique
    </h3>
    <p style="color:#64748b;margin-bottom:16px">Générer automatiquement les fiches de paie pour tous les employés actifs de la période en cours</p>
    <form method="post" onsubmit="return confirm('Générer automatiquement les fiches de paie pour la période courante ?')" style="margin:0">
      <input type="hidden" name="action" value="auto_generate">
      <button class="btn btn-primary" type="submit">
        <i class="fas fa-cogs"></i> Générer les paies automatiques
      </button>
    </form>
  </div>

  <footer style="margin-top:40px;padding-top:24px;border-top:2px solid #e2e8f0;color:#94a3b8;font-size:13px;text-align:center">
    <p><i class="fas fa-shield-alt"></i> ERP Webitech - Système de gestion d'entreprise moderne et synchronisé avec CRM</p>
  </footer>
</main>
</body>
</html>
