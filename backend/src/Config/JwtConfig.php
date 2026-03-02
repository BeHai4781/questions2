<?php
declare(strict_types=1);

namespace App\Config;

final class JwtConfig
{
    public static function secret(): string
    {
        return $_ENV['JWT_SECRET'] ?? 'default-secret-change-in-production';
    }

    public static function expiresIn(): string
    {
        return $_ENV['JWT_EXPIRE'] ?? '7d';
    }

    public static function refreshExpiresIn(): string
    {
        return $_ENV['JWT_REFRESH_EXPIRE'] ?? '30d';
    }
}
