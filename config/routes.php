<?php
use App\Controllers\Auth\LoginController;
use App\Controllers\Shared\UbicacionesController;
use App\Controllers\Comercial\DashboardController as ComercialDashboard;
use App\Controllers\Contabilidad\DashboardController as ContabDashboard;
use App\Controllers\Sistemas\DashboardController as SistemasDashboard;
use App\Controllers\Cumplimiento\DashboardController as CumplimientoDashboard;
use App\Controllers\Administrador\DashboardController as AdminDashboard;
use App\Controllers\Comercial\EntidadesController;
use App\Controllers\Comercial\AgendaController;
use App\Controllers\Comercial\ContactosController;
use App\Controllers\Comercial\IncidenciasController;
use App\Controllers\Comercial\SeguimientoController;

// ---------- Auth ----------
$router->get('/login',  [LoginController::class, 'show']);
$router->post('/login', [LoginController::class, 'login']);
$router->get('/logout', [LoginController::class, 'logout']);

// ---------- Home ----------
$router->get('/', [LoginController::class, 'home']);

// ---------- Landings por rol (protegidos) ----------
$router->get('/comercial',     function(){ redirect('/comercial/dashboard'); },     ['middleware'=>['auth']]);
$router->get('/contabilidad',  function(){ redirect('/contabilidad/dashboard'); },  ['middleware'=>['auth']]);
$router->get('/sistemas',      function(){ redirect('/sistemas/dashboard'); },      ['middleware'=>['auth']]);
$router->get('/cumplimiento',  function(){ redirect('/cumplimiento/dashboard'); },  ['middleware'=>['auth']]);
$router->get('/administrador', function(){ redirect('/administrador/dashboard'); }, ['middleware'=>['auth']]);

// ---------- Dashboards (auth + role) ----------
$router->get('/comercial/dashboard',     [ComercialDashboard::class,   'index'], ['middleware'=>['auth','role:comercial,administrador']]);
$router->get('/contabilidad/dashboard',  [ContabDashboard::class,      'index'], ['middleware'=>['auth','role:contabilidad,administrador']]);
$router->get('/sistemas/dashboard',      [SistemasDashboard::class,    'index'], ['middleware'=>['auth','role:sistemas,administrador']]);
$router->get('/cumplimiento/dashboard',  [CumplimientoDashboard::class,'index'], ['middleware'=>['auth','role:cumplimiento,administrador']]);
$router->get('/administrador/dashboard', [AdminDashboard::class,       'index'], ['middleware'=>['auth','role:administrador']]);
$router->get(
    '/comercial/entidades/cards',
    function () {
        header('Location: /comercial/entidades', true, 301);
        exit;
    },
    ['middleware'=>['auth','role:comercial']]
);

// ---------- Comercial → Entidades (CRUD, auth + role) ----------
$router->get(
    '/comercial/entidades',
    [EntidadesController::class, 'index'],
    ['middleware'=>['auth','role:comercial']]
);
$router->get('/comercial/entidades/show', [EntidadesController::class, 'show'], ['middleware'=>['auth','role:comercial']]);
$router->get('/comercial/entidades/{id}/show', [EntidadesController::class, 'showJson'], ['middleware'=>['auth','role:comercial']]);
$router->get('/comercial/entidades/crear',     [EntidadesController::class, 'createForm'], ['middleware'=>['auth','role:comercial,administrador']]);
$router->post('/comercial/entidades',          [EntidadesController::class, 'create'],     ['middleware'=>['auth','role:comercial,administrador']]);
$router->get('/comercial/entidades/editar',    [EntidadesController::class, 'editForm'],   ['middleware'=>['auth','role:comercial,administrador']]);
$router->post('/comercial/entidades/{id}',     [EntidadesController::class, 'update'],     ['middleware'=>['auth','role:comercial,administrador']]);
$router->post('/comercial/entidades/eliminar', [EntidadesController::class, 'delete'],     ['middleware'=>['auth','role:comercial,administrador']]);

// 

$router->get('/comercial/agenda',                [AgendaController::class, 'index'],        ['middleware'=>['auth','role:comercial']]);
$router->post('/comercial/agenda',               [AgendaController::class, 'store'],        ['middleware'=>['auth','role:comercial']]);
$router->get('/comercial/agenda/exportar',       [AgendaController::class, 'export'],       ['middleware'=>['auth','role:comercial']]);
$router->post('/comercial/agenda/{id}/estado',   [AgendaController::class, 'changeStatus'], ['middleware'=>['auth','role:comercial']]);
$router->post('/comercial/agenda/{id}/eliminar', [AgendaController::class, 'delete'],       ['middleware'=>['auth','role:comercial']]);

// AJAX: cantones por provincia (auth)
$router->get('/shared/cantones', [UbicacionesController::class, 'cantones'], ['middleware'=>['auth']]);
// ---------- Comercial → Contactos (CRUD) ----------
$router->get(
    '/comercial/contactos',
    [ContactosController::class, 'index'],
    ['middleware'=>['auth','role:comercial']]
);
$router->get(
    '/comercial/contactos/sugerencias',
    [ContactosController::class, 'suggest'],
    ['middleware'=>['auth','role:comercial']]
);
$router->post(
    '/comercial/contactos',
    [ContactosController::class, 'create'],
    ['middleware'=>['auth','role:comercial']]
);
$router->get(
    '/comercial/contactos/{id}/editar',
    [ContactosController::class, 'editForm'],
    ['middleware'=>['auth','role:comercial']]
);
$router->post(
    '/comercial/contactos/{id}/editar',
    [ContactosController::class, 'update'],
    ['middleware'=>['auth','role:comercial']]
);
$router->post(
    '/comercial/contactos/{id}/eliminar',
    [ContactosController::class, 'delete'],
    ['middleware'=>['auth','role:comercial']]
);


$router->get(
    '/comercial/eventos',
    [SeguimientoController::class, 'index'],
    ['middleware'=>['auth','role:comercial']]
);
$router->get(
    '/comercial/eventos/contactos',
    [SeguimientoController::class, 'contactos'],
    ['middleware'=>['auth','role:comercial']]
);
$router->get(
    '/comercial/eventos/tickets/buscar',
    [SeguimientoController::class, 'ticketSearch'],
    ['middleware'=>['auth','role:comercial']]
);
$router->get(
    '/comercial/eventos/tickets/{id}',
    [SeguimientoController::class, 'ticketInfo'],
    ['middleware'=>['auth','role:comercial']]
);
$router->get(
    '/comercial/eventos/crear',
    [SeguimientoController::class, 'createForm'],
    ['middleware'=>['auth','role:comercial']]
);
$router->post(
    '/comercial/eventos',
    [SeguimientoController::class, 'store'],
    ['middleware'=>['auth','role:comercial']]
);
$router->post(
    '/comercial/eventos/{id}',
    [SeguimientoController::class, 'update'],
    ['middleware'=>['auth','role:comercial']]
);
$router->get(
    '/comercial/eventos/exportar',
    [SeguimientoController::class, 'export'],
    ['middleware'=>['auth','role:comercial']]
);
$router->get(
    '/comercial/incidencias',
    [IncidenciasController::class, 'index'],
    ['middleware'=>['auth','role:comercial']]
);
$router->post(
    '/comercial/incidencias',
    [IncidenciasController::class, 'store'],
    ['middleware'=>['auth','role:comercial']]
);
$router->post(
    '/comercial/incidencias/{id}',
    [IncidenciasController::class, 'update'],
    ['middleware'=>['auth','role:comercial']]
);
$router->post(
    '/comercial/incidencias/{id}/eliminar',
    [IncidenciasController::class, 'delete'],
    ['middleware'=>['auth','role:comercial']]
);
