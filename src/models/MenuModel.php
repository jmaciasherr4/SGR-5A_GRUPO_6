<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class MenuModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ========================================================================
    // GESTIÓN DE CATEGORÍAS
    // ========================================================================

    public function listarCategorias(): array
    {
        $sql = "
            SELECT c.id, c.nombre, COUNT(p.id) AS total_platos
            FROM categoria_menu c
            LEFT JOIN plato p ON c.id = p.categoria_id
            GROUP BY c.id, c.nombre
            ORDER BY c.id ASC;
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function buscarCategoriaPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, nombre FROM categoria_menu WHERE id = :id;");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existeCategoriaNombre(string $nombre, ?int $excluirId = null): bool
    {
        if ($excluirId !== null) {
            $stmt = $this->db->prepare("SELECT 1 FROM categoria_menu WHERE LOWER(nombre) = LOWER(:nombre) AND id != :excluir;");
            $stmt->execute(['nombre' => trim($nombre), 'excluir' => $excluirId]);
        } else {
            $stmt = $this->db->prepare("SELECT 1 FROM categoria_menu WHERE LOWER(nombre) = LOWER(:nombre);");
            $stmt->execute(['nombre' => trim($nombre)]);
        }
        return (bool)$stmt->fetchColumn();
    }

    public function crearCategoria(string $nombre): int
    {
        $stmt = $this->db->prepare("INSERT INTO categoria_menu (nombre) VALUES (:nombre) RETURNING id;");
        $stmt->execute(['nombre' => trim($nombre)]);
        return (int)$stmt->fetchColumn();
    }

    public function actualizarCategoria(int $id, string $nombre): bool
    {
        $stmt = $this->db->prepare("UPDATE categoria_menu SET nombre = :nombre WHERE id = :id;");
        return $stmt->execute(['nombre' => trim($nombre), 'id' => $id]);
    }

    public function eliminarCategoria(int $id): array
    {
        $stmtPlatos = $this->db->prepare("SELECT 1 FROM plato WHERE categoria_id = :id LIMIT 1;");
        $stmtPlatos->execute(['id' => $id]);
        if ($stmtPlatos->fetchColumn()) {
            return [
                'ok'      => false,
                'mensaje' => 'No se puede eliminar la categoría porque tiene platos asociados. Reasigna o elimina los platos primero.'
            ];
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM categoria_menu WHERE id = :id;");
            $stmt->execute(['id' => $id]);
            return ['ok' => true, 'mensaje' => 'Categoría eliminada correctamente.'];
        } catch (PDOException $e) {
            Database::logError("Error al eliminar categoría {$id}: " . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'Error al intentar eliminar la categoría.'];
        }
    }

    // ========================================================================
    // GESTIÓN DE PLATOS DEL MENÚ (RF-08)
    // ========================================================================

    public function listarPlatos(?int $categoriaId = null, ?string $busqueda = null, ?bool $soloActivos = null): array
    {
        $sql = "
            SELECT p.id, p.categoria_id, p.nombre, p.descripcion, p.precio, p.activo,
                   c.nombre AS categoria_nombre
            FROM plato p
            INNER JOIN categoria_menu c ON p.categoria_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if ($categoriaId !== null && $categoriaId > 0) {
            $sql .= " AND p.categoria_id = :cat";
            $params['cat'] = $categoriaId;
        }

        if ($busqueda !== null && trim($busqueda) !== '') {
            $sql .= " AND (p.nombre ILIKE :b1 OR p.descripcion ILIKE :b2)";
            $termino = '%' . trim($busqueda) . '%';
            $params['b1'] = $termino;
            $params['b2'] = $termino;
        }

        if ($soloActivos !== null) {
            $sql .= " AND p.activo = :activo";
            $params['activo'] = $soloActivos ? 'true' : 'false';
        }

        $sql .= " ORDER BY c.id ASC, p.id ASC;";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPlatoPorId(int $id): ?array
    {
        $sql = "
            SELECT p.id, p.categoria_id, p.nombre, p.descripcion, p.precio, p.activo,
                   c.nombre AS categoria_nombre
            FROM plato p
            INNER JOIN categoria_menu c ON p.categoria_id = c.id
            WHERE p.id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existePlatoNombre(string $nombre, int $categoriaId, ?int $excluirId = null): bool
    {
        if ($excluirId !== null) {
            $stmt = $this->db->prepare("SELECT 1 FROM plato WHERE LOWER(nombre) = LOWER(:nombre) AND categoria_id = :cat AND id != :excluir;");
            $stmt->execute(['nombre' => trim($nombre), 'cat' => $categoriaId, 'excluir' => $excluirId]);
        } else {
            $stmt = $this->db->prepare("SELECT 1 FROM plato WHERE LOWER(nombre) = LOWER(:nombre) AND categoria_id = :cat;");
            $stmt->execute(['nombre' => trim($nombre), 'cat' => $categoriaId]);
        }
        return (bool)$stmt->fetchColumn();
    }

    public function tieneHistorialPedidos(int $platoId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM detalle_pedido WHERE plato_id = :id LIMIT 1;");
        $stmt->execute(['id' => $platoId]);
        return (bool)$stmt->fetchColumn();
    }

    public function crearPlato(array $datos): int
    {
        $sql = "
            INSERT INTO plato (categoria_id, nombre, descripcion, precio, activo)
            VALUES (:categoria_id, :nombre, :descripcion, :precio, :activo)
            RETURNING id;
        ";
        $stmt = $this->db->prepare($sql);
        $activoVal = (!empty($datos['activo']) && $datos['activo'] !== 'false') ? 'true' : 'false';

        $stmt->execute([
            'categoria_id' => (int)$datos['categoria_id'],
            'nombre'       => trim((string)$datos['nombre']),
            'descripcion'  => trim((string)($datos['descripcion'] ?? '')) ?: null,
            'precio'       => (float)$datos['precio'],
            'activo'       => $activoVal
        ]);
        return (int)$stmt->fetchColumn();
    }

    public function actualizarPlato(int $id, array $datos): bool
    {
        $sql = "
            UPDATE plato
            SET categoria_id = :categoria_id, nombre = :nombre, descripcion = :descripcion, precio = :precio, activo = :activo
            WHERE id = :id;
        ";
        $stmt = $this->db->prepare($sql);
        $activoVal = (!empty($datos['activo']) && $datos['activo'] !== 'false') ? 'true' : 'false';

        return $stmt->execute([
            'categoria_id' => (int)$datos['categoria_id'],
            'nombre'       => trim((string)$datos['nombre']),
            'descripcion'  => trim((string)($datos['descripcion'] ?? '')) ?: null,
            'precio'       => (float)$datos['precio'],
            'activo'       => $activoVal,
            'id'           => $id
        ]);
    }

    /**
     * RF-08: Baja lógica de plato (activo = true/false).
     */
    public function cambiarEstadoPlato(int $id, bool $activo): bool
    {
        $stmt = $this->db->prepare("UPDATE plato SET activo = :activo WHERE id = :id;");
        return $stmt->execute([
            'activo' => $activo ? 'true' : 'false',
            'id'     => $id
        ]);
    }

    /**
     * Eliminación con control de integridad referencial (ON DELETE RESTRICT).
     */
    public function eliminarPlato(int $id): array
    {
        $plato = $this->buscarPlatoPorId($id);
        if (!$plato) {
            return ['ok' => false, 'mensaje' => 'El plato no existe.'];
        }

        // Si el plato ya tiene pedidos históricos, se bloquea la eliminación física y se sugiere desactivar
        if ($this->tieneHistorialPedidos($id)) {
            return [
                'ok'            => false,
                'tiene_historial' => true,
                'mensaje'       => 'No se puede eliminar el plato porque figura en pedidos históricos del restaurante. Para que no aparezca en nuevos pedidos, utiliza la opción "Desactivar".'
            ];
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM plato WHERE id = :id;");
            $stmt->execute(['id' => $id]);
            return ['ok' => true, 'mensaje' => "Plato '{$plato['nombre']}' eliminado correctamente."];
        } catch (PDOException $e) {
            Database::logError("Error al eliminar plato {$id}: " . $e->getMessage());
            if ($e->getCode() === '23503') {
                return [
                    'ok'            => false,
                    'tiene_historial' => true,
                    'mensaje'       => 'No se puede eliminar el plato por restricciones de integridad (receta o pedidos asociados). Se recomienda "Desactivar".'
                ];
            }
            return ['ok' => false, 'mensaje' => 'Error al intentar eliminar el plato.'];
        }
    }
}
