<?php
declare(strict_types=1);

/**
 * Bootstrap de la aplicación: Autocargador PSR-4 simple, zona horaria y variables de entorno.
 */

// Zona horaria obligatoria del sistema
date_default_timezone_set('America/Guayaquil');

// Autocargador simple para el namespace App\
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Cargar variables de entorno
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    \App\Core\Env::load($envPath);
} elseif (file_exists(dirname(__DIR__) . '/.env.example')) {
    \App\Core\Env::load(dirname(__DIR__) . '/.env.example');
}
