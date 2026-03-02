<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\QuestionModel;
use App\Utils\Response;

final class QuestionController
{
    public static function getQuestions(array $query, array $currentUser): void
    {
        $search = $query['search'] ?? '';
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 10;

        $pageNum = max((int)$page, 1);
        $limitNum = min(max((int)$limit, 1), 100);
        $skip = ($pageNum - 1) * $limitNum;

        $filter = [
            'search' => (string)$search,
            'class' => $query['class'] ?? null,
            'difficulty' => $query['difficulty'] ?? null,
            'type' => $query['type'] ?? null,
        ];

        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'teacher') {
            $filter['createdBy'] = (string)($currentUser['id'] ?? '');
        } elseif (isset($query['createdBy'])) {
            $filter['createdBy'] = $query['createdBy'];
        }

        $model = new QuestionModel();
        $total = $model->countDocuments($filter);
        $totalPages = (int)ceil($total / $limitNum) ?: 1;
        $items = $model->find($filter, $skip, $limitNum);

        Response::paginated($items, [
            'page' => $pageNum,
            'limit' => $limitNum,
            'total' => $total,
            'totalPages' => $totalPages,
        ], 'Questions retrieved successfully');
    }

    public static function getQuestionById(string $id, array $currentUser): void
    {
        $model = new QuestionModel();
        $question = $model->findById($id);
        if (!$question) {
            Response::error('Question not found', 404, 'QUESTION_NOT_FOUND');
            return;
        }
        Response::success(['question' => $question], 'Question retrieved successfully');
    }

    public static function createQuestion(array $body, array $currentUser): void
    {
        $payload = $body;
        if (!isset($payload['createdBy']) && isset($currentUser['id'])) {
            $payload['createdBy'] = (string)$currentUser['id'];
        }

        $model = new QuestionModel();
        $created = $model->create($payload);
        if (!$created) {
            Response::error('Unable to create question', 400, 'QUESTION_CREATE_FAILED');
            return;
        }
        Response::success(['question' => $created], 'Question created successfully', 201);
    }

    public static function updateQuestion(string $id, array $body, array $currentUser): void
    {
        $model = new QuestionModel();
        $existing = $model->findById($id);
        if (!$existing) {
            Response::error('Question not found', 404, 'QUESTION_NOT_FOUND');
            return;
        }

        $payload = $body;
        if (!isset($payload['updatedBy']) && isset($currentUser['id'])) {
            $payload['updatedBy'] = (string)$currentUser['id'];
        }

        $updated = $model->updateById($id, $payload);
        Response::success(['question' => $updated], 'Question updated successfully');
    }

    public static function deleteQuestion(string $id, array $currentUser): void
    {
        $model = new QuestionModel();
        $existing = $model->findById($id);
        if (!$existing) {
            Response::error('Question not found', 404, 'QUESTION_NOT_FOUND');
            return;
        }

        $ok = $model->deleteById($id);
        if (!$ok) {
            Response::error('Question not found', 404, 'QUESTION_NOT_FOUND');
            return;
        }
        Response::success(['id' => $id], 'Question deleted successfully');
    }
}
