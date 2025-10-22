<?php
$eventsJson = isset($eventsJson) && is_string($eventsJson) ? $eventsJson : '[]';
$startDate  = isset($startDate) ? (string)$startDate : date('Y-m-01');
$endDate    = isset($endDate) ? (string)$endDate : date('Y-m-t');
$month      = isset($month) ? (string)$month : date('Y-m');
$module     = isset($module) ? (string)$module : '';
?>
<link rel="stylesheet" href="/css/calendar.css">

<section
  id="calendar-app"
  class="calendar"
  data-events-endpoint="/calendario/eventos"
  data-initial-start="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?>"
  data-initial-end="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8') ?>"
  data-initial-month="<?= htmlspecialchars($month, ENT_QUOTES, 'UTF-8') ?>"
  data-initial-module="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>"
  data-initial-events='<?= htmlspecialchars($eventsJson, ENT_QUOTES, 'UTF-8') ?>'
>
  <header class="calendar__header">
    <div class="calendar__nav">
      <button type="button" class="calendar__btn" data-calendar-prev aria-label="Mes anterior">
        <span class="material-symbols-outlined" aria-hidden="true">chevron_left</span>
      </button>
      <h1 class="calendar__title" data-calendar-month>Calendario</h1>
      <button type="button" class="calendar__btn" data-calendar-next aria-label="Mes siguiente">
        <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
      </button>
    </div>
    <div class="calendar__filters">
      <label for="calendar-module" class="calendar__label">Módulo</label>
      <select id="calendar-module" class="calendar__select" data-calendar-module>
        <option value="">Todos</option>
        <option value="Comercial" <?= $module === 'Comercial' ? 'selected' : '' ?>>Comercial</option>
        <option value="Contabilidad" <?= $module === 'Contabilidad' ? 'selected' : '' ?>>Contabilidad</option>
      </select>
    </div>
  </header>

  <div class="calendar__weekdays">
    <span>Lun</span>
    <span>Mar</span>
    <span>Mié</span>
    <span>Jue</span>
    <span>Vie</span>
    <span>Sáb</span>
    <span>Dom</span>
  </div>
  <div class="calendar__grid" data-calendar-grid aria-live="polite"></div>

  <section class="calendar__list" aria-live="polite">
    <h2 class="calendar__list-title">Eventos del período</h2>
    <ul class="calendar__list-items" data-calendar-list></ul>
  </section>
</section>

<script src="/js/calendar.js" defer></script>
