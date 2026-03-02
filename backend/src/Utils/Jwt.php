<?php
declare(strict_types=1);

namespace App\Utils;

use App\Config\JwtConfig;
use Firebase\JWT\JWT as FirebaseJwt;
use Firebase\JWT\Key as JwtKey;

final class Jwt
{
    public static function generateToken(string $userId): string
    {
        $now = time();
        $exp = $now + self::parseExpiresIn(JwtConfig::expiresIn());

        return FirebaseJwt::encode([
            'userId' => $userId,
            'iat' => $now,
            'exp' => $exp,
        ], JwtConfig::secret(), 'HS256');
    }

    public static function generateRefreshToken(string $userId): string
    {
        $now = time();
        $exp = $now + self::parseExpiresIn(JwtConfig::refreshExpiresIn());

        return FirebaseJwt::encode([
            'userId' => $userId,
            'iat' => $now,
            'exp' => $exp,
        ], JwtConfig::secret(), 'HS256');
    }

    /** @return array<string,mixed> */
    public static function verifyToken(string $token): array
    {
        $decoded = FirebaseJwt::decode($token, new JwtKey(JwtConfig::secret(), 'HS256'));
        return (array)$decoded;
    }

    private static function parseExpiresIn(string $value): int
    {
        $value = trim($value);
        if ($value === '') return 0;

        if (ctype_digit($value)) {
            return (int)$value;
        }

        if (!preg_match('/^(\d+)([smhd])$/i', $value, $m)) {
            // fallback: 7 days
            return 7 * 24 * 60 * 60;
        }

        $n = (int)$m[1];
        $unit = strtolower($m[2]);

        return match ($unit) {
            's' => $n,
            'm' => $n * 60,
            'h' => $n * 60 * 60,
            'd' => $n * 24 * 60 * 60,
            default => 7 * 24 * 60 * 60,
        };
    }
}
