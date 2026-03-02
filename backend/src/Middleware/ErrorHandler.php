<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Response;
use PDOException;
use Throwable;

final class ErrorHandler
{
    public static function handle(Throwable $err): void
    {
        $message = $err->getMessage();
        $code = 'SERVER_ERROR';
        $statusCode = 500;

        // PostgreSQL unique violation
        if ($err instanceof PDOException) {
            // SQLSTATE 23505 = unique_violation
            if (($err->getCode() ?? '') === '23505') {
                $code = 'DUPLICATE_KEY';
                $statusCode = 400;
                $message = 'duplicate key already exists';
            }
        }

        $payload = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message ?: 'Server Error',
            ],
        ];

        if (($_ENV['NODE_ENV'] ?? 'development') === 'development') {
            $payload['error']['stack'] = $err->getTraceAsString();
        }

        Response::json($payload, $statusCode);
    }
}
