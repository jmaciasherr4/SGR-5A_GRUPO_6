<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class MesaModel
{
    private PDO $db;

    public const ESTADOS_VALIDOS = ['libre', 'ocupada', 'reservada'];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(?string $zona = null, ?string $estado = null): array
    {
        $sql = "SELECT id, numero, capacidad, zona, estado FROM mesa WHERE 1=1";
        $params = [];

        if ($zona !== null && $zona !== '') {
            $sql .= " AND zona = :zona";
            $params['zona'] = $zona;
        }

        if ($estado !== null && $estado !== '') {
            $sql .= " AND estado = :estado";
            $params['estado'] = $estado;
        }

        $sql .= " ORDER BY numero ASC;";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, numero, capacidad, zona, estado FROM mesa WHERE id = :id;");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function buscarPorNumero(int $numero): ?array
    {
        $stmt = $this->db->prepare("SELECT id, numero, capacidad, zona, estado FROM mesa WHERE numero = :numero;");
        $stmt->execute(['numero' => $numero]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existeNumero(int $numero, ?int $excluirId = null): bool
    {
        if ($excluirId !== null) {
            $stmt = $this->db->prepare("SELECT 1 FROM mesa WHERE numero = :numero AND id != :excluir;");
            $stmt->execute(['numero' => $numero, 'excluir' => $excluirId]);
        } else {
            $stmt = $this->db->prepare("SELECT 1 FROM mesa WHERE numero = :numero;");
            $stmt->execute(['numero' => $numero]);
        }
        return (bool)$stmt->fetchColumn();
    }

    public function obtenerZonas(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT zona FROM mesa ORDER BY zona ASC;");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO mesa (numero, capacidad, zona, estado)
            VALUES (:numero, :capacidad, :zona, :estado)
            RETURNING id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'numero'    => (int)$datos['numero'],
            'capacidad' => (int)$datos['capacidad'],
            'zona'      => trim((string)$datos['zona']),
            'estado'    => $datos['estado'] ?? 'libre'
        ]);
        return (int)$stmt->fetchColumn();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $sql = "
            UPDATE mesa
            SET numero = :numero, capacidad = :capacidad, zona = :zona, estado = :estado
            WHERE id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'numero'    => (int)$datos['numero'],
            'capacidad' => (int)$datos['capacidad'],
            'zona'      => trim((string)$datos['zona']),
            'estado'    => $datos['estado'],
            'id'        => $id
        ]);
    }

    /**
     * RF-07: Cambio rápido de estado de mesa (libre, ocupada, reservada).
     */
    public function cambiarEstado(int $id, string $nuevoEstado): bool
    {
        if (!in_array($nuevoEstado, self::ESTADOS_VALIDOS, true)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE mesa SET estado = :estado WHERE id = :id;");
        return $stmt->execute([
            'estado' => $nuevoEstado,
            'id'     => $id
        ]);
    }

    public function tieneHistorial(int $mesaId): bool
    {
        $stmt1 = $this->db->prepare("SELECT 1 FROM pedido WHERE mesa_id = :id LIMIT 1;");
        $stmt1->execute(['id' => $mesaId]);
        if ($stmt1->fetchColumn()) return true;

        $stmt2 = $this->db->prepare("SELECT 1 FROM reserva WHERE mesa_id = :id LIMIT 1;");
        $stmt2->execute(['id' => $mesaId]);
        if ($stmt2->fetchColumn()) return true;

        return false;
    }

    public function eliminar(int $id): array
    {
        $mesa = $this->buscarPorId($id);
        if (!$mesa) {
            return ['ok' => false, 'mensaje' => 'La mesa no existe.'];
        }

        if ($this->tieneHistorial($id)) {
            return [
                'ok'      => false,
                'mensaje' => 'No se puede eliminar la mesa porque tiene pedidos o reservas históricas asociadas.'
            ];
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM mesa WHERE id = :id;");
            $stmt->execute(['id' => $id]);
            return ['ok' => true, 'mensaje' => "Mesa número {$mesa['numero']} eliminada correctamente."];
        } catch (PDOException $e) {
            Database::logError("Error al eliminar mesa {$id}: " . $e->getMessage());
            if ($e->getCode() === '23503') {
                return [
                    'ok'      => false,
                    'mensaje' => 'No se puede eliminar la mesa por restricciones de integridad referencial (clave foránea activa).'
                ];
            }
            return ['ok' => false, 'mensaje' => 'Error al intentar eliminar la mesa.'];
        }
    }
}
