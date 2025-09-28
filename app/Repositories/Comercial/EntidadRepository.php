<?php
declare(strict_types=1);

namespace App\Repositories\Comercial;

use PDO;

final class EntidadRepository
{
    private PDO $db;
    private const TBL = 'public.cooperativas';
    private const PK  = 'id_cooperativa';

    public function __construct(PDO $db) { $this->db = $db; }

    public function findById(int $id): ?array {
        $sql = "SELECT
                  c.".self::PK." AS id,
                  c.nombre, c.ruc, c.telefono_fijo_1, c.telefono_movil, c.email,
                  c.provincia_id, c.canton_id, c.tipo_entidad, c.id_segmento, c.notas
                FROM ".self::TBL." c
                WHERE c.".self::PK." = :id
                LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([':id'=>$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $d): int {
        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO ".self::TBL."
                    (nombre, ruc, telefono_fijo_1, telefono_movil, email,
                     provincia_id, canton_id, tipo_entidad, id_segmento, notas)
                    VALUES
                    (:nombre, :ruc, :tfijo, :tmovil, :email,
                     :prov, :canton, :tipo, :seg, :notas)
                    RETURNING ".self::PK." AS id";
            $st = $this->db->prepare($sql);
            $st->execute([
                ':nombre'=>$d['nombre'],
                ':ruc'=>$d['ruc'],
                ':tfijo'=>$d['telefono_fijo_1'],
                ':tmovil'=>$d['telefono_movil'],
                ':email'=>$d['email'],
                ':prov'=>$d['provincia_id'],
                ':canton'=>$d['canton_id'],
                ':tipo'=>$d['tipo_entidad'],
                ':seg'=>$d['id_segmento'],
                ':notas'=>$d['notas'],
            ]);
            $id = (int)$st->fetchColumn();

            $this->replaceServicios($id, $d['servicios']);
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $d): void {
        $this->db->beginTransaction();
        try {
            $sql = "UPDATE ".self::TBL." SET
                      nombre=:nombre, ruc=:ruc,
                      telefono_fijo_1=:tfijo, telefono_movil=:tmovil,
                      email=:email, provincia_id=:prov, canton_id=:canton,
                      tipo_entidad=:tipo, id_segmento=:seg, notas=:notas
                    WHERE ".self::PK." = :id";
            $st = $this->db->prepare($sql);
            $st->execute([
                ':nombre'=>$d['nombre'], ':ruc'=>$d['ruc'],
                ':tfijo'=>$d['telefono_fijo_1'], ':tmovil'=>$d['telefono_movil'],
                ':email'=>$d['email'], ':prov'=>$d['provincia_id'], ':canton'=>$d['canton_id'],
                ':tipo'=>$d['tipo_entidad'], ':seg'=>$d['id_segmento'], ':notas'=>$d['notas'],
                ':id'=>$id,
            ]);

            $this->replaceServicios($id, $d['servicios']);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** Borra e inserta pivot cooperativa_servicio en la misma transacción */
    public function replaceServicios(int $id, array $ids): void {
        // aquí asumimos que ya estamos en tx desde create/update
        $this->db->prepare("DELETE FROM public.cooperativa_servicio WHERE id_cooperativa=:id")
                 ->execute([':id'=>$id]);
        if (!$ids) return;

        // Exclusividad Matrix ya viene aplicada desde Controller (si incluye 1 => [1])
        $ins = $this->db->prepare(
            "INSERT INTO public.cooperativa_servicio (id_cooperativa, id_servicio, activo, fecha_alta)
             VALUES (:id, :sid, TRUE, now())"
        );
        foreach ($ids as $sid) {
            $ins->execute([':id'=>$id, ':sid'=>(int)$sid]);
        }
    }

    public function delete(int $id): void {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM public.cooperativa_servicio WHERE id_cooperativa = :id")
                ->execute([':id' => $id]);
            $stmt = $this->db->prepare("DELETE FROM ".self::TBL." WHERE ".self::PK." = :id");
            $stmt->execute([':id' => $id]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
