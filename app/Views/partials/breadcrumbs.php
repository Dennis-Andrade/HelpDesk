<?php /** @var array $crumbs = [['href'=>'/comercial','label'=>'Comercial'], ['label'=>'Dashboard']] */ ?>
<nav class="breadcrumbs" aria-label="breadcrumb">
  <ol>
    <?php foreach ($crumbs as $i => $c): ?>
      <li>
        <?php if (!empty($c['href'])): ?>
          <a href="<?= htmlspecialchars($c['href']) ?>"><?= htmlspecialchars($c['label']) ?></a>
        <?php else: ?>
          <span aria-current="page"><?= htmlspecialchars($c['label']) ?></span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>
