<?php
use DateTimeImmutable;
use Exception;
/** @var string $action */
/** @var string $submitLabel */
/** @var array<string,string> $estados */
/** @var array<int,array{id:int,nombre:string}> $entidades */
/** @var array<string,string> $errors */
/** @var array<string,mixed> $old */
/** @var array<string,mixed>|null $evento */
/** @var string|null $csrf */

$errors = $errors ?? [];
$old = $old ?? [];
$evento = $evento ?? [];
$estados = $estados ?? [];
$entidades = $entidades ?? [];
$csrf = $csrf ?? null;

$valores = array_merge([
    'id'                 => $evento['id'] ?? null,
    'id_cooperativa'     => '',
    'titulo'             => '',
    'descripcion'        => '',
    'fecha_evento'       => '',
    'telefono_contacto'  => '',
    'email_contacto'     => '',
    'estado'             => 'pendiente',
], $evento, $old);

$fechaEntrada = (string)($valores['fecha_evento'] ?? '');
$fechaInput = '';
if ($fechaEntrada !== '') {
    try {
        $dt = new DateTimeImmutable($fechaEntrada);
        $fechaInput = $dt->format('Y-m-d\TH:i');
    } catch (Exception $e) {
        $fechaInput = $fechaEntrada;
    }
}

$estadoActual = (string)($valores['estado'] ?? 'pendiente');
$cooperativaActual = (string)($valores['id_cooperativa'] ?? '');
$tituloActual = (string)($valores['titulo'] ?? '');
$descripcionActual = (string)($valores['descripcion'] ?? '');
$telefonoActual = (string)($valores['telefono_contacto'] ?? '');
$emailActual = (string)($valores['email_contacto'] ?? '');
?>
<form action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" method="post" class="agenda-form">
    <?php if ($csrf !== null): ?>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <?php elseif (function_exists('csrf_field')): ?>
        <?php csrf_field(); ?>
    <?php endif; ?>

    <div class="agenda-form__row">
        <label for="agenda-id-cooperativa">Entidad</label>
        <?php $error = $errors['id_cooperativa'] ?? null; $errorId = $error ? 'agenda-error-id-cooperativa' : null; ?>
        <select
            id="agenda-id-cooperativa"
            name="id_cooperativa"
            required
            <?= $error ? 'aria-invalid="true"' : '' ?>
            <?= $errorId ? 'aria-describedby="' . $errorId . '"' : '' ?>
        >
            <option value="">Seleccione una entidad</option>
            <?php foreach ($entidades as $entidad): ?>
                <?php
                    $optionId = (string)$entidad['id'];
                    $selected = $optionId === $cooperativaActual ? 'selected' : '';
                ?>
                <option value="<?= htmlspecialchars($optionId, ENT_QUOTES, 'UTF-8') ?>" <?= $selected ?>>
                    <?= htmlspecialchars($entidad['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($error): ?>
            <p class="agenda-form__error" id="<?= $errorId ?>" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="agenda-form__row">
        <label for="agenda-titulo">Título</label>
        <?php $error = $errors['titulo'] ?? null; $errorId = $error ? 'agenda-error-titulo' : null; ?>
        <input
            type="text"
            id="agenda-titulo"
            name="titulo"
            maxlength="160"
            value="<?= htmlspecialchars($tituloActual, ENT_QUOTES, 'UTF-8') ?>"
            required
            <?= $error ? 'aria-invalid="true"' : '' ?>
            <?= $errorId ? 'aria-describedby="' . $errorId . '"' : '' ?>
        >
        <?php if ($error): ?>
            <p class="agenda-form__error" id="<?= $errorId ?>" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="agenda-form__row">
        <label for="agenda-fecha">Fecha y hora</label>
        <?php $error = $errors['fecha_evento'] ?? null; $errorId = $error ? 'agenda-error-fecha' : null; ?>
        <input
            type="datetime-local"
            id="agenda-fecha"
            name="fecha_evento"
            value="<?= htmlspecialchars($fechaInput, ENT_QUOTES, 'UTF-8') ?>"
            required
            <?= $error ? 'aria-invalid="true"' : '' ?>
            <?= $errorId ? 'aria-describedby="' . $errorId . '"' : '' ?>
        >
        <?php if ($error): ?>
            <p class="agenda-form__error" id="<?= $errorId ?>" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="agenda-form__row">
        <label for="agenda-descripcion">Descripción</label>
        <?php $error = $errors['descripcion'] ?? null; $errorId = $error ? 'agenda-error-descripcion' : null; ?>
        <textarea
            id="agenda-descripcion"
            name="descripcion"
            rows="4"
            <?= $error ? 'aria-invalid="true"' : '' ?>
            <?= $errorId ? 'aria-describedby="' . $errorId . '"' : '' ?>
        ><?= htmlspecialchars($descripcionActual, ENT_QUOTES, 'UTF-8') ?></textarea>
        <?php if ($error): ?>
            <p class="agenda-form__error" id="<?= $errorId ?>" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="agenda-form__group">
        <div class="agenda-form__row">
            <label for="agenda-telefono">Teléfono de contacto</label>
            <?php $error = $errors['telefono_contacto'] ?? null; $errorId = $error ? 'agenda-error-telefono' : null; ?>
            <input
                type="text"
                id="agenda-telefono"
                name="telefono_contacto"
                inputmode="tel"
                value="<?= htmlspecialchars($telefonoActual, ENT_QUOTES, 'UTF-8') ?>"
                <?= $error ? 'aria-invalid="true"' : '' ?>
                <?= $errorId ? 'aria-describedby="' . $errorId . '"' : '' ?>
            >
            <?php if ($error): ?>
                <p class="agenda-form__error" id="<?= $errorId ?>" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>

        <div class="agenda-form__row">
            <label for="agenda-email">Email de contacto</label>
            <?php $error = $errors['email_contacto'] ?? null; $errorId = $error ? 'agenda-error-email' : null; ?>
            <input
                type="email"
                id="agenda-email"
                name="email_contacto"
                value="<?= htmlspecialchars($emailActual, ENT_QUOTES, 'UTF-8') ?>"
                <?= $error ? 'aria-invalid="true"' : '' ?>
                <?= $errorId ? 'aria-describedby="' . $errorId . '"' : '' ?>
            >
            <?php if ($error): ?>
                <p class="agenda-form__error" id="<?= $errorId ?>" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="agenda-form__row">
        <label for="agenda-estado">Estado</label>
        <?php $error = $errors['estado'] ?? null; $errorId = $error ? 'agenda-error-estado' : null; ?>
        <select
            id="agenda-estado"
            name="estado"
            <?= $error ? 'aria-invalid="true"' : '' ?>
            <?= $errorId ? 'aria-describedby="' . $errorId . '"' : '' ?>
        >
            <?php foreach ($estados as $valor => $label): ?>
                <?php $selected = $valor === $estadoActual ? 'selected' : ''; ?>
                <option value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>" <?= $selected ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($error): ?>
            <p class="agenda-form__error" id="<?= $errorId ?>" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="agenda-form__actions">
        <button type="submit" class="agenda-form__submit"><?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?></button>
    </div>
</form>
