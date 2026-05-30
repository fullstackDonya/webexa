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

// Récupérer les documents scannés
$stmt = $pdo->prepare("
    SELECT * FROM erp_scanned_documents 
    WHERE customer_id = ? 
    ORDER BY created_at DESC 
    LIMIT 100
");
$stmt->execute([$customer_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_docs,
        SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
    FROM erp_scanned_documents
    WHERE customer_id = ?
");
$statsStmt->execute([$customer_id]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

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
    <h1><i class="fas fa-file-invoice"></i> Scanner de Documents Comptables</h1>

    <!-- Stats Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:32px">
      <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:24px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08);border-left:4px solid #3b82f6">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <span style="color:#64748b;font-size:14px;font-weight:600">Total documents</span>
          <i class="fas fa-file-alt" style="color:#3b82f6;font-size:20px"></i>
        </div>
        <div style="font-size:32px;font-weight:800;color:#0f172a"><?= $stats['total_docs'] ?? 0 ?></div>
      </div>

      <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:24px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08);border-left:4px solid #10b981">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <span style="color:#64748b;font-size:14px;font-weight:600">Traités</span>
          <i class="fas fa-check-circle" style="color:#10b981;font-size:20px"></i>
        </div>
        <div style="font-size:32px;font-weight:800;color:#0f172a"><?= $stats['processed'] ?? 0 ?></div>
      </div>

      <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:24px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08);border-left:4px solid #f59e0b">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <span style="color:#64748b;font-size:14px;font-weight:600">En attente</span>
          <i class="fas fa-clock" style="color:#f59e0b;font-size:20px"></i>
        </div>
        <div style="font-size:32px;font-weight:800;color:#0f172a"><?= $stats['pending'] ?? 0 ?></div>
      </div>

      <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:24px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08);border-left:4px solid #ef4444">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <span style="color:#64748b;font-size:14px;font-weight:600">Erreurs</span>
          <i class="fas fa-exclamation-triangle" style="color:#ef4444;font-size:20px"></i>
        </div>
        <div style="font-size:32px;font-weight:800;color:#0f172a"><?= $stats['errors'] ?? 0 ?></div>
      </div>
    </div>

    <!-- Zone d'upload -->
    <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:32px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08);margin-bottom:24px">
      <h2 style="margin:0 0 20px;font-size:20px;font-weight:700;color:#0f172a"><i class="fas fa-cloud-upload-alt"></i> Importer des documents</h2>
      
      <div id="dropZone" style="border:3px dashed #e2e8f0;border-radius:16px;padding:48px;text-align:center;transition:all 0.3s;cursor:pointer;background:linear-gradient(135deg, rgba(59,130,246,0.02) 0%, rgba(139,92,246,0.02) 100%)">
        <i class="fas fa-cloud-upload-alt" style="font-size:64px;color:#3b82f6;margin-bottom:16px;display:block"></i>
        <p style="font-size:18px;font-weight:600;color:#0f172a;margin-bottom:8px">Glissez-déposez vos documents ici</p>
        <p style="font-size:14px;color:#64748b;margin-bottom:20px">ou cliquez pour sélectionner des fichiers</p>
        <input type="file" id="fileInput" multiple accept=".pdf,.csv,.xlsx,.xls,.doc,.docx,.png,.jpg,.jpeg,.gif" style="display:none">
        <button onclick="document.getElementById('fileInput').click()" style="background:linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);color:#fff;padding:14px 28px;border:none;border-radius:12px;font-weight:600;font-size:14px;cursor:pointer;box-shadow:0 4px 16px rgba(59,130,246,0.3)">
          <i class="fas fa-folder-open"></i> Parcourir les fichiers
        </button>
      </div>

      <div style="margin-top:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
        <div style="background:rgba(59,130,246,0.05);padding:12px;border-radius:8px;font-size:13px;color:#64748b">
          <i class="fas fa-file-pdf" style="color:#ef4444"></i> PDF - Factures, reçus
        </div>
        <div style="background:rgba(59,130,246,0.05);padding:12px;border-radius:8px;font-size:13px;color:#64748b">
          <i class="fas fa-file-excel" style="color:#10b981"></i> Excel/CSV - Relevés bancaires
        </div>
        <div style="background:rgba(59,130,246,0.05);padding:12px;border-radius:8px;font-size:13px;color:#64748b">
          <i class="fas fa-file-word" style="color:#3b82f6"></i> Word - Contrats
        </div>
        <div style="background:rgba(59,130,246,0.05);padding:12px;border-radius:8px;font-size:13px;color:#64748b">
          <i class="fas fa-file-image" style="color:#8b5cf6"></i> Images - Tickets de caisse
        </div>
      </div>

      <!-- Preview zone -->
      <div id="uploadPreview" style="margin-top:24px;display:none">
        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;color:#0f172a">Fichiers sélectionnés</h3>
        <div id="fileList" style="display:grid;gap:12px"></div>
        <button id="uploadBtn" onclick="uploadFiles()" style="margin-top:16px;background:linear-gradient(135deg, #10b981 0%, #059669 100%);color:#fff;padding:14px 28px;border:none;border-radius:12px;font-weight:600;font-size:14px;cursor:pointer;width:100%;box-shadow:0 4px 16px rgba(16,185,129,0.3)">
          <i class="fas fa-rocket"></i> Analyser les documents
        </button>
      </div>
    </div>

    <!-- Documents traités -->
    <div style="background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);padding:28px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08)">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
        <h2 style="margin:0;font-size:20px;font-weight:700;color:#0f172a"><i class="fas fa-history"></i> Historique des documents</h2>
        <div style="display:flex;gap:8px">
          <select id="filterStatus" onchange="filterDocs()" style="padding:10px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:14px;background:#fff">
            <option value="">Tous les statuts</option>
            <option value="processed">Traités</option>
            <option value="pending">En attente</option>
            <option value="error">Erreurs</option>
          </select>
          <select id="filterType" onchange="filterDocs()" style="padding:10px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:14px;background:#fff">
            <option value="">Tous les types</option>
            <option value="invoice">Factures</option>
            <option value="receipt">Reçus</option>
            <option value="bank_statement">Relevés bancaires</option>
            <option value="payslip">Bulletins de paie</option>
            <option value="other">Autres</option>
          </select>
        </div>
      </div>

      <?php if (empty($documents)): ?>
        <div style="text-align:center;padding:48px;color:#64748b">
          <i class="fas fa-inbox" style="font-size:64px;margin-bottom:16px;opacity:0.3"></i>
          <p style="font-size:16px">Aucun document scanné</p>
          <p style="font-size:14px;margin-top:8px">Commencez par importer vos factures, tickets et relevés bancaires</p>
        </div>
      <?php else: ?>
        <div style="overflow-x:auto">
          <table class="table-compact" id="docsTable">
            <thead>
              <tr>
                <th>Aperçu</th>
                <th>Nom du fichier</th>
                <th>Type</th>
                <th>Données extraites</th>
                <th>Montant</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($documents as $doc): ?>
                <tr data-status="<?= $doc['status'] ?>" data-type="<?= $doc['document_type'] ?>">
                  <td>
                    <?php if (in_array($doc['file_type'], ['png', 'jpg', 'jpeg', 'gif'])): ?>
                      <img src="<?= htmlspecialchars($doc['file_path']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:8px">
                    <?php else: ?>
                      <div style="width:48px;height:48px;background:linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700">
                        <?= strtoupper(substr($doc['file_type'], 0, 3)) ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td style="font-weight:600"><?= htmlspecialchars($doc['filename']) ?></td>
                  <td>
                    <span style="padding:4px 12px;border-radius:8px;font-size:12px;font-weight:600;background:rgba(59,130,246,0.1);color:#3b82f6">
                      <?php
                      $types = [
                        'invoice' => 'Facture',
                        'receipt' => 'Reçu',
                        'bank_statement' => 'Relevé',
                        'payslip' => 'Bulletin',
                        'other' => 'Autre'
                      ];
                      echo $types[$doc['document_type']] ?? 'Document';
                      ?>
                    </span>
                  </td>
                  <td style="font-size:13px;color:#64748b">
                    <?php 
                    $data = json_decode($doc['extracted_data'], true);
                    if ($data && !empty($data)) {
                      // Compter tous les champs extraits
                      $fieldCount = count($data);
                      echo '<span style="background:rgba(59,130,246,0.1);color:#3b82f6;padding:2px 8px;border-radius:6px;font-weight:600">';
                      echo $fieldCount . ' champ' . ($fieldCount > 1 ? 's' : '');
                      echo '</span>';
                      
                      // Afficher aperçu des champs importants
                      $preview = [];
                      if (isset($data['numero_document'])) $preview[] = 'N°' . $data['numero_document'];
                      if (isset($data['fournisseur'])) $preview[] = $data['fournisseur'];
                      if (isset($data['total_ttc'])) $preview[] = number_format($data['total_ttc'], 2, ',', ' ') . '€';
                      
                      if (!empty($preview)) {
                        echo '<br><small style="color:#94a3b8">' . implode(' • ', array_slice($preview, 0, 2)) . '</small>';
                      }
                    } else {
                      echo '<span style="color:#94a3b8">Aucune donnée</span>';
                    }
                    ?>
                  </td>
                  <td style="font-weight:700;color:#10b981">
                    <?= $doc['amount'] ? number_format($doc['amount'], 2, ',', ' ') . ' €' : '—' ?>
                  </td>
                  <td><?= $doc['document_date'] ? date('d/m/Y', strtotime($doc['document_date'])) : '—' ?></td>
                  <td>
                    <?php if ($doc['status'] === 'processed'): ?>
                      <span style="padding:4px 12px;border-radius:8px;font-size:12px;font-weight:600;background:rgba(16,185,129,0.1);color:#10b981">
                        <i class="fas fa-check-circle"></i> Traité
                      </span>
                    <?php elseif ($doc['status'] === 'pending'): ?>
                      <span style="padding:4px 12px;border-radius:8px;font-size:12px;font-weight:600;background:rgba(245,158,11,0.1);color:#f59e0b">
                        <i class="fas fa-clock"></i> En attente
                      </span>
                    <?php else: ?>
                      <span style="padding:4px 12px;border-radius:8px;font-size:12px;font-weight:600;background:rgba(239,68,68,0.1);color:#ef4444">
                        <i class="fas fa-exclamation-triangle"></i> Erreur
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <button onclick="viewDocument(<?= $doc['id'] ?>)" style="background:transparent;border:none;color:#3b82f6;cursor:pointer;padding:4px 8px" title="Voir les détails">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="reprocessDocument(<?= $doc['id'] ?>)" style="background:transparent;border:none;color:#10b981;cursor:pointer;padding:4px 8px" title="Retraiter">
                      <i class="fas fa-sync"></i>
                    </button>
                    <button onclick="deleteDocument(<?= $doc['id'] ?>)" style="background:transparent;border:none;color:#ef4444;cursor:pointer;padding:4px 8px" title="Supprimer">
                      <i class="fas fa-trash"></i>
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

<!-- Modal de détails -->
<div id="detailsModal" style="display:none;position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(2,6,23,.6);backdrop-filter:blur(10px);z-index:100;align-items:center;justify-content:center;padding:20px;overflow-y:auto">
  <div style="background:#fff;padding:32px;border-radius:16px;max-width:900px;width:100%;box-shadow:0 20px 60px rgba(2,6,23,.2);max-height:90vh;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
      <h3 style="margin:0;font-size:22px;font-weight:700"><i class="fas fa-file-invoice"></i> Détails du document</h3>
      <button onclick="closeDetailsModal()" style="background:transparent;border:none;font-size:24px;color:#64748b;cursor:pointer">&times;</button>
    </div>
    <div id="detailsContent"></div>
  </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const uploadPreview = document.getElementById('uploadPreview');
const fileList = document.getElementById('fileList');
let selectedFiles = [];

// Drag & Drop
dropZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropZone.style.borderColor = '#3b82f6';
  dropZone.style.background = 'rgba(59,130,246,0.05)';
});

dropZone.addEventListener('dragleave', () => {
  dropZone.style.borderColor = '#e2e8f0';
  dropZone.style.background = 'linear-gradient(135deg, rgba(59,130,246,0.02) 0%, rgba(139,92,246,0.02) 100%)';
});

dropZone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropZone.style.borderColor = '#e2e8f0';
  dropZone.style.background = 'linear-gradient(135deg, rgba(59,130,246,0.02) 0%, rgba(139,92,246,0.02) 100%)';
  handleFiles(e.dataTransfer.files);
});

dropZone.addEventListener('click', () => {
  fileInput.click();
});

fileInput.addEventListener('change', (e) => {
  handleFiles(e.target.files);
});

function handleFiles(files) {
  selectedFiles = Array.from(files);
  displayFilePreview();
}

function displayFilePreview() {
  fileList.innerHTML = '';
  uploadPreview.style.display = 'block';
  
  selectedFiles.forEach((file, index) => {
    const fileItem = document.createElement('div');
    fileItem.style.cssText = 'display:flex;align-items:center;gap:12px;padding:12px;background:rgba(59,130,246,0.05);border-radius:8px;border:1px solid rgba(59,130,246,0.2)';
    
    const icon = getFileIcon(file.type);
    const size = (file.size / 1024).toFixed(1) + ' KB';
    
    fileItem.innerHTML = `
      <i class="fas ${icon}" style="font-size:24px;color:#3b82f6"></i>
      <div style="flex:1">
        <div style="font-weight:600;font-size:14px;color:#0f172a">${file.name}</div>
        <div style="font-size:12px;color:#64748b">${size}</div>
      </div>
      <button onclick="removeFile(${index})" style="background:transparent;border:none;color:#ef4444;cursor:pointer;padding:4px 8px">
        <i class="fas fa-times"></i>
      </button>
    `;
    
    fileList.appendChild(fileItem);
  });
}

function getFileIcon(type) {
  if (type.includes('pdf')) return 'fa-file-pdf';
  if (type.includes('excel') || type.includes('spreadsheet')) return 'fa-file-excel';
  if (type.includes('word') || type.includes('document')) return 'fa-file-word';
  if (type.includes('image')) return 'fa-file-image';
  return 'fa-file-alt';
}

function removeFile(index) {
  selectedFiles.splice(index, 1);
  if (selectedFiles.length === 0) {
    uploadPreview.style.display = 'none';
  } else {
    displayFilePreview();
  }
}

async function uploadFiles() {
  if (selectedFiles.length === 0) return;
  
  const uploadBtn = document.getElementById('uploadBtn');
  uploadBtn.disabled = true;
  uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyse en cours...';
  
  const formData = new FormData();
  selectedFiles.forEach(file => {
    formData.append('files[]', file);
  });
  
  try {
    const response = await fetch('api/document_scanner.php?action=upload', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      alert(`${data.count} document(s) importé(s) avec succès !`);
      location.reload();
    } else {
      alert('Erreur : ' + data.error);
      uploadBtn.disabled = false;
      uploadBtn.innerHTML = '<i class="fas fa-rocket"></i> Analyser les documents';
    }
  } catch (error) {
    alert('Erreur lors de l\'upload : ' + error.message);
    uploadBtn.disabled = false;
    uploadBtn.innerHTML = '<i class="fas fa-rocket"></i> Analyser les documents';
  }
}

function filterDocs() {
  const statusFilter = document.getElementById('filterStatus').value;
  const typeFilter = document.getElementById('filterType').value;
  const rows = document.querySelectorAll('#docsTable tbody tr');
  
  rows.forEach(row => {
    const status = row.dataset.status;
    const type = row.dataset.type;
    const showStatus = !statusFilter || status === statusFilter;
    const showType = !typeFilter || type === typeFilter;
    
    row.style.display = (showStatus && showType) ? '' : 'none';
  });
}

async function viewDocument(id) {
  const response = await fetch(`api/document_scanner.php?action=details&id=${id}`);
  const data = await response.json();
  
  if (data.success) {
    const doc = data.document;
    const extracted = JSON.parse(doc.extracted_data || '{}');
    
    let html = `
      <div style="display:grid;gap:24px">
        <!-- Informations de base -->
        <div style="background:rgba(59,130,246,0.05);padding:20px;border-radius:12px;border-left:4px solid #3b82f6">
          <h4 style="margin:0 0 16px;font-size:16px;font-weight:700;color:#0f172a">
            <i class="fas fa-info-circle"></i> Informations générales
          </h4>
          <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
            <div>
              <div style="font-size:12px;color:#64748b;margin-bottom:4px">Fichier</div>
              <div style="font-weight:600;color:#0f172a">${doc.filename}</div>
            </div>
            <div>
              <div style="font-size:12px;color:#64748b;margin-bottom:4px">Type</div>
              <div style="font-weight:600;color:#0f172a">${getDocumentTypeLabel(doc.document_type)}</div>
            </div>
            <div>
              <div style="font-size:12px;color:#64748b;margin-bottom:4px">Taille</div>
              <div style="font-weight:600;color:#0f172a">${formatFileSize(doc.file_size)}</div>
            </div>
            <div>
              <div style="font-size:12px;color:#64748b;margin-bottom:4px">Date d'import</div>
              <div style="font-weight:600;color:#0f172a">${formatDate(doc.created_at)}</div>
            </div>
          </div>
        </div>
    `;
    
    // Données financières
    const hasFinancialData = doc.amount || extracted.total_ht || extracted.tva;
    if (hasFinancialData) {
      html += `
        <div style="background:rgba(16,185,129,0.05);padding:20px;border-radius:12px;border-left:4px solid #10b981">
          <h4 style="margin:0 0 16px;font-size:16px;font-weight:700;color:#0f172a">
            <i class="fas fa-euro-sign"></i> Données financières
          </h4>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
      `;
      
      if (doc.amount) {
        html += `
          <div style="text-align:center;padding:12px;background:rgba(16,185,129,0.1);border-radius:8px">
            <div style="font-size:12px;color:#64748b;margin-bottom:4px">Total TTC</div>
            <div style="font-size:24px;font-weight:800;color:#10b981">${formatAmount(doc.amount)}€</div>
          </div>
        `;
      }
      
      if (extracted.total_ht) {
        html += `
          <div style="text-align:center;padding:12px;background:rgba(59,130,246,0.1);border-radius:8px">
            <div style="font-size:12px;color:#64748b;margin-bottom:4px">Total HT</div>
            <div style="font-size:20px;font-weight:700;color:#3b82f6">${formatAmount(extracted.total_ht)}€</div>
          </div>
        `;
      }
      
      if (extracted.tva) {
        html += `
          <div style="text-align:center;padding:12px;background:rgba(245,158,11,0.1);border-radius:8px">
            <div style="font-size:12px;color:#64748b;margin-bottom:4px">TVA ${extracted.taux_tva ? '(' + extracted.taux_tva + '%)' : ''}</div>
            <div style="font-size:20px;font-weight:700;color:#f59e0b">${formatAmount(extracted.tva)}€</div>
          </div>
        `;
      }
      
      html += `
          </div>
        </div>
      `;
    }
    
    // Données extraites détaillées
    if (Object.keys(extracted).length > 0) {
      html += `
        <div style="background:rgba(139,92,246,0.05);padding:20px;border-radius:12px;border-left:4px solid #8b5cf6">
          <h4 style="margin:0 0 16px;font-size:16px;font-weight:700;color:#0f172a">
            <i class="fas fa-database"></i> Données extraites (${Object.keys(extracted).length})
          </h4>
          <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
      `;
      
      const fieldLabels = {
        'numero_document': 'Numéro',
        'numero_commande': 'N° Commande',
        'reference_client': 'Référence client',
        'date_document': 'Date',
        'date_echeance': 'Échéance',
        'fournisseur': 'Fournisseur',
        'siret': 'SIRET',
        'numero_tva': 'N° TVA',
        'email': 'Email',
        'telephone': 'Téléphone',
        'moyen_paiement': 'Moyen de paiement',
        'iban': 'IBAN'
      };
      
      for (const [key, value] of Object.entries(extracted)) {
        if (['total_ttc', 'total_ht', 'tva', 'taux_tva'].includes(key)) continue; // Déjà affiché
        
        const label = fieldLabels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        const icon = getFieldIcon(key);
        
        html += `
          <div style="background:rgba(255,255,255,0.5);padding:12px;border-radius:8px;border:1px solid rgba(139,92,246,0.2)">
            <div style="font-size:12px;color:#64748b;margin-bottom:4px">
              <i class="fas ${icon}" style="margin-right:4px"></i>${label}
            </div>
            <div style="font-weight:600;color:#0f172a;word-break:break-all">${value}</div>
          </div>
        `;
      }
      
      html += `
          </div>
        </div>
      `;
    }
    
    // Bouton de téléchargement
    html += `
      <div style="text-align:center;padding-top:12px">
        <a href="${doc.file_path}" target="_blank" style="display:inline-block;background:linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:600">
          <i class="fas fa-download"></i> Télécharger le document
        </a>
      </div>
    `;
    
    html += '</div>';
    
    document.getElementById('detailsContent').innerHTML = html;
    document.getElementById('detailsModal').style.display = 'flex';
  }
}

function getDocumentTypeLabel(type) {
  const types = {
    'invoice': 'Facture',
    'receipt': 'Reçu',
    'bank_statement': 'Relevé bancaire',
    'payslip': 'Bulletin de paie',
    'contract': 'Contrat',
    'other': 'Autre'
  };
  return types[type] || type;
}

function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' o';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
  return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
}

function formatDate(dateStr) {
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatAmount(amount) {
  return parseFloat(amount).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function getFieldIcon(key) {
  const icons = {
    'numero_document': 'fa-hashtag',
    'numero_commande': 'fa-shopping-cart',
    'reference_client': 'fa-tag',
    'date_document': 'fa-calendar',
    'date_echeance': 'fa-clock',
    'fournisseur': 'fa-building',
    'siret': 'fa-id-card',
    'numero_tva': 'fa-percent',
    'email': 'fa-envelope',
    'telephone': 'fa-phone',
    'moyen_paiement': 'fa-credit-card',
    'iban': 'fa-university'
  };
  return icons[key] || 'fa-file-alt';
}

function closeDetailsModal() {
  document.getElementById('detailsModal').style.display = 'none';
}

async function reprocessDocument(id) {
  if (confirm('Relancer le traitement de ce document ?')) {
    const response = await fetch(`api/document_scanner.php?action=reprocess&id=${id}`, { method: 'POST' });
    const data = await response.json();
    if (data.success) {
      alert('Document retraité avec succès');
      location.reload();
    } else {
      alert('Erreur : ' + data.error);
    }
  }
}

async function deleteDocument(id) {
  if (confirm('Supprimer définitivement ce document ?')) {
    const response = await fetch(`api/document_scanner.php?action=delete&id=${id}`, { method: 'DELETE' });
    const data = await response.json();
    if (data.success) {
      location.reload();
    } else {
      alert('Erreur : ' + data.error);
    }
  }
}
</script>

</div>
</body>
</html>
