<?php

namespace SchemaEngine\Database;

use PDO;

class ConnectionFactory
{
    public static function make(array $config): PDO
    {
        $driver = $config['driver'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $database = $config['database'] ?? ':memory:';

            $pdo = new PDO(
                "sqlite:{$database}",
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            $pdo->exec('PRAGMA foreign_keys = ON');

            return $pdo;
        }

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $driver,
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        return new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}
