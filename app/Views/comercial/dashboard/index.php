<?php $crumbs = $crumbs ?? []; include __DIR__ . '/../../partials/breadcrumbs.php'; ?>
<section class="card">
  <h1>Dashboard Comercial</h1>
  <div class="cards">
    <?php foreach ($metrics as $m): ?>
      <div class="mini-card"><div class="kpi"><?= (int)$m['value'] ?></div><div class="kpi-label"><?= htmlspecialchars($m['label']) ?></div></div>
    <?php endforeach; ?>
  </div>
</section>
