<?php
$crumbs = $crumbs ?? [];
include __DIR__ . '/../../partials/breadcrumbs.php';

$action = isset($action) ? (string)$action : '/comercial/entidades';
$errors = is_array($errors ?? null) ? $errors : array();
?>
<link rel="stylesheet" href="/css/comercial_style/entidades-cards.css">

<section class="card ent-container">
  <h1 class="ent-title">Nueva entidad</h1>
  <?php if (isset($errors['general'])): ?>
    <div class="ent-alert ent-alert--error" role="alert"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="form ent-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <?php include __DIR__ . '/_form.php'; ?>
    <div class="form-actions ent-actions">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-cancel" href="/comercial/entidades">Cancelar</a>
    </div>
  </form>
</section>
