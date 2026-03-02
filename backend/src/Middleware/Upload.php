<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Config\Upload as UploadConfig;

final class Upload
{
    public static function uploadImage(string $field = 'image'): ?array
    {
        return self::handleSingle($field);
    }

    public static function uploadFile(string $field = 'file'): ?array
    {
        return self::handleSingle($field);
    }

    public static function uploadMultiple(string $field = 'files', int $max = 10): array
    {
        return [];
    }

    private static function handleSingle(string $field): ?array
    {
        if (!isset($_FILES[$field])) return null;

        $f = $_FILES[$field];
        $mimetype = $f['type'] ?? '';
        $allowedTypes = array_merge(UploadConfig::allowedImageTypes(), UploadConfig::allowedFileTypes());

        if (!in_array($mimetype, $allowedTypes, true)) {
            throw new \RuntimeException('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
        }

        if (($f['size'] ?? 0) > UploadConfig::maxSize()) {
            throw new \RuntimeException('File too large');
        }

        $uploadDir = UploadConfig::uploadPath();
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $original = $f['name'] ?? 'file';
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        $name = pathinfo($original, PATHINFO_FILENAME);
        $uniqueSuffix = time() . '-' . random_int(0, 1000000000);
        $filename = $name . '-' . $uniqueSuffix . ($ext ? ('.' . $ext) : '');

        $dest = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            throw new \RuntimeException('Upload failed');
        }

        return [
            'filename' => $filename,
            'path' => $dest,
            'mimetype' => $mimetype,
            'size' => $f['size'] ?? 0,
        ];
    }
}
