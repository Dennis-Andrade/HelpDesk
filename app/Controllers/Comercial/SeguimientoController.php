<?php
namespace App\Controllers\Comercial;

use App\Repositories\Comercial\SeguimientoRepository;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use function \redirect;
use function \view;

final class SeguimientoController
{
    /** @var SeguimientoRepository */
    private $repo;

    public function __construct(?SeguimientoRepository $repo = null)
    {
        $this->repo = $repo ?? new SeguimientoRepository();
    }

    public function index(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        $fechaFiltro = isset($filters['fecha']) ? trim((string)$filters['fecha']) : '';
        if ($fechaFiltro === '') {
            $filters['fecha'] = date('Y-m-d');
        }

        $pager = Pagination::fromRequest($filters, 1, 10, 0);
        $result = $this->repo->paginate($filters, $pager->page, $pager->perPage);

        view('comercial/seguimiento/index', [
            'layout'       => 'layout',
            'title'        => 'Seguimiento diario',
            'items'        => $result['items'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'perPage'      => $result['perPage'],
            'filters'      => $filters,
            'cooperativas' => $this->repo->listadoCooperativas(),
            'tipos'        => $this->repo->catalogoTipos(),
        ]);
    }

    public function createForm(): void
    {
        $crumbs = Breadcrumbs::make([
            ['href' => '/comercial', 'label' => 'Comercial'],
            ['href' => '/comercial/eventos', 'label' => 'Seguimiento diario'],
            ['label' => 'Nuevo seguimiento'],
        ]);

        view('comercial/seguimiento/create', [
            'layout'        => 'layout',
            'title'         => 'Nuevo seguimiento',
            'crumbs'        => $crumbs,
            'cooperativas'  => $this->repo->listadoCooperativas(),
            'tipos'         => $this->repo->catalogoTipos(),
            'defaultFecha'  => date('Y-m-d'),
            'defaultTipo'   => 'Seguimiento',
        ]);
    }

    public function store(): void
    {
        $fecha = isset($_POST['fecha']) ? trim((string)$_POST['fecha']) : '';
        if ($fecha === '') {
            $fecha = date('Y-m-d');
        }

        $data = [
            'id_cooperativa' => (int)($_POST['id_cooperativa'] ?? 0),
            'fecha'          => $fecha,
            'tipo'           => trim((string)($_POST['tipo'] ?? '')),
            'descripcion'    => trim((string)($_POST['descripcion'] ?? '')),
            'ticket'         => trim((string)($_POST['ticket'] ?? '')),
            'creado_por'     => $this->currentUserId(),
        ];

        if ($data['tipo'] === '') {
            $data['tipo'] = 'Seguimiento';
        }

        if ($data['id_cooperativa'] < 1 || $data['descripcion'] === '') {
            redirect('/comercial/eventos');
            return;
        }

        $this->repo->create($data);
        redirect('/comercial/eventos');
    }

    public function export(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        $fechaFiltro = isset($filters['fecha']) ? trim((string)$filters['fecha']) : '';
        if ($fechaFiltro === '') {
            $filters['fecha'] = date('Y-m-d');
        }

        $rows = $this->repo->listarParaExportar($filters);

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="seguimiento-' . date('Ymd') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) {
            redirect('/comercial/eventos');
            return;
        }

        fputcsv($out, ['Fecha', 'Cooperativa', 'Tipo', 'Descripción', 'Ticket', 'Registrado por'], ';');
        foreach ($rows as $row) {
            $descripcion = isset($row['descripcion']) ? preg_replace('/\s+/u', ' ', (string)$row['descripcion']) : '';
            $usuario = isset($row['usuario']) ? (string)$row['usuario'] : '';
            fputcsv($out, [
                isset($row['fecha']) ? (string)$row['fecha'] : '',
                isset($row['cooperativa']) ? (string)$row['cooperativa'] : '',
                isset($row['tipo']) ? (string)$row['tipo'] : '',
                $descripcion,
                isset($row['ticket']) ? (string)$row['ticket'] : '',
                $usuario,
            ], ';');
        }

        fclose($out);
        exit;
    }

    private function currentUserId(): ?int
    {
        if (!empty($_SESSION['auth']['id'])) {
            return (int)$_SESSION['auth']['id'];
        }
        return null;
    }
}
