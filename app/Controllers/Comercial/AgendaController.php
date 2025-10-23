<?php
declare(strict_types=1);

namespace App\Controllers\Comercial;

use App\Repositories\Comercial\AgendaRepository;
use App\Services\Comercial\AgendaService;
use function redirect;
use function view;

final class AgendaController
{
    private AgendaService $service;

    public function __construct(?AgendaService $service = null)
    {
        $this->service = $service ?? new AgendaService(new AgendaRepository());
    }

    public function index(): void
    {
        $filters = [
            'q'      => trim((string)($_GET['q'] ?? '')),
            'coop'   => $_GET['coop'] ?? '',
            'estado' => trim((string)($_GET['estado'] ?? '')),
        ];

        $items = $this->service->listado($filters);
        $coops = $this->service->cooperativas();

        $notice = $this->pullFlash('notice');
        $errorNotice = $this->pullFlash('error');
        $formErrors = $this->pullFlashData('errors');
        $formOld = $this->pullFlashData('old');

        view('comercial/agenda/index', [
            'layout'        => 'layout',
            'title'         => 'Agenda de contactos',
            'filters'       => $filters,
            'cooperativas'  => $coops,
            'items'         => $items,
            'notice'        => $notice,
            'errorNotice'   => $errorNotice,
            'formErrors'    => $formErrors,
            'formOld'       => $formOld,
        ]);
    }

    public function store(): void
    {
        $result = $this->service->crear($_POST, $this->currentUserId());
        if (!$result['ok']) {
            $this->flashData('errors', $result['errors']);
            $this->flashData('old', $result['data']);
            $this->flash('error', 'Revisa los campos del formulario.');
            redirect('/comercial/agenda');
            return;
        }

        $this->flash('notice', 'Contacto creado correctamente.');
        redirect('/comercial/agenda');
    }

    public function changeStatus(int $id): void
    {
        $estado = (string)($_POST['estado'] ?? '');
        $result = $this->service->cambiarEstado($id, $estado);
        if ($result['ok']) {
            $this->flash('notice', 'Estado actualizado correctamente.');
        } else {
            $this->flash('error', $this->erroresComoTexto($result['errors']));
        }
        redirect('/comercial/agenda');
    }

    public function delete(int $id): void
    {
        if ($this->service->eliminar($id)) {
            $this->flash('notice', 'Contacto eliminado correctamente.');
        } else {
            $this->flash('error', 'No se pudo eliminar el contacto.');
        }
        redirect('/comercial/agenda');
    }

    public function export(): void
    {
        $filters = [
            'q'      => trim((string)($_GET['q'] ?? '')),
            'coop'   => $_GET['coop'] ?? '',
            'estado' => trim((string)($_GET['estado'] ?? '')),
        ];

        $rows = $this->service->datosParaExportar($filters);
        $vcf = $this->construirVcf($rows);
        $filename = 'agenda-' . date('Ymd') . '.vcf';

        header('Content-Type: text/vcard; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($vcf));
        echo $vcf;
    }

    private function construirVcf(array $rows): string
    {
        $out = '';
        $rev = gmdate('Ymd\THis\Z');
        foreach ($rows as $row) {
            $nombre = (string)($row['oficial_nombre'] ?? $row['contacto'] ?? $row['coop_nombre'] ?? $row['titulo'] ?? '');
            $fn = $nombre !== '' ? $nombre : 'Contacto Agenda';
            [$family, $given, $add, $pref, $suf] = $this->vcfNameParts($nombre);

            $org = $row['coop_nombre'] ?? '';
            $cargo = $row['cargo'] ?? '';
            $titulo = $row['titulo'] ?? '';

            $notaPartes = [];
            if (!empty($row['fecha_evento'])) {
                $notaPartes[] = 'Fecha: ' . $row['fecha_evento'];
            }
            if (!empty($row['nota'])) {
                $notaPartes[] = (string)$row['nota'];
            }
            $nota = $notaPartes ? implode('\n', $notaPartes) : '';

            $telefono = $row['telefono_contacto'] ?? ($row['coop_telefono'] ?? '');
            $correo = $row['oficial_correo'] ?? ($row['coop_email'] ?? '');

            $lineas = [
                'BEGIN:VCARD',
                'VERSION:3.0',
                'FN:' . $this->vcfEscape($fn),
                'N:' . $family . ';' . $given . ';' . $add . ';' . $pref . ';' . $suf,
            ];

            if ($org !== '') {
                $lineas[] = 'ORG:' . $this->vcfEscape($org);
            }
            if ($cargo !== '') {
                $lineas[] = 'ROLE:' . $this->vcfEscape($cargo);
            }
            if ($titulo !== '') {
                $lineas[] = 'TITLE:' . $this->vcfEscape($titulo);
            }
            if ($correo !== '') {
                $lineas[] = 'EMAIL;TYPE=WORK:' . $this->vcfEscape($correo);
            }
            if ($telefono !== '') {
                $lineas[] = 'TEL;TYPE=WORK,VOICE:' . $this->vcfEscape($telefono);
            }
            if (!empty($row['coop_canton']) || !empty($row['coop_provincia'])) {
                $city = $this->vcfEscape((string)($row['coop_canton'] ?? ''));
                $region = $this->vcfEscape((string)($row['coop_provincia'] ?? ''));
                $lineas[] = 'ADR;TYPE=WORK:;;;' . $city . ';' . $region . ';;Ecuador';
            }
            if ($nota !== '') {
                $lineas[] = 'NOTE:' . $this->vcfEscape($nota);
            }

            $lineas[] = 'CATEGORIES:Agenda HelpDesk,Comercial';
            $lineas[] = 'UID:agenda-' . ($row['id'] ?? uniqid('', true)) . '@helpdesk';
            $lineas[] = 'REV:' . $rev;
            $lineas[] = 'END:VCARD';

            $out .= implode("\r\n", $lineas) . "\r\n";
        }

        return $out;
    }

    private function vcfEscape(string $value): string
    {
        return str_replace(
            ["\\", ";", ",", "\r\n", "\n", "\r"],
            ["\\\\", "\\;", "\\,", "\\n", "\\n", ''],
            $value
        );
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:string}
     */
    private function vcfNameParts(string $full): array
    {
        $full = trim(preg_replace('/\s+/', ' ', $full) ?? '');
        if ($full === '') {
            return ['', '', '', '', ''];
        }
        $parts = explode(' ', $full);
        $family = array_pop($parts) ?? '';
        $given = trim(implode(' ', $parts));
        return [
            $this->vcfEscape($family),
            $this->vcfEscape($given),
            '',
            '',
            '',
        ];
    }

    private function currentUserId(): ?int
    {
        if (!isset($_SESSION['auth']['id'])) {
            return null;
        }
        return (int)$_SESSION['auth']['id'];
    }

    private function flash(string $key, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['agenda_flash_' . $key] = $message;
    }

    private function pullFlash(string $key): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $fullKey = 'agenda_flash_' . $key;
        if (!isset($_SESSION[$fullKey])) {
            return null;
        }
        $value = $_SESSION[$fullKey];
        unset($_SESSION[$fullKey]);
        return is_string($value) ? $value : null;
    }

    private function flashData(string $key, array $value): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['agenda_flash_data_' . $key] = $value;
    }

    /**
     * @return array<string,mixed>
     */
    private function pullFlashData(string $key): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $fullKey = 'agenda_flash_data_' . $key;
        if (!isset($_SESSION[$fullKey])) {
            return [];
        }
        $value = $_SESSION[$fullKey];
        unset($_SESSION[$fullKey]);
        return is_array($value) ? $value : [];
    }

    /**
     * @param array<string,string> $errors
     */
    private function erroresComoTexto(array $errors): string
    {
        if (!$errors) {
            return 'No se pudo completar la operación.';
        }
        $partes = [];
        foreach ($errors as $campo => $mensaje) {
            if (!is_string($mensaje)) {
                continue;
            }
            $partes[] = ucfirst(str_replace('_', ' ', (string)$campo)) . ': ' . $mensaje;
        }
        return implode('. ', $partes);
    }
}
