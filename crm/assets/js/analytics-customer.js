
document.addEventListener('DOMContentLoaded', function() {
    console.log('Analytics Customer page loaded');

    // Configuration des graphiques
    initCustomerAnalytics();
    loadCustomerData();
    setupFilterHandlers();
    observeChartContainers();
    observeCustomerJourney();
});



function initCustomerAnalytics() {
    // Évolution du comportement (canvas id="behaviorTrendChart")
    if (document.getElementById('behaviorTrendChart')) {
        const ctx = document.getElementById('behaviorTrendChart').getContext('2d');
        try { if (window.behaviorChart && window.behaviorChart.destroy) window.behaviorChart.destroy(); } catch(e){}
        window.behaviorChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan','Fév','Mar','Avr','Mai','Jun'],
                datasets: [{
                    label: 'Évolution du comportement',
                    data: [10, 12, 9, 14, 13, 15],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0,123,255,0.08)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: false } }
            }
        });
    }

    // Graphique de segmentation des clients (existant)
    if (document.getElementById('segmentChart') || document.getElementById('customerSegmentChart')) {
        const id = document.getElementById('segmentChart') ? 'segmentChart' : 'customerSegmentChart';
        const ctx = document.getElementById(id).getContext('2d');
        try { if (window.segmentChart && window.segmentChart.destroy) window.segmentChart.destroy(); } catch(e){}
        window.segmentChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Nouveaux', 'Actifs', 'Fidèles', 'À risque', 'Inactifs'],
                datasets: [{ data: [25, 35, 20, 15, 5], backgroundColor: ['#28a745','#17a2b8','#ffc107','#fd7e14','#dc3545'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // Chart RFM (nouveau)
    if (document.getElementById('rfmChart')) {
        const ctxRfm = document.getElementById('rfmChart').getContext('2d');
        try { if (window.rfmChart && window.rfmChart.destroy) window.rfmChart.destroy(); } catch(e){}
        window.rfmChart = new Chart(ctxRfm, {
            type: 'bar',
            data: {
                labels: ['1','2','3','4','5'],
                datasets: [{ label: 'Répartition RFM', data: [10,20,30,25,15], backgroundColor: '#17a2b8' }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
        });
    }

    // Chart Risque de churn (nouveau)
    if (document.getElementById('churnRiskChart')) {
        const ctxChurn = document.getElementById('churnRiskChart').getContext('2d');
        try { if (window.churnChart && window.churnChart.destroy) window.churnChart.destroy(); } catch(e){}
        window.churnChart = new Chart(ctxChurn, {
            type: 'pie',
            data: {
                labels: ['Faible','Moyen','Élevé','Inconnu'],
                datasets: [{ data: [120,40,10,30], backgroundColor: ['#28a745','#ffc107','#fd7e14','#6c757d'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // (optionnel) ancien satisfactionTrendChart -> si vous l'avez gardé
    if (document.getElementById('satisfactionTrendChart')) {
        const ctx2 = document.getElementById('satisfactionTrendChart').getContext('2d');
        try { if (window.satisfactionChart && window.satisfactionChart.destroy) window.satisfactionChart.destroy(); } catch(e){}
        window.satisfactionChart = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: ['Jan','Fév','Mar','Avr','Mai','Jun'],
                datasets: [{ label: 'Score de satisfaction', data: [4.2,4.3,4.1,4.5,4.4,4.6], borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,0.1)', tension: 0.4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: false, min: 3, max: 5 } } }
        });
    }
}



function observeCustomerJourney() {
    const el = document.getElementById('customer-journey');
    if (!el) return;
    const apply = debounce(() => adjustCustomerJourneyLayout(), 100);

    if (typeof ResizeObserver !== 'undefined') {
        // garder référence si besoin de disconnect plus tard
        if (!el._journeyRO) el._journeyRO = new ResizeObserver(apply);
        try { el._journeyRO.observe(el); } catch(e){ /* noop */ }
        window.addEventListener('resize', apply);
    } else {
        window.addEventListener('resize', apply);
    }

    // layout initial
    adjustCustomerJourneyLayout();
}


function adjustCustomerJourneyLayout() {
    const el = document.getElementById('customer-journey');
    if (!el) return;
    const w = el.clientWidth || el.getBoundingClientRect().width;
    // seuil : si trop étroit on passe en mode empilé
    const compactThreshold = 640;
    if (w < compactThreshold) el.classList.add('journey-compact');
    else el.classList.remove('journey-compact');
}

function updateChartsFromApi(data) {
    // comportement / trend
    const trend = data.behavior_trend ?? data.behaviorTrend ?? [];
    if (window.behaviorChart && Array.isArray(trend) && trend.length) {
        window.behaviorChart.data.labels = trend.map(r => r.period ?? r.label ?? '');
        window.behaviorChart.data.datasets[0].data = trend.map(r => Number(r.cnt ?? r.value ?? 0));
        window.behaviorChart.update();
    }

    // segments
    const segments = data.segments ?? data.segment ?? [];
    if (window.segmentChart && Array.isArray(segments) && segments.length) {
        window.segmentChart.data.labels = segments.map(s => s.segment ?? s.label ?? '');
        window.segmentChart.data.datasets[0].data = segments.map(s => Number(s.count ?? s.value ?? 0));
        window.segmentChart.update();
    }

    // rfm
    const rfm = data.rfm ?? data.rfm_data ?? [];
    if (window.rfmChart && Array.isArray(rfm) && rfm.length) {
        window.rfmChart.data.labels = rfm.map(r => r.score ?? r.label ?? '');
        window.rfmChart.data.datasets[0].data = rfm.map(r => Number(r.count ?? 0));
        window.rfmChart.update();
    }

    // churn
    const churn = data.churn ?? data.churn_data ?? [];
    if (window.churnChart && Array.isArray(churn) && churn.length) {
        window.churnChart.data.labels = churn.map(c => c.risk ?? c.label ?? '');
        window.churnChart.data.datasets[0].data = churn.map(c => Number(c.count ?? 0));
        window.churnChart.update();
    }
}


function loadCustomerData() {
    fetch('api/analytics_customer.php')
        .then(response => {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                updateCustomerMetrics(data.metrics || {});
                updateCustomerTable(data.customers || []);
            } else {
                console.error('API error', data);
                showDemoData();
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des données:', error);
            showDemoData();
        });
}

function updateCustomerMetrics(metrics) {
    // adaptation aux clés renvoyées par api/analytics_customer.php
    const avgEl = document.getElementById('avg-clv');
    const retEl = document.getElementById('retention-rate');
    const freqEl = document.getElementById('purchase-frequency');
    const engEl = document.getElementById('engagement-score');

    if (avgEl) avgEl.textContent = metrics.avg_clv !== undefined ? '€' + Number(metrics.avg_clv).toLocaleString() : '—';
    if (retEl) retEl.textContent = metrics.retention_rate !== undefined ? metrics.retention_rate + '%' : '—';
    if (freqEl) freqEl.textContent = metrics.purchase_frequency !== undefined ? metrics.purchase_frequency : '—';
    if (engEl) engEl.textContent = metrics.engagement_score !== undefined ? metrics.engagement_score : '—';
}

function updateCustomerTable(customers) {
    const tableBody = document.querySelector('#customer-analysis-table tbody');
    if (!tableBody) return;

    if (!Array.isArray(customers) || customers.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Aucun client disponible</td></tr>';
        return;
    }

    tableBody.innerHTML = customers.map(customer => {
        const clv = customer.clv ?? customer.total_spent ?? customer.totalSpent ?? 0;
        const orders = customer.orders ?? customer.order_count ?? 0;
        const last = customer.last_purchase ?? customer.lastPurchase ?? customer.updated_at ?? '';
        const segment = customer.segment ?? '—';
        const satisfaction = customer.satisfaction_score ?? customer.satisfaction ?? 0;
        const id = customer.id ?? '';

        return `
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-2">
                        <img src="${customer.avatar || 'assets/img/default-avatar.png'}" class="rounded-circle" width="32" height="32" alt="avatar">
                    </div>
                    <div>
                        <div class="fw-bold">${escapeHtml(customer.name || '')}</div>
                        <small class="text-muted">${escapeHtml(customer.email || '')}</small>
                    </div>
                </div>
            </td>
            <td><span class="badge bg-${getSegmentColor(segment)}">${escapeHtml(segment)}</span></td>
            <td>€${Number(clv).toLocaleString()}</td>
            <td>${Number(orders)}</td>
            <td>${escapeHtml(last)}</td>
            <td>
                <div class="d-flex align-items-center">
                    <span class="me-2">${escapeHtml(String(satisfaction))}</span>
                    <div class="progress" style="width: 60px; height: 8px;">
                        <div class="progress-bar" style="width: ${Math.min(100, (Number(satisfaction) || 0) * 20)}%"></div>
                    </div>
                </div>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="viewCustomerDetails('${escapeAttr(id)}')">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

function getSegmentColor(segment) {
    const colors = { 'Nouveau':'success','Actif':'info','Fidèle':'warning','À risque':'danger','Inactif':'secondary' };
    return colors[segment] || 'secondary';
}

function showDemoData() {
    updateCustomerMetrics({ avg_clv: 1850, retention_rate: 92.1, purchase_frequency: 3.4, engagement_score: 4.2 });
    const demo = [{
        id: '1', name: 'Demo Client', email: 'demo@example.com', segment: 'Actif',
        total_spent: 1234.56, orders: 5, last_purchase: '2025-10-01', satisfaction_score: 4.5
    }];
    updateCustomerTable(demo);
}

function setupFilterHandlers() {
    const filters = ['segmentFilter','periodFilter','sortFilter'];
    filters.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', applyFilters);
    });
    const searchInput = document.getElementById('customerSearch');
    if (searchInput) searchInput.addEventListener('input', debounce(applyFilters, 300));
}

function applyFilters() {
    // pour l'instant on recharge les données ; on peut ajouter query params si besoin
    loadCustomerData();
}


function viewCustomerDetails(customerId) {
    const id = String(customerId || '').trim();
    if (!id) { console.warn('viewCustomerDetails: id manquant'); return; }

    const customers = window._customerData || [];
    const c = customers.find(x => String(x.id) === id);
    if (!c) {
        alert('Détails non trouvés pour le client id=' + id);
        return;
    }

    // Remplir la modal
    const modalTitle = document.getElementById('customerDetailsModalLabel');
    const body = document.getElementById('customerDetailsModalBody');
    if (modalTitle) modalTitle.textContent = c.name || 'Détails client';
    if (body) {
        body.innerHTML = `
            <div class="row">
                <div class="col-md-3 text-center">
                    <img src="${escapeAttr(c.avatar || 'assets/img/default-avatar.png')}" class="rounded-circle" width="96" height="96" alt="avatar">
                </div>
                <div class="col-md-9">
                    <p><strong>Email:</strong> ${escapeHtml(c.email || '—')}</p>
                    <p><strong>Segment:</strong> ${escapeHtml(c.segment ?? '—')}</p>
                    <p><strong>CLV:</strong> €${Number(c.clv ?? c.total_spent ?? 0).toLocaleString()}</p>
                    <p><strong>Score RFM:</strong> ${escapeHtml(String(c.score_rfm ?? c.score ?? '—'))}</p>
                    <p><strong>Risque churn:</strong> ${escapeHtml(String(c.churn_risk ?? c.churn ?? '—'))}</p>
                    <p><strong>Dernière activité:</strong> ${escapeHtml(String(c.last_purchase ?? c.updated_at ?? '—'))}</p>
                </div>
            </div>
        `;
    }

    // Utilise Bootstrap modal si présent
    if (typeof bootstrap !== 'undefined' && document.getElementById('customerDetailsModal')) {
        const modalEl = document.getElementById('customerDetailsModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    } else {
        // fallback simple
        alert(`Client: ${c.name}\nEmail: ${c.email}\nSegment: ${c.segment || '—'}`);
    }
}


function exportReport() {
    // Exposé global utilisé par analytics-customer.php
    // simple export CSV de la table affichée
    const rows = [];
    const tableBody = document.querySelector('#customer-analysis-table tbody');
    if (!tableBody) return;
    tableBody.querySelectorAll('tr').forEach(tr => {
        const cols = Array.from(tr.querySelectorAll('td')).map(td => td.innerText.trim());
        if (cols.length) rows.push(cols.join(','));
    });
    if (!rows.length) return;
    const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'customers_report.csv'; document.body.appendChild(a); a.click();
    URL.revokeObjectURL(url); a.remove();
}

function refreshAnalytics() { loadCustomerData(); }

// petites helpers pour sécurité XSS minimale
function escapeHtml(str) { return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[s]); }
function escapeAttr(str) { return String(str).replace(/['"]/g, ''); }

// debounce util
function debounce(func, wait) {
    let timeout;
    return function(...args) { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), wait); };
}
