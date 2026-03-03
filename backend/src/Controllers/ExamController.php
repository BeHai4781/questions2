<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ExamModel;
use App\Models\QuestionModel;
use App\Utils\Response;

final class ExamController
{
    public static function getExams(array $query, array $currentUser): void
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
            'type' => $query['type'] ?? null,
            'status' => $query['status'] ?? null,
        ];

        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'teacher') {
            $filter['createdBy'] = (string)($currentUser['id'] ?? '');
        } elseif (isset($query['createdBy'])) {
            $filter['createdBy'] = $query['createdBy'];
        }

        $model = new ExamModel();
        $total = $model->countDocuments($filter);
        $totalPages = (int)ceil($total / $limitNum) ?: 1;
        $items = $model->find($filter, $skip, $limitNum);

        Response::paginated($items, [
            'page' => $pageNum,
            'limit' => $limitNum,
            'total' => $total,
            'totalPages' => $totalPages,
        ], 'Exams retrieved successfully');
    }

    public static function getExamById(string $id, array $currentUser): void
    {
        $model = new ExamModel();
        $exam = $model->findById($id);
        if (!$exam) {
            Response::error('Exam not found', 404, 'EXAM_NOT_FOUND');
            return;
        }
        $model2 = new QuestionModel();
        $questions = $model2->find(['examId' => $id], 0, 100);
        Response::success(['exam' => $exam, 'questions' => $questions], 'Exam retrieved successfully');
    }

    public static function createExam(array $body, array $currentUser): void
    {
        $payload = $body;
        if (!isset($payload['createdBy']) && isset($currentUser['id'])) {
            $payload['createdBy'] = (string)$currentUser['id'];
        }

        $model = new ExamModel();
        $created = $model->create($payload);
        if (!$created) {
            Response::error('Unable to create exam', 400, 'EXAM_CREATE_FAILED');
            return;
        }
        Response::success(['exam' => $created], 'Exam created successfully', 201);
    }

    public static function updateExam(string $id, array $body, array $currentUser): void
    {
        $model = new ExamModel();
        $existing = $model->findById($id);
        if (!$existing) {
            Response::error('Exam not found', 404, 'EXAM_NOT_FOUND');
            return;
        }

        $payload = $body;
        if (!isset($payload['updatedBy']) && isset($currentUser['id'])) {
            $payload['updatedBy'] = (string)$currentUser['id'];
        }

        $updated = $model->updateById($id, $payload);
        Response::success(['exam' => $updated], 'Exam updated successfully');
    }

    public static function deleteExam(string $id, array $currentUser): void
    {
        $model = new ExamModel();
        $existing = $model->findById($id);
        if (!$existing) {
            Response::error('Exam not found', 404, 'EXAM_NOT_FOUND');
            return;
        }

        $ok = $model->deleteById($id);
        if (!$ok) {
            Response::error('Exam not found', 404, 'EXAM_NOT_FOUND');
            return;
        }
        Response::success(['id' => $id], 'Exam deleted successfully');
    }
}
