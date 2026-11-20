<?php
declare(strict_types=1);

use App\Core\Env;

return [
    'driver'   => Env::get('DB_CONNECTION', 'pgsql'),
    'host'     => Env::get('DB_HOST', '127.0.0.1'),
    'port'     => (int) Env::get('DB_PORT', 5434),
    'database' => Env::get('DB_DATABASE', 'restaurante'),
    'username' => Env::get('DB_USERNAME', 'restaurante_user'),
    'password' => Env::get('DB_PASSWORD', 'restaurante_pass'),
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];
