<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class ClienteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(?string $busqueda = null): array
    {
        $sql = "
            SELECT c.id, c.persona_id, c.creado_en,
                   p.nombres, p.apellidos, p.documento, p.telefono
            FROM cliente c
            INNER JOIN persona p ON c.persona_id = p.id
        ";

        $params = [];
        if ($busqueda !== null && trim($busqueda) !== '') {
            $sql .= " WHERE p.nombres ILIKE :b1 OR p.apellidos ILIKE :b2 OR p.documento ILIKE :b3";
            $termino = '%' . trim($busqueda) . '%';
            $params = ['b1' => $termino, 'b2' => $termino, 'b3' => $termino];
        }

        $sql .= " ORDER BY c.id ASC;";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT c.id, c.persona_id, c.creado_en,
                   p.nombres, p.apellidos, p.documento, p.telefono
            FROM cliente c
            INNER JOIN persona p ON c.persona_id = p.id
            WHERE c.id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existeDocumento(string $documento, ?int $excluirPersonaId = null): bool
    {
        if ($excluirPersonaId !== null) {
            $stmt = $this->db->prepare("SELECT 1 FROM persona WHERE documento = :documento AND id != :excluir;");
            $stmt->execute(['documento' => $documento, 'excluir' => $excluirPersonaId]);
        } else {
            $stmt = $this->db->prepare("SELECT 1 FROM persona WHERE documento = :documento;");
            $stmt->execute(['documento' => $documento]);
        }
        return (bool)$stmt->fetchColumn();
    }

    public function tieneHistorial(int $clienteId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM reserva WHERE cliente_id = :id LIMIT 1;");
        $stmt->execute(['id' => $clienteId]);
        return (bool)$stmt->fetchColumn();
    }

    public function crear(array $personaData): int
    {
        $this->db->beginTransaction();
        try {
            $sqlPersona = "
                INSERT INTO persona (nombres, apellidos, documento, telefono)
                VALUES (:nombres, :apellidos, :documento, :telefono)
                RETURNING id;
            ";
            $stmtPersona = $this->db->prepare($sqlPersona);
            $stmtPersona->execute([
                'nombres'   => $personaData['nombres'],
                'apellidos' => $personaData['apellidos'],
                'documento' => $personaData['documento'],
                'telefono'  => $personaData['telefono'] ?: null
            ]);
            $personaId = (int)$stmtPersona->fetchColumn();

            $sqlCliente = "
                INSERT INTO cliente (persona_id)
                VALUES (:persona_id)
                RETURNING id;
            ";
            $stmtCliente = $this->db->prepare($sqlCliente);
            $stmtCliente->execute(['persona_id' => $personaId]);
            $clienteId = (int)$stmtCliente->fetchColumn();

            $this->db->commit();
            return $clienteId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Database::logError("Error al crear cliente: " . $e->getMessage());
            throw $e;
        }
    }

    public function actualizar(int $clienteId, array $personaData): bool
    {
        $cliente = $this->buscarPorId($clienteId);
        if (!$cliente) {
            return false;
        }

        $sql = "
            UPDATE persona
            SET nombres = :nombres, apellidos = :apellidos, documento = :documento, telefono = :telefono
            WHERE id = :persona_id;
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombres'    => $personaData['nombres'],
            'apellidos'  => $personaData['apellidos'],
            'documento'  => $personaData['documento'],
            'telefono'   => $personaData['telefono'] ?: null,
            'persona_id' => $cliente['persona_id']
        ]);
    }

    public function eliminar(int $clienteId): array
    {
        $cliente = $this->buscarPorId($clienteId);
        if (!$cliente) {
            return ['ok' => false, 'mensaje' => 'El cliente no existe.'];
        }

        if ($this->tieneHistorial($clienteId)) {
            return [
                'ok'      => false,
                'mensaje' => 'No se puede eliminar el cliente porque tiene reservas históricas registradas en el restaurante.'
            ];
        }

        $this->db->beginTransaction();
        try {
            $stmtCliente = $this->db->prepare("DELETE FROM cliente WHERE id = :id;");
            $stmtCliente->execute(['id' => $clienteId]);

            // Eliminar persona asociada si no está vinculada a un usuario del sistema
            $stmtUsuario = $this->db->prepare("SELECT 1 FROM usuario WHERE persona_id = :p_id;");
            $stmtUsuario->execute(['p_id' => $cliente['persona_id']]);
            if (!$stmtUsuario->fetchColumn()) {
                $stmtPersona = $this->db->prepare("DELETE FROM persona WHERE id = :p_id;");
                $stmtPersona->execute(['p_id' => $cliente['persona_id']]);
            }

            $this->db->commit();
            return ['ok' => true, 'mensaje' => 'Cliente eliminado correctamente.'];
        } catch (PDOException $e) {
            $this->db->rollBack();
            Database::logError("Error al eliminar cliente {$clienteId}: " . $e->getMessage());

            if ($e->getCode() === '23503') {
                return [
                    'ok'      => false,
                    'mensaje' => 'No se puede eliminar el cliente por restricciones de integridad referencial (registros asociados).'
                ];
            }
            return ['ok' => false, 'mensaje' => 'Ocurrió un error al intentar eliminar el cliente.'];
        }
    }
}
