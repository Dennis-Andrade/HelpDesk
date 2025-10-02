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

  <header class="agenda-page__header agenda-page__header--list">
    <div>
      <h2>Listado de contactos</h2>
      <p>Consulta y administra los contactos registrados.</p>
    </div>
  </header>

  <div class="agenda-card agenda-card--list">
    <form method="get" class="agenda-filters agenda-filters--inline" action="/comercial/agenda">
      <label>
        Buscar
        <input type="text" name="q" value="<?= $filterText ?>" placeholder="Nombre, cargo o entidad">
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
      <div class="agenda-filters__actions agenda-filters__actions--list">
        <button type="submit" class="agenda-button agenda-button--primary"><span class="material-icons">search</span> Buscar</button>
        <a class="agenda-button agenda-button--ghost" href="/comercial/agenda"><span class="material-icons">backspace</span> Limpiar</a>
      </div>
    </form>

    <div class="agenda-table-wrapper agenda-table-wrapper--simple">
      <table class="agenda-table agenda-table--simple">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Entidad</th>
            <th>Cargo</th>
            <th>Teléfono</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="5" class="agenda-table__empty">Sin contactos registrados.</td></tr>
        <?php else: ?>
          <?php foreach ($items as $item): ?>
            <?php
              $id = (int)($item['id'] ?? 0);
              $nombre = agenda_h($item['oficial_nombre'] ?? ($item['contacto'] ?? ''));
              $entidad = agenda_h($item['coop_nombre'] ?? '—');
              $cargo = agenda_h($item['cargo'] ?? '');
              $telefono = agenda_h($item['telefono_contacto'] ?? ($item['coop_telefono'] ?? ''));
            ?>
            <tr>
              <td><?= $nombre !== '' ? $nombre : '—' ?></td>
              <td><?= $entidad !== '' ? $entidad : '—' ?></td>
              <td><?= $cargo !== '' ? $cargo : '—' ?></td>
              <td><?= $telefono !== '' ? $telefono : '—' ?></td>
              <td class="agenda-table__actions">
                <a href="/comercial/agenda/<?= $id ?>/editar" class="agenda-button agenda-button--icon"><span class="material-icons">edit</span> Editar</a>
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

<script src="/js/agenda.js" defer></script>
