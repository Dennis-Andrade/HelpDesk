<?php
/** @var array<int,array{id:int,nombre:string}> $entidades */
/** @var array<string,string> $estados */
/** @var array<string,string> $errors */
/** @var array<string,mixed> $old */
/** @var string|null $csrf */

$entidades = $entidades ?? [];
$estados = $estados ?? [];
$errors = $errors ?? [];
$old = $old ?? [];
$csrf = $csrf ?? (function_exists('csrf_token') ? csrf_token() : null);

$action = '/comercial/agenda';
$submitLabel = 'Registrar evento';
?>
<section class="agenda-wrapper">
    <header class="agenda-header">
        <h1>Registrar evento en la agenda</h1>
        <p>Completa los campos para agendar una actividad vinculada a una entidad.</p>
    </header>
    <?php include __DIR__ . '/_form.php'; ?>
    <p class="agenda-back-link">
        <a href="/comercial/agenda" class="agenda-link">&larr; Volver al listado</a>
    </p>
</section>
