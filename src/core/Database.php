<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Singleton de conexión a Base de Datos con PDO (PostgreSQL)
 * Garantiza una única instancia, manejo de excepciones seguro y logs.
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
        // Constructor privado para patrón Singleton
    }

    private function __clone()
    {
        // Evita clonación
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $configPath = dirname(__DIR__) . '/config/database.php';
            if (!file_exists($configPath)) {
                throw new RuntimeException("Archivo de configuración de base de datos no encontrado: {$configPath}");
            }

            $config = require $configPath;

            $dsn = sprintf(
                "pgsql:host=%s;port=%d;dbname=%s;options='--client_encoding=UTF8'",
                $config['host'],
                $config['port'],
                $config['database']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                self::logError($e->getMessage());
                // Mensaje genérico sin exponer credenciales ni estructura técnica interna
                throw new RuntimeException("Error al conectar con la base de datos PostgreSQL. Verifique que el servicio esté activo y las credenciales sean válidas.");
            }
        }

        return self::$instance;
    }

    public static function logError(string $message): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $logFile = $logDir . '/database.log';
        $timestamp = date('Y-m-d H:i:s');
        $entry = sprintf("[%s] [America/Guayaquil] %s%s", $timestamp, $message, PHP_EOL);
        @file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
