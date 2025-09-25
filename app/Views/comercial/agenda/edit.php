<?php
/** @var array<int,array{id:int,nombre:string}> $entidades */
/** @var array<string,string> $estados */
/** @var array<string,string> $errors */
/** @var array<string,mixed> $old */
/** @var array<string,mixed> $evento */
/** @var string|null $csrf */

$entidades = $entidades ?? [];
$estados = $estados ?? [];
$errors = $errors ?? [];
$old = $old ?? [];
$evento = $evento ?? [];
$csrf = $csrf ?? (function_exists('csrf_token') ? csrf_token() : null);

$eventoId = $evento['id'] ?? $old['id'] ?? null;
?>
<section class="agenda-wrapper">
    <header class="agenda-header">
        <h1>Editar evento de la agenda</h1>
        <p>Actualiza la información registrada para este evento.</p>
    </header>
    <?php if ($eventoId === null): ?>
        <div class="agenda-empty">
            <p>No se encontró el evento solicitado.</p>
            <p><a class="agenda-link" href="/comercial/agenda">Volver al listado</a></p>
        </div>
    <?php else: ?>
        <?php
            $action = '/comercial/agenda/' . rawurlencode((string)$eventoId) . '/editar';
            $submitLabel = 'Actualizar evento';
        ?>
        <?php include __DIR__ . '/_form.php'; ?>
        <p class="agenda-back-link">
            <a href="/comercial/agenda" class="agenda-link">&larr; Volver al listado</a>
        </p>
    <?php endif; ?>
</section>
