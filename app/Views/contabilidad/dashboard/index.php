<?php
use App\Services\Shared\Breadcrumbs;

if (!function_exists('contab_h')) {
    function contab_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('contab_number')) {
    function contab_number($value, int $decimals = 0): string
    {
        $number = is_numeric($value) ? (float)$value : 0;
        return number_format($number, $decimals, ',', '.');
    }
}

$stats   = isset($stats) && is_array($stats) ? $stats : [];
$crumbs  = $crumbs ?? Breadcrumbs::make([
    ['href' => '/contabilidad', 'label' => 'Contabilidad'],
    ['label' => 'Dashboard'],
]);
$errorMsg = isset($error) && $error ? (string)$error : '';

$cardsRaw = isset($stats['cards']) && is_array($stats['cards']) ? $stats['cards'] : [];
$cards = [
    [
        'label' => 'Contratos activos',
        'value' => (int)($cardsRaw['contratos_activos'] ?? 0),
        'icon'  => 'assignment_turned_in',
        'hint'  => 'Servicios en producción',
    ],
    [
        'label' => 'Servicios vigentes',
        'value' => (int)($cardsRaw['servicios_vigentes'] ?? 0),
        'icon'  => 'category',
        'hint'  => 'Portafolio comprometido',
    ],
    [
        'label' => 'Facturas del mes',
        'value' => (int)($cardsRaw['facturas_mes'] ?? 0),
        'icon'  => 'request_quote',
        'hint'  => 'Emitidas este mes',
    ],
    [
        'label' => 'Pagos pendientes',
        'value' => (int)($cardsRaw['pagos_pendientes'] ?? 0),
        'icon'  => 'report_problem',
        'hint'  => 'En seguimiento de cobranza',
    ],
];

$serviciosData = isset($stats['servicios']) && is_array($stats['servicios'])
    ? $stats['servicios']
    : ['labels' => [], 'counts' => []];
$estadosData = isset($stats['estados']) && is_array($stats['estados'])
    ? $stats['estados']
    : ['labels' => [], 'counts' => []];
$mensualData = isset($stats['mensual']) && is_array($stats['mensual'])
    ? $stats['mensual']
    : ['labels' => [], 'amounts' => [], 'facturas' => []];

include __DIR__ . '/../../partials/breadcrumbs.php';
?>
<link rel="stylesheet" href="/css/contabilidad.css">

<div class="comercial-dashboard contabilidad-dashboard">
  <section class="card comercial-dashboard__header">
    <div class="comercial-dashboard__title">
      <h1>Dashboard Contable</h1>
      <p class="comercial-dashboard__subtitle">Indicadores de facturación y contratos activos.</p>
    </div>
  </section>

  <?php if ($errorMsg !== ''): ?>
    <div class="card comercial-dashboard__alert" role="alert">
      <?= contab_h($errorMsg) ?>
    </div>
  <?php endif; ?>

  <div class="comercial-dashboard__stats">
    <?php foreach ($cards as $card): ?>
      <article class="card comercial-dashboard__stat">
        <span class="material-symbols-outlined comercial-dashboard__icon"><?= contab_h($card['icon']) ?></span>
        <h3><?= contab_h($card['label']) ?></h3>
        <p class="comercial-dashboard__stat-value"><?= contab_number($card['value']) ?></p>
        <span class="comercial-dashboard__stat-hint"><?= contab_h($card['hint']) ?></span>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="comercial-dashboard__charts">
    <section class="card comercial-dashboard__chart">
      <header class="comercial-dashboard__chart-header">
        <h3><span class="material-symbols-outlined">donut_small</span> Contratos por servicio</h3>
      </header>
      <div class="comercial-dashboard__canvas">
        <canvas id="contabServiciosChart" width="380" height="320" aria-label="Distribución de contratos por servicio"></canvas>
      </div>
    </section>

    <section class="card comercial-dashboard__chart">
      <header class="comercial-dashboard__chart-header">
        <h3><span class="material-symbols-outlined">stacked_bar_chart</span> Facturación mensual</h3>
      </header>
      <div class="comercial-dashboard__canvas">
        <canvas id="contabMensualChart" width="380" height="320" aria-label="Totales facturados por mes"></canvas>
      </div>
    </section>

    <section class="card comercial-dashboard__chart">
      <header class="comercial-dashboard__chart-header">
        <h3><span class="material-symbols-outlined">pie_chart</span> Estados de pago</h3>
      </header>
      <div class="comercial-dashboard__canvas">
        <canvas id="contabEstadosChart" width="380" height="320" aria-label="Distribución por estado de pago"></canvas>
      </div>
    </section>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
(function() {
  var serviciosLabels = <?= json_encode($serviciosData['labels'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
  var serviciosCounts = <?= json_encode($serviciosData['counts'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
  var estadosLabels   = <?= json_encode($estadosData['labels'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
  var estadosCounts   = <?= json_encode($estadosData['counts'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
  var mensualLabels   = <?= json_encode($mensualData['labels'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
  var mensualAmounts  = <?= json_encode($mensualData['amounts'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
  var mensualFacturas = <?= json_encode($mensualData['facturas'] ?? [], JSON_UNESCAPED_UNICODE) ?>;

  if (typeof Chart === 'undefined') {
    return;
  }

  var palette = [
    '#212A53',
    '#FF6600',
    '#3A4A80',
    '#FF8533',
    '#2EC4B6',
    '#4895EF',
    '#F72585',
    '#2D9BF0'
  ];

  var serviciosCanvas = document.getElementById('contabServiciosChart');
  if (serviciosCanvas) {
    new Chart(serviciosCanvas, {
      type: 'doughnut',
      data: {
        labels: serviciosLabels,
        datasets: [{
          data: serviciosCounts,
          backgroundColor: palette,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { usePointStyle: true }
          }
        }
      },
      plugins: typeof ChartDataLabels !== 'undefined' ? [ChartDataLabels] : []
    });
  }

  var mensualCanvas = document.getElementById('contabMensualChart');
  if (mensualCanvas) {
    new Chart(mensualCanvas, {
      type: 'bar',
      data: {
        labels: mensualLabels,
        datasets: [{
          label: 'Total facturado (USD)',
          data: mensualAmounts,
          backgroundColor: 'rgba(33, 42, 83, 0.85)',
          borderRadius: 8,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          tooltip: {
            callbacks: {
              label: function(context) {
                var value = context.parsed.y || 0;
                var facturas = mensualFacturas[context.dataIndex] || 0;
                var formatter = new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' });
                return formatter.format(value) + ' · ' + facturas + ' factura(s)';
              }
            }
          },
          legend: { display: false }
        },
        scales: {
          y: {
            ticks: {
              callback: function(value) {
                var formatter = new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
                return formatter.format(value);
              }
            }
          }
        }
      }
    });
  }

  var estadosCanvas = document.getElementById('contabEstadosChart');
  if (estadosCanvas) {
    new Chart(estadosCanvas, {
      type: 'pie',
      data: {
        labels: estadosLabels,
        datasets: [{
          data: estadosCounts,
          backgroundColor: palette,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { usePointStyle: true }
          }
        }
      },
      plugins: typeof ChartDataLabels !== 'undefined' ? [ChartDataLabels] : []
    });
  }
})();
</script>
