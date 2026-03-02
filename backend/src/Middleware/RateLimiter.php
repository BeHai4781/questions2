<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Response;

final class RateLimiter
{
    public static function enforce(string $prefix, int $windowMs, int $max): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (!str_starts_with($uri, $prefix)) {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $windowSeconds = (int)max(1, floor($windowMs / 1000));

        $key = 'rl_' . sha1($ip . '|' . $prefix);
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $key . '.json';

        $now = time();
        $data = ['start' => $now, 'count' => 0];

        if (is_file($file)) {
            $raw = file_get_contents($file);
            if ($raw !== false) {
                $tmp = json_decode($raw, true);
                if (is_array($tmp) && isset($tmp['start'], $tmp['count'])) {
                    $data = $tmp;
                }
            }
        }

        if (($now - (int)$data['start']) >= $windowSeconds) {
            $data = ['start' => $now, 'count' => 0];
        }

        $data['count'] = (int)$data['count'] + 1;
        file_put_contents($file, json_encode($data));

        if ($data['count'] > $max) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'TOO_MANY_REQUESTS',
                    'message' => 'Too many requests from this IP, please try again later.',
                ],
            ], 429);
            exit;
        }
    }
}
