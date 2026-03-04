<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\QuestionBankModel;
use App\Utils\Response;

final class QuestionBankController
{
    // GET /api/question-bank
    public static function getQuestions(array $query, array $currentUser): void
    {
        $page  = max((int)($query['page']  ?? 1), 1);
        $limit = min(max((int)($query['limit'] ?? 15), 1), 100);
        $skip  = ($page - 1) * $limit;

        $filter = [
            'search'  => trim((string)($query['search']  ?? '')),
            'classId' => $query['classId'] ?? $query['class_id'] ?? null,
            'levelId' => $query['levelId'] ?? $query['level_id'] ?? $query['difficulty'] ?? null,
        ];

        $role = (string)($currentUser['role'] ?? '');
        // Teacher chỉ thấy câu hỏi của mình; admin thấy tất cả
        if ($role === 'teacher') {
            $filter['createdBy'] = (string)($currentUser['id'] ?? '');
        } elseif (!empty($query['createdBy'])) {
            $filter['createdBy'] = (string)$query['createdBy'];
        }

        $model      = new QuestionBankModel();
        $total      = $model->countDocuments($filter);
        $totalPages = (int)ceil($total / $limit) ?: 1;
        $items      = $model->find($filter, $skip, $limit);

        Response::paginated($items, [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'totalPages' => $totalPages,
        ], 'Question bank retrieved successfully');
    }

    // GET /api/question-bank/:id
    public static function getQuestionById(string $id, array $currentUser): void
    {
        $model    = new QuestionBankModel();
        $question = $model->findById($id);

        if (!$question) {
            Response::error('Question not found', 404, 'QUESTION_BANK_NOT_FOUND');
            return;
        }

        // Teacher chỉ được xem câu hỏi của mình
        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'teacher') {
            $createdBy = (string)($question['created_by'] ?? '');
            if ($createdBy !== (string)($currentUser['id'] ?? '')) {
                Response::error('Forbidden', 403, 'FORBIDDEN');
                return;
            }
        }

        Response::success(['question' => $question], 'Question retrieved successfully');
    }

    // POST /api/question-bank
    public static function createQuestion(array $body, array $currentUser): void
    {
        $payload = $body;

        // Gán created_by từ token nếu chưa có
        if (!isset($payload['createdBy']) && isset($currentUser['id'])) {
            $payload['createdBy'] = (string)$currentUser['id'];
        }

        $model   = new QuestionBankModel();
        $created = $model->create($payload);

        if (!$created) {
            Response::error('Unable to create question', 400, 'QUESTION_BANK_CREATE_FAILED');
            return;
        }

        Response::success(['question' => $created], 'Question created successfully', 201);
    }

    // PUT /api/question-bank/:id
    public static function updateQuestion(string $id, array $body, array $currentUser): void
    {
        $model    = new QuestionBankModel();
        $existing = $model->findById($id);

        if (!$existing) {
            Response::error('Question not found', 404, 'QUESTION_BANK_NOT_FOUND');
            return;
        }

        // Teacher chỉ được sửa câu hỏi của mình
        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'teacher') {
            $createdBy = (string)($existing['created_by'] ?? '');
            if ($createdBy !== (string)($currentUser['id'] ?? '')) {
                Response::error('Forbidden', 403, 'FORBIDDEN');
                return;
            }
        }

        $updated = $model->updateById($id, $body);
        Response::success(['question' => $updated], 'Question updated successfully');
    }

    // DELETE /api/question-bank/:id
    public static function deleteQuestion(string $id, array $currentUser): void
    {
        $model    = new QuestionBankModel();
        $existing = $model->findById($id);

        if (!$existing) {
            Response::error('Question not found', 404, 'QUESTION_BANK_NOT_FOUND');
            return;
        }

        // Teacher chỉ được xóa câu hỏi của mình
        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'teacher') {
            $createdBy = (string)($existing['created_by'] ?? '');
            if ($createdBy !== (string)($currentUser['id'] ?? '')) {
                Response::error('Forbidden', 403, 'FORBIDDEN');
                return;
            }
        }

        $ok = $model->deleteById($id);
        if (!$ok) {
            Response::error('Question not found', 404, 'QUESTION_BANK_NOT_FOUND');
            return;
        }

        Response::success(['id' => $id], 'Question deleted successfully');
    }
}