<?php

require_once __DIR__ . '/../crm/config/database.php';
include __DIR__ . '/../crm/includes/auth.php';

// Vérifier l'authentification
if (!isAuthenticated()) {
    header('Location: ../crm/login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    die("Erreur: customer_id introuvable");
}

// Récupérer les comptes bancaires
$stmt = $pdo->prepare("SELECT * FROM erp_bank_accounts WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->execute([$customer_id]);
$bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les transactions récentes
$transStmt = $pdo->prepare("
    SELECT t.*, b.bank_name, b.account_number 
    FROM erp_bank_transactions t
    INNER JOIN erp_bank_accounts b ON t.account_id = b.id
    WHERE b.customer_id = ?
    ORDER BY t.transaction_date DESC
    LIMIT 50
");
$transStmt->execute([$customer_id]);
$transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des statistiques
$statsStmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) as total_income,
        SUM(CASE WHEN t.amount < 0 THEN ABS(t.amount) ELSE 0 END) as total_expenses,
        COUNT(*) as total_transactions
    FROM erp_bank_transactions t
    INNER JOIN erp_bank_accounts b ON t.account_id = b.id
    WHERE b.customer_id = ?
    AND MONTH(t.transaction_date) = MONTH(CURRENT_DATE())
    AND YEAR(t.transaction_date) = YEAR(CURRENT_DATE())
");
$statsStmt->execute([$customer_id]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$total_income = $stats['total_income'] ?? 0;
$total_expenses = $stats['total_expenses'] ?? 0;
$balance = $total_income - $total_expenses;
$total_transactions = $stats['total_transactions'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <title>Comptabilité & Connexion Bancaire</title>
</head>
<body>
    

<?php include __DIR__ . '/erp_nav.php'; ?>


<div class="main-content">
  <div class="container">
    <h1><i class="fas fa-university"></i> Comptabilité & Connexion Bancaire</h1>

    <!-- Stats Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:32px">
      <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:24px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08);border-left:4px solid #10b981">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <span style="color:#64748b;font-size:14px;font-weight:600">Revenus du mois</span>
          <i class="fas fa-arrow-up" style="color:#10b981;font-size:20px"></i>
        </div>
        <div style="font-size:32px;font-weight:800;color:#0f172a"><?= number_format($total_income, 2, ',', ' ') ?> €</div>
      </div>

      <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:24px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08);border-left:4px solid #ef4444">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <span style="color:#64748b;font-size:14px;font-weight:600">Dépenses du mois</span>
          <i class="fas fa-arrow-down" style="color:#ef4444;font-size:20px"></i>
        </div>
        <div style="font-size:32px;font-weight:800;color:#0f172a"><?= number_format($total_expenses, 2, ',', ' ') ?> €</div>
      </div>

      <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:24px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08);border-left:4px solid #3b82f6">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <span style="color:#64748b;font-size:14px;font-weight:600">Solde</span>
          <i class="fas fa-wallet" style="color:#3b82f6;font-size:20px"></i>
        </div>
        <div style="font-size:32px;font-weight:800;color:<?= $balance >= 0 ? '#10b981' : '#ef4444' ?>"><?= number_format($balance, 2, ',', ' ') ?> €</div>
      </div>

      <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:24px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08);border-left:4px solid #8b5cf6">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <span style="color:#64748b;font-size:14px;font-weight:600">Transactions</span>
          <i class="fas fa-exchange-alt" style="color:#8b5cf6;font-size:20px"></i>
        </div>
        <div style="font-size:32px;font-weight:800;color:#0f172a"><?= $total_transactions ?></div>
      </div>
    </div>

    <!-- Comptes bancaires -->
    <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:28px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08);margin-bottom:24px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
        <h2 style="margin:0;font-size:20px;font-weight:700;color:#0f172a"><i class="fas fa-building-columns"></i> Comptes bancaires</h2>
        <button onclick="openAddBankModal()" class="btn" style="background:linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);color:#fff;padding:12px 20px;border:none;border-radius:12px;font-weight:600;cursor:pointer;box-shadow:0 4px 16px rgba(59,130,246,0.3)">
          <i class="fas fa-plus"></i> Ajouter un compte
        </button>
      </div>

      <?php if (empty($bankAccounts)): ?>
        <div style="text-align:center;padding:48px;color:#64748b">
          <i class="fas fa-university" style="font-size:64px;margin-bottom:16px;opacity:0.3"></i>
          <p style="font-size:16px">Aucun compte bancaire configuré</p>
          <p style="font-size:14px;margin-top:8px">Connectez votre banque pour synchroniser vos transactions automatiquement</p>
        </div>
      <?php else: ?>
        <div style="display:grid;gap:16px">
          <?php foreach ($bankAccounts as $account): ?>
            <div style="background:linear-gradient(135deg, rgba(59,130,246,0.05) 0%, rgba(139,92,246,0.05) 100%);border:1px solid rgba(59,130,246,0.2);padding:20px;border-radius:12px;display:flex;justify-content:space-between;align-items:center">
              <div style="flex:1">
                <div style="font-weight:700;font-size:16px;color:#0f172a;margin-bottom:8px">
                  <i class="fas fa-university" style="color:#3b82f6;margin-right:8px"></i>
                  <?= htmlspecialchars($account['bank_name']) ?>
                </div>
                <div style="display:flex;gap:24px;font-size:14px;color:#64748b">
                  <span><i class="fas fa-hashtag"></i> <?= htmlspecialchars($account['account_number']) ?></span>
                  <span><i class="fas fa-wallet"></i> <?= number_format($account['balance'], 2, ',', ' ') ?> €</span>
                  <?php if ($account['connection_status'] === 'connected'): ?>
                    <span style="color:#10b981"><i class="fas fa-check-circle"></i> Connecté</span>
                  <?php else: ?>
                    <span style="color:#ef4444"><i class="fas fa-exclamation-circle"></i> Déconnecté</span>
                  <?php endif; ?>
                </div>
              </div>
              <div style="display:flex;gap:8px">
                <button onclick="syncAccount(<?= $account['id'] ?>)" class="btn" style="padding:8px 16px;background:#fff;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;color:#3b82f6;font-weight:600">
                  <i class="fas fa-sync"></i> Synchroniser
                </button>
                <button onclick="viewTransactions(<?= $account['id'] ?>)" class="btn" style="padding:8px 16px;background:#fff;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;color:#64748b;font-weight:600">
                  <i class="fas fa-list"></i> Voir
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Transactions récentes -->
    <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:28px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08)">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
        <h2 style="margin:0;font-size:20px;font-weight:700;color:#0f172a"><i class="fas fa-exchange-alt"></i> Transactions récentes</h2>
        <button onclick="generateReport()" class="btn" style="padding:10px 18px;background:#fff;border:2px solid #e2e8f0;border-radius:12px;cursor:pointer;color:#3b82f6;font-weight:600">
          <i class="fas fa-file-pdf"></i> Générer bilan
        </button>
      </div>

      <?php if (empty($transactions)): ?>
        <div style="text-align:center;padding:48px;color:#64748b">
          <i class="fas fa-exchange-alt" style="font-size:64px;margin-bottom:16px;opacity:0.3"></i>
          <p style="font-size:16px">Aucune transaction disponible</p>
        </div>
      <?php else: ?>
        <div style="overflow-x:auto">
          <table class="table-compact">
            <thead>
              <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Compte</th>
                <th>Catégorie</th>
                <th>Montant</th>
                <th>Solde</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($transactions as $trans): ?>
                <tr>
                  <td style="font-weight:600"><?= date('d/m/Y', strtotime($trans['transaction_date'])) ?></td>
                  <td><?= htmlspecialchars($trans['description']) ?></td>
                  <td><span style="font-size:12px;color:#64748b"><?= htmlspecialchars($trans['bank_name']) ?> •••• <?= substr($trans['account_number'], -4) ?></span></td>
                  <td>
                    <span style="padding:4px 12px;border-radius:8px;font-size:12px;font-weight:600;background:rgba(59,130,246,0.1);color:#3b82f6">
                      <?= htmlspecialchars($trans['category'] ?? 'Non catégorisé') ?>
                    </span>
                  </td>
                  <td style="font-weight:700;font-size:15px;color:<?= $trans['amount'] >= 0 ? '#10b981' : '#ef4444' ?>">
                    <?= $trans['amount'] >= 0 ? '+' : '' ?><?= number_format($trans['amount'], 2, ',', ' ') ?> €
                  </td>
                  <td style="color:#64748b"><?= number_format($trans['balance_after'], 2, ',', ' ') ?> €</td>
                  <td>
                    <button onclick="editTransaction(<?= $trans['id'] ?>)" style="background:transparent;border:none;color:#3b82f6;cursor:pointer;padding:4px 8px">
                      <i class="fas fa-edit"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal Ajout Compte Bancaire -->
<div id="bankModal" style="display:none;position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(2,6,23,.6);backdrop-filter:blur(10px);z-index:100;align-items:center;justify-content:center;padding:20px">
  <div style="background:#fff;padding:32px;border-radius:16px;max-width:600px;width:100%;box-shadow:0 20px 60px rgba(2,6,23,.2)">
    <h3 style="margin:0 0 24px;font-size:22px;font-weight:700"><i class="fas fa-university"></i> Connecter un compte bancaire</h3>
    
    <form id="bankForm" method="POST" action="api/bank_accounts.php">
      <input type="hidden" name="action" value="add">
      
      <div style="margin-bottom:20px">
        <label style="display:block;font-weight:600;margin-bottom:8px;color:#0f172a">Banque</label>
        <select name="bank_name" required style="width:100%;padding:12px;border:2px solid #e2e8f0;border-radius:10px;font-size:14px">
          <option value="">Sélectionner une banque...</option>
          <option value="BNP Paribas">BNP Paribas</option>
          <option value="Crédit Agricole">Crédit Agricole</option>
          <option value="Société Générale">Société Générale</option>
          <option value="LCL">LCL</option>
          <option value="Caisse d'Épargne">Caisse d'Épargne</option>
          <option value="Banque Postale">Banque Postale</option>
          <option value="Crédit Mutuel">Crédit Mutuel</option>
          <option value="Boursorama">Boursorama</option>
          <option value="N26">N26</option>
          <option value="Revolut">Revolut</option>
          <option value="Autre">Autre</option>
        </select>
      </div>

      <div style="margin-bottom:20px">
        <label style="display:block;font-weight:600;margin-bottom:8px;color:#0f172a">Numéro de compte (IBAN)</label>
        <input type="text" name="account_number" placeholder="FR76 XXXX XXXX XXXX XXXX XXXX XXX" required style="width:100%;padding:12px;border:2px solid #e2e8f0;border-radius:10px;font-size:14px">
      </div>

      <div style="margin-bottom:20px">
        <label style="display:block;font-weight:600;margin-bottom:8px;color:#0f172a">Type de compte</label>
        <select name="account_type" required style="width:100%;padding:12px;border:2px solid #e2e8f0;border-radius:10px;font-size:14px">
          <option value="checking">Compte courant</option>
          <option value="savings">Compte épargne</option>
          <option value="business">Compte professionnel</option>
        </select>
      </div>

      <div style="background:rgba(59,130,246,0.05);padding:16px;border-radius:10px;margin-bottom:20px;border-left:4px solid #3b82f6">
        <p style="margin:0;font-size:13px;color:#64748b;line-height:1.6">
          <i class="fas fa-info-circle" style="color:#3b82f6"></i> 
          La connexion bancaire utilise une API sécurisée pour synchroniser vos transactions. Vos identifiants bancaires ne sont jamais stockés sur nos serveurs.
        </p>
      </div>

      <div style="display:flex;gap:12px;justify-content:flex-end">
        <button type="button" onclick="closeBankModal()" style="padding:12px 24px;background:#fff;border:2px solid #e2e8f0;border-radius:12px;cursor:pointer;font-weight:600">Annuler</button>
        <button type="submit" style="padding:12px 24px;background:linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);color:#fff;border:none;border-radius:12px;cursor:pointer;font-weight:600;box-shadow:0 4px 16px rgba(59,130,246,0.3)">
          <i class="fas fa-plug"></i> Connecter
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddBankModal() {
  document.getElementById('bankModal').style.display = 'flex';
}

function closeBankModal() {
  document.getElementById('bankModal').style.display = 'none';
}

function syncAccount(accountId) {
  if (confirm('Synchroniser les transactions de ce compte ?')) {
    fetch('api/bank_accounts.php?action=sync&id=' + accountId)
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          alert('Synchronisation réussie : ' + data.count + ' nouvelles transactions');
          location.reload();
        } else {
          alert('Erreur : ' + data.error);
        }
      });
  }
}

function viewTransactions(accountId) {
  window.location.href = 'accounting.php?account=' + accountId;
}

function generateReport() {
  window.open('api/accounting_report.php?format=pdf&month=' + new Date().getMonth() + 1, '_blank');
}

function editTransaction(id) {
  // Modal d'édition de transaction
  alert('Édition transaction #' + id + ' (à implémenter)');
}

document.getElementById('bankForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('api/bank_accounts.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('Compte bancaire ajouté avec succès');
      location.reload();
    } else {
      alert('Erreur : ' + data.error);
    }
  });
});
</script>

</div>
</body>
</html>
