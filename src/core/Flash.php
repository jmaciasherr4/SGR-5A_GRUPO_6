<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Gestor de mensajes Flash (notificaciones temporales de un solo uso).
 */
class Flash
{
    private const SESSION_KEY = '_flash_messages';

    public static function set(string $type, string $message): void
    {
        Session::start();
        $messages = Session::get(self::SESSION_KEY, []);
        $messages[] = [
            'type'    => $type, // success, danger, warning, info
            'message' => $message
        ];
        Session::set(self::SESSION_KEY, $messages);
    }

    public static function success(string $message): void
    {
        self::set('success', $message);
    }

    public static function error(string $message): void
    {
        self::set('danger', $message);
    }

    public static function warning(string $message): void
    {
        self::set('warning', $message);
    }

    public static function info(string $message): void
    {
        self::set('info', $message);
    }

    public static function get(): array
    {
        Session::start();
        $messages = Session::get(self::SESSION_KEY, []);
        Session::remove(self::SESSION_KEY);
        return $messages;
    }

    public static function has(): bool
    {
        Session::start();
        return !empty(Session::get(self::SESSION_KEY, []));
    }
}
