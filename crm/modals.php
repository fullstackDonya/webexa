<?php
// ==========================
// Initialisation des variables
// ==========================
$showCustomerModal = false;
$showCompanyModal = false;
$showCustomerValidationModal = false;
$selectedCustomerId = null;

// Charger la société associée AVANT la logique des modals
$company = null;
if ($customer) {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE customer_id = ?");
    $stmt->execute([$customer['id']]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupère le step depuis l'URL
$step = $_GET['step'] ?? null;

// ==========================
// Détermination du modal à afficher
// ==========================

// Cas forcé par la session
if (!empty($_SESSION['force_customer_validation_modal'])) {
    $showCustomerValidationModal = true;
    unset($_SESSION['force_customer_validation_modal']);
}

// Vérification par step prioritaire
if ($step === 'customerValidationModal') {
    $showCustomerValidationModal = true;
    $selectedCustomerId = $_SESSION['pending_customer_id'] ?? null;
    $showCustomerModal = false;
    $showCompanyModal = false;
} elseif ($step === 'companyModal') {
    $showCompanyModal = true;
    $showCustomerModal = false;
} elseif (!$customer) {
    // Aucun client → modal client
    $showCustomerModal = true;
} elseif (!$company) {
    // Client existe mais pas de société → modal société
    $showCompanyModal = true;
}

// ==========================
// DEBUG
// ==========================
// echo '<pre>';
// echo 'step: ' . ($step ?? 'none') . "\n";
// echo 'showCustomerModal: ' . ($showCustomerModal ? '1' : '0') . "\n";
// echo 'showCustomerValidationModal: ' . ($showCustomerValidationModal ? '1' : '0') . "\n";
// echo 'showCompanyModal: ' . ($showCompanyModal ? '1' : '0') . "\n";
// echo 'selectedCustomerId: ' . ($selectedCustomerId ?? 'null') . "\n";
// echo '</pre>';
// echo '<script>console.log("DEBUG JS: customer_id = ' . ($customer_id ?? 'none') . '");</script>';
// echo '<pre>';
// echo 'user_id: ' . ($user_id ?? 'none') . "\n";
// echo 'customer_id: ' . ($customer_id ?? 'none') . "\n";

// if ($showCustomerValidationModal) echo '<div>DEBUG: customerValidationModal doit s\'ouvrir</div>';
// if ($showCompanyModal) echo '<div>DEBUG: companyModal doit s\'ouvrir</div>';

// echo '<script>console.log("DEBUG JS: showCustomerValidationModal = ' . ($showCustomerValidationModal ? '1' : '0') . '");</script>';
// echo '<script>console.log("DEBUG JS: showCompanyModal = ' . ($showCompanyModal ? '1' : '0') . '");</script>';
?>

<!-- ========== MODALS ========== -->

<!-- Modal 1 : Création client -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customerModalLabel">Votre profil entreprise</h5>
      </div>
      <div class="modal-body">
        <div class="accordion" id="customerAccordion">
          <!-- Choisir une entreprise existante -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingExisting">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExisting" aria-expanded="true" aria-controls="collapseExisting">
                Associer à une entreprise existante
              </button>
            </h2>
            <div id="collapseExisting" class="accordion-collapse collapse show" aria-labelledby="headingExisting" data-bs-parent="#customerAccordion">
              <div class="accordion-body">
                <form id="select-customer-form" method="post" action="includes/process_customer.php">
                  <div class="mb-3">
                    <label for="existingCustomer" class="form-label">Sélectionnez une entreprise</label>
                    <select id="existingCustomer" class="form-select" name="existing_customer_id" required>
                      <option value="">-- Choisir --</option>
                      <?php foreach ($all_customers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-gradient w-100">Valider</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <!-- Créer un nouveau client -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingNew">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNew" aria-expanded="false" aria-controls="collapseNew">
                Créer un nouveau client
              </button>
            </h2>
            <div id="collapseNew" class="accordion-collapse collapse" aria-labelledby="headingNew" data-bs-parent="#customerAccordion">
              <div class="accordion-body">
                <form id="customer-form" method="post" action="includes/create_customer.php">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Nom complet *</label>
                      <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Email *</label>
                      <input type="email" class="form-control" value="<?= htmlspecialchars($user_email) ?>" disabled>
                      <input type="hidden" name="email" value="<?= htmlspecialchars($user_email) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Téléphone</label>
                      <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Pays</label>
                      <input type="text" name="country" class="form-control" value="France">
                    </div>
                    <div class="col-12">
                      <label class="form-label">Adresse</label>
                      <input type="text" name="address" class="form-control">
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-gradient w-100">Valider mon profil client</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div><!-- /accordion -->
      </div>
    </div>
  </div>
</div>

<!-- Modal 2 : Vérification société -->
<div class="modal fade" id="customerValidationModal" tabindex="-1" aria-labelledby="customerValidationModalLabel" aria-hidden="true">
  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">Les informations saisies sont incorrectes.</div>
  <?php endif; ?>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="customer-validation-form" method="post" action="includes/process_customer.php">
        <div class="modal-header">
          <h5 class="modal-title" id="customerValidationModalLabel">Vérification de la société</h5>
        </div>
        <div class="modal-body">
          <input type="hidden" name="pending_customer_id" id="selected_customer_id" value="<?= htmlspecialchars($selectedCustomerId ?? '') ?>">
          <div class="mb-3">
            <label class="form-label">Email de la société</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Code postal</label>
            <input type="text" name="postal_code" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Code de validation</label>
            <input type="text" name="validation_code" class="form-control" required>
          </div>
          <hr>
          <h6>Power BI (optionnel)</h6>
          <div class="mb-3">
            <label class="form-label">Power BI - Client ID</label>
            <input type="text" name="powerbi_client_id" class="form-control" placeholder="ID client Azure (optionnel)">
          </div>
          <div class="mb-3">
            <label class="form-label">Power BI - Client Secret</label>
            <input type="text" name="powerbi_client_secret" class="form-control" placeholder="Secret client (optionnel)">
          </div>
          <div class="mb-3">
            <label class="form-label">Power BI - Tenant ID</label>
            <input type="text" name="powerbi_tenant_id" class="form-control" placeholder="Tenant ID (optionnel)">
          </div>
          <div class="mb-3">
            <label class="form-label">Power BI - Workspace ID</label>
            <input type="text" name="powerbi_workspace_id" class="form-control" placeholder="Workspace ID (optionnel)">
          </div>
          <div class="mb-3">
            <label class="form-label">Power BI - Report ID</label>
            <input type="text" name="powerbi_report_id" class="form-control" placeholder="Report ID (optionnel)">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-gradient w-100">Valider</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 3 : Création société -->
<div class="modal fade" id="companyModal" tabindex="-1" aria-labelledby="companyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
                    
        <form id="company-form" method="post" action="includes/process_company.php">
            <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer_id ?? '') ?>">
            <div class="modal-header">
                <h5 class="modal-title" id="companyModalLabel">Informations sur votre société</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom de la société *</label>
                        <input type="text" name="name" class="form-control" required
                            value="<?= htmlspecialchars($existing_company['name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Secteur d'activité</label>
                        <select name="industry" class="form-select" required>
                            <option value="">Sélectionnez...</option>
                            <?php
                            $industries = [
                                "Informatique", "Commerce", "Santé", "Finance", "Immobilier", "Technologie",
                                "Éducation", "Industrie", "Services", "Tourisme", "Autre"
                            ];
                            foreach ($industries as $industry) {
                                $selected = (isset($existing_company['industry']) && $existing_company['industry'] === $industry) ? 'selected' : '';
                                echo "<option value=\"$industry\" $selected>$industry</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Site web</label>
                        <input type="url" name="website" class="form-control"
                            value="<?= htmlspecialchars($existing_company['website'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="phone" class="form-control"
                            value="<?= htmlspecialchars($existing_company['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($existing_company['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="address" class="form-control"
                            value="<?= htmlspecialchars($existing_company['address'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ville</label>
                        <input type="text" name="city" class="form-control"
                            value="<?= htmlspecialchars($existing_company['city'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Code postal</label>
                        <input type="text" name="postal_code" class="form-control"
                            value="<?= htmlspecialchars($existing_company['postal_code'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pays</label>
                        <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($existing_company['country'] ?? 'France') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre d'employés</label>
                        <input type="number" name="employee_count" class="form-control" min="1"
                            value="<?= htmlspecialchars($existing_company['employee_count'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Chiffre d'affaires annuel (€)</label>
                        <input type="number" step="0.01" name="annual_revenue" class="form-control"
                            value="<?= htmlspecialchars($existing_company['annual_revenue'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <?php
                            $statuses = ["prospect", "client", "partner", "inactive"];
                            foreach ($statuses as $status) {
                                $selected = (isset($existing_company['status']) && $existing_company['status'] === $status) ? 'selected' : '';
                                echo "<option value=\"$status\" $selected>" . ucfirst($status) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Source</label>
                        <input type="text" name="source" class="form-control"
                            value="<?= htmlspecialchars($existing_company['source'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($existing_company['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-gradient w-100">Valider et activer l'essai CRM</button>
            </div>
        </form>
        </div>
    </div>
    

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Sécurité : fermer toutes modals ouvertes par erreur
  document.querySelectorAll('.modal.show').forEach(function(m) {
    m.classList.remove('show');
    m.style.display = '';
    document.body.classList.remove('modal-open');
    let backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.remove();
  });

  <?php if ($showCustomerModal): ?>
    var modalEl = document.getElementById('customerModal');
  <?php elseif ($showCustomerValidationModal): ?>
    var modalEl = document.getElementById('customerValidationModal');
  <?php elseif ($showCompanyModal): ?>
    var modalEl = document.getElementById('companyModal');
  <?php else: ?>
    var modalEl = null;
  <?php endif; ?>


  if (modalEl) {
    var modal = new bootstrap.Modal(modalEl, {backdrop: 'static', keyboard: false});
    modal.show();
  }
});
</script>
