<?php
/**
 * Modal d'envoi de campagne
 * À inclure dans campaigns.php
 */
?>

<!-- Modal Envoi Campagne -->
<div class="modal fade" id="campaignSendModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-paper-plane"></i> Envoyer la Campagne
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="campaignSendForm">
                    <input type="hidden" id="campaignId" name="campaign_id">
                    <input type="hidden" name="action" value="send">
                    
                    <!-- Sélection du canal -->
                    <div class="mb-3">
                        <label class="form-label">Canal d'envoi *</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="channel" id="channelEmail" value="email" checked>
                            <label class="btn btn-outline-primary" for="channelEmail">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            
                            <input type="radio" class="btn-check" name="channel" id="channelWhatsapp" value="whatsapp">
                            <label class="btn btn-outline-success" for="channelWhatsapp">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </label>
                        </div>
                    </div>

                    <!-- Sélection des leads -->
                    <div class="mb-3">
                        <label class="form-label">Destinataires</label>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="recipients" id="recipientsAll" value="all" checked>
                            <label class="form-check-label" for="recipientsAll">
                                Tous les leads
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="recipients" id="recipientsSelected" value="selected">
                            <label class="form-check-label" for="recipientsSelected">
                                Leads sélectionnés seulement
                            </label>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <span id="selectedLeadsCount">Aucun</span> lead(s) sélectionné(s)
                        </small>
                    </div>

                    <!-- Options programmation -->
                    <div class="mb-3">
                        <label class="form-label">Programmation</label>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="timing" id="timingNow" value="now" checked>
                            <label class="form-check-label" for="timingNow">
                                Envoyer maintenant
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="timing" id="timingScheduled" value="scheduled">
                            <label class="form-check-label" for="timingScheduled">
                                Programmer pour plus tard
                            </label>
                        </div>

                        <div id="scheduledOptions" class="mt-3" style="display:none;">
                            <label class="form-label">Date et heure</label>
                            <input type="datetime-local" class="form-control" id="scheduledAt" name="scheduled_at">
                        </div>
                    </div>

                    <!-- Aperçu -->
                    <div class="alert alert-info">
                        <small>
                            <strong>Aperçu:</strong><br>
                            <span id="previewChannel">Email</span> -
                            <span id="previewRecipients">Tous les leads</span> -
                            <span id="previewTiming">Envoi immédiat</span>
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btnSendCampaign">
                    <i class="fas fa-send"></i> Envoyer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let selectedLeadIds = [];

    // Afficher/masquer les options de programmation
    document.querySelectorAll('input[name="timing"]').forEach(el => {
        el.addEventListener('change', function() {
            document.getElementById('scheduledOptions').style.display = 
                this.value === 'scheduled' ? 'block' : 'none';
            updatePreview();
        });
    });

    // Mettre à jour l'aperçu
    function updatePreview() {
        const channel = document.querySelector('input[name="channel"]:checked').value;
        const recipients = document.querySelector('input[name="recipients"]:checked').value;
        const timing = document.querySelector('input[name="timing"]:checked').value;

        document.getElementById('previewChannel').textContent = 
            channel === 'whatsapp' ? 'WhatsApp' : 'Email';
        document.getElementById('previewRecipients').textContent = 
            recipients === 'selected' ? selectedLeadIds.length + ' leads sélectionnés' : 'Tous les leads';
        document.getElementById('previewTiming').textContent = 
            timing === 'scheduled' ? 'Programmé' : 'Envoi immédiat';
    }

    // Mettre à jour les aperçus
    document.querySelectorAll('input[name="channel"], input[name="recipients"], input[name="timing"]').forEach(el => {
        el.addEventListener('change', updatePreview);
    });

    // Envoyer la campagne
    document.getElementById('btnSendCampaign').addEventListener('click', async function() {
        const campaignId = document.getElementById('campaignId').value;
        const channel = document.querySelector('input[name="channel"]:checked').value;
        const timing = document.querySelector('input[name="timing"]:checked').value;
        const recipients = document.querySelector('input[name="recipients"]:checked').value;
        const scheduledAt = document.getElementById('scheduledAt').value;

        const formData = new FormData();
        formData.append('campaign_id', campaignId);
        formData.append('channel', channel);

        if (timing === 'scheduled') {
            if (!scheduledAt) {
                alert('Veuillez sélectionner une date et heure');
                return;
            }
            formData.append('action', 'schedule');
            formData.append('scheduled_at', scheduledAt);
        } else {
            formData.append('action', 'send');
            if (recipients === 'selected' && selectedLeadIds.length > 0) {
                formData.append('lead_ids', selectedLeadIds.join(','));
            }
        }

        try {
            const response = await fetch('api/campaign-send.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert('✅ ' + result.message);
                location.reload();
            } else {
                alert('❌ Erreur: ' + result.message);
                if (result.errors && result.errors.length > 0) {
                    console.log('Détails:', result.errors);
                }
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('campaignSendModal'));
            modal.hide();
        } catch (error) {
            alert('❌ Erreur lors de l\'envoi: ' + error.message);
        }
    });

    // Fonction pour ouvrir le modal avec une campagne
    function openSendModal(campaignId) {
        document.getElementById('campaignId').value = campaignId;
        selectedLeadIds = [];
        document.getElementById('selectedLeadsCount').textContent = '0';
        
        const modal = new bootstrap.Modal(document.getElementById('campaignSendModal'));
        modal.show();
    }
</script>
