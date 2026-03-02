<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    /**
     * Get a shared PDO connection to PostgreSQL.
     */
    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?: '';

        if ($dbUrl !== '') {
            $parts = parse_url($dbUrl);
            if ($parts === false) {
                throw new \RuntimeException('Invalid DATABASE_URL');
            }

            $host = $parts['host'] ?? '127.0.0.1';
            $port = (int)($parts['port'] ?? 5432);
            $user = $parts['user'] ?? 'postgres';
            $pass = $parts['pass'] ?? '';
            $db   = ltrim((string)($parts['path'] ?? ''), '/') ?: 'questions2';

            $query = [];
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $query);
            }
            $sslmode = $query['sslmode'] ?? null;

            $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
            if ($sslmode) {
                $dsn .= ";sslmode={$sslmode}";
            }

            self::$pdo = self::connect($dsn, $user, $pass);
            return self::$pdo;
        }

        $host = $_ENV['PGHOST'] ?? getenv('PGHOST') ?: '127.0.0.1';
        $port = (int)($_ENV['PGPORT'] ?? getenv('PGPORT') ?: 5432);
        $db   = $_ENV['PGDATABASE'] ?? getenv('PGDATABASE') ?: 'questions2';
        $user = $_ENV['PGUSER'] ?? getenv('PGUSER') ?: 'postgres';
        $pass = $_ENV['PGPASSWORD'] ?? getenv('PGPASSWORD') ?: '';

        $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
        self::$pdo = self::connect($dsn, $user, $pass);
        return self::$pdo;
    }

    private static function connect(string $dsn, string $user, string $pass): PDO
    {
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
