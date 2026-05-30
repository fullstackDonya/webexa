<?php

include __DIR__ . '/includes/payroll.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ERP - Générateur de fiches de paie</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    .payroll-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .payroll-header h1 {
      margin: 0;
      font-size: 1.875rem;
      font-weight: 700;
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
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
    .help-text {
      font-size: 0.75rem;
      color: #6b7280;
      margin-top: 0.25rem;
    }
    .info-box {
      background: linear-gradient(135deg, #dbeafe, #e0e7ff);
      border-left: 4px solid #3b82f6;
      padding: 1rem;
      border-radius: 8px;
      margin-top: 1.5rem;
    }
    .info-box p {
      margin: 0;
      font-size: 0.875rem;
      color: #1e40af;
    }
  </style>
</head>
<body>
    <?php include 'erp_nav.php'; ?>

  <div class="container">
    <div class="payroll-header">
      <h1><i class="fas fa-file-invoice-dollar"></i> Générateur de fiches de paie</h1>
    </div>

    <div class="form-container">
      <h2><i class="fas fa-calculator"></i> Générer une nouvelle fiche de paie</h2>
      <form method="post">
        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-user"></i> Employé *</label>
            <select name="employee_id" required>
              <option value="">-- Sélectionner un employé --</option>
              <?php foreach ($employees as $e): ?>
                <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['last_name'] . ' ' . $e['first_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-field">
            <label><i class="fas fa-calendar-alt"></i> Période *</label>
            <input name="period" placeholder="YYYY-MM" pattern="\d{4}-\d{2}" required>
            <div class="help-text">Format: 2026-01 pour Janvier 2026</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-building"></i> Société (facultatif)</label>
            <select name="company_id">
              <option value="">-- Aucune / Sélectionner --</option>
              <?php foreach ($companies as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="help-text">Liste filtrée par client si applicable</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-euro-sign"></i> Salaire brut</label>
            <input type="number" step="0.01" name="gross_salary" placeholder="Laisser vide pour utiliser le salaire de base">
            <div class="help-text">Laisser vide pour utiliser le salaire de base de l'employé</div>
          </div>
          <div class="form-field">
            <label><i class="fas fa-gift"></i> Primes</label>
            <input type="number" step="0.01" name="bonus" value="0" placeholder="0.00">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label><i class="fas fa-clock"></i> Heures supplémentaires (montant)</label>
            <input type="number" step="0.01" name="overtime" value="0" placeholder="0.00">
          </div>
          <div class="form-field">
            <label><i class="fas fa-minus-circle"></i> Retenues</label>
            <input type="number" step="0.01" name="deductions" value="0" placeholder="0.00">
            <div class="help-text">Titres restaurant, absences, etc.</div>
          </div>
        </div>

        <div style="margin-top: 1.5rem;">
          <button class="btn btn-primary" type="submit">
            <i class="fas fa-file-pdf"></i> Générer le PDF et enregistrer
          </button>
        </div>

        <div class="info-box">
          <p>
            <i class="fas fa-info-circle"></i> 
            <strong>Information importante:</strong> Les calculs sont indicatifs. Vérifiez les taux selon votre convention collective et validez le bulletin final avec votre service paie.
          </p>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
