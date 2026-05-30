<?php
/**
 * Sélecteur de templates pour les campagnes
 * Composant réutilisable à inclure dans tous les formulaires de campagne
 */

// Charger les templates si pas déjà fait
if (!function_exists('get_email_templates')) {
    require_once __DIR__ . '/email_templates.php';
}

$templates = get_email_templates();
$categories = get_template_categories();
?>

<!-- Sélecteur de Templates -->
<div class="card mb-3 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-magic"></i> Templates de Communication
    </div>
    <div class="card-body">
        <!-- Filtre par canal -->
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">Canal de communication</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="templateChannel" id="channelAll" value="" checked>
                    <label class="btn btn-outline-secondary" for="channelAll">
                        <i class="fas fa-th"></i> Tous
                    </label>
                    
                    <input type="radio" class="btn-check" name="templateChannel" id="channelEmail" value="email">
                    <label class="btn btn-outline-primary" for="channelEmail">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    
                    <input type="radio" class="btn-check" name="templateChannel" id="channelWhatsapp" value="whatsapp">
                    <label class="btn btn-outline-success" for="channelWhatsapp">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </label>
                </div>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">Choisir un template</label>
                <select class="form-select" id="templateSelector" onchange="loadSelectedTemplate()">
                    <option value="">-- Créer un message personnalisé --</option>
                    <?php foreach ($categories as $cat_key => $cat_label): ?>
                        <optgroup label="<?php echo htmlspecialchars($cat_label); ?>" data-channel="<?php echo $cat_key === 'whatsapp' ? 'whatsapp' : 'email'; ?>">
                            <?php foreach ($templates as $tpl_id => $tpl): ?>
                                <?php if ($tpl['category'] === $cat_key): ?>
                                    <option value="<?php echo htmlspecialchars($tpl_id); ?>" data-channel="<?php echo isset($tpl['channel']) ? $tpl['channel'] : 'email'; ?>">
                                        <?php echo htmlspecialchars($tpl['name']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted" id="templateCount">29 templates professionnels disponibles</small>
            </div>
        </div>
        
        <!-- Aperçu template -->
        <div id="templatePreview" class="alert alert-info d-none">
            <strong>Aperçu :</strong>
            <div id="templatePreviewContent"></div>
        </div>
    </div>
</div>

<script>
// Filtrer les templates par canal
document.querySelectorAll('input[name="templateChannel"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const channel = this.value;
        const select = document.getElementById('templateSelector');
        const options = select.querySelectorAll('option');
        const optgroups = select.querySelectorAll('optgroup');
        
        let visibleCount = 0;
        
        // Réinitialiser la sélection
        select.value = '';
        document.getElementById('templatePreview').classList.add('d-none');
        
        if (channel === '') {
            // Afficher tous
            optgroups.forEach(og => og.style.display = '');
            options.forEach(opt => {
                opt.style.display = '';
                if (opt.value) visibleCount++;
            });
        } else {
            // Filtrer par canal
            optgroups.forEach(og => {
                const ogChannel = og.dataset.channel || 'email';
                if (ogChannel === channel) {
                    og.style.display = '';
                    Array.from(og.querySelectorAll('option')).forEach(opt => {
                        opt.style.display = '';
                        if (opt.value) visibleCount++;
                    });
                } else {
                    og.style.display = 'none';
                    Array.from(og.querySelectorAll('option')).forEach(opt => {
                        opt.style.display = 'none';
                    });
                }
            });
        }
        
        document.getElementById('templateCount').textContent = visibleCount + ' template' + (visibleCount > 1 ? 's' : '') + ' disponible' + (visibleCount > 1 ? 's' : '');
    });
});

// Fonction pour charger le template sélectionné
function loadSelectedTemplate() {
    const select = document.getElementById('templateSelector');
    const templateId = select.value;
    
    if (!templateId) {
        document.getElementById('templatePreview').classList.add('d-none');
        return;
    }
    
    fetch(`api/email-templates.php?action=template&id=${templateId}`)
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(template => {
            // Remplir les champs du formulaire - Compatible avec tous les formulaires
            const nameField = document.getElementById('campaignName') 
                || document.getElementById('campaign_name') 
                || document.querySelector('input[name="name"]')
                || document.querySelector('input[name="campaign_name"]');
                
            const subjectField = document.getElementById('campaignSubject') 
                || document.getElementById('campaign_subject')
                || document.querySelector('input[name="subject"]')
                || document.querySelector('input[name="campaign_subject"]');
                
            const contentField = document.getElementById('campaignContent') 
                || document.getElementById('campaign_content')
                || document.querySelector('textarea[name="content"]') 
                || document.querySelector('textarea[name="message"]')
                || document.querySelector('textarea[name="campaign_content"]');
            
            if (nameField && !nameField.value) {
                nameField.value = template.name || '';
            }
            
            if (subjectField) {
                subjectField.value = template.subject || '';
            }
            
            if (contentField) {
                // Nettoyer le HTML pour textarea ou garder tel quel
                const content = template.body || '';
                contentField.value = content;
                
                // Si TinyMCE est présent
                if (window.tinymce && window.tinymce.get(contentField.id)) {
                    window.tinymce.get(contentField.id).setContent(content);
                }
            }
            
            // Afficher aperçu
            document.getElementById('templatePreview').classList.remove('d-none');
            document.getElementById('templatePreviewContent').innerHTML = `
                <div class="mt-2">
                    <strong>Nom :</strong> ${template.name}<br>
                    <strong>Sujet :</strong> ${template.subject}<br>
                    <strong>Catégorie :</strong> ${select.options[select.selectedIndex].parentElement.label}
                </div>
            `;
            
            // Notification succès
            showNotification('✅ Template chargé avec succès !', 'success');
        })
        .catch(err => {
            console.error('Erreur chargement template:', err);
            showNotification('❌ Erreur lors du chargement du template', 'danger');
        });
}

// Fonction de notification
function showNotification(message, type = 'info') {
    const existingAlert = document.querySelector('.template-notification');
    if (existingAlert) existingAlert.remove();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show template-notification`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid') || document.body;
    container.insertBefore(alert, container.firstChild);
    
    setTimeout(() => alert.remove(), 4000);
}
</script>
