
tinymce.init({
    selector: 'textarea',
    plugins: [
      // Core editing features (free plugins only)
      'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount', 'code', 'image', 'fullscreen', 'insertdatetime', 'help'
    ],
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | code fullscreen | removeformat help',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; font-size: 14px; }',
    height: 500,
    menubar: 'file edit view insert format tools table help',
    automatic_uploads: false,
    relative_urls: false,
    remove_script_host: false,
    convert_urls: false
  });

let currentTemplateId = null;

function initEditor() {
  if (typeof tinymce === 'undefined') {
    console.error('TinyMCE non chargé');
    return;
  }
  tinymce.init({
    selector: '#editor',
    height: 500,
    menubar: 'file edit view insert format tools table help',
    plugins: 'lists link image table code autoresize advlist charmap searchreplace visualblocks visualchars fullscreen insertdatetime media nonbreaking save directionality emoticons',
    toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table hr | removeformat | code fullscreen',
    content_style: 'body { font-family: Inter, Arial, sans-serif; font-size:14px }',
    automatic_uploads: false
  });
}

async function loadTemplates() {
  const typeEl = document.getElementById('templateType');
  const type = typeEl ? typeEl.value : '';
  const url = 'api/email-templates.php' + (type ? ('?type=' + encodeURIComponent(type)) : '');
  try {
    const res = await fetch(url);
    const text = await res.text();
    // debug: si la réponse contient du HTML inattendu, loguer pour diagnostic
    if (text.trim().startsWith('<')) {
      console.error('Réponse non JSON (HTML) reçue pour', url, text.slice(0,300));
      // essayer de parser si JSON encodé malgré tout :
      // fallthrough -> set empty list
      document.getElementById('templatesList').innerHTML = '<div class="text-muted small">Erreur serveur, vérifier logs</div>';
      return;
    }
    const json = JSON.parse(text);
    const list = document.getElementById('templatesList');
    list.innerHTML = '';
    if (!json.success) return;
    (json.templates || []).forEach(t => {
      const a = document.createElement('a');
      a.href = '#';
      a.className = 'list-group-item list-group-item-action';
      a.dataset.id = t.id;
      a.textContent = `${t.name} · ${t.type}`;
      a.addEventListener('click', (e) => { e.preventDefault(); selectTemplate(t.id); });
      list.appendChild(a);
    });
  } catch (e) {
    console.error('loadTemplates error', e);
    const list = document.getElementById('templatesList');
    if (list) list.innerHTML = '<div class="text-muted small">Erreur réseau ou serveur</div>';
  }
}

async function selectTemplate(id) {
  try {
    const res = await fetch('api/email-templates.php?action=get&id=' + encodeURIComponent(id));
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
    if (!json.success || !json.template) return;
    currentTemplateId = json.template.id;
    const nameEl = document.getElementById('tplName');
    const subjEl = document.getElementById('tplSubject');
    if (nameEl) nameEl.value = json.template.name || '';
    if (subjEl) subjEl.value = json.template.subject || '';
    if (typeof tinymce !== 'undefined' && tinymce.get('editor')) tinymce.get('editor').setContent(json.template.content_html || '');
    const delBtn = document.getElementById('deleteTemplateBtn');
    if (delBtn) delBtn.disabled = false;
  } catch (e) {
    console.error('selectTemplate error', e);
  }
}

async function saveTemplate() {
  const nameEl = document.getElementById('tplName');
  const subjEl = document.getElementById('tplSubject');
  const name = nameEl ? nameEl.value.trim() : '';
  const subject = subjEl ? subjEl.value.trim() : '';
  const type = document.getElementById('templateType') ? (document.getElementById('templateType').value || 'newsletter') : 'newsletter';
  const content_html = (typeof tinymce !== 'undefined' && tinymce.get('editor')) ? tinymce.get('editor').getContent() : '';
  if (!name) { alert('Nom requis'); return; }
  try {
    const res = await fetch('api/email-templates.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: currentTemplateId, name, type, subject, content_html })
    });
    const json = await res.json();
    if (json.success) {
      currentTemplateId = json.id;
      await loadTemplates();
      alert('Modèle enregistré');
    } else {
      alert(json.message || 'Erreur');
    }
  } catch (e) {
    console.error('saveTemplate error', e);
    alert('Erreur réseau ou serveur');
  }
}

function newTemplate() {
  currentTemplateId = null;
  const nameEl = document.getElementById('tplName');
  const subjEl = document.getElementById('tplSubject');
  if (nameEl) nameEl.value = '';
  if (subjEl) subjEl.value = '';
  if (typeof tinymce !== 'undefined' && tinymce.get('editor')) {
    tinymce.get('editor').setContent('<p>Bonjour {{contact_first_name}},</p><p>...</p><p><small><img src="track-open.php?cid={{campaign_id}}" width="1" height="1" style="display:none"></small></p>');
  }
  const delBtn = document.getElementById('deleteTemplateBtn');
  if (delBtn) delBtn.disabled = true;
}

async function deleteTemplate() {
  if (!currentTemplateId) return;
  if (!confirm('Supprimer ce modèle ?')) return;
  try {
    const res = await fetch('api/email-templates.php?id=' + encodeURIComponent(currentTemplateId), { method: 'DELETE' });
    const json = await res.json();
    if (json.success) {
      newTemplate();
      await loadTemplates();
    } else {
      alert(json.message || 'Erreur suppression');
    }
  } catch (e) {
    console.error('deleteTemplate error', e);
  }
}

async function uploadImage() {
  const inp = document.getElementById('imageFile');
  const status = document.getElementById('uploadStatus');
  if (!inp || !inp.files || inp.files.length === 0) { alert('Sélectionnez une image'); return; }
  const fd = new FormData();
  fd.append('file', inp.files[0]);
  if (status) status.textContent = 'Upload...';
  try {
    const res = await fetch('api/upload.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (!json.success) throw new Error(json.message || 'upload error');
    const url = json.url;
    if (typeof tinymce !== 'undefined' && tinymce.activeEditor) tinymce.activeEditor.insertContent(`<img src="${url}" alt="" style="max-width:100%;">`);
    if (status) status.textContent = 'Image insérée';
  } catch (e) {
    if (status) status.textContent = 'Erreur: ' + (e.message || e);
    console.error('uploadImage error', e);
  }
}

// Events - guarded binds
window.addEventListener('DOMContentLoaded', () => {
  initEditor();
  setTimeout(loadTemplates, 400);

  // ensure safe fetch for tpl param and handle JSON parse errors
  try {
    const params = new URLSearchParams(window.location.search);
    const tpl = params.get('tpl');
    if (tpl) {
      // call ensure_defaults then try to load template by name; handle parse errors
      fetch('api/email-templates.php?action=ensure_defaults')
        .then(()=> fetch('api/email-templates.php?action=get_by_name&name=' + encodeURIComponent(
          tpl === 'welcome' ? 'Séquence de Bienvenue' : (tpl === 'abandoned_cart' ? 'Panier Abandonné' : (tpl === 'birthday' ? 'Anniversaire Client' : tpl))
        )))
        .then(async r => {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          const text = await r.text();
          if (text.trim().startsWith('<')) {
            console.error('Réponse non JSON reçue pour get_by_name:', text.slice(0,400));
            return;
          }
          const j = JSON.parse(text);
          if (j.success && j.template) {
            const nameEl = document.getElementById('tplName');
            const subjEl = document.getElementById('tplSubject');
            if (nameEl) nameEl.value = j.template.name || '';
            if (subjEl) subjEl.value = j.template.subject || '';
            if (typeof tinymce !== 'undefined' && tinymce.get('editor')) tinymce.get('editor').setContent(j.template.content_html || '');
            currentTemplateId = j.template.id;
          }
        })
        .catch(e => console.error('template load error', e));
    }
  } catch (e) {
    console.error(e);
  }

  // guarded event binding
  const elTemplateType = document.getElementById('templateType');
  if (elTemplateType) elTemplateType.addEventListener('change', loadTemplates);
  const elSave = document.getElementById('saveTemplateBtn');
  if (elSave) elSave.addEventListener('click', saveTemplate);
  const elNew = document.getElementById('newTemplateBtn');
  if (elNew) elNew.addEventListener('click', newTemplate);
  const elDelete = document.getElementById('deleteTemplateBtn');
  if (elDelete) elDelete.addEventListener('click', deleteTemplate);
  const elUpload = document.getElementById('uploadImageBtn');
  if (elUpload) elUpload.addEventListener('click', uploadImage);
});
