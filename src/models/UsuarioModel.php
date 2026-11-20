<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function buscarPorUsername(string $username): ?array
    {
        $sql = "
            SELECT u.id, u.persona_id, u.username, u.password_hash, u.rol_id, u.activo, u.creado_en,
                   r.nombre AS rol_nombre,
                   p.nombres, p.apellidos, p.documento, p.telefono
            FROM usuario u
            INNER JOIN rol r ON u.rol_id = r.id
            INNER JOIN persona p ON u.persona_id = p.id
            WHERE u.username = :username;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT u.id, u.persona_id, u.username, u.password_hash, u.rol_id, u.activo, u.creado_en,
                   r.nombre AS rol_nombre,
                   p.nombres, p.apellidos, p.documento, p.telefono
            FROM usuario u
            INNER JOIN rol r ON u.rol_id = r.id
            INNER JOIN persona p ON u.persona_id = p.id
            WHERE u.id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listarTodos(): array
    {
        $sql = "
            SELECT u.id, u.persona_id, u.username, u.rol_id, u.activo, u.creado_en,
                   r.nombre AS rol_nombre,
                   p.nombres, p.apellidos, p.documento, p.telefono
            FROM usuario u
            INNER JOIN rol r ON u.rol_id = r.id
            INNER JOIN persona p ON u.persona_id = p.id
            ORDER BY u.id ASC;
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function existeUsername(string $username, ?int $excluirId = null): bool
    {
        if ($excluirId !== null) {
            $stmt = $this->db->prepare("SELECT 1 FROM usuario WHERE username = :username AND id != :excluirId;");
            $stmt->execute(['username' => $username, 'excluirId' => $excluirId]);
        } else {
            $stmt = $this->db->prepare("SELECT 1 FROM usuario WHERE username = :username;");
            $stmt->execute(['username' => $username]);
        }
        return (bool)$stmt->fetchColumn();
    }

    public function existeDocumento(string $documento, ?int $excluirPersonaId = null): bool
    {
        if ($excluirPersonaId !== null) {
            $stmt = $this->db->prepare("SELECT 1 FROM persona WHERE documento = :documento AND id != :excluirPersonaId;");
            $stmt->execute(['documento' => $documento, 'excluirPersonaId' => $excluirPersonaId]);
        } else {
            $stmt = $this->db->prepare("SELECT 1 FROM persona WHERE documento = :documento;");
            $stmt->execute(['documento' => $documento]);
        }
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Comprueba si el usuario tiene registros vinculados en el sistema
     */
    public function tieneHistorial(int $usuarioId): bool
    {
        // Revisar en pedidos
        $stmt1 = $this->db->prepare("SELECT 1 FROM pedido WHERE usuario_id = :id LIMIT 1;");
        $stmt1->execute(['id' => $usuarioId]);
        if ($stmt1->fetchColumn()) return true;

        // Revisar en facturas
        $stmt2 = $this->db->prepare("SELECT 1 FROM factura WHERE usuario_id = :id LIMIT 1;");
        $stmt2->execute(['id' => $usuarioId]);
        if ($stmt2->fetchColumn()) return true;

        // Revisar en historial de estados
        $stmt3 = $this->db->prepare("SELECT 1 FROM historial_estado_pedido WHERE usuario_id = :id LIMIT 1;");
        $stmt3->execute(['id' => $usuarioId]);
        if ($stmt3->fetchColumn()) return true;

        // Revisar en movimientos de inventario
        $stmt4 = $this->db->prepare("SELECT 1 FROM movimiento_inventario WHERE usuario_id = :id LIMIT 1;");
        $stmt4->execute(['id' => $usuarioId]);
        if ($stmt4->fetchColumn()) return true;

        return false;
    }

    /**
     * RF-02: Creación de usuario dentro de una transacción segura.
     */
    public function crear(array $personaData, array $usuarioData): int
    {
        $this->db->beginTransaction();
        try {
            // 1. Insertar persona
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
                'telefono'  => $personaData['telefono'] ?: null,
            ]);
            $personaId = (int)$stmtPersona->fetchColumn();

            // 2. Insertar usuario
            $sqlUsuario = "
                INSERT INTO usuario (persona_id, username, password_hash, rol_id, activo)
                VALUES (:persona_id, :username, :password_hash, :rol_id, :activo)
                RETURNING id;
            ";
            $stmtUsuario = $this->db->prepare($sqlUsuario);
            $stmtUsuario->execute([
                'persona_id'    => $personaId,
                'username'      => $usuarioData['username'],
                'password_hash' => password_hash($usuarioData['password'], PASSWORD_BCRYPT),
                'rol_id'        => $usuarioData['rol_id'],
                'activo'        => (!empty($usuarioData['activo']) && $usuarioData['activo'] !== 'false') ? 'true' : 'false',
            ]);
            $usuarioId = (int)$stmtUsuario->fetchColumn();

            $this->db->commit();
            return $usuarioId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Database::logError("Error al crear usuario: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * RF-02: Actualización de usuario y persona.
     */
    public function actualizar(int $usuarioId, array $personaData, array $usuarioData): bool
    {
        $usuarioActual = $this->buscarPorId($usuarioId);
        if (!$usuarioActual) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            // 1. Actualizar persona
            $sqlPersona = "
                UPDATE persona
                SET nombres = :nombres, apellidos = :apellidos, documento = :documento, telefono = :telefono
                WHERE id = :persona_id;
            ";
            $stmtPersona = $this->db->prepare($sqlPersona);
            $stmtPersona->execute([
                'nombres'    => $personaData['nombres'],
                'apellidos'  => $personaData['apellidos'],
                'documento'  => $personaData['documento'],
                'telefono'   => $personaData['telefono'] ?: null,
                'persona_id' => $usuarioActual['persona_id']
            ]);

            // 2. Actualizar usuario (con o sin cambio de contraseña)
            $activoVal = (!empty($usuarioData['activo']) && $usuarioData['activo'] !== 'false') ? 'true' : 'false';

            if (!empty($usuarioData['password'])) {
                $sqlUsuario = "
                    UPDATE usuario
                    SET username = :username, rol_id = :rol_id, activo = :activo, password_hash = :password_hash
                    WHERE id = :id;
                ";
                $params = [
                    'username'      => $usuarioData['username'],
                    'rol_id'        => $usuarioData['rol_id'],
                    'activo'        => $activoVal,
                    'password_hash' => password_hash($usuarioData['password'], PASSWORD_BCRYPT),
                    'id'            => $usuarioId
                ];
            } else {
                $sqlUsuario = "
                    UPDATE usuario
                    SET username = :username, rol_id = :rol_id, activo = :activo
                    WHERE id = :id;
                ";
                $params = [
                    'username' => $usuarioData['username'],
                    'rol_id'   => $usuarioData['rol_id'],
                    'activo'   => $activoVal,
                    'id'       => $usuarioId
                ];
            }

            $stmtUsuario = $this->db->prepare($sqlUsuario);
            $stmtUsuario->execute($params);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Database::logError("Error al actualizar usuario: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * RF-02: Baja lógica de usuario (activo = true/false).
     */
    public function cambiarEstado(int $id, bool $activo): bool
    {
        $stmt = $this->db->prepare("UPDATE usuario SET activo = :activo WHERE id = :id;");
        return $stmt->execute(['activo' => $activo ? 'true' : 'false', 'id' => $id]);
    }

    /**
     * Eliminación con control de integridad referencial (ON DELETE RESTRICT).
     * Captura SQLSTATE 23503 y retorna estado indicando que tiene registros vinculados.
     */
    public function eliminar(int $id): array
    {
        $usuario = $this->buscarPorId($id);
        if (!$usuario) {
            return ['ok' => false, 'mensaje' => 'El usuario no existe.'];
        }

        // Si tiene historial, bloqueamos de inmediato para sugerir desactivación
        if ($this->tieneHistorial($id)) {
            return [
                'ok'            => false,
                'tiene_historial' => true,
                'mensaje'       => 'No se puede eliminar el usuario porque tiene registros de historial asociados (pedidos, facturas o auditoría). Para revocar su acceso, utiliza la opción "Desactivar".'
            ];
        }

        $this->db->beginTransaction();
        try {
            // Eliminar usuario
            $stmtUsuario = $this->db->prepare("DELETE FROM usuario WHERE id = :id;");
            $stmtUsuario->execute(['id' => $id]);

            // Eliminar persona asociada si no está vinculada a cliente
            $stmtCliente = $this->db->prepare("SELECT 1 FROM cliente WHERE persona_id = :p_id;");
            $stmtCliente->execute(['p_id' => $usuario['persona_id']]);
            if (!$stmtCliente->fetchColumn()) {
                $stmtPersona = $this->db->prepare("DELETE FROM persona WHERE id = :p_id;");
                $stmtPersona->execute(['p_id' => $usuario['persona_id']]);
            }

            $this->db->commit();
            return ['ok' => true, 'mensaje' => 'Usuario eliminado correctamente.'];
        } catch (PDOException $e) {
            $this->db->rollBack();
            Database::logError("Error al eliminar usuario {$id}: " . $e->getMessage());

            // Código PostgreSQL 23503: foreign_key_violation
            if ($e->getCode() === '23503') {
                return [
                    'ok'              => false,
                    'tiene_historial' => true,
                    'mensaje'         => 'No se puede eliminar el usuario por restricciones de integridad (clave foránea activa). Se recomienda cambiar su estado a "Desactivado".'
                ];
            }

            return ['ok' => false, 'mensaje' => 'Ocurrió un error al intentar eliminar el usuario.'];
        }
    }
}
