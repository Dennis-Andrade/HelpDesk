<?php
/** @var array<int,array<string,mixed>> $items */
/** @var array<string,string> $filters */
/** @var array<string,string> $estados */
/** @var array<int,array{id:int,nombre:string}> $entidades */
/** @var string $csrf */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string|null $flashError */
/** @var string|null $flashOk */
/** @var array<string,mixed>|null $editTarget */
/** @var array<string,string> $editErrors */
/** @var array<string,mixed> $editOld */

$items = $items ?? [];
$filters = $filters ?? ['texto' => '', 'desde' => '', 'hasta' => '', 'estado' => ''];
$estados = $estados ?? [];
$entidades = $entidades ?? [];
$csrf = $csrf ?? (function_exists('csrf_token') ? csrf_token() : '');
$total = isset($total) ? (int)$total : 0;
$page = isset($page) ? max(1, (int)$page) : 1;
$perPage = isset($perPage) ? max(1, (int)$perPage) : 20;
$totalPages = max(1, (int)ceil($total / $perPage));
$flashError = $flashError ?? null;
$flashOk = $flashOk ?? null;
$editTarget = $editTarget ?? null;
$editErrors = $editErrors ?? [];
$editOld = $editOld ?? [];

$texto = htmlspecialchars($filters['texto'] ?? '', ENT_QUOTES, 'UTF-8');
$desde = htmlspecialchars($filters['desde'] ?? '', ENT_QUOTES, 'UTF-8');
$hasta = htmlspecialchars($filters['hasta'] ?? '', ENT_QUOTES, 'UTF-8');
$estadoFiltro = htmlspecialchars($filters['estado'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="/css/agenda.css">
<section class="agenda">
    <header class="agenda__header">
        <div>
            <h1>Agenda comercial</h1>
            <p>Consulta, filtra y administra los eventos agendados para las entidades comerciales.</p>
        </div>
        <a href="#agenda-form-crear" class="agenda__create-link">Nuevo evento</a>
    </header>

    <?php if ($flashOk): ?>
        <div class="agenda__alert agenda__alert--success" role="status"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="agenda__alert agenda__alert--error" role="alert"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <section class="agenda__filters" aria-labelledby="agenda-filtros-titulo">
        <h2 id="agenda-filtros-titulo">Filtros</h2>
        <form action="/comercial/agenda" method="get" class="agenda-filtros-form">
            <div class="agenda-filtros-form__row">
                <label for="agenda-filtro-texto">Entidad</label>
                <input type="text" id="agenda-filtro-texto" name="texto" value="<?= $texto ?>" placeholder="Buscar por nombre">
            </div>
            <div class="agenda-filtros-form__row">
                <label for="agenda-filtro-desde">Desde</label>
                <input type="date" id="agenda-filtro-desde" name="desde" value="<?= $desde ?>">
            </div>
            <div class="agenda-filtros-form__row">
                <label for="agenda-filtro-hasta">Hasta</label>
                <input type="date" id="agenda-filtro-hasta" name="hasta" value="<?= $hasta ?>">
            </div>
            <div class="agenda-filtros-form__row">
                <label for="agenda-filtro-estado">Estado</label>
                <select id="agenda-filtro-estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $valor => $label): ?>
                        <?php $valorEsc = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>
                        <option value="<?= $valorEsc ?>" <?= $valorEsc === $estadoFiltro ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="agenda-filtros-form__actions">
                <button type="submit">Aplicar filtros</button>
                <a class="agenda-link" href="/comercial/agenda">Limpiar</a>
            </div>
        </form>
    </section>

    <div id="agenda-feedback" class="agenda-feedback" role="status" aria-live="polite"></div>

    <div class="agenda__table-wrapper">
        <table class="agenda-table">
            <caption class="sr-only">Listado de eventos agendados</caption>
            <thead>
                <tr>
                    <th scope="col">Fecha</th>
                    <th scope="col">Entidad</th>
                    <th scope="col">Título</th>
                    <th scope="col">Contacto</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="agenda-table__actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="agenda-table__empty">No se encontraron eventos con los criterios seleccionados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $id = isset($item['id']) ? (int)$item['id'] : 0;
                            $fecha = htmlspecialchars((string)($item['fecha_evento'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $entidad = htmlspecialchars((string)($item['cooperativa'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $titulo = htmlspecialchars((string)($item['titulo'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $telefono = htmlspecialchars((string)($item['telefono_contacto'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $email = htmlspecialchars((string)($item['email_contacto'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $estado = htmlspecialchars((string)($item['estado'] ?? ''), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td data-title="Fecha"><?= $fecha ?></td>
                            <td data-title="Entidad"><?= $entidad !== '' ? $entidad : '—' ?></td>
                            <td data-title="Título"><?= $titulo !== '' ? $titulo : '—' ?></td>
                            <td data-title="Contacto">
                                <?php if ($telefono !== ''): ?>
                                    <span class="agenda-table__contacto">Tel: <?= $telefono ?></span>
                                <?php endif; ?>
                                <?php if ($email !== ''): ?>
                                    <span class="agenda-table__contacto">Email: <?= $email ?></span>
                                <?php endif; ?>
                                <?php if ($telefono === '' && $email === ''): ?>
                                    <span class="agenda-table__contacto">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-title="Estado"><span class="agenda-estado agenda-estado--<?= strtolower($estado) ?>"><?= $estado ?></span></td>
                            <td class="agenda-table__actions" data-title="Acciones">
                                <button type="button" class="agenda-btn" data-agenda-view="<?= $id ?>">Ver</button>
                                <a class="agenda-btn agenda-btn--secondary" href="/comercial/agenda?edit=<?= $id ?>">Editar</a>
                                <form action="/comercial/agenda/<?= $id ?>/eliminar" method="post" class="agenda-inline-form">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="agenda-btn agenda-btn--danger" data-agenda-delete>Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="agenda-pagination" aria-label="Paginación">
            <ul>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                        $query = $filters;
                        $query['page'] = $i;
                        $query['per_page'] = $perPage;
                        $url = '/comercial/agenda?' . htmlspecialchars(http_build_query($query), ENT_QUOTES, 'UTF-8');
                    ?>
                    <li>
                        <a href="<?= $url ?>" class="<?= $i === $page ? 'is-active' : '' ?>">Página <?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <section class="agenda__panel" id="agenda-form-crear">
        <h2>Registrar un nuevo evento</h2>
        <?php
            $action = '/comercial/agenda';
            $submitLabel = 'Registrar evento';
            $errors = [];
            $old = [];
            include __DIR__ . '/_form.php';
        ?>
    </section>

    <?php if ($editTarget !== null): ?>
        <section class="agenda__panel agenda__panel--highlight">
            <h2>Editar evento seleccionado</h2>
            <?php
                $action = '/comercial/agenda/' . rawurlencode((string)($editTarget['id'] ?? '')) . '/editar';
                $submitLabel = 'Actualizar evento';
                $errors = $editErrors;
                $old = $editOld;
                $evento = $editTarget;
                include __DIR__ . '/_form.php';
            ?>
        </section>
    <?php endif; ?>
</section>

<div class="agenda-modal" id="agenda-modal" role="dialog" aria-modal="true" aria-labelledby="agenda-modal-title" hidden>
    <div class="agenda-modal__dialog" role="document">
        <button type="button" class="agenda-modal__close" aria-label="Cerrar">&times;</button>
        <h2 id="agenda-modal-title">Detalle del evento</h2>
        <div class="agenda-modal__body" id="agenda-modal-body"></div>
    </div>
</div>
<div class="agenda-modal__backdrop" data-agenda-modal-backdrop hidden></div>

<script src="/js/agenda.js" defer></script>
