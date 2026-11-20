<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class RolModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query("SELECT id, nombre FROM rol ORDER BY id ASC;");
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, nombre FROM rol WHERE id = :id;");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
