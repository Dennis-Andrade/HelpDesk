<?php
declare(strict_types=1);
namespace App\Controllers\Comercial;

use App\Repositories\Comercial\ContactoRepository;
use App\Repositories\Comercial\EntidadRepository;
use App\Services\Shared\Pagination;
use App\Support\Logger;
use function \view;
use function \redirect;
use const \FILTER_VALIDATE_EMAIL;

/**
 * Controlador para la agenda de contactos.
 *
 * Este controlador orquesta la visualización y creación de contactos
 * asociados a las entidades financieras. Utiliza el patrón MVC presente
 * en la aplicación para separar responsabilidades y delegar el acceso
 * a datos al repositorio correspondiente.
 */
final class ContactosController
{
    /** @var ContactoRepository */
    private $repo;
    /** @var EntidadRepository */
    private $entidades;

    public function __construct(
        ?ContactoRepository $repo = null,
        ?EntidadRepository $entidades = null
    ) {
        $this->repo      = $repo ?? new ContactoRepository();
        $this->entidades = $entidades ?? new EntidadRepository();
    }

    /**
     * Muestra la lista de contactos junto con el formulario de alta.
     *
     * Acepta parámetros de paginación y filtro mediante query string y
     * pasa la información necesaria a la vista.
     */
    public function index(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        $toast   = $this->extractToast($filters);
        $q       = trim((string)($filters['q'] ?? ''));
        $pager   = Pagination::fromRequest($filters, 1, 10, 0);
        $result  = $this->repo->search($q, $pager->page, $pager->perPage);

        view('comercial/contactos/index', [
            'layout'    => 'layout',
            'title'     => 'Agenda de contactos',
            'items'     => $result['items'],
            'total'     => $result['total'],
            'page'      => $result['page'],
            'perPage'   => $result['perPage'],
            'q'         => $q,
            'filters'   => $filters,
            'entidades' => $this->listadoEntidades(),
            'toast'     => $toast,
        ]);
    }

    /**
     * Devuelve sugerencias de búsqueda para el cuadro de texto principal.
     */
    public function suggest(): void
    {
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if (mb_strlen($q) < 3) {
            $this->respondJson(['items' => []]);
        }

        try {
            $rows = $this->repo->suggest($q, 12);
        } catch (\Throwable $e) {
            $this->respondJson(['items' => [], 'error' => 'No se pudo obtener sugerencias'], 500);
        }

        $contactItems = [];
        $entityItems  = [];

        foreach ($rows as $row) {
            $nombre  = trim((string)($row['nombre'] ?? ''));
            $entidad = trim((string)($row['entidad_nombre'] ?? ''));

            if ($entidad !== '' && !isset($entityItems[$entidad])) {
                $entityItems[$entidad] = [
                    'type' => 'entity',
                    'term' => $entidad,
                    'label'=> 'Entidad · ' . $entidad,
                ];
            }

            if ($nombre !== '') {
                $contactItems[] = [
                    'type'    => 'contact',
                    'term'    => $nombre,
                    'label'   => $nombre . ($entidad !== '' ? ' · ' . $entidad : ''),
                    'cargo'   => (string)($row['cargo'] ?? ''),
                    'entidad' => $entidad,
                ];
            }
        }

        $entities   = array_slice(array_values($entityItems), 0, 5);
        $contacts   = array_slice($contactItems, 0, 7);
        $items      = array_merge($entities, $contacts);

        $this->respondJson(['items' => $items]);
    }

    /**
     * Maneja la creación de un contacto a partir de los datos del POST.
     */
    public function create(): void
    {
        $validated = $this->validatePayload($_POST);
        if (!$validated['ok']) {
            redirect('/comercial/contactos?error=validacion');
            return;
        }

        try {
            $this->repo->create($validated['data']);
        } catch (\Throwable $e) {
            Logger::error($e, 'ContactosController::create');
            redirect('/comercial/contactos?error=servidor');
            return;
        }

        redirect('/comercial/contactos?created=1');
    }

    /**
     * Muestra el formulario para editar un contacto existente.
     */
    public function editForm($id)
    {
        $id = (int)$id;
        if ($id < 1) {
            redirect('/comercial/contactos');
            return;
        }

        $contacto = $this->repo->find($id);
        if ($contacto === null) {
            redirect('/comercial/contactos');
            return;
        }

        return view('comercial/contactos/edit', [
            'layout'    => 'layout',
            'title'     => 'Editar contacto',
            'contacto'  => $contacto,
            'entidades' => $this->listadoEntidades(),
        ]);
    }

    /**
     * Actualiza un contacto.
     *
     * @param int|string $id
     */
    public function update($id): void
    {
        $id = (int)$id;
        if ($id < 1) {
            redirect('/comercial/contactos?error=validacion');
            return;
        }

        $postedId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($postedId > 0 && $postedId !== $id) {
            redirect('/comercial/contactos?error=validacion');
            return;
        }

        try {
            $existing = $this->repo->find($id);
        } catch (\Throwable $e) {
            Logger::error($e, 'ContactosController::update find');
            $existing = null;
        }

        $validated = $this->validatePayload($_POST);
        if (!$validated['ok']) {
            redirect('/comercial/contactos?error=validacion');
            return;
        }

        if (is_array($existing) && array_key_exists('fecha_evento', $existing)) {
            $validated['data']['fecha_evento'] = $existing['fecha_evento'];
        }
        if (is_array($existing) && isset($existing['contacto_cooperativa_id'])) {
            $validated['data']['contacto_cooperativa_id'] = $existing['contacto_cooperativa_id'] !== null
                ? (int)$existing['contacto_cooperativa_id']
                : null;
        }

        try {
            $this->repo->update($id, $validated['data']);
        } catch (\Throwable $e) {
            Logger::error($e, 'ContactosController::update');
            redirect('/comercial/contactos?error=servidor');
            return;
        }

        redirect('/comercial/contactos?updated=1');
    }

    /**
     * Elimina un contacto identificado por su id.
     *
     * @param int|string $id Identificador del contacto a eliminar.
     */
    public function delete($id): void
    {
        $id = (int)$id;
        if ($id < 1) {
            $id = (int)($_POST['id'] ?? 0);
        }
        if ($id > 0) {
            try {
                $this->repo->delete($id);
            } catch (\Throwable $e) {
                Logger::error($e, 'ContactosController::delete');
                redirect('/comercial/contactos?error=servidor');
                return;
            }
        }
        redirect('/comercial/contactos?deleted=1');
    }

    public function exportCsv(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        try {
            $rows = $this->repo->listForExport($filters);
        } catch (\Throwable $e) {
            Logger::error($e, 'ContactosController::exportCsv');
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'No se pudo generar el archivo CSV.';
            return;
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="contactos-' . date('Ymd') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        fputcsv($out, ['Nombre', 'Entidad', 'Cargo', 'Teléfono', 'Correo', 'Notas'], ';');
        foreach ($rows as $row) {
            $nombre  = $this->cleanExportText($row['nombre'] ?? '');
            $entidad = $this->cleanExportText($row['entidad_nombre'] ?? '');
            $cargo   = $this->cleanExportText($row['cargo'] ?? '');
            $telefono= $this->cleanExportText($row['telefono'] ?? '');
            $correo  = $this->cleanExportText($row['correo'] ?? '');
            $nota    = $this->cleanExportText($row['nota'] ?? '');
            fputcsv($out, [$nombre, $entidad, $cargo, $telefono, $correo, $nota], ';');
        }

        fclose($out);
        exit;
    }

    public function exportVcf(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        try {
            $rows = $this->repo->listForExport($filters);
        } catch (\Throwable $e) {
            Logger::error($e, 'ContactosController::exportVcf');
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'No se pudo generar el archivo vCard.';
            return;
        }

        header('Content-Type: text/vcard; charset=UTF-8');
        header('Content-Disposition: attachment; filename="contactos-' . date('Ymd') . '.vcf"');
        header('Pragma: no-cache');
        header('Expires: 0');

        foreach ($rows as $row) {
            $nombre   = $this->vcfEscape($row['nombre'] ?? '');
            $entidad  = $this->vcfEscape($row['entidad_nombre'] ?? '');
            $cargo    = $this->vcfEscape($row['cargo'] ?? '');
            $telefono = $this->normalizePhone($row['telefono'] ?? '');
            $correo   = $this->vcfEscape($row['correo'] ?? '');
            $nota     = $this->vcfEscape($row['nota'] ?? '');

            echo "BEGIN:VCARD\r\n";
            echo "VERSION:3.0\r\n";
            if ($nombre !== '') {
                echo 'FN:' . $nombre . "\r\n";
            }
            if ($entidad !== '') {
                echo 'ORG:' . $entidad . "\r\n";
            }
            if ($cargo !== '') {
                echo 'TITLE:' . $cargo . "\r\n";
            }
            if ($telefono !== '') {
                echo 'TEL;TYPE=CELL,VOICE:' . $telefono . "\r\n";
            }
            if ($correo !== '') {
                echo 'EMAIL;TYPE=INTERNET:' . $correo . "\r\n";
            }
            if ($nota !== '') {
                echo 'NOTE:' . $nota . "\r\n";
            }
            echo "END:VCARD\r\n";
        }

        exit;
    }

    /**
     * Devuelve una lista de entidades para el selector desplegable.
     *
     * @return array<int,array{id:int,nombre:string}>
     */
    private function listadoEntidades(): array
    {
        $resultado = $this->entidades->search('', 1, 200);
        $items     = $resultado['items'] ?? [];
        $list      = [];
        foreach ($items as $item) {
            if (!isset($item['id'], $item['nombre'])) {
                continue;
            }
            $list[] = [
                'id'     => (int)$item['id'],
                'nombre' => (string)$item['nombre'],
            ];
        }
        return $list;
    }

    private function cleanExportText($value): string
    {
        if (!is_scalar($value)) {
            return '';
        }
        $text = preg_replace('/\s+/u', ' ', (string)$value);
        return $text === null ? '' : trim($text);
    }

    private function normalizePhone($value): string
    {
        if (!is_scalar($value)) {
            return '';
        }
        $digits = preg_replace('/[^0-9+]/', '', (string)$value);
        return $digits === null ? '' : $digits;
    }

    private function vcfEscape($value): string
    {
        if (!is_scalar($value)) {
            return '';
        }
        $text = (string)$value;
        $text = str_replace(["\\", "\r\n", "\n", "\r"], ['\\\\', '\\n', '\\n', ''], $text);
        $text = str_replace([';', ','], ['\\;', '\\,'], $text);
        return trim($text);
    }

    /**
     * Envía una respuesta JSON y termina la ejecución.
     *
     * @param array<string,mixed> $payload
     */
    private function respondJson(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @param array<string,mixed> $filters
     */
    private function extractToast(array &$filters): ?array
    {
        $message = null;
        $variant = 'success';

        if (isset($filters['created']) && $filters['created'] === '1') {
            $message = 'Contacto registrado correctamente.';
        } elseif (isset($filters['updated']) && $filters['updated'] === '1') {
            $message = 'Contacto actualizado correctamente.';
        } elseif (isset($filters['deleted']) && $filters['deleted'] === '1') {
            $message = 'Contacto eliminado correctamente.';
        } elseif (isset($filters['error'])) {
            $variant = 'error';
            $message = $filters['error'] === 'validacion'
                ? 'Revisa los datos obligatorios antes de guardar.'
                : 'No se pudo completar la operación.';
        }

        unset(
            $filters['created'],
            $filters['updated'],
            $filters['deleted'],
            $filters['error']
        );

        if ($message === null) {
            return null;
        }

        return [
            'message' => $message,
            'variant' => $variant,
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array{ok:bool,data:array<string,mixed>,errors:array<int,string>}
     */
    private function validatePayload(array $source): array
    {
        $idEntidad = (int)($source['id_entidad'] ?? 0);
        $nombre    = trim((string)($source['nombre'] ?? ''));
        $titulo    = trim((string)($source['titulo'] ?? ''));
        $cargo     = trim((string)($source['cargo'] ?? ''));
        $nota      = trim((string)($source['nota'] ?? ''));

        $telefono  = $this->sanitizePhone((string)($source['telefono'] ?? ''));
        $correo    = trim((string)($source['correo'] ?? ''));

        $errors = [];

        if ($idEntidad < 1) {
            $errors[] = 'Selecciona la entidad a la que pertenece el contacto.';
        }

        if ($nombre === '') {
            $errors[] = 'El nombre del contacto es obligatorio.';
        }

        if ($telefono === '' && $correo === '') {
            $errors[] = 'Registra al menos un medio de contacto (celular o correo).';
        }

        $telefonoLen = strlen($telefono);
        if ($telefono !== '' && ($telefonoLen < 7 || $telefonoLen > 10)) {
            $errors[] = 'El número de celular debe tener entre 7 y 10 dígitos.';
        }

        if ($correo !== '' && !\filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresa un correo electrónico válido.';
        }

        if ($errors) {
            return [
                'ok'     => false,
                'data'   => [],
                'errors' => $errors,
            ];
        }

        $tituloFinal = $titulo !== '' ? $titulo : 'Contacto';

        return [
            'ok'   => true,
            'data' => [
                'id_cooperativa'    => $idEntidad,
                'nombre'            => $nombre,
                'titulo'            => $tituloFinal,
                'cargo'             => $cargo,
                'telefono_contacto' => $telefono,
                'email_contacto'    => $correo,
                'nota'              => $nota,
                'fecha_evento'      => null,
            ],
            'errors' => [],
        ];
    }

    private function sanitizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === null) {
            return '';
        }
        return $digits;
    }
}
