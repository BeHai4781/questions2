<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Response;

final class Validator
{
    /** @param array<int,string> $messages */
    public static function failIf(array $messages): void
    {
        if (!empty($messages)) {
            Response::error(implode(', ', $messages), 400, 'VALIDATION_ERROR');
            exit;
        }
    }
}
