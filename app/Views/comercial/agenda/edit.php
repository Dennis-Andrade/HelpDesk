<?php
/** @var array<string,mixed> $item */
/** @var array<int,array{id:int,nombre:string}> $cooperativas */
/** @var array<string,string> $formErrors */
/** @var array<string,mixed> $formOld */

$item = is_array($item) ? $item : [];
$cooperativas = is_array($cooperativas) ? $cooperativas : [];
$formErrors = is_array($formErrors) ? $formErrors : [];
$formOld = is_array($formOld) ? $formOld : [];

function agenda_edit_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function agenda_edit_field(array $old, string $key, string $fallback = ''): string
{
    if (array_key_exists($key, $old)) {
        return (string)$old[$key];
    }
    return $fallback;
}

$contactId = isset($item['id']) ? (int)$item['id'] : 0;
$estadoActual = agenda_edit_field($formOld, 'estado', (string)($item['estado'] ?? 'Pendiente'));
?>
<link rel="stylesheet" href="/css/agenda.css">
<section class="agenda-page">
  <header class="agenda-page__header">
    <div>
      <h1>Editar contacto</h1>
      <p>Actualiza la información registrada de la agenda comercial.</p>
    </div>
    <a class="agenda-page__export" href="/comercial/agenda"><span class="material-icons">arrow_back</span> Volver al listado</a>
  </header>

  <?php if (!empty($formErrors)): ?>
    <div class="agenda-page__toast agenda-page__toast--error" role="alert">Corrige los campos marcados antes de guardar.</div>
  <?php endif; ?>

  <div class="agenda-card">
    <h2 class="agenda-card__title">Datos del contacto</h2>
    <form action="/comercial/agenda/<?= $contactId ?>/actualizar" method="post" class="agenda-form">
      <div class="agenda-form__grid">
        <label>
          Entidad
          <select name="id_cooperativa">
            <option value="">Selecciona una cooperativa (opcional)</option>
            <?php foreach ($cooperativas as $coop): ?>
              <?php $selected = agenda_edit_field($formOld, 'id_cooperativa', (string)($item['id_cooperativa'] ?? '')) === (string)$coop['id'] ? 'selected' : ''; ?>
              <option value="<?= (int)$coop['id'] ?>" <?= $selected ?>><?= agenda_edit_h($coop['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($formErrors['id_cooperativa'])): ?>
            <small class="agenda-form__error"><?= agenda_edit_h($formErrors['id_cooperativa']) ?></small>
          <?php endif; ?>
        </label>

        <label>
          Nombre de contacto
          <input type="text" name="oficial_nombre" maxlength="100" placeholder="Ej.: Ana Pérez" value="<?= agenda_edit_h(agenda_edit_field($formOld, 'oficial_nombre', (string)($item['oficial_nombre'] ?? ($item['contacto'] ?? '')))) ?>">
        </label>

        <label>
          Teléfono de contacto
          <input type="tel" name="telefono_contacto" minlength="7" maxlength="10" placeholder="Ej.: 0998765432" value="<?= agenda_edit_h(agenda_edit_field($formOld, 'telefono_contacto', (string)($item['telefono_contacto'] ?? ''))) ?>">
          <?php if (isset($formErrors['telefono_contacto'])): ?>
            <small class="agenda-form__error"><?= agenda_edit_h($formErrors['telefono_contacto']) ?></small>
          <?php endif; ?>
        </label>

        <label>
          Correo electrónico
          <input type="email" name="oficial_correo" maxlength="120" placeholder="Ej.: contacto@coac.ec" value="<?= agenda_edit_h(agenda_edit_field($formOld, 'oficial_correo', (string)($item['oficial_correo'] ?? ''))) ?>">
          <?php if (isset($formErrors['oficial_correo'])): ?>
            <small class="agenda-form__error"><?= agenda_edit_h($formErrors['oficial_correo']) ?></small>
          <?php endif; ?>
        </label>

        <label>
          Fecha del evento *
          <input type="date" name="fecha_evento" required value="<?= agenda_edit_h(agenda_edit_field($formOld, 'fecha_evento', (string)($item['fecha_evento'] ?? ''))) ?>">
          <?php if (isset($formErrors['fecha_evento'])): ?>
            <small class="agenda-form__error"><?= agenda_edit_h($formErrors['fecha_evento']) ?></small>
          <?php endif; ?>
        </label>

        <label>
          Título *
          <input type="text" name="titulo" required maxlength="150" placeholder="Ej.: Reunión de seguimiento" value="<?= agenda_edit_h(agenda_edit_field($formOld, 'titulo', (string)($item['titulo'] ?? ''))) ?>">
          <?php if (isset($formErrors['titulo'])): ?>
            <small class="agenda-form__error"><?= agenda_edit_h($formErrors['titulo']) ?></small>
          <?php endif; ?>
        </label>

        <label>
          Cargo
          <input type="text" name="cargo" maxlength="100" placeholder="Ej.: Jefe de Sistemas" value="<?= agenda_edit_h(agenda_edit_field($formOld, 'cargo', (string)($item['cargo'] ?? ''))) ?>">
        </label>

        <label>
          Estado
          <select name="estado">
            <?php foreach (['Pendiente','Completado','Cancelado'] as $estado): ?>
              <?php $value = agenda_edit_h($estado); ?>
              <option value="<?= $value ?>" <?= $estadoActual === $estado ? 'selected' : '' ?>><?= $value ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($formErrors['estado'])): ?>
            <small class="agenda-form__error"><?= agenda_edit_h($formErrors['estado']) ?></small>
          <?php endif; ?>
        </label>

        <label class="agenda-form__full">
          Nota
          <textarea name="nota" rows="3" placeholder="Detalles adicionales, acuerdos o recordatorios."><?= agenda_edit_h(agenda_edit_field($formOld, 'nota', (string)($item['nota'] ?? ''))) ?></textarea>
        </label>
      </div>
      <div class="agenda-form__actions">
        <a href="/comercial/agenda" class="agenda-button agenda-button--ghost"><span class="material-icons">close</span> Cancelar</a>
        <button type="submit" class="agenda-button agenda-button--primary">
          <span class="material-icons">save</span> Guardar cambios
        </button>
      </div>
    </form>
  </div>
</section>

<script src="/js/agenda.js" defer></script>
