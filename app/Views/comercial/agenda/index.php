<?php
/** @var array<string,mixed> $filters */
/** @var array<int,array{id:int,nombre:string}> $cooperativas */
/** @var array<int,array<string,mixed>> $items */
/** @var array<string,string> $formErrors */
/** @var array<string,mixed> $formOld */
/** @var string|null $notice */
/** @var string|null $errorNotice */

$filters = is_array($filters) ? $filters : [];
$cooperativas = is_array($cooperativas) ? $cooperativas : [];
$items = is_array($items) ? $items : [];
$formErrors = is_array($formErrors) ? $formErrors : [];
$formOld = is_array($formOld) ? $formOld : [];
$notice = isset($notice) && $notice !== '' ? (string)$notice : null;
$errorNotice = isset($errorNotice) && $errorNotice !== '' ? (string)$errorNotice : null;

function agenda_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function agenda_field(array $old, string $key, string $default = ''): string
{
    if (array_key_exists($key, $old)) {
        return (string)$old[$key];
    }
    return $default;
}

$filterText = agenda_h($filters['q'] ?? '');
$filterCoop = agenda_h((string)($filters['coop'] ?? ''));
$filterEstado = agenda_h($filters['estado'] ?? '');

?>
<link rel="stylesheet" href="/css/agenda.css">
<section class="agenda-page">
  <header class="agenda-page__header">
    <div>
      <h1>Agenda de contactos</h1>
      <p>Registra y consulta los contactos clave de cada entidad comercial.</p>
    </div>
    <a class="agenda-page__export" href="/comercial/agenda/exportar<?= $filterText !== '' || $filterCoop !== '' || $filterEstado !== '' ? '?' . agenda_h(http_build_query(array_filter([
        'q' => $filters['q'] ?? '',
        'coop' => $filters['coop'] ?? '',
        'estado' => $filters['estado'] ?? '',
    ], static function ($value) {
        return $value !== '' && $value !== null;
    }), '', '&', PHP_QUERY_RFC3986)) : '' ?>">
      <span class="material-icons">download</span> Descargar VCF
    </a>
  </header>

  <?php if ($notice !== null): ?>
    <div class="agenda-page__toast agenda-page__toast--ok" role="status"><?= agenda_h($notice) ?></div>
  <?php endif; ?>
  <?php if ($errorNotice !== null): ?>
    <div class="agenda-page__toast agenda-page__toast--error" role="alert"><?= agenda_h($errorNotice) ?></div>
  <?php endif; ?>

  <div class="agenda-card">
    <h2 class="agenda-card__title">Nuevo contacto</h2>
    <form action="/comercial/agenda" method="post" class="agenda-form">
      <div class="agenda-form__grid">
        <label>
          Entidad
          <select name="id_cooperativa">
            <option value="">Selecciona una cooperativa (opcional)</option>
            <?php foreach ($cooperativas as $coop): ?>
              <?php $selected = agenda_field($formOld, 'id_cooperativa') === (string)$coop['id'] ? 'selected' : ''; ?>
              <option value="<?= (int)$coop['id'] ?>" <?= $selected ?>><?= agenda_h($coop['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($formErrors['id_cooperativa'])): ?>
            <small class="agenda-form__error"><?= agenda_h($formErrors['id_cooperativa']) ?></small>
          <?php endif; ?>
        </label>

        <label>
          Nombre de contacto
          <input type="text" name="oficial_nombre" placeholder="Ej.: Ana Pérez" maxlength="100" value="<?= agenda_h(agenda_field($formOld, 'oficial_nombre')) ?>">
        </label>

        <label>
          Teléfono de contacto
          <input type="tel" name="telefono_contacto" placeholder="Ej.: 0998765432" minlength="7" maxlength="10" value="<?= agenda_h(agenda_field($formOld, 'telefono_contacto')) ?>">
          <?php if (isset($formErrors['telefono_contacto'])): ?>
            <small class="agenda-form__error"><?= agenda_h($formErrors['telefono_contacto']) ?></small>
          <?php endif; ?>
        </label>

        <label>
          Correo electrónico
          <input type="email" name="oficial_correo" placeholder="Ej.: contacto@coac.ec" maxlength="120" value="<?= agenda_h(agenda_field($formOld, 'oficial_correo')) ?>">
          <?php if (isset($formErrors['oficial_correo'])): ?>
            <small class="agenda-form__error"><?= agenda_h($formErrors['oficial_correo']) ?></small>
          <?php endif; ?>
        </label>

        <label>
          Fecha del evento *
          <input type="date" name="fecha_evento" required value="<?= agenda_h(agenda_field($formOld, 'fecha_evento')) ?>">
          <?php if (isset($formErrors['fecha_evento'])): ?>
            <small class="agenda-form__error"><?= agenda_h($formErrors['fecha_evento']) ?></small>
          <?php endif; ?>
        </label>

        <label>
          Título *
          <input type="text" name="titulo" required maxlength="150" placeholder="Ej.: Reunión de seguimiento" value="<?= agenda_h(agenda_field($formOld, 'titulo')) ?>">
          <?php if (isset($formErrors['titulo'])): ?>
            <small class="agenda-form__error"><?= agenda_h($formErrors['titulo']) ?></small>
          <?php endif; ?>
        </label>

        <label>
          Cargo
          <input type="text" name="cargo" maxlength="100" placeholder="Ej.: Jefe de Sistemas" value="<?= agenda_h(agenda_field($formOld, 'cargo')) ?>">
        </label>

        <label class="agenda-form__full">
          Nota
          <textarea name="nota" rows="3" placeholder="Detalles adicionales, acuerdos o recordatorios."><?= agenda_h(agenda_field($formOld, 'nota')) ?></textarea>
        </label>
      </div>
      <div class="agenda-form__actions">
        <button type="submit" class="agenda-button agenda-button--primary">
          <span class="material-icons">save</span> Guardar
        </button>
      </div>
    </form>
  </div>

  <div class="agenda-card">
    <h2 class="agenda-card__title">Listado de contactos</h2>
    <form method="get" class="agenda-filters" action="/comercial/agenda">
      <label>
        Buscar
        <input type="text" name="q" value="<?= $filterText ?>" placeholder="Entidad, nombre o cargo">
      </label>
      <label>
        Entidad
        <select name="coop">
          <option value="">Todas</option>
          <?php foreach ($cooperativas as $coop): ?>
            <?php $selected = ($filterCoop !== '' && $filterCoop === (string)$coop['id']) ? 'selected' : ''; ?>
            <option value="<?= (int)$coop['id'] ?>" <?= $selected ?>><?= agenda_h($coop['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        Estado
        <select name="estado">
          <option value="">Todos</option>
          <?php foreach (['Pendiente','Completado','Cancelado'] as $estado): ?>
            <?php $val = agenda_h($estado); ?>
            <option value="<?= $val ?>" <?= $filterEstado === $val ? 'selected' : '' ?>><?= $val ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="agenda-filters__actions">
        <button type="submit" class="agenda-button agenda-button--primary"><span class="material-icons">filter_alt</span> Filtrar</button>
        <a class="agenda-button agenda-button--ghost" href="/comercial/agenda"><span class="material-icons">backspace</span> Limpiar</a>
      </div>
    </form>

    <div class="agenda-table-wrapper">
      <table class="agenda-table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Título</th>
            <th>Entidad</th>
            <th>Contacto</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="6" class="agenda-table__empty">Sin contactos registrados.</td></tr>
        <?php else: ?>
          <?php foreach ($items as $item): ?>
            <?php
              $id = (int)($item['id'] ?? 0);
              $fecha = agenda_h($item['fecha_evento'] ?? '');
              $titulo = agenda_h($item['titulo'] ?? '');
              $coopNombre = agenda_h($item['coop_nombre'] ?? '—');
              $nombre = agenda_h($item['oficial_nombre'] ?? '');
              $telefono = agenda_h($item['telefono_contacto'] ?? ($item['coop_telefono'] ?? ''));
              $correo = agenda_h($item['oficial_correo'] ?? ($item['coop_email'] ?? ''));
              $cargo = agenda_h($item['cargo'] ?? '');
              $nota = agenda_h($item['nota'] ?? '');
              $estado = agenda_h($item['estado'] ?? 'Pendiente');
            ?>
            <tr
              data-agenda-id="<?= $id ?>"
              data-agenda-fecha="<?= $fecha ?>"
              data-agenda-titulo="<?= $titulo ?>"
              data-agenda-entidad="<?= $coopNombre ?>"
              data-agenda-nombre="<?= $nombre ?>"
              data-agenda-telefono="<?= $telefono ?>"
              data-agenda-correo="<?= $correo ?>"
              data-agenda-cargo="<?= $cargo ?>"
              data-agenda-nota="<?= $nota ?>"
              data-agenda-estado="<?= $estado ?>"
            >
              <td><?= $fecha !== '' ? $fecha : '—' ?></td>
              <td><?= $titulo !== '' ? $titulo : '—' ?></td>
              <td><?= $coopNombre !== '' ? $coopNombre : '—' ?></td>
              <td>
                <?php if ($nombre !== ''): ?><div class="agenda-table__detail"><span class="agenda-table__label">Nombre:</span> <?= $nombre ?></div><?php endif; ?>
                <?php if ($telefono !== ''): ?><div class="agenda-table__detail"><span class="agenda-table__label">Tel:</span> <?= $telefono ?></div><?php endif; ?>
                <?php if ($correo !== ''): ?><div class="agenda-table__detail"><span class="agenda-table__label">Correo:</span> <?= $correo ?></div><?php endif; ?>
                <?php if ($nombre === '' && $telefono === '' && $correo === ''): ?><span class="agenda-table__detail">—</span><?php endif; ?>
              </td>
              <td><span class="agenda-badge agenda-badge--<?= strtolower($estado) ?>"><?= $estado ?></span></td>
              <td class="agenda-table__actions">
                <button type="button" class="agenda-button agenda-button--icon" data-agenda-open><span class="material-icons">visibility</span> Ver</button>
                <form action="/comercial/agenda/<?= $id ?>/eliminar" method="post" class="agenda-inline-form" onsubmit="return confirm('¿Eliminar este contacto?');">
                  <button type="submit" class="agenda-button agenda-button--danger"><span class="material-icons">delete</span> Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<div class="agenda-modal" id="agenda-modal" hidden>
  <div class="agenda-modal__card">
    <header class="agenda-modal__header">
      <h3><span class="material-icons">event</span> Detalle del contacto</h3>
      <button type="button" class="agenda-button agenda-button--icon" data-agenda-close><span class="material-icons">close</span></button>
    </header>
    <div class="agenda-modal__body">
      <dl class="agenda-modal__list">
        <div><dt>Fecha</dt><dd data-agenda-field="fecha">—</dd></div>
        <div><dt>Título</dt><dd data-agenda-field="titulo">—</dd></div>
        <div><dt>Entidad</dt><dd data-agenda-field="entidad">—</dd></div>
        <div><dt>Nombre</dt><dd data-agenda-field="nombre">—</dd></div>
        <div><dt>Teléfono</dt><dd data-agenda-field="telefono">—</dd></div>
        <div><dt>Correo</dt><dd data-agenda-field="correo">—</dd></div>
        <div><dt>Cargo</dt><dd data-agenda-field="cargo">—</dd></div>
        <div class="agenda-modal__full"><dt>Nota</dt><dd data-agenda-field="nota">—</dd></div>
        <div><dt>Estado</dt><dd data-agenda-field="estado">—</dd></div>
      </dl>
    </div>
    <footer class="agenda-modal__footer">
      <form action="" method="post" data-agenda-status-form class="agenda-inline-form">
        <input type="hidden" name="estado" value="Completado">
        <button type="submit" class="agenda-button agenda-button--primary"><span class="material-icons">check_circle</span> Marcar como completado</button>
      </form>
      <form action="" method="post" data-agenda-cancel-form class="agenda-inline-form">
        <input type="hidden" name="estado" value="Cancelado">
        <button type="submit" class="agenda-button agenda-button--ghost"><span class="material-icons">cancel</span> Cancelar</button>
      </form>
    </footer>
  </div>
</div>
<div class="agenda-modal__backdrop" id="agenda-modal-backdrop" hidden></div>

<script src="/js/agenda.js" defer></script>
