/**
 * Student dashboard charts
 */
(function () {
    'use strict';

    function initStudentCharts() {
        if (typeof Chart === 'undefined') return;

        var el = document.getElementById('ftpStudentActivityChart');
        if (!el) return;

        var data;
        try {
            data = JSON.parse(el.getAttribute('data-chart') || '{}');
        } catch (e) {
            return;
        }

        new Chart(el, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Applications',
                    data: data.values || [],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 800 },
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 } },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.12)' },
                    },
                },
            },
        });
    }

    document.addEventListener('DOMContentLoaded', initStudentCharts);
})();
