<?php
include __DIR__ . '/includes/shifts.php';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Planning - ERP</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    :root{
      --accent:#3b82f6;
      --muted:#64748b;
      --bg:#f0f4f8;
      --hour-h:60px;
      --company-blue:#3b82f6;
      --company-bg:#dbeafe;
      --employee-orange:#f59e0b;
      --employee-bg:#fef3c7;
    }
    body{background:var(--bg);font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
    .container{max-width:1400px;margin:24px auto;padding:0 20px}
    h1{margin:16px 0 24px;font-size:28px;font-weight:800;background:linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .controls{
      display:flex;gap:12px;align-items:center;margin-bottom:20px;flex-wrap:wrap;
      background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);
      padding:20px;border-radius:16px;box-shadow:0 10px 40px rgba(16,24,40,0.08);
    }
    .btn{padding:10px 16px;border-radius:12px;border:none;cursor:pointer;font-weight:600;font-size:14px;transition:all 0.3s}
    .btn-primary{background:linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);color:#fff;box-shadow:0 4px 16px rgba(59,130,246,0.3)}
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(59,130,246,0.4)}
    .btn-ghost{background:#fff;border:2px solid #e2e8f0;color:#3b82f6}
    .btn-ghost:hover{background:rgba(59,130,246,0.05);border-color:#3b82f6}
    .calendar-wrap{
      display:flex;gap:16px;
      background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);
      border-radius:16px;padding:20px;
      box-shadow:0 10px 40px rgba(16,24,40,0.08);
    }
    .hours-col{width:80px;border-right:2px solid #e2e8f0;padding-right:12px}
    .hours-col .hour{height:var(--hour-h);display:flex;align-items:flex-start;color:var(--muted);font-size:13px;font-weight:600;padding-top:8px}
    .days-col{flex:1;display:flex;flex-direction:column}
    .days-head{display:flex;border-bottom:3px solid #e2e8f0}
    .days-head .day-head{flex:1;padding:12px;font-weight:700;font-size:14px;text-align:center;color:#0f172a}
    .grid{display:flex;flex:1;min-height: calc(var(--hour-h) * 14); }
    .day{flex:1;border-left:1px solid #e2e8f0;position:relative;background:linear-gradient(to bottom, transparent 0, transparent 99%, rgba(2,6,23,0.01) 100%);}
    .cell{height:var(--hour-h);border-bottom:1px dashed #e2e8f0}
    .selection{
      position:absolute;left:8px;right:8px;
      background:rgba(59,130,246,0.15);
      border:2px solid rgba(59,130,246,0.3);
      border-radius:8px;pointer-events:none;z-index:5;
    }
    .shift{
      position:absolute;left:8px;right:8px;padding:8px 12px;
      border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.1);
      z-index:10;cursor:pointer;overflow:hidden;
      transition:all 0.3s;
    }
    .shift:hover{transform:scale(1.03);box-shadow:0 8px 20px rgba(0,0,0,0.15)}
    .shift.company{ background:var(--company-bg); border-left:4px solid var(--company-blue); color: #0b1220; }
    .shift.employee{ background:var(--employee-bg); border-left:4px solid var(--employee-orange); color:#2b1b00; }
    .shift.mission{ 
      background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); 
      border-left:4px solid #10b981; 
      color:#064e3b;
      border:1px solid rgba(16, 185, 129, 0.3);
    }
    .shift.mission:hover{
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    }
    .shift .title{font-weight:700;font-size:13px;margin-bottom:4px}
    .shift .meta{font-size:12px;color:var(--muted);margin-top:4px;white-space:nowrap;text-overflow:ellipsis;overflow:hidden}
    .modal{
      position:fixed;left:0;top:0;right:0;bottom:0;
      background:rgba(2,6,23,.6);backdrop-filter:blur(10px);
      display:none;align-items:center;justify-content:center;padding:20px;z-index:60;
    }
    .modal .box{
      background:#fff;padding:28px;border-radius:16px;
      min-width:400px;max-width:800px;width:100%;
      box-shadow:0 20px 60px rgba(2,6,23,.2);
    }
    .modal .box h3{margin:0 0 20px;font-size:20px;font-weight:700}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .form-field{display:flex;flex-direction:column}
    .form-field label{font-weight:600;font-size:13px;margin-bottom:8px;color:#0f172a}
    .form-field input, .form-field select{
      width:100%;padding:10px 14px;
      border:2px solid #e2e8f0;border-radius:10px;
      background:#fff;outline:none;font-size:14px;
      transition:all 0.3s;
    }
    .form-field input:focus, .form-field select:focus{
      border-color:#3b82f6;
      box-shadow:0 0 0 3px rgba(59,130,246,0.1);
    }
    .form-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:20px}
    .hidden { display: none !important; }
    select{padding:10px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:14px;background:#fff}
    select:focus{border-color:#3b82f6;outline:none;box-shadow:0 0 0 3px rgba(59,130,246,0.1)}
    label{font-size:13px;font-weight:600;color:#64748b;margin-right:8px}
    @media (max-width:900px){ 
      .calendar-wrap{flex-direction:column} 
      .hours-col{display:none} 
      .days-head .day-head{font-size:13px;padding:8px} 
      .grid{min-height: calc(var(--hour-h) * 10)}
      .controls{flex-direction:column;align-items:stretch}
    }
  </style>
</head>
<body>
    <?php include 'erp_nav.php'; ?>
<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div>
      <h1><i class="fas fa-calendar-alt"></i> Planning & Shifts</h1>
      <p style="color:#64748b;font-size:14px">Gestion intelligente du planning synchronisé avec les missions</p>
    </div>
    <div style="display:flex;gap:12px">
      <a href="missions.php" class="btn btn-ghost">
        <i class="fas fa-tasks"></i> Voir Missions
      </a>
      <button id="btnAdd" class="btn btn-primary">
        <i class="fas fa-plus"></i> Ajouter un créneau
      </button>
    </div>
  </div>

  <div class="controls">
    <label>Sem. départ <input type="date" id="weekStart"></label>
    <button id="prevWeek" class="btn btn-ghost">‹ Semaine préc.</button>
    <button id="nextWeek" class="btn btn-ghost">Semaine suiv. ›</button>
    <button id="btnToday" class="btn btn-ghost">Aujourd'hui</button>

    <label style="margin-left:8px">Vue
      <select id="viewMode">
        <option value="employee">Personnel</option>
        <option value="company">Sociétés</option>
      </select>
    </label>

    <label style="margin-left:8px">Filtrer employé
      <select id="filterEmployee">
        <option value="">Tous</option>
        <?php foreach ($employees as $e): ?>
          <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['last_name'].' '.$e['first_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label style="margin-left:8px">Filtrer société
      <select id="filterCompany">
        <option value="">Toutes</option>
        <?php foreach ($companies as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <div style="margin-left:auto">
      <button id="btnAdd" class="btn btn-primary">Ajouter un créneau</button>
    </div>
  </div>

  <div class="calendar-wrap" id="calendarWrap" aria-live="polite">
    <div class="hours-col" id="hoursCol" aria-hidden="true"></div>
    <div class="days-col" style="flex:1">
      <div class="days-head" id="daysHead"></div>
      <div class="grid" id="grid"></div>
    </div>
  </div>
</div>

<!-- modal -->
<div class="modal" id="modal">
  <div class="box">
    <h3 id="modalTitle">Ajouter créneau</h3>
    <form id="shiftForm" novalidate>
      <input type="hidden" name="id" id="shiftId">
      <input type="hidden" name="view" id="formView" value="employee">
      <div class="form-grid">
        <div class="form-field" id="fieldEmployee">
          <label for="employeeSelect">Employé</label>
          <select name="employee_id" id="employeeSelect">
            <option value="">-- Sélectionner --</option>
            <?php foreach ($employees as $e): ?>
              <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['last_name'].' '.$e['first_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field" id="fieldCompany">
          <label for="companySelect">Société (facultatif)</label>
          <select name="company_id" id="companySelect">
            <option value="">-- Aucune --</option>
            <?php foreach ($companies as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-field">
          <label for="startDatetime">Début</label>
          <input type="datetime-local" name="start_datetime" id="startDatetime" required>
        </div>
        <div class="form-field">
          <label for="endDatetime">Fin</label>
          <input type="datetime-local" name="end_datetime" id="endDatetime" required>
        </div>

        <div class="form-field">
          <label for="role">Rôle</label>
          <input name="role" id="role" placeholder="Ex: Réception, Support...">
        </div>
        <div class="form-field">
          <label for="notes">Notes</label>
          <input name="notes" id="notes" placeholder="Infos complémentaires">
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <button type="button" id="btnDelete" class="btn btn-danger" style="display:none">Supprimer</button>
        <button type="button" id="btnClose" class="btn btn-ghost">Fermer</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const hoursCol = document.getElementById('hoursCol');
  const daysHead = document.getElementById('daysHead');
  const grid = document.getElementById('grid');
  const weekStartInput = document.getElementById('weekStart');
  const filterCompany = document.getElementById('filterCompany');
  const filterEmployee = document.getElementById('filterEmployee');
  const viewModeEl = document.getElementById('viewMode');
  const modal = document.getElementById('modal');
  const shiftForm = document.getElementById('shiftForm');
  const btnAdd = document.getElementById('btnAdd');
  const btnClose = document.getElementById('btnClose');
  const btnDelete = document.getElementById('btnDelete');
  const modalTitle = document.getElementById('modalTitle');

  const fieldEmployee = document.getElementById('fieldEmployee');
  const fieldCompany = document.getElementById('fieldCompany');
  const employeeSelect = document.getElementById('employeeSelect');
  const companySelect = document.getElementById('companySelect');
  const formView = document.getElementById('formView');

  const HOUR_HEIGHT = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--hour-h')) || 60;
  const VISIBLE_HOURS = 19;
  let visibleStartHour = 5;
  let visibleEndHour = visibleStartHour + VISIBLE_HOURS;
  let currentView = viewModeEl.value || 'employee';

  function startOfWeek(d){
    const date = new Date(d);
    const day = date.getDay() || 7;
    if (day !== 1) date.setDate(date.getDate() - (day - 1));
    date.setHours(0,0,0,0);
    return date;
  }
  function pad(n){ return String(n).padStart(2,'0'); }
  function formatDateISO(d){ return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }

  function buildGrid(days){
    hoursCol.innerHTML = '';
    for(let h = visibleStartHour; h < visibleEndHour; h++){
      const div = document.createElement('div');
      div.className = 'hour';
      div.textContent = `${pad(h)}:00`;
      div.style.height = HOUR_HEIGHT + 'px';
      hoursCol.appendChild(div);
    }
    daysHead.innerHTML = '';
    days.forEach(d=>{
      const hd = document.createElement('div');
      hd.className = 'day-head';
      hd.textContent = d.toLocaleDateString('fr-FR',{weekday:'short', day:'2-digit', month:'2-digit'});
      daysHead.appendChild(hd);
    });
    grid.innerHTML = '';
    days.forEach(d=>{
      const dayCol = document.createElement('div');
      dayCol.className = 'day';
      dayCol.dataset.date = formatDateISO(d);
      dayCol.style.minHeight = (HOUR_HEIGHT * (visibleEndHour - visibleStartHour)) + 'px';
      for(let h = visibleStartHour; h < visibleEndHour; h++){
        const cell = document.createElement('div');
        cell.className = 'cell';
        cell.dataset.hour = h;
        cell.style.height = HOUR_HEIGHT + 'px';
        dayCol.appendChild(cell);
      }
      attachSelectionHandlers(dayCol);
      grid.appendChild(dayCol);
    });
  }

  function minutesToPx(min){ return (min / 60) * HOUR_HEIGHT; }

  function addShiftToDOM(s){
    const day = s.start_datetime.slice(0,10);
    const dayCol = [...grid.children].find(c => c.dataset.date === day);
    if (!dayCol) return;
    const start = new Date(s.start_datetime);
    const end = new Date(s.end_datetime);
    const startMinutes = (start.getHours() * 60) + start.getMinutes();
    const endMinutes = (end.getHours() * 60) + end.getMinutes();
    const top = minutesToPx(Math.max(0, startMinutes - visibleStartHour*60));
    const height = minutesToPx(Math.max(30, endMinutes - startMinutes));
    const el = document.createElement('div');
    // class selon type (employé présent => orange, sinon company => bleu)
    el.className = 'shift ' + (s.employee_id ? 'employee' : 'company');
    el.style.top = top + 'px';
    el.style.height = height + 'px';
    el.dataset.id = s.id;

    if (currentView === 'company') {
      const title = s.company_name ? escapeHtml(s.company_name) : (escapeHtml(s.last_name || '')+' '+escapeHtml(s.first_name || ''));
      el.innerHTML = `<div class="title">${title}</div>
                      <div class="meta">${s.start_datetime.slice(11,16)} - ${s.end_datetime.slice(11,16)} ${s.last_name ? ' · '+escapeHtml(s.last_name)+' '+escapeHtml(s.first_name) : ''}</div>`;
    } else {
      const title = (s.last_name || '') + (s.first_name ? ' '+s.first_name : '');
      el.innerHTML = `<div class="title">${escapeHtml(title.trim())}</div>
                      <div class="meta">${s.start_datetime.slice(11,16)} - ${s.end_datetime.slice(11,16)} ${s.company_name ? ' · ' + escapeHtml(s.company_name) : ''}</div>`;
    }

    el.addEventListener('click', ()=> openEdit(s));
    dayCol.appendChild(el);
  }

  function addMissionToDOM(m){
    // Afficher la mission sur le planning (différent des shifts)
    const missionDate = m.datetime.slice(0,10);
    const dayCol = [...grid.children].find(c => c.dataset.date === missionDate);
    if (!dayCol) return;
    
    const start = new Date(m.datetime);
    const startMinutes = (start.getHours() * 60) + start.getMinutes();
    // Par défaut, une mission dure 2h si on n'a pas de fin
    const endMinutes = startMinutes + 120;
    const top = minutesToPx(Math.max(0, startMinutes - visibleStartHour*60));
    const height = minutesToPx(120); // 2h par défaut
    
    const el = document.createElement('div');
    el.className = 'shift mission'; // classe 'mission' pour un style différent
    el.style.top = top + 'px';
    el.style.height = height + 'px';
    el.dataset.missionId = m.mission_id;
    
    const title = m.company_name || '';
    const route = (m.departure || '') + (m.arrival ? ' → ' + m.arrival : '');
    const driver = m.driver || '';
    const vehicle = m.vehicle || '';
    const status = m.status_name || '';
    
    el.innerHTML = `<div class="title"><i class="fas fa-route"></i> ${escapeHtml(title)}</div>
                    <div class="meta">${m.datetime.slice(11,16)} · ${escapeHtml(route)}</div>
                    ${driver ? `<div class="meta"><i class="fas fa-user"></i> ${escapeHtml(driver)}</div>` : ''}
                    ${vehicle ? `<div class="meta"><i class="fas fa-truck"></i> ${escapeHtml(vehicle)}</div>` : ''}`;
    
    el.addEventListener('click', ()=> {
      alert(`Mission CRM #${m.mission_id}\n${route}\nStatut: ${status}\nChauffeur: ${driver}\nVéhicule: ${vehicle}\n\nNotes: ${m.notes || 'Aucune'}`);
    });
    
    dayCol.appendChild(el);
  }

  function clearShifts(){ 
    [...grid.querySelectorAll('.shift')].forEach(n=>n.remove());
    [...grid.querySelectorAll('.mission')].forEach(n=>n.remove());
  }

  function fetchAndRender(startDate, endDate){
    const companyParam = filterCompany.value ? '&company_id='+encodeURIComponent(filterCompany.value) : '';
    const employeeParam = filterEmployee.value ? '&employee_id='+encodeURIComponent(filterEmployee.value) : '';
    let extra = '';
    if (currentView === 'employee') extra = employeeParam;
    else extra = companyParam;
    fetch('shifts.php?action=fetch&start='+formatDateISO(startDate)+'&end='+formatDateISO(endDate) + extra)
      .then(r=>r.json())
      .then(data=>{
        clearShifts();
        // Afficher les shifts
        if(data.shifts) {
          data.shifts.forEach(addShiftToDOM);
        }
        // Afficher les missions
        if(data.missions) {
          data.missions.forEach(addMissionToDOM);
        }
      });
  }

  function loadWeek(base){
    const start = startOfWeek(base);
    const days = [];
    for(let i=0;i<7;i++){ const d = new Date(start); d.setDate(start.getDate()+i); days.push(d); }
    buildGrid(days);
    weekStartInput.value = formatDateISO(days[0]);
    fetchAndRender(days[0], days[6]);
  }

  function attachSelectionHandlers(dayCol){
    let selecting = false;
    let selStartY = 0;
    let selEl = null;

    function dayYToTime(y){
      const minutes = Math.round((y / HOUR_HEIGHT) * 60) + visibleStartHour*60;
      return Math.max(0, Math.round(minutes / 15) * 15);
    }

    dayCol.addEventListener('mousedown', (e)=>{
      // n'autorise la sélection par glisser que en vue "employee"
      if (currentView !== 'employee') return;
      if (e.button !== 0) return;
      selecting = true;
      const rect = dayCol.getBoundingClientRect();
      selStartY = e.clientY - rect.top;
      selEl = document.createElement('div');
      selEl.className = 'selection';
      selEl.style.top = (selStartY) + 'px';
      selEl.style.height = '2px';
      dayCol.appendChild(selEl);
      document.body.style.userSelect = 'none';
    });

    window.addEventListener('mousemove', (e)=>{
      if (!selecting || !selEl) return;
      const rect = dayCol.getBoundingClientRect();
      const y = Math.max(0, Math.min(rect.height, e.clientY - rect.top));
      const top = Math.min(selStartY, y);
      const bottom = Math.max(selStartY, y);
      selEl.style.top = top + 'px';
      selEl.style.height = (bottom - top) + 'px';
    });

    window.addEventListener('mouseup', (e)=>{
      if (!selecting || !selEl) return;
      const rect = dayCol.getBoundingClientRect();
      const endY = Math.max(0, Math.min(rect.height, e.clientY - rect.top));
      const startMinutes = dayYToTime(selStartY);
      const endMinutes = dayYToTime(endY);
      const dayDate = dayCol.dataset.date;
      const start = new Date(`${dayDate}T00:00:00`);
      start.setMinutes(startMinutes);
      const end = new Date(`${dayDate}T00:00:00`);
      end.setMinutes(endMinutes > startMinutes ? endMinutes : startMinutes + 60);
      openAddWithRange(start, end);
      selEl.remove();
      selEl = null;
      selecting = false;
      document.body.style.userSelect = '';
    });

    dayCol.addEventListener('click', (e)=>{
      // clic simple ouvre modal (autorisé pour les deux vues)
      if (e.detail && e.detail > 1) return;
      const rect = dayCol.getBoundingClientRect();
      const y = e.clientY - rect.top;
      const minutes = Math.round((y / HOUR_HEIGHT) * 60) + visibleStartHour*60;
      const startMin = Math.round(minutes / 15) * 15;
      const start = new Date(`${dayCol.dataset.date}T00:00:00`);
      start.setMinutes(startMin);
      const end = new Date(start.getTime() + 60*60*1000);
      openAddWithRange(start, end);
    });
  }

  function openAddWithRange(start, end){
    modal.style.display = 'flex';
    modalTitle.textContent = 'Ajouter créneau';
    document.getElementById('shiftId').value = '';
    // préremplissage selon vue et filtres
    if (currentView === 'employee') {
      employeeSelect.value = filterEmployee.value || '';
      companySelect.value = '';
    } else {
      employeeSelect.value = '';
      companySelect.value = filterCompany.value || '';
    }
    document.getElementById('startDatetime').value = toLocalInput(start);
    document.getElementById('endDatetime').value = toLocalInput(end);
    document.getElementById('role').value = '';
    document.getElementById('notes').value = '';
    btnDelete.style.display='none';
    adaptFormForView(currentView);
  }

  function openEdit(s){
    modal.style.display='flex';
    modalTitle.textContent = 'Modifier créneau';
    document.getElementById('shiftId').value = s.id;
    employeeSelect.value = s.employee_id || '';
    companySelect.value = s.company_id || '';
    document.getElementById('startDatetime').value = s.start_datetime.replace(' ', 'T').slice(0,16);
    document.getElementById('endDatetime').value = s.end_datetime.replace(' ', 'T').slice(0,16);
    document.getElementById('role').value = s.role || '';
    document.getElementById('notes').value = s.notes || '';
    btnDelete.style.display='inline-block';
    adaptFormForView(currentView);
  }

  function adaptFormForView(view){
    formView.value = view;
    if (view === 'employee') {
      fieldEmployee.classList.remove('hidden');
      employeeSelect.required = true;
      fieldCompany.classList.remove('hidden');
      companySelect.required = false;
    } else {
      fieldEmployee.classList.add('hidden');
      employeeSelect.required = false;
      fieldCompany.classList.remove('hidden');
      companySelect.required = true;
    }
  }

  function toLocalInput(d){ const dt = new Date(d); const y = dt.getFullYear(), m = pad(dt.getMonth()+1), day = pad(dt.getDate()), hh = pad(dt.getHours()), mm = pad(dt.getMinutes()); return `${y}-${m}-${day}T${hh}:${mm}`; }
  function escapeHtml(s){ return s ? s.replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])) : ''; }

  shiftForm.addEventListener('submit', function(e){
    e.preventDefault();
    const id = document.getElementById('shiftId').value;
    const form = new FormData(shiftForm);
    form.append('view', currentView);
    const action = id ? 'update' : 'create';
    if (id) form.append('id', id);
    fetch('shifts.php?action='+action, {method:'POST', body: form})
      .then(r=>r.json()).then(res=>{
        if (res.error){ alert(res.error); return; }
        modal.style.display='none';
        loadWeek(new Date(weekStartInput.value));
      }).catch(()=>{ alert('Erreur réseau'); });
  });

  document.getElementById('btnDelete').addEventListener('click', function(){
    if (!confirm('Supprimer ce créneau ?')) return;
    const id = document.getElementById('shiftId').value;
    const form = new FormData(); form.append('id', id);
    fetch('shifts.php?action=delete', {method:'POST', body: form})
      .then(r=>r.json()).then(()=>{ modal.style.display='none'; loadWeek(new Date(weekStartInput.value)); });
  });

  btnAdd.addEventListener('click', ()=> {
    const now = new Date();
    openAddWithRange(new Date(now.getFullYear(), now.getMonth(), now.getDate(), 9, 0), new Date(now.getFullYear(), now.getMonth(), now.getDate(), 17, 0));
  });

  btnClose.addEventListener('click', ()=> modal.style.display='none');

  document.getElementById('prevWeek').addEventListener('click', ()=> { const d = new Date(weekStartInput.value); d.setDate(d.getDate()-7); loadWeek(d); });
  document.getElementById('nextWeek').addEventListener('click', ()=> { const d = new Date(weekStartInput.value); d.setDate(d.getDate()+7); loadWeek(d); });
  document.getElementById('btnToday').addEventListener('click', ()=> loadWeek(new Date()));
  weekStartInput.addEventListener('change', ()=> loadWeek(new Date(weekStartInput.value)));

  viewModeEl.addEventListener('change', ()=> {
    currentView = viewModeEl.value;
    filterEmployee.parentElement.style.display = currentView === 'employee' ? '' : 'none';
    adaptFormForView(currentView);
    loadWeek(new Date(weekStartInput.value));
  });

  filterEmployee.addEventListener('change', ()=> loadWeek(new Date(weekStartInput.value)));
  filterCompany.addEventListener('change', ()=> loadWeek(new Date(weekStartInput.value)));

  if (currentView !== 'employee') filterEmployee.parentElement.style.display = 'none';
  adaptFormForView(currentView);

  loadWeek(new Date());
})();
</script>

</body>
</html>
