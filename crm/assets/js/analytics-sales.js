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
    let currentSource = "opportunities";

    const sourceButtons = Array.from(
        document.querySelectorAll('#salesSourceSwitcher [data-source]')
    );
  
    const title = document.getElementById('sales-chart-title');


    if(title){

        if(currentSource === "missions"){
            title.textContent="Évolution des Missions";
        }

        else if(currentSource === "erp_sales"){
            title.textContent="Évolution des Ventes ERP";
        }

        else{
            title.textContent="Opportunités gagnées";
        }

    }

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

        const params = new URLSearchParams({
            period: period,
            source: currentSource
        });


        const url = endpointBase + '?' + params.toString();

        console.log("API :", url);

        try {

            const res = await fetch(url, {
                cache:'no-store'
            });


            if (!res.ok)
                throw new Error('HTTP ' + res.status);


            const data = await res.json();

            return data;


        } catch(err){

            console.error(
                'Erreur fetch sales-data:',
                err
            );

            return null;
        }
    }

    async function loadAndRender(period = 'month') {
    
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

        const growth = Number(stats.growth_rate || 0);

        function renderGrowth(elementId, value){

            const el = document.getElementById(elementId);

            if(!el) return;


            if(value > 0){

                el.className = "text-xs text-success";

                el.innerHTML =
                `<i class="fas fa-arrow-up"></i> +${value}% vs période précédente`;

            }
            else if(value < 0){

                el.className = "text-xs text-danger";

                el.innerHTML =
                `<i class="fas fa-arrow-down"></i> ${value}% vs période précédente`;

            }
            else{

                el.className = "text-xs text-muted";

                el.innerHTML =
                `<i class="fas fa-minus"></i> 0% vs période précédente`;

            }

        }


        renderGrowth('sales-growth', growth);
        renderGrowth('count-growth', growth);
        renderGrowth('avg-growth', growth);
        if(data.kpi_labels){

            const tableHeaders = document.querySelectorAll('#salesAnalysisTable thead th');


            if(currentSource === "missions"){

                tableHeaders[1].textContent = "Montant missions (€)";
                tableHeaders[2].textContent = "Nombre missions";
                tableHeaders[3].textContent = "Prix moyen";

            }
            else if(currentSource === "erp_sales"){

                tableHeaders[1].textContent = "CA ERP (€)";
                tableHeaders[2].textContent = "Nombre ventes";
                tableHeaders[3].textContent = "Ticket moyen";

            }
            else{

                tableHeaders[1].textContent = "Ventes (€)";
                tableHeaders[2].textContent = "Opportunités";
                tableHeaders[3].textContent = "Valeur moyenne";

            }

            safeText(
                document.getElementById('kpi-total-label'),
                data.kpi_labels.total
            );

            safeText(
                document.getElementById('kpi-count-label'),
                data.kpi_labels.count
            );

            safeText(
                document.getElementById('kpi-avg-label'),
                data.kpi_labels.avg
            );

        }
        const labels = data.labels || [];
        const sales = data.sales || data.series || [];

        safeText(elMonthly, stats.total_sales ? '€' + Number(stats.total_sales).toLocaleString('fr-FR') : '€0');
        safeText(elCount, stats.total_deals ?? '0');
        safeText(elAvg, stats.avg_deal_size ? '€' + Number(stats.avg_deal_size).toLocaleString('fr-FR') : '€0');
        safeText(elGoal, (stats.goal_achieved ?? Math.round((stats.total_sales ?? 0) ? (100 * (stats.total_sales / (stats.goal ?? stats.total_sales))) : 0)) + '%');

        const progress = document.getElementById('goal-progress');

        if(progress){

            let percent = Number(
                stats.goal_achieved ??
                (
                    stats.goal 
                    ? (stats.total_sales / stats.goal) * 100 
                    : 0
                )
            );

            // limite entre 0 et 100
            percent = Math.min(Math.max(percent, 0), 100);

            progress.style.width = percent + "%";
            progress.setAttribute('aria-valuenow', percent);

        }
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
                    <td>
                    <span class="text-success">
                        ${escapeHtml(r.growth || '')}
                    </span>
                    </td>
                    <td>${escapeHtml(r.top_product || '-')}</td>
                    <td>${escapeHtml(r.top_salesperson || '-')}</td>
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

    // Switch entre opportunités / ventes ERP / missions

    sourceButtons.forEach(btn => {

        btn.addEventListener('click', () => {
            currentSource = btn.dataset.source;
            sourceButtons.forEach(b => {
                b.classList.remove('active','btn-primary');

                b.classList.add('btn-outline-primary');
            });

            btn.classList.add('active');

            btn.classList.remove('btn-outline-primary');

            btn.classList.add('btn-primary');

            loadAndRender('30d');
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