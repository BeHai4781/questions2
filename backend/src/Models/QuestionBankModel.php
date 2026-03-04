<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

final class QuestionBankModel
{
    private PDO $pdo;
    /** @var array<int,string>|null */
    private ?array $columns = null;
    /** @var array<string,string>|null */
    private ?array $columnTypes = null;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }
    /**
     * Lấy một câu hỏi theo id, kèm danh sách đáp án từ bank_answers.
     */
    public function findById(string $id): ?array
    {
        if (!ctype_digit($id)) return null;

        $st = $this->pdo->prepare('SELECT * FROM question_bank WHERE id = :id LIMIT 1');
        $st->execute([':id' => (int)$id]);
        $row = $st->fetch();
        if (!$row) return null;

        $question = $this->toJson($row);
        $question['answers'] = $this->findAnswers((int)$id);
        return $question;
    }

    /**
     * Đếm số câu hỏi theo filter.
     */
    public function countDocuments(array $filter): int
    {
        [$whereSql, $params] = $this->buildListWhere($filter);
        $sql = 'SELECT COUNT(*) FROM question_bank'
            . ($whereSql ? " WHERE {$whereSql}" : '');
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /**
     * Danh sách câu hỏi có phân trang, kèm answers.
     * @return array<int,array<string,mixed>>
     */
    public function find(array $filter, int $skip, int $limit): array
    {
        [$whereSql, $params] = $this->buildListWhere($filter);

        $sql = 'SELECT * FROM question_bank'
            . ($whereSql ? " WHERE {$whereSql}" : '')
            . ' ORDER BY created_at DESC OFFSET :skip LIMIT :limit';

        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':skip',  $skip,  PDO::PARAM_INT);
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll() ?: [];

        $out = [];
        foreach ($rows as $row) {
            $q = $this->toJson($row);
            $q['answers'] = $this->findAnswers((int)$q['id']);
            $out[] = $q;
        }
        return $out;
    }

    /**
     * Tạo câu hỏi mới trong ngân hàng, cùng với các đáp án (bank_answers).
     *
     * @param array{
     *   content: string,
     *   class_id?: int,
     *   classId?: int,
     *   level_id?: int,
     *   levelId?: int,
     *   difficulty?: int,
     *   image?: string|null,
     *   createdBy?: int,
     *   answers?: array<int,array{content:string,is_correct:bool,order_index?:int}>
     * } $data
     * @return array<string,mixed>
     */
    public function create(array $data): array
    {
        $map = [
            'classId'   => 'class_id',
            'levelId'   => 'level_id',
            'difficulty'=> 'level_id',
            'createdBy' => 'created_by',
            'content'   => 'content',
            'image'     => 'image',
        ];

        $payload = $this->filterData($data, $map);

        // Gán timestamps
        $now = date('Y-m-d H:i:s');
        if (!array_key_exists('created_at', $payload)) $payload['created_at'] = $now;
        if (!array_key_exists('updated_at', $payload)) $payload['updated_at'] = $now;

        if (!$payload) return [];

        $cols         = array_keys($payload);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);

        $sql = 'INSERT INTO question_bank (' . implode(', ', $cols) . ')'
             . ' VALUES (' . implode(', ', $placeholders) . ')'
             . ' RETURNING id';

        $st = $this->pdo->prepare($sql);
        foreach ($payload as $k => $v) {
            $st->bindValue(':' . $k, $v);
        }
        $st->execute();
        $newId = (int)$st->fetchColumn();

        // Lưu đáp án vào bank_answers
        if (!empty($data['answers']) && is_array($data['answers'])) {
            $this->insertAnswers($newId, $data['answers']);
        }

        return $this->findById((string)$newId) ?? ['id' => (string)$newId];
    }

    /**
     * Cập nhật câu hỏi và (tuỳ chọn) thay toàn bộ danh sách đáp án.
     * @return array<string,mixed>|null
     */
    public function updateById(string $id, array $updates): ?array
    {
        if (!ctype_digit($id)) return null;

        $map = [
            'classId'   => 'class_id',
            'levelId'   => 'level_id',
            'difficulty'=> 'level_id',
            'createdBy' => 'created_by',
            'content'   => 'content',
            'image'     => 'image',
        ];

        $payload = $this->filterData($updates, $map);
        $payload['updated_at'] = date('Y-m-d H:i:s');

        if (count($payload) > 1) {   // > 1 vì updated_at luôn có
            $set = [];
            foreach ($payload as $col => $val) {
                $set[] = "{$col} = :{$col}";
            }
            $sql = 'UPDATE question_bank SET ' . implode(', ', $set) . ' WHERE id = :id';
            $st  = $this->pdo->prepare($sql);
            $st->bindValue(':id', (int)$id, PDO::PARAM_INT);
            foreach ($payload as $k => $v) {
                $st->bindValue(':' . $k, $v);
            }
            $st->execute();
        }

        // Nếu gửi kèm answers → xóa cũ, chèn mới
        if (isset($updates['answers']) && is_array($updates['answers'])) {
            $this->deleteAnswers((int)$id);
            $this->insertAnswers((int)$id, $updates['answers']);
        }

        return $this->findById($id);
    }

    /**
     * Xóa câu hỏi (bank_answers tự xóa theo CASCADE).
     */
    public function deleteById(string $id): bool
    {
        if (!ctype_digit($id)) return false;
        $st = $this->pdo->prepare('DELETE FROM question_bank WHERE id = :id');
        $st->execute([':id' => (int)$id]);
        return $st->rowCount() > 0;
    }

    /** @return array<int,array<string,mixed>> */
    private function findAnswers(int $questionId): array
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM bank_answers WHERE bank_ques_id = :qid ORDER BY order_index ASC'
        );
        $st->execute([':qid' => $questionId]);
        $rows = $st->fetchAll() ?: [];
        return array_map(function (array $row): array {
            $row['id']           = (string)$row['id'];
            $row['bank_ques_id'] = (string)$row['bank_ques_id'];
            $row['is_correct']   = (bool)$row['is_correct'];
            return $row;
        }, $rows);
    }

    /** @param array<int,array{content:string,is_correct:bool,order_index?:int}> $answers */
    private function insertAnswers(int $questionId, array $answers): void
    {
        $sql = 'INSERT INTO bank_answers (bank_ques_id, content, is_correct, order_index)
                VALUES (:qid, :content, :is_correct, :order_index)';
        $st  = $this->pdo->prepare($sql);

        foreach ($answers as $idx => $ans) {
            $st->execute([
                ':qid'         => $questionId,
                ':content'     => (string)($ans['content'] ?? ''),
                ':is_correct'  => !empty($ans['is_correct']) ? 1 : 0,
                ':order_index' => (int)($ans['order_index'] ?? $idx + 1),
            ]);
        }
    }

    private function deleteAnswers(int $questionId): void
    {
        $st = $this->pdo->prepare('DELETE FROM bank_answers WHERE bank_ques_id = :qid');
        $st->execute([':qid' => $questionId]);
    }

    /** @param array<string,mixed> $row */
    private function toJson(array $row): array
    {
        if (isset($row['id']))         $row['id']         = (string)$row['id'];
        if (isset($row['created_by'])) $row['created_by'] = (string)$row['created_by'];
        if (isset($row['class_id']))   $row['class_id']   = (string)$row['class_id'];
        if (isset($row['level_id']))   $row['level_id']   = (string)$row['level_id'];

        // Camel-case aliases cho frontend
        if (isset($row['created_at'])) $row['createdAt'] = $row['created_at'];
        if (isset($row['updated_at'])) $row['updatedAt'] = $row['updated_at'];
        if (isset($row['created_by'])) $row['createdBy'] = $row['created_by'];
        if (isset($row['class_id']))   $row['classId']   = $row['class_id'];
        if (isset($row['level_id']))   $row['levelId']   = $row['level_id'];

        return $row;
    }

    private function loadColumns(): void
    {
        if ($this->columns !== null) return;

        $st = $this->pdo->prepare(
            "SELECT column_name, data_type
               FROM information_schema.columns
              WHERE table_schema = 'public' AND table_name = 'question_bank'"
        );
        $st->execute();
        $rows = $st->fetchAll() ?: [];

        $this->columns     = [];
        $this->columnTypes = [];
        foreach ($rows as $row) {
            $name = (string)($row['column_name'] ?? '');
            if ($name === '') continue;
            $this->columns[]            = $name;
            $this->columnTypes[$name]   = (string)($row['data_type'] ?? '');
        }
    }

    private function hasColumn(string $name): bool
    {
        $this->loadColumns();
        return in_array($name, $this->columns ?? [], true);
    }

    /** @param array<string,mixed> $data */
    private function filterData(array $data, array $map): array
    {
        $this->loadColumns();
        $out = [];
        foreach ($data as $key => $value) {
            $col = $map[$key] ?? $key;
            if (!$this->hasColumn((string)$col)) continue;
            $out[$col] = $value;
        }
        return $out;
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function buildListWhere(array $filter): array
    {
        $this->loadColumns();
        $where  = [];
        $params = [];

        // Tìm kiếm full-text trong cột content
        if (isset($filter['search']) && trim((string)$filter['search']) !== '') {
            $q             = trim((string)$filter['search']);
            $where[]       = 'content ILIKE :search';
            $params[':search'] = '%' . $q . '%';
        }

        // Filter theo created_by (teacher chỉ thấy câu hỏi của mình)
        if (!empty($filter['createdBy']) && $this->hasColumn('created_by')) {
            $where[]               = 'created_by = :created_by';
            $params[':created_by'] = $filter['createdBy'];
        }

        // Filter theo lớp
        if (!empty($filter['classId']) && $this->hasColumn('class_id')) {
            $where[]             = 'class_id = :class_id';
            $params[':class_id'] = $filter['classId'];
        }

        // Filter theo mức độ
        if (!empty($filter['levelId']) && $this->hasColumn('level_id')) {
            $where[]             = 'level_id = :level_id';
            $params[':level_id'] = $filter['levelId'];
        }

        return [implode(' AND ', $where), $params];
    }
}