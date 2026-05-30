// ...existing code...
document.addEventListener('DOMContentLoaded', function () {
    const endpointBase = 'api/sales-data.php';

    // DOM refs (protégés)
    const elMonthly = document.getElementById('monthly-sales');
    const elCount = document.getElementById('sales-count');
    const elAvg = document.getElementById('avg-ticket');
    const elGoal = document.getElementById('goal-achieved');
    const tableBody = document.querySelector('#salesAnalysisTable tbody');
    const periodButtons = Array.from(document.querySelectorAll('.btn-group [data-period]'));

    // Chart instances
    let salesEvolutionChart = null;
    let productChart = null;
    let teamChart = null;
    let seasonalChart = null;

    // helper safe set
    function safeText(el, v) { if (!el) return; el.textContent = v; }
    function escapeHtml(s){ if (!s && s !== 0) return ''; return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    function createCharts(labels = [], series = [], products = [], team = []) {
        // sales evolution
        const evoEl = document.getElementById('sales-evolution-chart');
        if (evoEl && window.Chart) {
            const ctx = evoEl.getContext('2d');
            if (salesEvolutionChart) salesEvolutionChart.destroy();
            salesEvolutionChart = new Chart(ctx, {
                type: 'line',
                data: { labels, datasets: [{ label: 'Ventes', data: series, borderColor: '#5a67d8', backgroundColor: 'rgba(90,103,216,0.08)', fill: true, tension: 0.25 }]},
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        // products donut (if provided)
        const prodEl = document.getElementById('product-sales-chart');
        if (prodEl && window.Chart && products && products.length) {
            const ctx = prodEl.getContext('2d');
            if (productChart) productChart.destroy();
            productChart = new Chart(ctx, {
                type: 'doughnut',
                data: { labels: products.map(p => p.name || '—'), datasets: [{ data: products.map(p => p.total || 0), backgroundColor: ['#ffc107','#17a2b8','#28a745','#6c757d','#007bff','#6610f2'] }]},
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        // team bar
        const teamEl = document.getElementById('team-performance-chart');
        if (teamEl && window.Chart && team && team.length) {
            const ctx = teamEl.getContext('2d');
            if (teamChart) teamChart.destroy();
            teamChart = new Chart(ctx, {
                type: 'bar',
                data: { labels: team.map(t => t.name || '—'), datasets: [{ label: 'Ventes', data: team.map(t => t.total || 0), backgroundColor: '#20c997' }]},
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        // seasonal (reuse last 12 points)
        const seasonalEl = document.getElementById('seasonal-trends-chart');
        if (seasonalEl && window.Chart && labels.length) {
            const ctx = seasonalEl.getContext('2d');
            if (seasonalChart) seasonalChart.destroy();
            const l = labels.slice(-12);
            const s = series.slice(-12);
            seasonalChart = new Chart(ctx, {
                type: 'line',
                data: { labels: l, datasets: [{ label: 'Tendance', data: s, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,0.08)', fill: true }]},
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    }

    async function fetchData(period = 'month') {
        const params = new URLSearchParams({ period });
        const url = endpointBase + '?' + params.toString();
        try {
            const res = await fetch(url, { cache: 'no-store' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            return data;
        } catch (err) {
            console.error('Erreur fetch sales-data:', err);
            return null;
        }
    }

    async function loadAndRender(period = 'month') {
        // visual feedback
        periodButtons.forEach(b => b.classList.toggle('active', b.dataset.period === period || (period === 'month' && b.dataset.period === '30d')));
        const data = await fetchData(mapPeriod(period));
        if (!data) {
            // affichage d'erreur simple
            safeText(elMonthly, '—');
            safeText(elCount, '—');
            safeText(elAvg, '—');
            safeText(elGoal, '—');
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="7">Impossible de charger les données.</td></tr>';
            return;
        }

        // API older format: if stats exists, use it; else fallback to metrics naming
        const stats = data.stats || data.metrics || {};
        const labels = data.labels || [];
        const sales = data.sales || data.series || [];

        safeText(elMonthly, stats.total_sales ? '€' + Number(stats.total_sales).toLocaleString('fr-FR') : '€0');
        safeText(elCount, stats.total_deals ?? '0');
        safeText(elAvg, stats.avg_deal_size ? '€' + Number(stats.avg_deal_size).toLocaleString('fr-FR') : '€0');
        safeText(elGoal, (stats.goal_achieved ?? Math.round((stats.total_sales ?? 0) ? (100 * (stats.total_sales / (stats.goal ?? stats.total_sales))) : 0)) + '%');

        // create charts (products/team might be absent in this API -> pass empty arrays)
        createCharts(labels, sales, data.products || [], data.team || []);

        // optionally fill table rows if supplied
        if (tableBody && data.table_rows && data.table_rows.length) {
            tableBody.innerHTML = data.table_rows.map(r => `
                <tr>
                    <td><strong>${escapeHtml(r.label)}</strong></td>
                    <td>€${Number(r.sales || 0).toLocaleString('fr-FR')}</td>
                    <td>${Number(r.count || 0)}</td>
                    <td>€${Number(r.avg || 0).toLocaleString('fr-FR')}</td>
                    <td><span class="text-success">${escapeHtml(r.growth || '')}</span></td>
                    <td>${escapeHtml(r.top_product || '')}</td>
                    <td>${escapeHtml(r.top_salesperson || '')}</td>
                </tr>
            `).join('');
        }
    }

    // map UI period to API period
    function mapPeriod(btnPeriod) {
        switch (btnPeriod) {
            case '7d': return 'week';
            case '30d': return 'month';
            case '90d': return 'quarter';
            case '1y': return 'year';
            default: return btnPeriod;
        }
    }

    // wire period buttons
    periodButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const p = btn.dataset.period;
            loadAndRender(p);
        });
    });

    // expose refresh/export used by page buttons
    window.refreshAnalytics = () => loadAndRender('30d');
    window.exportAnalytics = () => window.print();

    // initial load (default month)
    loadAndRender('30d');

    // auto-refresh every 5 minutes
    setInterval(() => loadAndRender('30d'), 300000);
});