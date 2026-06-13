/* Executive dashboard charts */
(function () {
  'use strict';

  function parseCanvas(id) {
    var el = document.getElementById(id);
    if (!el) return null;
    try {
      return JSON.parse(el.getAttribute('data-chart') || 'null');
    } catch (e) {
      return null;
    }
  }

  function initDashCharts() {
    if (typeof Chart === 'undefined') return;
    var root = document.getElementById('apDashRoot');
    if (!root) return;

    var monthly = parseCanvas('apChartApplications');
    if (monthly && document.getElementById('apChartApplications')) {
      new Chart(document.getElementById('apChartApplications'), {
        type: 'line',
        data: {
          labels: monthly.labels,
          datasets: [
            {
              label: 'Applications',
              data: monthly.apps,
              borderColor: '#2563eb',
              backgroundColor: 'rgba(37, 99, 235, 0.1)',
              fill: true,
              tension: 0.4,
            },
            {
              label: 'New users',
              data: monthly.users,
              borderColor: '#6366f1',
              backgroundColor: 'transparent',
              tension: 0.4,
            },
            {
              label: 'Organizations',
              data: monthly.orgs,
              borderColor: '#10b981',
              backgroundColor: 'transparent',
              tension: 0.4,
              borderDash: [4, 4],
            },
          ],
        },
        options: chartOpts(280),
      });
    }

    var daily = parseCanvas('apChartDaily');
    if (daily && document.getElementById('apChartDaily')) {
      new Chart(document.getElementById('apChartDaily'), {
        type: 'bar',
        data: {
          labels: daily.labels,
          datasets: [{
            data: daily.values,
            backgroundColor: function (ctx) {
              var g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 280);
              g.addColorStop(0, 'rgba(37, 99, 235, 0.85)');
              g.addColorStop(1, 'rgba(37, 99, 235, 0.15)');
              return g;
            },
            borderRadius: 8,
            maxBarThickness: 28,
          }],
        },
        options: chartOpts(260, false),
      });
    }

    var field = parseCanvas('apChartField');
    if (field && document.getElementById('apChartField')) {
      new Chart(document.getElementById('apChartField'), {
        type: 'bar',
        data: {
          labels: field.labels,
          datasets: [{
            data: field.values,
            backgroundColor: ['#2563eb', '#6366f1', '#10b981', '#f59e0b', '#38bdf8', '#94a3b8'],
            borderRadius: 8,
          }],
        },
        options: chartOpts(240, false),
      });
    }
  }

  function chartOpts(height, legend) {
    if (legend === undefined) legend = true;
    return {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 600 },
      plugins: {
        legend: { display: legend, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.12)' }, ticks: { precision: 0, color: '#94a3b8' } },
      },
    };
  }

  document.addEventListener('DOMContentLoaded', initDashCharts);
})();
