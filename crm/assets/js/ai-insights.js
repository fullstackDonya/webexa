// ...existing code...
document.addEventListener('DOMContentLoaded', () => {
  const API = 'api/ai/insights.php'; // chemin relatif depuis /crm/

  async function loadInsights() {
    try {
      const res = await fetch(API, { cache: 'no-store' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      applyKPIs(data.kpis ?? {});
      renderAlerts(data.alerts ?? []);
      renderRecommendations(data.recommendations ?? []);
      renderSuggestions(data.suggested_actions ?? []);
      renderLeadsToScore(data.leads_to_score ?? []);
      renderMonthlyChart(data.monthly_predictions ?? []);
    } catch (err) {
      console.error('Erreur chargement insights:', err);
      // fallback minimal
      document.getElementById('ai-alerts').innerHTML = '<div class="alert alert-info">Impossible de charger les données IA.</div>';
    }
  }

  function applyKPIs(kpis) {
    const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setText('high-potential-leads', kpis.high_potential_leads ?? '0');
    setText('at-risk-opportunities', kpis.at_risk_opportunities ?? '0');
    setText('revenue-prediction', kpis.revenue_prediction ? `€${Number(kpis.revenue_prediction).toLocaleString()}` : '€0');
    setText('ai-confidence', (kpis.ai_confidence ?? 0) + '%');
  }

  function renderAlerts(list) {
    const container = document.getElementById('ai-alerts');
    if (!container) return;
    container.innerHTML = '';
    list.forEach(a => {
      const div = document.createElement('div');
      div.className = 'alert ' + (a.type === 'warning' ? 'alert-warning' : (a.type === 'danger' ? 'alert-danger' : 'alert-info'));
      div.textContent = a.message;
      container.appendChild(div);
    });
  }

  function renderRecommendations(list) {
    const container = document.getElementById('ai-recommendations');
    if (!container) return;
    container.innerHTML = '';
    const ul = document.createElement('ul');
    list.forEach(r => {
      const li = document.createElement('li');
      li.textContent = r;
      ul.appendChild(li);
    });
    container.appendChild(ul);
  }

  function renderSuggestions(list) {
    const c = document.getElementById('suggested-actions');
    if (!c) return;
    c.innerHTML = '';
    list.forEach(s => {
      const p = document.createElement('p');
      p.textContent = s;
      c.appendChild(p);
    });
  }

  function renderLeadsToScore(list) {
    const c = document.getElementById('leads-to-score');
    if (!c) return;
    c.innerHTML = '';
    if (list.length === 0) { c.textContent = 'Aucun lead à scorer.'; return; }
    const ul = document.createElement('ul');
    list.forEach(l => {
      const li = document.createElement('li');
      li.innerHTML = `${escapeHtml(l.name ?? '—')} ${l.email ? ' <small class="text-muted">&lt;' + escapeHtml(l.email) + '&gt;</small>' : ''}`;
      ul.appendChild(li);
    });
    c.appendChild(ul);
  }

  function escapeHtml(s){ return String(s).replace(/[&<>"']/g, (m)=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#39;' }[m])); }

  // Chart
  let chartInstance = null;
  function renderMonthlyChart(monthly) {
    const ctx = document.getElementById('prediction-chart');
    if (!ctx) return;
    const labels = monthly.map(m => m.month);
    const values = monthly.map(m => m.value);
    if (chartInstance) chartInstance.destroy();
    chartInstance = new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Revenus (€)',
          data: values,
          borderColor: '#4e73df',
          backgroundColor: 'rgba(78,115,223,0.08)',
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          y: { ticks: { callback: (v) => '€' + Number(v).toLocaleString() } }
        }
      }
    });
    // monthly predictions rendering
    const mp = document.getElementById('monthly-predictions');
    if (mp) {
      mp.innerHTML = monthly.map(m => `<div>${escapeHtml(m.month)} : <strong>€${Number(m.value).toLocaleString()}</strong></div>`).join('');
    }
  }


  // expose quick functions
  window.generateNewInsights = async function(options = { force: false }) {
    const API_RECOMPUTE = 'api/ai/generate-insights.php?action=recompute';
    const alerts = document.getElementById('ai-alerts');
    // helper notice
    const makeNotice = (cls, txt) => {
      const d = document.createElement('div');
      d.className = `alert ${cls}`;
      d.textContent = txt;
      return d;
    };

    let notice = null;
   try {
     if (alerts) {
       notice = makeNotice('alert-info', 'Génération des insights en cours...');
       alerts.prepend(notice);
     }

     // tenter de demander un recompute côté serveur (si pris en charge)
     const res = await fetch(API_RECOMPUTE, {
       method: 'POST',
       headers: { 'Content-Type': 'application/json' },
       body: JSON.stringify({ force: !!options.force }),
       cache: 'no-store'
     });

     if (!res.ok) {
       const txt = await res.text();
       throw new Error('HTTP ' + res.status + ' ' + txt.substring(0, 200));
     }

     // si réponse JSON, on peut l'utiliser ; sinon on se contente du refresh
     let payload = null;
     const ct = res.headers.get('content-type') || '';
     if (ct.includes('application/json')) payload = await res.json();

     if (alerts && notice) {
       notice.className = 'alert alert-success';
       notice.textContent = 'Insights générés.';
       setTimeout(() => notice.remove(), 2500);
     }
     
     // rafraîchir l'affichage avec les nouvelles données
     await loadInsights();
     return payload;
   } catch (err) {
     console.error('generateNewInsights error', err);
     if (alerts) {
       if (notice && notice.parentNode) notice.remove();
       const errN = makeNotice('alert-danger', 'Erreur génération insights — vérifie les logs serveur.');
       alerts.prepend(errN);
       setTimeout(() => errN.remove(), 6000);
     }
     // fallback : forcer un refresh malgré l'erreur
     try { await loadInsights(); } catch (_) {}
     return null;
   }
 };
  window.refreshInsights = function() { loadInsights(); };
  window.saveAISettings = function() {
    // For now save settings client side or send to server later
    const thresh = document.getElementById('confidence-threshold')?.value;
    const auto = document.getElementById('auto-scoring')?.checked;
    const alerts = document.getElementById('predictive-alerts')?.checked;
    console.log('save settings', {thresh, auto, alerts});
    // TODO: POST to server endpoint to persist
    alert('Paramètres enregistrés (démo).');
  };


  // threshold UI
  const threshold = document.getElementById('confidence-threshold');
  const thresholdValue = document.getElementById('threshold-value');
  if (threshold && thresholdValue) {
    threshold.addEventListener('input', (e) => thresholdValue.textContent = `${e.target.value}%`);
  }

  // initial load
  loadInsights();
});
// ...existing code...