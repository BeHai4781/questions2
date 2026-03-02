<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ExamAttemptModel;
use App\Utils\Response;

final class ExamAttemptController
{
    public static function getAttempts(array $query, array $currentUser): void
    {
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 10;

        $pageNum = max((int)$page, 1);
        $limitNum = min(max((int)$limit, 1), 100);
        $skip = ($pageNum - 1) * $limitNum;

        $filter = [
            'examId' => $query['examId'] ?? null,
            'status' => $query['status'] ?? null,
        ];

        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'student') {
            $filter['userId'] = (string)($currentUser['id'] ?? '');
        } elseif (isset($query['userId'])) {
            $filter['userId'] = $query['userId'];
        }

        $model = new ExamAttemptModel();
        $total = $model->countDocuments($filter);
        $totalPages = (int)ceil($total / $limitNum) ?: 1;
        $items = $model->find($filter, $skip, $limitNum);

        Response::paginated($items, [
            'page' => $pageNum,
            'limit' => $limitNum,
            'total' => $total,
            'totalPages' => $totalPages,
        ], 'Exam attempts retrieved successfully');
    }

    public static function getAttemptById(string $id, array $currentUser): void
    {
        $model = new ExamAttemptModel();
        $attempt = $model->findById($id);
        if (!$attempt) {
            Response::error('Exam attempt not found', 404, 'ATTEMPT_NOT_FOUND');
            return;
        }

        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'student' && (string)($attempt['user_id'] ?? '') !== (string)($currentUser['id'] ?? '')) {
            Response::error('You do not have permission to access this resource', 403, 'FORBIDDEN');
            return;
        }

        Response::success(['attempt' => $attempt], 'Exam attempt retrieved successfully');
    }

    public static function createAttempt(array $body, array $currentUser): void
    {
        $payload = $body;
        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'student' || !isset($payload['userId'])) {
            $payload['userId'] = (string)($currentUser['id'] ?? '');
        }

        $model = new ExamAttemptModel();
        $created = $model->create($payload);
        if (!$created) {
            Response::error('Unable to create exam attempt', 400, 'ATTEMPT_CREATE_FAILED');
            return;
        }
        Response::success(['attempt' => $created], 'Exam attempt created successfully', 201);
    }

    public static function updateAttempt(string $id, array $body, array $currentUser): void
    {
        $model = new ExamAttemptModel();
        $existing = $model->findById($id);
        if (!$existing) {
            Response::error('Exam attempt not found', 404, 'ATTEMPT_NOT_FOUND');
            return;
        }

        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'student' && (string)($existing['user_id'] ?? '') !== (string)($currentUser['id'] ?? '')) {
            Response::error('You do not have permission to access this resource', 403, 'FORBIDDEN');
            return;
        }

        $updated = $model->updateById($id, $body);
        Response::success(['attempt' => $updated], 'Exam attempt updated successfully');
    }

    public static function deleteAttempt(string $id, array $currentUser): void
    {
        $model = new ExamAttemptModel();
        $existing = $model->findById($id);
        if (!$existing) {
            Response::error('Exam attempt not found', 404, 'ATTEMPT_NOT_FOUND');
            return;
        }

        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'student' && (string)($existing['user_id'] ?? '') !== (string)($currentUser['id'] ?? '')) {
            Response::error('You do not have permission to access this resource', 403, 'FORBIDDEN');
            return;
        }

        $ok = $model->deleteById($id);
        if (!$ok) {
            Response::error('Exam attempt not found', 404, 'ATTEMPT_NOT_FOUND');
            return;
        }
        Response::success(['id' => $id], 'Exam attempt deleted successfully');
    }
}
