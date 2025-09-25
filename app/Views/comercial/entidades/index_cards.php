<?php
use App\Services\Shared\Pagination;

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$items         = is_array($items ?? null) ? $items : array();
$total         = (int)($total ?? 0);
$page          = (int)($page ?? 1);
$perPage       = (int)($perPage ?? 12);
$q             = isset($q) ? (string)$q : '';
$csrf          = isset($csrf) ? (string)$csrf : '';
$toastMessage  = isset($toastMessage) && $toastMessage !== '' ? (string)$toastMessage : null;
$errorMessage  = isset($errorMessage) && $errorMessage !== '' ? (string)$errorMessage : null;

$pagination = Pagination::fromRequest(array(
    'page'    => $page,
    'perPage' => $perPage,
), 1, $perPage, $total);

$page    = $pagination->page;
$perPage = $pagination->perPage;
$pages   = $pagination->pages();

function buildPageUrl(int $pageNumber, int $perPage, string $q): string
{
    $query = array('page' => $pageNumber, 'perPage' => $perPage);
    if ($q !== '') {
        $query['q'] = $q;
    }
    $qs = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return '/comercial/entidades' . ($qs !== '' ? '?' . $qs : '');
}
?>
<link rel="stylesheet" href="/css/comercial_style/entidades-cards.css">
<section class="ent-list ent-list--cards" aria-labelledby="ent-cards-title">
  <?php if ($toastMessage !== null): ?>
    <div id="ent-toast" class="ent-toast" role="status" aria-live="polite"><?= h($toastMessage) ?></div>
  <?php endif; ?>
  <?php if ($errorMessage !== null): ?>
    <div class="ent-alert ent-alert--error" role="alert"><?= h($errorMessage) ?></div>
  <?php endif; ?>
  <header class="ent-toolbar" role="search">
    <div class="ent-toolbar__lead">
      <h1 id="ent-cards-title" class="ent-title">Entidades financieras</h1>
      <p class="ent-toolbar__caption" aria-live="polite">
        <?= h((string)$total) ?> entidades · Página <?= h((string)$page) ?> de <?= h((string)$pages) ?>
      </p>
    </div>
    <a class="btn btn-primary" href="/comercial/entidades/crear">Nueva entidad</a>
    <form class="ent-search" action="/comercial/entidades" method="get">
      <label for="ent-search-input">Buscar por nombre o RUC</label>
      <input id="ent-search-input" type="text" name="q" value="<?= h($q) ?>" placeholder="Cooperativa..." minlength="3">
      <button class="btn btn-outline" type="submit">Buscar</button>
    </form>
  </header>

  <?php if (empty($items)): ?>
    <p class="ent-empty" role="status">No se encontraron entidades.</p>
  <?php else: ?>
    <ul class="ent-cards-grid" role="list">
      <?php foreach ($items as $row): ?>
        <?php
          $entityId = isset($row['id']) ? (int)$row['id'] : 0;
          $nombre   = isset($row['nombre']) ? (string)$row['nombre'] : 'Entidad';
          $segmento = isset($row['segmento']) && $row['segmento'] !== '' ? (string)$row['segmento'] : 'No especificado';
          $prov     = isset($row['provincia']) ? trim((string)$row['provincia']) : '';
          $cant     = isset($row['canton']) ? trim((string)$row['canton']) : '';
          $ubicacion = $prov !== '' && $cant !== '' ? $prov . ' – ' . $cant : ($prov !== '' ? $prov : ($cant !== '' ? $cant : 'No especificado'));
          $telefonos = isset($row['telefono']) && is_array($row['telefono']) ? $row['telefono'] : array();
          if (empty($telefonos) && isset($row['telefono']) && is_string($row['telefono'])) {
              $telefonos = array($row['telefono']);
          }
          $telefonos = array_values(array_filter(array_map('trim', $telefonos), static function ($v) {
              return $v !== '';
          }));
          $emails = isset($row['email']) && is_array($row['email']) ? $row['email'] : array();
          if (empty($emails) && isset($row['email']) && is_string($row['email'])) {
              $emails = array($row['email']);
          }
          $emails = array_values(array_filter(array_map('trim', $emails), static function ($v) {
              return $v !== '';
          }));
          $servicios = isset($row['servicios']) && is_array($row['servicios']) ? $row['servicios'] : array();
          if (empty($servicios) && isset($row['servicios']) && is_string($row['servicios'])) {
              $servicios = array($row['servicios']);
          }
          $servicios = array_values(array_filter(array_map('trim', $servicios), static function ($v) {
              return $v !== '';
          }));
        ?>
        <li class="ent-card" role="listitem">
          <article aria-labelledby="ent-card-title-<?= $entityId ?>">
            <header class="ent-card-head">
              <div class="ent-card-icon" aria-hidden="true">
                <span class="material-symbols-outlined" aria-hidden="true">account_balance</span>
              </div>
              <h2 id="ent-card-title-<?= $entityId ?>" class="ent-card-title"><?= h($nombre) ?></h2>
            </header>
            <div class="ent-card-body">
              <dl class="ent-card-details">
                <div>
                  <dt>Segmento</dt>
                  <dd><?= h($segmento) ?></dd>
                </div>
                <div>
                  <dt>Ubicación</dt>
                  <dd><?= h($ubicacion) ?></dd>
                </div>
                <div>
                  <dt>Teléfonos</dt>
                  <dd>
                    <?php if ($telefonos): ?>
                      <ul class="ent-card-list">
                        <?php foreach ($telefonos as $phone): ?>
                          <li><?= h($phone) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php else: ?>
                      <span>No especificado</span>
                    <?php endif; ?>
                  </dd>
                </div>
                <div>
                  <dt>Email</dt>
                  <dd>
                    <?php if ($emails): ?>
                      <ul class="ent-card-list">
                        <?php foreach ($emails as $mail): ?>
                          <li><?= h($mail) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php else: ?>
                      <span>No especificado</span>
                    <?php endif; ?>
                  </dd>
                </div>
                <div>
                  <dt>Servicios</dt>
                  <dd>
                    <?php if ($servicios): ?>
                      <ul class="ent-card-badges">
                        <?php foreach ($servicios as $svc): ?>
                          <li class="ent-badge"><?= h($svc) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php else: ?>
                      <span>No especificado</span>
                    <?php endif; ?>
                  </dd>
                </div>
              </dl>
            </div>
            <footer class="ent-card-actions">
              <button type="button" class="btn btn-outline ent-card-view" data-id="<?= $entityId ?>">Ver</button>
              <a class="btn btn-secondary" href="/comercial/entidades/editar?id=<?= $entityId ?>">Editar</a>
              <form method="post" action="/comercial/entidades/eliminar" class="ent-card-delete" onsubmit="return confirm('¿Eliminar entidad?');">
                <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="id" value="<?= $entityId ?>">
                <button type="submit" class="btn btn-danger">Eliminar</button>
              </form>
            </footer>
          </article>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if ($pages > 1): ?>
    <nav class="ent-pagination" aria-label="Paginación">
      <ul>
        <li><a class="btn" href="<?= h(buildPageUrl(max(1, $page - 1), $perPage, $q)) ?>" aria-label="Página anterior">«</a></li>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <li>
            <?php if ($i === $page): ?>
              <span class="btn btn--active" aria-current="page"><?= h((string)$i) ?></span>
            <?php else: ?>
              <a class="btn" href="<?= h(buildPageUrl($i, $perPage, $q)) ?>"><?= h((string)$i) ?></a>
            <?php endif; ?>
          </li>
        <?php endfor; ?>
        <li><a class="btn" href="<?= h(buildPageUrl(min($pages, $page + 1), $perPage, $q)) ?>" aria-label="Página siguiente">»</a></li>
      </ul>
    </nav>
  <?php endif; ?>
</section>

<div id="ent-modal" class="ent-modal" role="dialog" aria-modal="true" aria-labelledby="ent-modal-title" hidden>
  <div class="ent-modal__backdrop" data-dismiss="modal"></div>
  <div class="ent-modal__dialog" role="document">
    <header class="ent-modal__header">
      <h2 id="ent-modal-title">Detalle de entidad</h2>
      <button type="button" class="ent-modal__close" aria-label="Cerrar" data-dismiss="modal">×</button>
    </header>
    <div class="ent-modal__body" id="ent-modal-content"></div>
    <footer class="ent-modal__footer">
      <button type="button" class="btn" data-dismiss="modal">Cerrar</button>
    </footer>
  </div>
</div>

<script src="/js/entidades_cards.js" defer></script>
<script>
(function() {
  var toast = document.getElementById('ent-toast');
  if (toast) {
    setTimeout(function() {
      toast.classList.add('is-hidden');
      setTimeout(function() {
        if (toast && toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 400);
    }, 10000);
  }
})();
</script>
