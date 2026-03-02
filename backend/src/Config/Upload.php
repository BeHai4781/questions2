<?php
declare(strict_types=1);

namespace App\Config;

final class Upload
{
    public static function maxSize(): int
    {
        return (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 5242880);
    }

    public static function uploadPath(): string
    {
        return (string)($_ENV['UPLOAD_PATH'] ?? './uploads');
    }

    /** @return array<int,string> */
    public static function allowedImageTypes(): array
    {
        return ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    }

    /** @return array<int,string> */
    public static function allowedFileTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }
}
