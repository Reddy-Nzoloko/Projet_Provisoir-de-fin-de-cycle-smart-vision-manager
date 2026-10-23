<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    public static function connect(): PDO
    {
        $host = self::environment('DB_HOST', '127.0.0.1');
        $port = self::environment('DB_PORT', '3306');
        $name = self::environment('DB_DATABASE', 'smart_vision_manager');
        $username = self::environment('DB_USERNAME', 'root');
        $password = self::environment('DB_PASSWORD', '');

        $connection = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        $connection->exec("SET time_zone = '+00:00'");

        return $connection;
    }

    private static function environment(string $name, string $default): string
    {
        $value = getenv($name);

        return $value === false ? $default : $value;
    }
}
