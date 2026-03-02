<?php
declare(strict_types=1);

namespace App\Utils;

final class Response
{
    public static function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public static function success(array $data, string $message = 'Success', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $statusCode);
    }

    public static function error(string $message = 'Error', int $statusCode = 400, string $code = 'ERROR'): void
    {
        self::json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $statusCode);
    }

    public static function paginated(array $data, array $pagination, string $message = 'Success', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'data' => $data,
            'pagination' => $pagination,
            'message' => $message,
        ], $statusCode);
    }
}
