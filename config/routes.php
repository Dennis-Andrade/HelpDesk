<?php
use App\Controllers\Auth\LoginController;
use App\Controllers\Shared\CalendarController;
use App\Controllers\Shared\UbicacionesController;
use App\Controllers\Comercial\DashboardController as ComercialDashboard;
use App\Controllers\Contabilidad\DashboardController as ContabDashboard;
use App\Controllers\Contabilidad\EntidadesController as ContabEntidadesController;
use App\Controllers\Contabilidad\ContratosController as ContabContratosController;
use App\Controllers\Contabilidad\FacturacionController as ContabFacturacionController;
use App\Controllers\Contabilidad\HistorialController as ContabHistorialController;
use App\Controllers\Contabilidad\SwitchController as ContabSwitchController;
use App\Controllers\Contabilidad\SeguimientoController as ContabSeguimientoController;
use App\Controllers\Contabilidad\TicketsController as ContabTicketsController;
use App\Controllers\Contabilidad\InventarioController as ContabInventarioController;
use App\Controllers\Sistemas\DashboardController as SistemasDashboard;
use App\Controllers\Cumplimiento\DashboardController as CumplimientoDashboard;
use App\Controllers\Administrador\DashboardController as AdminDashboard;
use App\Controllers\Comercial\EntidadesController as ComercialEntidadesController;
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

// ---------- Calendario Unificado ----------
$router->get('/calendario', [CalendarController::class, 'index'], ['middleware'=>['auth']]);
$router->get('/calendario/eventos', [CalendarController::class, 'events'], ['middleware'=>['auth']]);

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
    ['middleware'=>['auth','role:comercial,contabilidad']]
);

// ---------- Comercial → Entidades (CRUD, auth + role) ----------
$router->get(
    '/comercial/entidades',
    [ComercialEntidadesController::class, 'index'],
    ['middleware'=>['auth','role:comercial,contabilidad']]
);
$router->get(
    '/comercial/entidades/sugerencias',
    [ComercialEntidadesController::class, 'suggest'],
    ['middleware'=>['auth','role:comercial,contabilidad']]
);
$router->get('/comercial/entidades/show', [ComercialEntidadesController::class, 'show'], ['middleware'=>['auth','role:comercial,contabilidad']]);
$router->get('/comercial/entidades/{id}/show', [ComercialEntidadesController::class, 'showJson'], ['middleware'=>['auth','role:comercial,contabilidad']]);
$router->get('/comercial/entidades/crear',     [ComercialEntidadesController::class, 'createForm'], ['middleware'=>['auth','role:comercial,contabilidad,administrador']]);
$router->post('/comercial/entidades',          [ComercialEntidadesController::class, 'create'],     ['middleware'=>['auth','role:comercial,contabilidad,administrador']]);
$router->get('/comercial/entidades/editar',    [ComercialEntidadesController::class, 'editForm'],   ['middleware'=>['auth','role:comercial,contabilidad,administrador']]);
$router->post('/comercial/entidades/{id}',     [ComercialEntidadesController::class, 'update'],     ['middleware'=>['auth','role:comercial,contabilidad,administrador']]);
$router->post('/comercial/entidades/eliminar', [ComercialEntidadesController::class, 'delete'],     ['middleware'=>['auth','role:comercial,contabilidad,administrador']]);

// ---------- Contabilidad → Entidades (reusa lógica compartida) ----------
$router->get(
    '/contabilidad/entidades',
    [ContabEntidadesController::class, 'index'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/entidades/sugerencias',
    [ContabEntidadesController::class, 'suggest'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/entidades/crear',
    [ContabEntidadesController::class, 'createForm'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->post(
    '/contabilidad/entidades',
    [ContabEntidadesController::class, 'create'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/entidades/editar',
    [ContabEntidadesController::class, 'editForm'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->post(
    '/contabilidad/entidades/{id}',
    [ContabEntidadesController::class, 'update'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->post(
    '/contabilidad/entidades/eliminar',
    [ContabEntidadesController::class, 'delete'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);

// ---------- Contabilidad → Seguimiento ----------
$router->get(
    '/contabilidad/seguimiento',
    [ContabSeguimientoController::class, 'index'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/seguimiento/crear',
    [ContabSeguimientoController::class, 'createForm'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->post(
    '/contabilidad/seguimiento',
    [ContabSeguimientoController::class, 'store'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/seguimiento/contactos',
    [ContabSeguimientoController::class, 'contactos'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/seguimiento/contratos',
    [ContabSeguimientoController::class, 'contratos'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/seguimiento/sugerencias/tickets',
    [ContabSeguimientoController::class, 'ticketSearch'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/seguimiento/tickets/{id}',
    [ContabSeguimientoController::class, 'ticketInfo'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);

// ---------- Contabilidad → Tickets ----------
$router->get(
    '/contabilidad/tickets',
    [ContabTicketsController::class, 'index'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/tickets/crear',
    [ContabTicketsController::class, 'createForm'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->post(
    '/contabilidad/tickets',
    [ContabTicketsController::class, 'store'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/tickets/editar',
    [ContabTicketsController::class, 'editForm'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->post(
    '/contabilidad/tickets/{id}',
    [ContabTicketsController::class, 'update'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->post(
    '/contabilidad/tickets/eliminar',
    [ContabTicketsController::class, 'delete'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);

// ---------- Contabilidad → Inventario ----------
$router->get(
    '/contabilidad/inventario',
    [ContabInventarioController::class, 'index'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/inventario/sugerencias',
    [ContabInventarioController::class, 'suggest'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/inventario/crear',
    [ContabInventarioController::class, 'createForm'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->post(
    '/contabilidad/inventario',
    [ContabInventarioController::class, 'store'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);
$router->get(
    '/contabilidad/inventario/{id}',
    [ContabInventarioController::class, 'show'],
    ['middleware'=>['auth','role:contabilidad,administrador']]
);

// ---------- Contabilidad → Contratos ----------
$router->get('/contabilidad/contratos', [ContabContratosController::class, 'index'], ['middleware'=>['auth','role:contabilidad,administrador']]);
$router->get('/contabilidad/contratos/crear', [ContabContratosController::class, 'createForm'], ['middleware'=>['auth','role:contabilidad,administrador']]);
$router->post('/contabilidad/contratos', [ContabContratosController::class, 'create'], ['middleware'=>['auth','role:contabilidad,administrador']]);
$router->get('/contabilidad/contratos/editar', [ContabContratosController::class, 'editForm'], ['middleware'=>['auth','role:contabilidad,administrador']]);
$router->post('/contabilidad/contratos/{id}', [ContabContratosController::class, 'update'], ['middleware'=>['auth','role:contabilidad,administrador']]);
$router->post('/contabilidad/contratos/eliminar', [ContabContratosController::class, 'delete'], ['middleware'=>['auth','role:contabilidad,administrador']]);

// ---------- Contabilidad → Datos de facturación ----------
$router->get('/contabilidad/facturacion', [ContabFacturacionController::class, 'index'], ['middleware'=>['auth','role:contabilidad,administrador']]);
$router->get('/contabilidad/facturacion/editar', [ContabFacturacionController::class, 'editForm'], ['middleware'=>['auth','role:contabilidad,administrador']]);
$router->post('/contabilidad/facturacion/guardar', [ContabFacturacionController::class, 'save'], ['middleware'=>['auth','role:contabilidad,administrador']]);

// ---------- Contabilidad → Historial de facturación (API modal) ----------
$router->get('/contabilidad/historial', [ContabHistorialController::class, 'list'], ['middleware'=>['auth','role:contabilidad,administrador']]);
$router->post('/contabilidad/historial', [ContabHistorialController::class, 'store'], ['middleware'=>['auth','role:contabilidad,administrador']]);
$router->post('/contabilidad/historial/{id}/eliminar', [ContabHistorialController::class, 'delete'], ['middleware'=>['auth','role:contabilidad,administrador']]);

// ---------- Contabilidad → Switch (placeholder) ----------
$router->get('/contabilidad/switch', [ContabSwitchController::class, 'index'], ['middleware'=>['auth','role:contabilidad,administrador']]);

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
    '/comercial/contactos/export/csv',
    [ContactosController::class, 'exportCsv'],
    ['middleware'=>['auth','role:comercial']]
);
$router->get(
    '/comercial/contactos/export/vcf',
    [ContactosController::class, 'exportVcf'],
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
    '/comercial/eventos/sugerencias/tickets',
    [SeguimientoController::class, 'ticketFilterSearch'],
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
    '/comercial/eventos/entidades/{id}',
    [SeguimientoController::class, 'entityHistory'],
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
$router->post(
    '/comercial/eventos/{id}/eliminar',
    [SeguimientoController::class, 'delete'],
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
$router->get('/contabilidad/dashboard',  [ContabDashboard::class,      'index'], ['middleware'=>['auth','role:contabilidad,administrador']]);
