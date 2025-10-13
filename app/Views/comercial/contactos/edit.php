<?php
/** @var array<string,mixed> $contacto */
/** @var array<int,array{id:int,nombre:string}> $entidades */

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$contactId    = (int)($contacto['id'] ?? 0);
$nombre       = $contacto['nombre'] ?? '';
$idEntidad    = (int)($contacto['id_entidad'] ?? 0);
$titulo       = $contacto['titulo'] ?? '';
$cargo        = $contacto['cargo'] ?? '';
$telefono     = $contacto['telefono'] ?? '';
$correo       = $contacto['correo'] ?? '';
$nota         = $contacto['nota'] ?? '';
$fechaEvento  = $contacto['fecha_evento'] ?? date('Y-m-d');
?>
<section class="ent-container" aria-labelledby="editar-contacto-title">
  <header class="ent-toolbar">
    <div class="ent-toolbar__lead">
      <h1 id="editar-contacto-title" class="ent-title">Editar contacto</h1>
      <p class="ent-toolbar__caption">Modifica la información y guarda los cambios.</p>
    </div>
  </header>

  <section class="card" aria-labelledby="form-editar-contacto">
    <h2 id="form-editar-contacto" class="ent-title">Información del contacto</h2>
    <form method="post" action="/comercial/contactos/<?= h((string)$contactId) ?>" class="form ent-form">
      <div class="form-row">
        <label for="contacto-entidad">Entidad</label>
        <select id="contacto-entidad" name="id_entidad" required>
          <?php foreach ($entidades as $ent): ?>
            <option value="<?= h((string)$ent['id']) ?>" <?= $ent['id'] === $idEntidad ? 'selected' : '' ?>>
              <?= h($ent['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <label for="contacto-nombre">Nombre</label>
        <input id="contacto-nombre" type="text" name="nombre" value="<?= h($nombre) ?>" required>
      </div>
      <div class="form-row">
        <label for="contacto-fecha">Fecha del evento</label>
        <input id="contacto-fecha" type="date" name="fecha_evento" value="<?= h($fechaEvento) ?>" required>
      </div>
      <div class="form-row">
        <label for="contacto-titulo">Título</label>
        <input id="contacto-titulo" type="text" name="titulo" value="<?= h($titulo) ?>">
      </div>
      <div class="form-row">
        <label for="contacto-cargo">Cargo</label>
        <input id="contacto-cargo" type="text" name="cargo" value="<?= h($cargo) ?>">
      </div>
      <div class="form-row">
        <label for="contacto-telefono">Celular</label>
        <input id="contacto-telefono" type="text" name="telefono" value="<?= h($telefono) ?>" inputmode="numeric" pattern="[0-9]{10}" maxlength="10">
      </div>
      <div class="form-row">
        <label for="contacto-correo">Correo</label>
        <input id="contacto-correo" type="email" name="correo" value="<?= h($correo) ?>">
      </div>
      <div class="form-row">
        <label for="contacto-nota">Nota</label>
        <textarea id="contacto-nota" name="nota"><?= h($nota) ?></textarea>
      </div>
      <div class="form-actions ent-actions">
        <button class="btn btn-primary" type="submit">Guardar cambios</button>
        <a class="btn btn-cancel" href="/comercial/contactos">Cancelar</a>
      </div>
    </form>
  </section>
</section>
