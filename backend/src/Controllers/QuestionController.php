<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\QuestionModel;
use App\Config\Database;
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
            'search'     => (string)$search,
            'class'      => $query['class']      ?? null,
            'difficulty' => $query['difficulty'] ?? null,
            'type'       => $query['type']       ?? null,
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
            'page'       => $pageNum,
            'limit'      => $limitNum,
            'total'      => $total,
            'totalPages' => $totalPages,
        ], 'Questions retrieved successfully');
    }

    public static function getQuestionById(string $id, array $currentUser): void
    {
        $model    = new QuestionModel();
        $question = $model->findById($id);
        if (!$question) {
            Response::error('Question not found', 404, 'QUESTION_NOT_FOUND');
            return;
        }
        Response::success(['question' => $question], 'Question retrieved successfully');
    }

    /**
     * POST /api/questions
     * Body: { exam_id, content, image, score, order_index,
     *         answers: [{content, is_correct, order_index}, ...] }
     *
     * Lưu câu hỏi + insert tất cả answers vào bảng answers.
     */
    public static function createQuestion(array $body, array $currentUser): void
    {
        $payload = $body;
        if (!isset($payload['createdBy']) && isset($currentUser['id'])) {
            $payload['createdBy'] = (string)$currentUser['id'];
        }

        // Tách answers ra trước khi gọi model (model không biết cột answers)
        $answersRaw = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
        unset($payload['answers']);

        $model   = new QuestionModel();
        $created = $model->create($payload);
        if (!$created) {
            Response::error('Unable to create question', 400, 'QUESTION_CREATE_FAILED');
            return;
        }

        $questionId = (int)$created['id'];

        // Insert answers vào bảng answers
        if ($answersRaw && $questionId > 0) {
            self::insertAnswers($questionId, $answersRaw);
            // Reload để trả về kèm answers
            $created = $model->findById((string)$questionId) ?? $created;
        }

        Response::success(['question' => $created], 'Question created successfully', 201);
    }

    /**
     * PUT /api/questions/:id
     * Body: { content, image, answers: [{id?, content, is_correct, order_index}, ...] }
     *
     * Cập nhật câu hỏi + xoá-tạo lại answers.
     */
    public static function updateQuestion(string $id, array $body, array $currentUser): void
    {
        $model    = new QuestionModel();
        $existing = $model->findById($id);
        if (!$existing) {
            Response::error('Question not found', 404, 'QUESTION_NOT_FOUND');
            return;
        }

        $payload = $body;
        if (!isset($payload['updatedBy']) && isset($currentUser['id'])) {
            $payload['updatedBy'] = (string)$currentUser['id'];
        }

        // Tách answers
        $answersRaw = is_array($payload['answers'] ?? null) ? $payload['answers'] : null;
        unset($payload['answers']);

        $updated = $model->updateById($id, $payload);

        // Nếu frontend gửi answers → xoá cũ, insert mới
        if ($answersRaw !== null && (int)$id > 0) {
            $pdo = Database::pdo();
            $pdo->prepare('DELETE FROM answers WHERE question_id = :qid')
                ->execute([':qid' => (int)$id]);
            self::insertAnswers((int)$id, $answersRaw);
            // Reload để trả về kèm answers mới
            $updated = $model->findById($id) ?? $updated;
        }

        Response::success(['question' => $updated], 'Question updated successfully');
    }

    public static function deleteQuestion(string $id, array $currentUser): void
    {
        $model    = new QuestionModel();
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

    // Private helper 

    /**
     * Insert một mảng answers cho 1 question_id.
     * @param array<int,array{content:string,is_correct:bool,order_index?:int}> $answers
     */
    private static function insertAnswers(int $questionId, array $answers): void
    {
        if (!$answers) return;

        $pdo = Database::pdo();
        $sql = 'INSERT INTO answers (question_id, content, is_correct, order_index)
                VALUES (:qid, :content, :is_correct, :order_index)';
        $st  = $pdo->prepare($sql);

        foreach ($answers as $i => $ans) {
            $content    = trim((string)($ans['content'] ?? ''));
            $isCorrect  = !empty($ans['is_correct']);
            $orderIndex = isset($ans['order_index']) ? (int)$ans['order_index'] : ($i + 1);

            if ($content === '') continue; // bỏ qua đáp án rỗng

            $st->execute([
                ':qid'         => $questionId,
                ':content'     => $content,
                ':is_correct'  => $isCorrect ? 1 : 0,
                ':order_index' => $orderIndex,
            ]);
        }
    }
}