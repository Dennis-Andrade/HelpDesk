<?php
$crumbs = $crumbs ?? array();
include __DIR__ . '/../../partials/breadcrumbs.php';

$stats = isset($stats) && is_array($stats) ? $stats : array();
$cards = isset($stats['cards']) && is_array($stats['cards']) ? $stats['cards'] : array();
$cards = array_merge(array(
  'total'                => 0,
  'activas'              => 0,
  'inactivas'            => 0,
  'porcentaje_activas'   => 0,
  'porcentaje_inactivas' => 0,
  'ultimo_mes'           => 0,
  'variacion'            => 'Nuevo registro',
), $cards);

$segmentos = array();
if (isset($stats['segmentos']) && is_array($stats['segmentos'])) {
  foreach ($stats['segmentos'] as $row) {
    if (!is_array($row)) {
      continue;
    }
    $segmentos[] = array(
      'nombre_segmento' => isset($row['nombre_segmento']) ? (string)$row['nombre_segmento'] : 'Sin segmento',
      'cantidad'        => isset($row['cantidad']) ? (int)$row['cantidad'] : 0,
    );
  }
}

$estado = array('activas'=>0, 'inactivas'=>0);
if (isset($stats['estado']) && is_array($stats['estado'])) {
  $estado['activas']   = isset($stats['estado']['activas']) ? (int)$stats['estado']['activas'] : 0;
  $estado['inactivas'] = isset($stats['estado']['inactivas']) ? (int)$stats['estado']['inactivas'] : 0;
}

$mensual = array('labels'=>array(), 'counts'=>array());
if (isset($stats['mensual']) && is_array($stats['mensual'])) {
  $mensual['labels'] = isset($stats['mensual']['labels']) && is_array($stats['mensual']['labels']) ? array_values($stats['mensual']['labels']) : array();
  $mensual['counts'] = isset($stats['mensual']['counts']) && is_array($stats['mensual']['counts']) ? array_map('intval', $stats['mensual']['counts']) : array();
}

$segmentoLabels = array();
$segmentoCounts = array();
foreach ($segmentos as $seg) {
  $segmentoLabels[] = $seg['nombre_segmento'];
  $segmentoCounts[] = $seg['cantidad'];
}

$estadoLabels = array('Activas', 'Inactivas');
$estadoCounts = array($estado['activas'], $estado['inactivas']);

$errorMsg = isset($error) && $error ? (string)$error : '';
?>
<div class="comercial-dashboard">
  <section class="card comercial-dashboard__header">
    <div class="comercial-dashboard__title">
      <h1>Dashboard Comercial</h1>
      <p class="comercial-dashboard__subtitle">Resumen de cooperativas y actividad reciente.</p>
    </div>
  </section>

  <?php if ($errorMsg !== ''): ?>
    <div class="card comercial-dashboard__alert">
      <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <div class="comercial-dashboard__stats">
    <article class="card comercial-dashboard__stat comercial-dashboard__stat--total">
      <span class="material-symbols-outlined comercial-dashboard__icon">diversity_3</span>
      <h3>Entidades totales</h3>
      <p class="comercial-dashboard__stat-value"><?= (int)$cards['total'] ?></p>
      <span class="comercial-dashboard__stat-hint">Registradas</span>
    </article>

    <article class="card comercial-dashboard__stat comercial-dashboard__stat--activas">
      <span class="material-symbols-outlined comercial-dashboard__icon">task_alt</span>
      <h3>Entidades activas</h3>
      <p class="comercial-dashboard__stat-value"><?= (int)$cards['activas'] ?></p>
      <span class="comercial-dashboard__stat-hint"><?= (int)$cards['porcentaje_activas'] ?>% del total</span>
    </article>

    <article class="card comercial-dashboard__stat comercial-dashboard__stat--inactivas">
      <span class="material-symbols-outlined comercial-dashboard__icon">block</span>
      <h3>Entidades inactivas</h3>
      <p class="comercial-dashboard__stat-value"><?= (int)$cards['inactivas'] ?></p>
      <span class="comercial-dashboard__stat-hint"><?= (int)$cards['porcentaje_inactivas'] ?>% del total</span>
    </article>

    <article class="card comercial-dashboard__stat comercial-dashboard__stat--ultimo">
      <span class="material-symbols-outlined comercial-dashboard__icon">calendar_month</span>
      <h3>Último mes</h3>
      <p class="comercial-dashboard__stat-value"><?= (int)$cards['ultimo_mes'] ?></p>
      <span class="comercial-dashboard__stat-hint"><?= htmlspecialchars((string)$cards['variacion'], ENT_QUOTES, 'UTF-8') ?></span>
    </article>
  </div>

  <div class="comercial-dashboard__charts">
    <section class="card comercial-dashboard__chart">
      <header class="comercial-dashboard__chart-header">
        <h3><span class="material-symbols-outlined">donut_small</span> Distribución por segmento</h3>
      </header>
      <div class="comercial-dashboard__canvas">
        <canvas id="segmentoChart" width="400" height="320"></canvas>
      </div>
    </section>

    <section class="card comercial-dashboard__chart">
      <header class="comercial-dashboard__chart-header">
        <h3><span class="material-symbols-outlined">show_chart</span> Registros mensuales</h3>
      </header>
      <div class="comercial-dashboard__canvas">
        <canvas id="mensualChart" width="400" height="320"></canvas>
      </div>
    </section>

    <section class="card comercial-dashboard__chart">
      <header class="comercial-dashboard__chart-header">
        <h3><span class="material-symbols-outlined">pie_chart</span> Estado de cooperativas</h3>
      </header>
      <div class="comercial-dashboard__canvas">
        <canvas id="estadoChart" width="400" height="320"></canvas>
      </div>
    </section>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
(function() {
  var segmentoLabels = <?= json_encode($segmentoLabels, JSON_UNESCAPED_UNICODE) ?>;
  var segmentoCounts = <?= json_encode($segmentoCounts, JSON_UNESCAPED_UNICODE) ?>;
  var estadoLabels   = <?= json_encode($estadoLabels, JSON_UNESCAPED_UNICODE) ?>;
  var estadoCounts   = <?= json_encode($estadoCounts, JSON_UNESCAPED_UNICODE) ?>;
  var mensualLabels  = <?= json_encode($mensual['labels'], JSON_UNESCAPED_UNICODE) ?>;
  var mensualCounts  = <?= json_encode($mensual['counts'], JSON_UNESCAPED_UNICODE) ?>;

  function initCharts() {
    if (typeof Chart === 'undefined') {
      return;
    }

    var chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 20,
            usePointStyle: true
          }
        },
        tooltip: {
          enabled: true,
          mode: 'index',
          intersect: false
        },
        datalabels: {
          color: '#fff',
          font: { weight: 'bold' }
        }
      }
    };

    var segmentoCanvas = document.getElementById('segmentoChart');
    if (segmentoCanvas) {
      new Chart(segmentoCanvas, {
        type: 'doughnut',
        data: {
          labels: segmentoLabels,
          datasets: [{
            data: segmentoCounts,
            backgroundColor: ['#212A53', '#FF6600', '#3A4A80', '#FF8533', '#2EC4B6', '#4895EF', '#F72585', '#2D9BF0'],
            borderWidth: 1
          }]
        },
        options: chartOptions,
        plugins: typeof ChartDataLabels !== 'undefined' ? [ChartDataLabels] : []
      });
    }

    var estadoCanvas = document.getElementById('estadoChart');
    if (estadoCanvas) {
      var estadoPlugins = typeof ChartDataLabels !== 'undefined' ? [ChartDataLabels] : [];
      var estadoOptions = JSON.parse(JSON.stringify(chartOptions));
      estadoOptions.plugins.datalabels.formatter = function(value, ctx) {
        var dataset = ctx.chart.data.datasets[0].data || [];
        var total = dataset.reduce(function(acc, cur) { return acc + cur; }, 0);
        if (!total) {
          return '';
        }
        var pct = Math.round((value / total) * 100);
        return pct > 5 ? pct + '%' : '';
      };

      new Chart(estadoCanvas, {
        type: 'pie',
        data: {
          labels: estadoLabels,
          datasets: [{
            data: estadoCounts,
            backgroundColor: ['#2EC4B6', '#FF6600'],
            borderWidth: 1
          }]
        },
        options: estadoOptions,
        plugins: estadoPlugins
      });
    }

    var mensualCanvas = document.getElementById('mensualChart');
    if (mensualCanvas) {
      var mensualOptions = JSON.parse(JSON.stringify(chartOptions));
      mensualOptions.plugins.datalabels = false;
      mensualOptions.interaction = { intersect: false, mode: 'nearest' };
      mensualOptions.scales = { y: { beginAtZero: true } };

      new Chart(mensualCanvas, {
        type: 'line',
        data: {
          labels: mensualLabels,
          datasets: [{
            label: 'Registros',
            data: mensualCounts,
            backgroundColor: 'rgba(33, 42, 83, 0.15)',
            borderColor: '#212A53',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#FF6600'
          }]
        },
        options: mensualOptions
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCharts);
  } else {
    initCharts();
  }
})();
</script>
