<?php
/** @var array $items  Lista paginada de cooperativas */
/** @var int   $total  Total de registros */
/** @var int   $page   Página actual (1..n) */
/** @var int   $perPage Registros por página */
/** @var string $q     Búsqueda */
/** @var string $csrf  Token CSRF */

$pages = max(1, (int)ceil($total / max(1, $perPage)));
$prev  = max(1, $page - 1);
$next  = min($pages, $page + 1);

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function segLabel($idSegmento){
  if (!$idSegmento) return 'No especificado';
  return 'Segmento ' . (int)$idSegmento;
}
function ubicacion($row){
  $p = trim((string)($row['provincia'] ?? ''));
  $c = trim((string)($row['canton']    ?? ''));
  if ($p === '' && $c === '') return 'No especificado';
  if ($p === '') return $c;
  if ($c === '') return $p;
  return $p . ' - ' . $c;
}
?>
<section class="ent-list">

  <h1 class="ent-title">Listado de Cooperativas</h1>

  <div class="ent-toolbar">
    <a class="btn btn-primary" href="/comercial/entidades/crear">Nueva</a>

    <form class="ent-search" action="/comercial/entidades" method="get">
      <input type="text" name="q" placeholder="Buscar por nombre..." value="<?= h($q) ?>">
      <button class="btn btn-outline" type="submit">Buscar</button>
    </form>

    <span style="margin-left:auto; color:#6b7280; font-weight:700;">
      <?= (int)$total ?> cooperativas · Página <?= (int)$page ?> de <?= (int)$pages ?>
    </span>
  </div>

  <?php if (empty($items)) : ?>
    <div class="card" style="padding:16px;">Sin resultados</div>
  <?php else: ?>

    <div class="ent-cards">
      <?php foreach ($items as $i => $r): ?>
        <?php
          $num = ($page - 1) * $perPage + $i + 1;
          $id  = (int)($r['id_entidad'] ?? $r['id'] ?? 0); // alias seguro
          $servCount = (int)($r['servicios_count'] ?? 0);  // si no tienes contador, se mostrará 0
        ?>
        <article class="ent-card">
          <header class="ent-card-head">
            <div class="ent-card-icon">🏦</div>
            <h3 class="ent-card-title"><?= h($r['nombre'] ?? '') ?></h3>
            <span class="ent-badge"><?= $servCount ?> servicios</span>
          </header>

          <div class="ent-card-body">
            <div class="ent-card-row">
              <span class="ent-card-label">Ubicación:</span>
              <span class="ent-card-value"><?= h(ubicacion($r)) ?></span>
            </div>
            <div class="ent-card-row">
              <span class="ent-card-label">Segmento:</span>
              <span class="ent-card-value"><?= h(segLabel($r['id_segmento'] ?? null)) ?></span>
            </div>
            <div class="ent-card-row">
              <span class="ent-card-label">Teléfono:</span>
              <span class="ent-card-value">
                <?php
                  $tf = trim((string)($r['telefono_fijo_1'] ?? $r['telefono'] ?? ''));
                  echo $tf === '' ? 'No especificado' : h($tf);
                ?>
              </span>
            </div>
            <div class="ent-card-row">
              <span class="ent-card-label">Email:</span>
              <span class="ent-card-value">
                <?php
                  $em = trim((string)($r['email'] ?? ''));
                  echo $em === '' ? 'No especificado' : h($em);
                ?>
              </span>
            </div>
          </div>

          <footer class="ent-card-actions">
            <button type="button" class="btn btn-outline btn-view" data-id="<?= $id ?>"> Ver</button>

            <a class="btn btn-primary" href="/comercial/entidades/editar?id=<?= $id ?>"> Editar</a>

            <form method="post" action="/comercial/entidades/eliminar" onsubmit="return confirm('¿Eliminar definitivamente?');">
              <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="id" value="<?= $id ?>">
              <button class="btn btn-danger" type="submit">Eliminar</button>
            </form>
          </footer>
        </article>
      <?php endforeach; ?>
    </div>

    <nav class="pagination">
      <?php if ($page > 1): ?>
        <a href="/comercial/entidades?page=<?= $prev ?>&perPage=<?= (int)$perPage ?>&q=<?= urlencode($q) ?>">&laquo; Anterior</a>
      <?php else: ?>
        <span class="disabled">&laquo; Anterior</span>
      <?php endif; ?>

      <span>Página <?= (int)$page ?> de <?= (int)$pages ?></span>

      <?php if ($page < $pages): ?>
        <a href="/comercial/entidades?page=<?= $next ?>&perPage=<?= (int)$perPage ?>&q=<?= urlencode($q) ?>">Siguiente &raquo;</a>
      <?php else: ?>
        <span class="disabled">Siguiente &raquo;</span>
      <?php endif; ?>
    </nav>

  <?php endif; ?>
</section>

<!-- Modal de detalle -->
<div id="ent-modal" class="ent-modal" aria-hidden="true">
  <div class="ent-modal__box" role="dialog" aria-modal="true" aria-label="Detalle entidad">
    <button class="ent-modal__close" type="button" title="Cerrar">×</button>

    <div class="ent-modal__header">
      <div class="ent-card-icon">🏦</div>
      <h3 id="ent-modal-title" class="ent-card-title">Cooperativa</h3>
      <span id="ent-modal-serv" class="ent-badge">0 servicios</span>
    </div>

    <div class="ent-modal__body">
      <dl class="ent-details">
        <div><dt>Ubicación</dt><dd id="md-ubicacion">—</dd></div>
        <div><dt>Segmento</dt><dd id="md-segmento">—</dd></div>
        <div><dt>Tipo</dt><dd id="md-tipo">—</dd></div>
        <div><dt>RUC</dt><dd id="md-ruc">—</dd></div>
        <div><dt>Teléfono fijo</dt><dd id="md-tfijo">—</dd></div>
        <div><dt>Celular</dt><dd id="md-tmov">—</dd></div>
        <div><dt>Email</dt><dd id="md-email">—</dd></div>
        <div><dt>Notas</dt><dd id="md-notas">—</dd></div>
        <div><dt>Servicios activos</dt><dd id="md-servicios">—</dd></div>
      </dl>
    </div>

    <div class="ent-modal__footer">
      <button class="btn btn-outline ent-modal__close">Cerrar</button>
    </div>
  </div>
</div>
