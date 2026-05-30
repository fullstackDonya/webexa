<!-- Modal pour sélectionner et générer des templates d'emails -->
<div class="modal fade" id="templateSuggestionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📧 Sélectionnez un template d'email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Filtres par catégorie -->
                <div class="mb-3">
                    <label class="form-label"><strong>Catégorie</strong></label>
                    <div id="categoryFilter" class="btn-group d-flex gap-2 flex-wrap">
                        <!-- Les catégories seront chargées ici -->
                    </div>
                </div>

                <!-- Liste des templates -->
                <div class="mb-3">
                    <label class="form-label"><strong>Templates disponibles</strong></label>
                    <div id="templateList" style="max-height: 300px; overflow-y: auto;">
                        <!-- Les templates seront chargés ici -->
                    </div>
                </div>

                <!-- Aperçu du template sélectionné -->
                <div id="templatePreview" class="alert alert-info" style="display:none;">
                    <div class="mb-2">
                        <strong>Sujet :</strong>
                        <input type="text" class="form-control form-control-sm" id="previewSubject" readonly>
                    </div>
                    <div>
                        <strong>Aperçu :</strong>
                        <div id="previewBody" class="border p-2 bg-white" style="max-height: 150px; overflow-y: auto; font-size: 12px;"></div>
                    </div>
                </div>

                <!-- Champ caché pour stocker le template sélectionné -->
                <input type="hidden" id="selectedTemplate" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmTemplateBtn" disabled>
                    <i class="bi bi-check-lg"></i> Utiliser ce template
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Charger les catégories et templates au démarrage du modal
    const modal = document.getElementById('templateSuggestionsModal');
    
    if (modal) {
        modal.addEventListener('show.bs.modal', function() {
            loadTemplateCategories();
            loadTemplates('lead_nurturing'); // Catégorie par défaut
        });
    }
});

/**
 * Charger les catégories de templates
 */
function loadTemplateCategories() {
    fetch('/api/email-templates.php?action=categories')
        .then(r => r.json())
        .then(categories => {
            const filterDiv = document.getElementById('categoryFilter');
            filterDiv.innerHTML = '';
            
            Object.entries(categories).forEach(([key, label]) => {
                const btn = document.createElement('button');
                btn.className = 'btn btn-outline-secondary btn-sm';
                btn.textContent = label;
                btn.onclick = () => loadTemplates(key);
                filterDiv.appendChild(btn);
                
                // Sélectionner la première par défaut
                if (key === 'lead_nurturing') {
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-secondary');
                }
            });
        })
        .catch(err => console.error('Erreur lors du chargement des catégories:', err));
}

/**
 * Charger les templates d'une catégorie
 */
function loadTemplates(category) {
    // Mettre à jour l'état des boutons
    document.querySelectorAll('#categoryFilter .btn').forEach(btn => {
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-outline-secondary');
    });
    
    event.target?.classList.remove('btn-outline-secondary');
    event.target?.classList.add('btn-secondary');
    
    // Charger les templates
    fetch(`/api/email-templates.php?action=templates&category=${category}`)
        .then(r => r.json())
        .then(templates => {
            const listDiv = document.getElementById('templateList');
            listDiv.innerHTML = '';
            
            templates.forEach(template => {
                const div = document.createElement('div');
                div.className = 'card mb-2 cursor-pointer template-item';
                div.style.cursor = 'pointer';
                div.innerHTML = `
                    <div class="card-body p-2">
                        <h6 class="card-title mb-1">${template.name}</h6>
                        <small class="text-muted">${template.subject.substring(0, 50)}...</small>
                    </div>
                `;
                
                div.onclick = () => selectTemplate(template.id, template);
                listDiv.appendChild(div);
            });
        })
        .catch(err => console.error('Erreur lors du chargement des templates:', err));
}

/**
 * Sélectionner un template et afficher l'aperçu
 */
function selectTemplate(templateId, templateInfo) {
    // Mettre à jour la sélection
    document.querySelectorAll('.template-item').forEach(item => {
        item.classList.remove('border-primary', 'bg-light');
    });
    
    event.currentTarget?.classList.add('border-primary', 'bg-light');
    
    // Récupérer le contenu complet du template
    fetch(`/api/email-templates.php?action=template&id=${templateId}`)
        .then(r => r.json())
        .then(template => {
            // Afficher l'aperçu
            document.getElementById('previewSubject').value = template.subject;
            document.getElementById('previewBody').innerHTML = template.body;
            document.getElementById('templatePreview').style.display = 'block';
            
            // Sauvegarder la sélection
            document.getElementById('selectedTemplate').value = templateId;
            document.getElementById('confirmTemplateBtn').disabled = false;
        })
        .catch(err => console.error('Erreur lors du chargement du template:', err));
}

/**
 * Utiliser le template sélectionné
 */
document.getElementById('confirmTemplateBtn')?.addEventListener('click', function() {
    const templateId = document.getElementById('selectedTemplate').value;
    
    if (!templateId) {
        alert('Veuillez sélectionner un template');
        return;
    }
    
    // Générer le contenu personnalisé
    const formData = new FormData();
    formData.append('template_id', templateId);
    
    fetch('/api/email-templates.php?action=generate', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(content => {
        // Injecter le contenu dans le formulaire de campagne
        const subjectField = document.querySelector('input[name="subject"]');
        const bodyField = document.querySelector('textarea[name="body"]');
        
        if (subjectField) subjectField.value = content.subject;
        if (bodyField) bodyField.value = content.body;
        
        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('templateSuggestionsModal'));
        modal?.hide();
        
        // Afficher une notification
        showNotification('✅ Template appliqué avec succès !', 'success');
    })
    .catch(err => {
        console.error('Erreur:', err);
        showNotification('❌ Erreur lors de l\'application du template', 'danger');
    });
});

/**
 * Fonction utilitaire pour afficher les notifications
 */
function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>

<style>
.template-item {
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

.template-item:hover {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

.template-item.border-primary {
    border-color: #0d6efd !important;
    background-color: #f0f6ff;
}
</style>
