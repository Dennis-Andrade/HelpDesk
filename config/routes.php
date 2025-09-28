<?php
use App\Controllers\Auth\LoginController;
use App\Controllers\Shared\UbicacionesController;
use App\Controllers\Comercial\DashboardController as ComercialDashboard;
use App\Controllers\Contabilidad\DashboardController as ContabDashboard;
use App\Controllers\Sistemas\DashboardController as SistemasDashboard;
use App\Controllers\Cumplimiento\DashboardController as CumplimientoDashboard;
use App\Controllers\Administrador\DashboardController as AdminDashboard;
use App\Controllers\Comercial\EntidadesController as Ctrl;
use App\Controllers\Comercial\AgendaController;

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
$router->get('/comercial/entidades/ver', [Ctrl::class, 'show'], ['middleware'=>['auth','role:comercial']]);
$router->get('/comercial/entidades/{id}/show', [Ctrl::class, 'showJson'], ['middleware'=>['auth','role:comercial']]);

// ---------- Comercial → Entidades (CRUD, auth + role) ----------
$router->get(
    '/comercial/entidades',
    [Ctrl::class, 'index'],
    ['middleware'=>['auth','role:comercial']]
);
$router->get('/comercial/entidades/crear',     [Ctrl::class, 'createForm'], ['middleware'=>['auth','role:comercial']]);
$router->post('/comercial/entidades',          [Ctrl::class, 'create'],    ['middleware'=>['auth','role:comercial']]);
$router->get('/comercial/entidades/{id}/edit', [Ctrl::class, 'editForm'],  ['middleware'=>['auth','role:comercial']]);
$router->post('/comercial/entidades/{id}',     [Ctrl::class, 'update'],    ['middleware'=>['auth','role:comercial']]);
$router->post('/comercial/entidades/eliminar', [Ctrl::class, 'delete'],    ['middleware'=>['auth','role:comercial']]);

//

$router->get('/comercial/agenda',                   [AgendaController::class, 'index'],        ['middleware'=>['auth','role:comercial']]);
$router->post('/comercial/agenda',                  [AgendaController::class, 'create'],       ['middleware'=>['auth','role:comercial']]);
$router->get('/comercial/agenda/{id}',              [AgendaController::class, 'showJson'],     ['middleware'=>['auth','role:comercial']]);
$router->post('/comercial/agenda/{id}/editar',      [AgendaController::class, 'edit'],         ['middleware'=>['auth','role:comercial']]);
$router->post('/comercial/agenda/{id}/estado',      [AgendaController::class, 'changeStatus'], ['middleware'=>['auth','role:comercial']]);
$router->post('/comercial/agenda/{id}/eliminar',    [AgendaController::class, 'delete'],       ['middleware'=>['auth','role:comercial']]);

// AJAX: cantones por provincia (auth)
$router->get('/shared/cantones', [UbicacionesController::class, 'cantones'], ['middleware'=>['auth']]);

