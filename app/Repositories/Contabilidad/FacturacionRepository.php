<?php
declare(strict_types=1);

namespace App\Repositories\Contabilidad;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;

final class FacturacionRepository extends BaseRepository
{
    private const TABLE = 'public.datos_facturacion';
    private const COOP_TABLE = 'public.cooperativas';
    private const PROV_TABLE = 'public.provincia';
    private const CANTON_TABLE = 'public.canton';

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,page:int,perPage:int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(5, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $params = [];
        $where = $this->buildFilters($filters, $params);
        $joins = $this->buildJoins();

        $countSql = 'SELECT COUNT(*) AS total FROM ' . self::TABLE . ' df' . $joins . $where;

        try {
            $row = $this->db->fetch($countSql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al contar datos de facturación.', 0, $e);
        }

        $total = $row ? (int)$row['total'] : 0;
        if ($total === 0) {
            return [
                'items'   => [],
                'total'   => 0,
                'page'    => $page,
                'perPage' => $perPage,
            ];
        }

        $params[':limit'] = [$perPage, PDO::PARAM_INT];
        $params[':offset'] = [$offset, PDO::PARAM_INT];

        $sql = 'SELECT df.id_facturacion AS id,
                       df.id_cooperativa,
                       coop.nombre AS cooperativa,
                       df.direccion,
                       df.provincia,
                       df.canton,
                       df.provincia_id,
                       df.canton_id,
                       df.email1,
                       df.email2,
                       df.email3,
                       df.email4,
                       df.email5,
                       df.tel_fijo1,
                       df.tel_fijo2,
                       df.tel_fijo3,
                       df.tel_cel1,
                       df.tel_cel2,
                       df.tel_cel3,
                       df.contabilidad_nombre,
                       df.contabilidad_telefono,
                       df.fecha_registro,
                       prov.nombre AS provincia_nombre,
                       cant.nombre AS canton_nombre
                  FROM ' . self::TABLE . ' df' . $joins . $where . '
             ORDER BY coop.nombre ASC
             LIMIT :limit OFFSET :offset';

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener datos de facturación.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRegistro($row);
        }

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = 'SELECT df.*, coop.nombre AS cooperativa FROM ' . self::TABLE . ' df
                LEFT JOIN ' . self::COOP_TABLE . ' coop ON coop.id_cooperativa = df.id_cooperativa
                WHERE df.id_facturacion = :id LIMIT 1';

        try {
            $row = $this->db->fetch($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener el registro de facturación.', 0, $e);
        }

        return $row ? $this->mapRegistro($row) : null;
    }

    public function findByCooperativa(int $cooperativaId): ?array
    {
        $sql = 'SELECT df.*, coop.nombre AS cooperativa FROM ' . self::TABLE . ' df
                LEFT JOIN ' . self::COOP_TABLE . ' coop ON coop.id_cooperativa = df.id_cooperativa
                WHERE df.id_cooperativa = :id LIMIT 1';

        try {
            $row = $this->db->fetch($sql, [':id' => [$cooperativaId, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al consultar los datos de facturación.', 0, $e);
        }

        return $row ? $this->mapRegistro($row) : null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function save(int $cooperativaId, array $data): int
    {
        $existing = $this->findByCooperativa($cooperativaId);

        $canonical = $this->canonicalEmail($data);

        if ($existing === null) {
            $sql = 'INSERT INTO ' . self::TABLE . ' (
                        id_cooperativa,
                        direccion,
                        provincia,
                        canton,
                        provincia_id,
                        canton_id,
                        email1,
                        email2,
                        email3,
                        email4,
                        email5,
                        tel_fijo1,
                        tel_fijo2,
                        tel_fijo3,
                        tel_cel1,
                        tel_cel2,
                        tel_cel3,
                        contabilidad_nombre,
                        contabilidad_telefono,
                        fecha_registro,
                        email_canonical
                    ) VALUES (
                        :cooperativa,
                        :direccion,
                        :provincia,
                        :canton,
                        :provincia_id,
                        :canton_id,
                        :email1,
                        :email2,
                        :email3,
                        :email4,
                        :email5,
                        :tel_fijo1,
                        :tel_fijo2,
                        :tel_fijo3,
                        :tel_cel1,
                        :tel_cel2,
                        :tel_cel3,
                        :contabilidad_nombre,
                        :contabilidad_telefono,
                        NOW(),
                        :email_canonical
                    ) RETURNING id_facturacion';

            $params = $this->buildParams($cooperativaId, $data, $canonical);

            try {
                $rows = $this->db->execute($sql, $params);
            } catch (\Throwable $e) {
                throw new RuntimeException('No se pudo registrar la información de facturación.', 0, $e);
            }

            $row = is_array($rows) && isset($rows[0]['id_facturacion']) ? $rows[0] : null;
            return $row ? (int)$row['id_facturacion'] : 0;
        }

        $sql = 'UPDATE ' . self::TABLE . ' SET
                    direccion = :direccion,
                    provincia = :provincia,
                    canton = :canton,
                    provincia_id = :provincia_id,
                    canton_id = :canton_id,
                    email1 = :email1,
                    email2 = :email2,
                    email3 = :email3,
                    email4 = :email4,
                    email5 = :email5,
                    tel_fijo1 = :tel_fijo1,
                    tel_fijo2 = :tel_fijo2,
                    tel_fijo3 = :tel_fijo3,
                    tel_cel1 = :tel_cel1,
                    tel_cel2 = :tel_cel2,
                    tel_cel3 = :tel_cel3,
                    contabilidad_nombre = :contabilidad_nombre,
                    contabilidad_telefono = :contabilidad_telefono,
                    email_canonical = :email_canonical
                WHERE id_facturacion = :id';

        $params = $this->buildParams($cooperativaId, $data, $canonical);
        $params[':id'] = [$existing['id'], PDO::PARAM_INT];

        try {
            $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo actualizar la información de facturación.', 0, $e);
        }

        return (int)$existing['id'];
    }

    private function buildJoins(): string
    {
        return ' LEFT JOIN ' . self::COOP_TABLE . ' coop ON coop.id_cooperativa = df.id_cooperativa'
            . ' LEFT JOIN ' . self::PROV_TABLE . ' prov ON prov.id = df.provincia_id'
            . ' LEFT JOIN ' . self::CANTON_TABLE . ' cant ON cant.id = df.canton_id';
    }

    private function buildFilters(array $filters, array &$params): string
    {
        $conditions = [];

        $q = isset($filters['q']) ? trim((string)$filters['q']) : '';
        if ($q !== '') {
            $conditions[] = '(
                unaccent(lower(coop.nombre)) LIKE unaccent(lower(:q_like))
                OR unaccent(lower(df.direccion)) LIKE unaccent(lower(:q_like))
                OR lower(df.email1) LIKE lower(:q_like)
            )';
            $params[':q_like'] = ['%' . $q . '%', PDO::PARAM_STR];
        }

        if (isset($filters['provincia']) && $filters['provincia'] !== '') {
            $conditions[] = 'df.provincia_id = :provincia';
            $params[':provincia'] = [(int)$filters['provincia'], PDO::PARAM_INT];
        }

        if (isset($filters['canton']) && $filters['canton'] !== '') {
            $conditions[] = 'df.canton_id = :canton';
            $params[':canton'] = [(int)$filters['canton'], PDO::PARAM_INT];
        }

        if (empty($conditions)) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $conditions);
    }

    private function mapRegistro(array $row): array
    {
        $emails = [];
        for ($i = 1; $i <= 5; $i++) {
            $key = 'email' . $i;
            if (!empty($row[$key])) {
                $emails[] = (string)$row[$key];
            }
        }

        $telefonosFijos = [];
        for ($i = 1; $i <= 3; $i++) {
            $key = 'tel_fijo' . $i;
            if (!empty($row[$key])) {
                $telefonosFijos[] = (string)$row[$key];
            }
        }

        $telefonosCel = [];
        for ($i = 1; $i <= 3; $i++) {
            $key = 'tel_cel' . $i;
            if (!empty($row[$key])) {
                $telefonosCel[] = (string)$row[$key];
            }
        }

        return [
            'id'                   => (int)($row['id'] ?? $row['id_facturacion'] ?? 0),
            'id_cooperativa'       => (int)($row['id_cooperativa'] ?? 0),
            'cooperativa'          => (string)($row['cooperativa'] ?? ''),
            'direccion'            => (string)($row['direccion'] ?? ''),
            'provincia'            => (string)($row['provincia'] ?? ''),
            'canton'               => (string)($row['canton'] ?? ''),
            'provincia_id'         => isset($row['provincia_id']) ? (int)$row['provincia_id'] : null,
            'canton_id'            => isset($row['canton_id']) ? (int)$row['canton_id'] : null,
            'emails'               => $emails,
            'telefonos_fijos'      => $telefonosFijos,
            'telefonos_cel'        => $telefonosCel,
            'contabilidad_nombre'  => (string)($row['contabilidad_nombre'] ?? ''),
            'contabilidad_telefono'=> (string)($row['contabilidad_telefono'] ?? ''),
            'fecha_registro'       => isset($row['fecha_registro']) ? (string)$row['fecha_registro'] : null,
            'provincia_nombre'     => (string)($row['provincia_nombre'] ?? ''),
            'canton_nombre'        => (string)($row['canton_nombre'] ?? ''),
            'raw'                  => $row,
        ];
    }

    /**
     * @return array<string,array{0:mixed,1:int}>
     */
    private function buildParams(int $cooperativaId, array $data, ?string $canonical): array
    {
        return [
            ':cooperativa'          => [$cooperativaId, PDO::PARAM_INT],
            ':direccion'            => [$data['direccion'] ?? null, $this->paramType($data['direccion'] ?? null)],
            ':provincia'            => [$data['provincia'] ?? null, $this->paramType($data['provincia'] ?? null)],
            ':canton'               => [$data['canton'] ?? null, $this->paramType($data['canton'] ?? null)],
            ':provincia_id'         => [$data['provincia_id'] ?? null, $this->paramType($data['provincia_id'] ?? null, true)],
            ':canton_id'            => [$data['canton_id'] ?? null, $this->paramType($data['canton_id'] ?? null, true)],
            ':email1'               => [$data['email1'] ?? null, $this->paramType($data['email1'] ?? null)],
            ':email2'               => [$data['email2'] ?? null, $this->paramType($data['email2'] ?? null)],
            ':email3'               => [$data['email3'] ?? null, $this->paramType($data['email3'] ?? null)],
            ':email4'               => [$data['email4'] ?? null, $this->paramType($data['email4'] ?? null)],
            ':email5'               => [$data['email5'] ?? null, $this->paramType($data['email5'] ?? null)],
            ':tel_fijo1'            => [$data['tel_fijo1'] ?? null, $this->paramType($data['tel_fijo1'] ?? null)],
            ':tel_fijo2'            => [$data['tel_fijo2'] ?? null, $this->paramType($data['tel_fijo2'] ?? null)],
            ':tel_fijo3'            => [$data['tel_fijo3'] ?? null, $this->paramType($data['tel_fijo3'] ?? null)],
            ':tel_cel1'             => [$data['tel_cel1'] ?? null, $this->paramType($data['tel_cel1'] ?? null)],
            ':tel_cel2'             => [$data['tel_cel2'] ?? null, $this->paramType($data['tel_cel2'] ?? null)],
            ':tel_cel3'             => [$data['tel_cel3'] ?? null, $this->paramType($data['tel_cel3'] ?? null)],
            ':contabilidad_nombre'  => [$data['contabilidad_nombre'] ?? null, $this->paramType($data['contabilidad_nombre'] ?? null)],
            ':contabilidad_telefono'=> [$data['contabilidad_telefono'] ?? null, $this->paramType($data['contabilidad_telefono'] ?? null)],
            ':email_canonical'      => [$canonical, $this->paramType($canonical)],
        ];
    }

    private function paramType($value, bool $forceInt = false): int
    {
        if ($forceInt) {
            return $value === null || $value === '' ? PDO::PARAM_NULL : PDO::PARAM_INT;
        }
        if ($value === null || $value === '') {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
    }

    private function canonicalEmail(array $data): ?string
    {
        $emails = [];
        for ($i = 1; $i <= 5; $i++) {
            $key = 'email' . $i;
            if (!empty($data[$key])) {
                $emails[] = strtolower(trim((string)$data[$key]));
            }
        }
        return $emails[0] ?? null;
    }
}
