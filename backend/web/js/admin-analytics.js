/* Analytics & Reports page */
(function () {
  'use strict';

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  var chartInstances = {};
  var accentColors = {
    blue: '#2f76ff',
    green: '#22c55e',
    purple: '#6d5cff',
    orange: '#f59e0b',
    amber: '#fbbf24',
    red: '#fb7185',
    teal: '#14b8a6',
    indigo: '#6366f1',
  };

  var kpiKeys = [
    'total_applications',
    'approved_students',
    'active_internships',
    'interview_conversion',
    'pending_reviews',
    'rejected_applications',
    'offer_acceptance',
    'completion_rate',
  ];

  function parseJsonAttr(el, name) {
    try {
      return JSON.parse(el.getAttribute(name) || '[]');
    } catch (e) {
      return [];
    }
  }

  function destroyCharts() {
    Object.keys(chartInstances).forEach(function (id) {
      if (chartInstances[id]) {
        chartInstances[id].destroy();
        delete chartInstances[id];
      }
    });
  }

  function gradientBar(ctx, height) {
    var g = ctx.createLinearGradient(0, 0, 0, height || 300);
    g.addColorStop(0, 'rgba(47, 118, 255, 0.85)');
    g.addColorStop(1, 'rgba(47, 118, 255, 0.15)');
    return g;
  }

  function fixSparklineFill(color) {
    if (color.indexOf('#') === 0) {
      var hex = color.replace('#', '');
      var r = parseInt(hex.substr(0, 2), 16);
      var g = parseInt(hex.substr(2, 2), 16);
      var b = parseInt(hex.substr(4, 2), 16);
      return 'rgba(' + r + ',' + g + ',' + b + ',0.18)';
    }
    return 'rgba(47,118,255,0.18)';
  }

  function initAnalyticsCharts() {
    if (typeof Chart === 'undefined') return;
    var root = qs('#apAnalyticsRoot');
    if (!root) return;

    qsa('canvas[data-chart]', root).forEach(function (canvas) {
      var id = canvas.id;
      if (!id) return;
      var type = canvas.getAttribute('data-chart') || 'bar';
      var labels = parseJsonAttr(canvas, 'data-labels');
      var values = parseJsonAttr(canvas, 'data-values');
      var colors = parseJsonAttr(canvas, 'data-colors');
      var variant = canvas.getAttribute('data-variant');

      if (chartInstances[id]) {
        chartInstances[id].destroy();
      }

      var palette = colors.length ? colors : ['#2f76ff', '#6d5cff', '#22c55e', '#f59e0b', '#38bdf8', '#fb7185'];
      var bg = type === 'doughnut' ? palette : (variant === 'gradient' ? function (ctx) {
        return gradientBar(ctx.chart.ctx, ctx.chart.height);
      } : palette);

      var dataset = {
        data: values,
        backgroundColor: bg,
        borderColor: type === 'line' ? '#2f76ff' : 'transparent',
        borderWidth: type === 'bar' && variant === 'gradient' ? 0 : 0,
        borderRadius: type === 'bar' ? 8 : 0,
        maxBarThickness: variant === 'gradient' ? 28 : 36,
        hoverBackgroundColor: type === 'bar' ? 'rgba(47,118,255,0.95)' : undefined,
      };

      chartInstances[id] = new Chart(canvas, {
        type: type === 'doughnut' ? 'doughnut' : (type === 'line' ? 'line' : 'bar'),
        data: { labels: labels, datasets: [dataset] },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          resizeDelay: 120,
          layout: { padding: { top: 4, right: 8, bottom: 4, left: 4 } },
          animation: { duration: 550, easing: 'easeOutQuart' },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: 'rgba(9, 14, 28, 0.94)',
              titleColor: '#fff',
              bodyColor: 'rgba(255,255,255,0.9)',
              padding: 10,
              cornerRadius: 8,
            },
          },
          cutout: type === 'doughnut' ? '72%' : undefined,
          scales: type === 'doughnut' ? {} : {
            x: {
              ticks: { color: 'rgba(255,255,255,0.55)', maxRotation: 45, font: { size: 11 } },
              grid: { display: false },
            },
            y: {
              beginAtZero: true,
              ticks: { color: 'rgba(255,255,255,0.55)', precision: 0, font: { size: 11 } },
              grid: { color: 'rgba(255,255,255,0.06)' },
            },
          },
        },
      });
    });

    initSparklines();
  }

  function initSparklines() {
    qsa('.ap-analytics-sparkline').forEach(function (canvas) {
      var values = parseJsonAttr(canvas, 'data-values');
      if (!values.length || typeof Chart === 'undefined') return;
      var accent = canvas.getAttribute('data-accent') || 'blue';
      var color = accentColors[accent] || accentColors.blue;
      var sid = 'spark-' + (canvas.getAttribute('data-spark-id') || Math.random().toString(36).slice(2, 9));
      if (!canvas.id) canvas.id = sid;
      if (chartInstances[canvas.id]) chartInstances[canvas.id].destroy();
      chartInstances[canvas.id] = new Chart(canvas, {
        type: 'line',
        data: {
          labels: values.map(function (_, i) { return String(i); }),
          datasets: [{
            data: values,
            borderColor: color,
            backgroundColor: fixSparklineFill(color),
            fill: true,
            tension: 0.42,
            pointRadius: 0,
            borderWidth: 2,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: { duration: 500 },
          plugins: { legend: { display: false }, tooltip: { enabled: false } },
          scales: { x: { display: false }, y: { display: false } },
        },
      });
    });
  }

  function updateDaterangeDisplay() {
    var form = qs('[data-ap-analytics-filter]');
    if (!form) return;
    var from = form.querySelector('[name="from"]');
    var to = form.querySelector('[name="to"]');
    var display = qs('.ap-analytics-daterange__display');
    if (!from || !to || !display || !from.value || !to.value) return;
    var opts = { month: 'short', day: 'numeric', year: 'numeric' };
    var f = new Date(from.value + 'T00:00:00');
    var t = new Date(to.value + 'T00:00:00');
    display.textContent = f.toLocaleDateString('en-US', opts) + ' – ' + t.toLocaleDateString('en-US', opts);
  }

  function setLoading(on) {
    var root = qs('#apAnalyticsRoot');
    if (root) root.classList.toggle('ap-analytics--loading', !!on);
  }

  function mergeQueryParams(baseUrl, qsStr) {
    try {
      var url = new URL(baseUrl, window.location.origin);
      var incoming = new URLSearchParams(qsStr);
      incoming.forEach(function (value, key) {
        url.searchParams.set(key, value);
      });
      return url.pathname + url.search + url.hash;
    } catch (e) {
      var joiner = baseUrl.indexOf('?') >= 0 ? '&' : '?';
      return baseUrl + joiner + qsStr;
    }
  }

  function initAnalyticsFilters() {
    var form = qs('[data-ap-analytics-filter]');
    var root = qs('#apAnalyticsRoot');
    if (!form || !root) return;
    if (form.dataset.apAnalyticsBound === '1') return;
    form.dataset.apAnalyticsBound = '1';

    qsa('input, select', form).forEach(function (el) {
      el.addEventListener('change', updateDaterangeDisplay);
    });
    updateDaterangeDisplay();

    form.addEventListener('submit', function (e) {
      var dataUrl = root.getAttribute('data-analytics-data-url');
      if (!dataUrl || !window.fetch) return;

      e.preventDefault();
      setLoading(true);
      var qsStr = new URLSearchParams(new FormData(form)).toString();
      var dataFetchUrl = mergeQueryParams(dataUrl, qsStr);

      fetch(dataFetchUrl, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      })
        .then(function (r) {
          if (!r.ok) throw new Error('Request failed');
          return r.json();
        })
        .then(function (res) {
          if (!res.success || !res.metrics) {
            window.location.href = mergeQueryParams(form.getAttribute('action') || window.location.pathname, qsStr);
            return;
          }
          applyMetrics(res.metrics);
          history.replaceState(null, '', mergeQueryParams(form.getAttribute('action') || window.location.pathname, qsStr));
          updateExportLinks(qsStr);
        })
        .catch(function () {
          window.location.href = mergeQueryParams(form.getAttribute('action') || window.location.pathname, qsStr);
        })
        .finally(function () { setLoading(false); });
    });
  }

  function updateExportLinks(qsStr) {
    qsa('[data-export-format]').forEach(function (link) {
      var fmt = link.getAttribute('data-export-format');
      var base = link.href.split('?')[0];
      link.href = base + '?' + qsStr + '&format=' + encodeURIComponent(fmt);
    });
  }

  function initExportMenu() {
    var wrap = qs('.ap-analytics-export-wrap');
    if (!wrap || wrap.dataset.orgExportBound === '1') return;
    wrap.dataset.orgExportBound = '1';
    var toggle = qs('.ap-analytics-export-toggle', wrap);
    var menu = qs('.ap-analytics-export-menu', wrap);
    if (!toggle || !menu) return;

    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = menu.hasAttribute('hidden');
      if (open) {
        menu.removeAttribute('hidden');
        toggle.setAttribute('aria-expanded', 'true');
      } else {
        menu.setAttribute('hidden', '');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('click', function () {
      menu.setAttribute('hidden', '');
      toggle.setAttribute('aria-expanded', 'false');
    });

    menu.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  }

  function updateInsights(insights) {
    var grid = qs('[data-analytics-insights]');
    if (!grid) return;
    if (!insights || !insights.length) {
      grid.innerHTML = '<article class="ap-analytics-insight ap-analytics-insight--neutral"><span class="ap-analytics-insight__icon"><i class="fas fa-chart-line"></i></span><p>No insights for this period.</p></article>';
      return;
    }
    grid.innerHTML = insights.map(function (ins) {
      return '<article class="ap-analytics-insight ap-analytics-insight--' + ins.type + '">' +
        '<span class="ap-analytics-insight__icon"><i class="fas ' + ins.icon + '"></i></span>' +
        '<p>' + ins.text.replace(/</g, '&lt;') + '</p></article>';
    }).join('');
  }

  function applyMetrics(metrics) {
    if (!metrics.kpi) return;
    var cards = qsa('.ap-analytics-kpi');
    cards.forEach(function (card, i) {
      var data = metrics.kpi[kpiKeys[i]];
      if (!data) return;
      var counter = card.querySelector('[data-ap-count]');
      if (counter) {
        counter.setAttribute('data-ap-count', data.value);
        counter.textContent = '0';
        if (window.requestAnimationFrame) {
          var target = data.value;
          var start = performance.now();
          function tick(now) {
            var p = Math.min((now - start) / 700, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            counter.textContent = String(Math.floor(target * eased));
            if (p < 1) requestAnimationFrame(tick);
          }
          requestAnimationFrame(tick);
        } else {
          counter.textContent = String(data.value);
        }
      }
    });

    destroyCharts();
    updateCanvas('apChartFields', metrics.by_field);
    updateCanvas('apChartDaily', metrics.daily);
    updateCanvas('apChartPipeline', metrics.pipeline, true);

    var donutTotal = qs('[data-analytics-donut-total]');
    if (donutTotal && metrics.pipeline) {
      donutTotal.textContent = String(metrics.pipeline.total || 0);
    }
    updateLegend(metrics.pipeline && metrics.pipeline.legend);
    updateInsights(metrics.insights);
    initAnalyticsCharts();
  }

  function updateCanvas(id, block, isPipeline) {
    var canvas = document.getElementById(id);
    if (!canvas || !block) return;
    canvas.setAttribute('data-labels', JSON.stringify(block.labels || []));
    canvas.setAttribute('data-values', JSON.stringify(block.values || []));
    if (isPipeline && block.colors) {
      canvas.setAttribute('data-colors', JSON.stringify(block.colors));
    }
  }

  function updateLegend(legend) {
    var ul = qs('[data-analytics-legend]');
    if (!ul) return;
    if (!legend || !legend.length) {
      ul.innerHTML = '<li class="ap-analytics-legend__empty">No applications in this period</li>';
      return;
    }
    ul.innerHTML = legend.map(function (item) {
      return '<li><span class="ap-analytics-legend__dot" style="background:' + item.color + '"></span>' +
        '<span class="ap-analytics-legend__label">' + item.label + '</span>' +
        '<span class="ap-analytics-legend__pct">' + item.pct + '%</span>' +
        '<span class="ap-analytics-legend__count">(' + item.count + ')</span></li>';
    }).join('');
  }

  function bootstrapAnalytics() {
    if (!qs('#apAnalyticsRoot')) return;
    initAnalyticsCharts();
    initAnalyticsFilters();
    initExportMenu();
  }

  document.addEventListener('DOMContentLoaded', bootstrapAnalytics);
  document.addEventListener('pjax:end', function () {
    destroyCharts();
    bootstrapAnalytics();
    if (typeof animateCounters === 'undefined') {
      qsa('[data-ap-count]').forEach(function (el) {
        var t = parseInt(el.getAttribute('data-ap-count'), 10) || 0;
        el.textContent = String(t);
      });
    }
  });
})();

