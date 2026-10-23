/*
 * Gráficos del dashboard. Los datos llegan como JSON en #dashboard-data
 * (no se ejecuta código en línea: compatible con la política CSP).
 */
(function () {
    'use strict';
    var el = document.getElementById('dashboard-data');
    if (!el || typeof Chart === 'undefined') return;
    var data = JSON.parse(el.textContent);

    var palette = ['#12355b', '#0b6e78', '#4fa3ab', '#c98a04', '#b42318', '#6c4f8f', '#2f7d4f', '#8a96a3', '#d06b2f', '#3d5a80'];
    var statusColors = {
        'Registrado': '#8a96a3', 'En revisión': '#4fa3ab', 'Requiere información': '#c98a04',
        'Asignado': '#3d5a80', 'En seguimiento': '#12355b', 'Derivado': '#212529', 'Cerrado': '#2f7d4f'
    };

    Chart.defaults.font.family = '"Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif';
    Chart.defaults.color = '#3a4652';
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.plugins.legend.labels.boxWidth = 12;

    function keys(o) { return Object.keys(o); }
    function vals(o) { return Object.keys(o).map(function (k) { return o[k]; }); }
    var integerTicks = { beginAtZero: true, ticks: { precision: 0 } };

    new Chart(document.getElementById('chart-mensual'), {
        type: 'line',
        data: { labels: keys(data.mensual), datasets: [{ label: 'Reportes', data: vals(data.mensual), borderColor: '#0b6e78', backgroundColor: 'rgba(11,110,120,.15)', fill: true, tension: .3, pointRadius: 4 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: integerTicks } }
    });

    var estados = {};
    keys(data.estados).forEach(function (k) { if (data.estados[k] > 0) estados[k] = data.estados[k]; });
    new Chart(document.getElementById('chart-estados'), {
        type: 'doughnut',
        data: { labels: keys(estados), datasets: [{ data: vals(estados), backgroundColor: keys(estados).map(function (k) { return statusColors[k] || '#8a96a3'; }), borderWidth: 2 }] },
        options: { plugins: { legend: { position: 'bottom' } }, cutout: '58%' }
    });

    new Chart(document.getElementById('chart-categorias'), {
        type: 'bar',
        data: { labels: keys(data.categorias), datasets: [{ label: 'Incidentes', data: vals(data.categorias), backgroundColor: palette }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: integerTicks } }
    });

    new Chart(document.getElementById('chart-prioridad'), {
        type: 'bar',
        data: { labels: keys(data.prioridad), datasets: [{ label: 'Casos abiertos', data: vals(data.prioridad), backgroundColor: ['#b42318', '#c98a04', '#8a96a3'] }] },
        options: { plugins: { legend: { display: false } }, scales: { y: integerTicks } }
    });
})();
