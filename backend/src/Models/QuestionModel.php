<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

final class QuestionModel
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

    public function findById(string $id): ?array
    {
        if (!ctype_digit($id)) return null;

        $st = $this->pdo->prepare('SELECT * FROM questions WHERE id = :id LIMIT 1');
        $st->execute([':id' => (int)$id]);
        $row = $st->fetch();
        if (!$row) return null;

        return $this->toJson($row);
    }

    public function countDocuments(array $filter): int
    {
        [$whereSql, $params] = $this->buildListWhere($filter);
        $sql = 'SELECT COUNT(*) FROM questions ' . ($whereSql ? "WHERE {$whereSql}" : '');
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /**
     * Lấy danh sách đáp án theo các question_id (schema questions3: bảng answers).
     * @return array<string, array<int, array{id: string, content: string, order_index: int|null}>>
     */
    public function getAnswersForQuestionIds(array $questionIds): array
    {
        if ($questionIds === []) {
            return [];
        }
        $ids = array_map('intval', array_filter($questionIds, 'ctype_digit'));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, question_id, content, order_index FROM answers WHERE question_id IN ({$placeholders}) ORDER BY question_id, order_index ASC NULLS LAST";
        $st = $this->pdo->prepare($sql);
        $st->execute(array_values($ids));
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $row) {
            $qid = (string)$row['question_id'];
            if (!isset($out[$qid])) {
                $out[$qid] = [];
            }
            $out[$qid][] = [
                'id' => (string)$row['id'],
                'content' => (string)($row['content'] ?? ''),
                'order_index' => isset($row['order_index']) ? (int)$row['order_index'] : null,
            ];
        }
        return $out;
    }

    /**
     * Load đáp án của 1 câu hỏi, bao gồm is_correct.
     * Dùng nội bộ trong toJson() để embed answers vào mọi response.
     *
     * @return array<int, array{id:string, content:string, is_correct:bool, order_index:int}>
     */
    private function findAnswers(int $questionId): array
    {
        $st = $this->pdo->prepare(
            'SELECT id, content, is_correct, order_index
               FROM answers
              WHERE question_id = :qid
              ORDER BY order_index ASC NULLS LAST, id ASC'
        );
        $st->execute([':qid' => $questionId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(static function (array $a): array {
            return [
                'id'          => (string)$a['id'],
                'content'     => (string)($a['content'] ?? ''),
                'is_correct'  => (bool)$a['is_correct'],
                'order_index' => (int)($a['order_index'] ?? 0),
            ];
        }, $rows);
    }

    /** @return array<int,array<string,mixed>> */
    public function find(array $filter, int $skip, int $limit): array
    {
        [$whereSql, $params] = $this->buildListWhere($filter);
        $orderBy = 'id DESC';
        if (isset($filter['examId']) && $this->hasColumn('order_index')) {
            $orderBy = 'order_index ASC NULLS LAST';
        } elseif ($this->hasColumn('created_at')) {
            $orderBy = 'created_at DESC';
        }

        $sql = 'SELECT * FROM questions '
            . ($whereSql ? "WHERE {$whereSql} " : '')
            . "ORDER BY {$orderBy} OFFSET :skip LIMIT :limit";

        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':skip', $skip, PDO::PARAM_INT);
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll() ?: [];

        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->toJson($row);
        }
        return $out;
    }

    /** @return array<string,mixed> */
    public function create(array $data): array
    {
        $map = [
            'classId' => 'class_id',
            'class' => 'class_id',
            'subjectId' => 'subject_id',
            'levelId' => 'level_id',
            'difficulty' => 'level_id',
            'answerA' => 'answer_a',
            'answerB' => 'answer_b',
            'answerC' => 'answer_c',
            'answerD' => 'answer_d',
            'correctAnswer' => 'correct_answer',
            'createdBy' => 'created_by',
        ];

        $payload = $this->filterData($data, $map);

        if ($this->hasColumn('created_at') && !array_key_exists('created_at', $payload)) {
            $payload['created_at'] = date('c');
        }
        if ($this->hasColumn('updated_at') && !array_key_exists('updated_at', $payload)) {
            $payload['updated_at'] = date('c');
        }

        if (!$payload) {
            return [];
        }

        $cols = array_keys($payload);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = 'INSERT INTO questions (' . implode(', ', $cols) . ')
                VALUES (' . implode(', ', $placeholders) . ')
                RETURNING id';
        $st = $this->pdo->prepare($sql);
        foreach ($payload as $k => $v) {
            $st->bindValue(':' . $k, $v);
        }
        $st->execute();
        $newId = (string)$st->fetchColumn();
        $created = $this->findById($newId);
        return $created ?? ['id' => $newId] + $payload;
    }

    /** @return array<string,mixed>|null */
    public function updateById(string $id, array $updates): ?array
    {
        if (!ctype_digit($id)) return null;

        $map = [
            'classId' => 'class_id',
            'class' => 'class_id',
            'subjectId' => 'subject_id',
            'levelId' => 'level_id',
            'difficulty' => 'level_id',
            'answerA' => 'answer_a',
            'answerB' => 'answer_b',
            'answerC' => 'answer_c',
            'answerD' => 'answer_d',
            'correctAnswer' => 'correct_answer',
            'createdBy' => 'created_by',
        ];

        $payload = $this->filterData($updates, $map);

        if ($this->hasColumn('updated_at') && !array_key_exists('updated_at', $payload)) {
            $payload['updated_at'] = date('c');
        }

        if (!$payload) {
            return $this->findById($id);
        }

        $set = [];
        foreach ($payload as $col => $val) {
            $set[] = "{$col} = :{$col}";
        }

        $sql = 'UPDATE questions SET ' . implode(', ', $set) . ' WHERE id = :id';
        $st = $this->pdo->prepare($sql);
        $st->bindValue(':id', (int)$id, PDO::PARAM_INT);
        foreach ($payload as $k => $v) {
            $st->bindValue(':' . $k, $v);
        }
        $st->execute();

        return $this->findById($id);
    }

    public function deleteById(string $id): bool
    {
        if (!ctype_digit($id)) return false;
        $st = $this->pdo->prepare('DELETE FROM questions WHERE id = :id');
        $st->execute([':id' => (int)$id]);
        return $st->rowCount() > 0;
    }

    /** @param array<string,mixed> $row */
    private function toJson(array $row): array
    {
        $this->loadColumns();
        $row = $this->decodeJsonColumns($row);
        if (isset($row['created_at']) && $row['created_at'] !== null) {
            $row['createdAt'] = is_string($row['created_at']) ? $row['created_at'] : (string)$row['created_at'];
        }
        if (isset($row['updated_at']) && $row['updated_at'] !== null) {
            $row['updatedAt'] = is_string($row['updated_at']) ? $row['updated_at'] : (string)$row['updated_at'];
        }
        if (isset($row['id'])) {
            $row['id'] = (string)$row['id'];
        }
        foreach ($row as $k => $v) {
            if (str_ends_with((string)$k, '_id') && $v !== null) {
                $row[$k] = (string)$v;
            }
        }
        if (array_key_exists('content', $row) && !array_key_exists('question', $row)) {
            $row['question'] = $row['content'];
        }
        // Embed answers để frontend không cần gọi thêm API
        if (isset($row['id']) && !array_key_exists('answers', $row)) {
            $row['answers'] = $this->findAnswers((int)$row['id']);
        }
        return $row;
    }

    private function loadColumns(): void
    {
        if ($this->columns !== null && $this->columnTypes !== null) return;

        $st = $this->pdo->prepare("SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :t");
        $st->execute([':t' => 'questions']);
        $rows = $st->fetchAll() ?: [];

        $this->columns = [];
        $this->columnTypes = [];
        foreach ($rows as $row) {
            $name = (string)($row['column_name'] ?? '');
            if ($name === '') continue;
            $this->columns[] = $name;
            $this->columnTypes[$name] = (string)($row['data_type'] ?? '');
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
            $out[$col] = $this->normalizeValue((string)$col, $value);
        }

        return $out;
    }

    private function normalizeValue(string $col, mixed $value): mixed
    {
        $type = $this->columnTypes[$col] ?? '';
        if ((is_array($value) || is_object($value)) && in_array($type, ['json', 'jsonb'], true)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if ($type === 'boolean' && is_string($value)) {
            if ($value === 'true') return true;
            if ($value === 'false') return false;
        }
        return $value;
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function buildListWhere(array $filter): array
    {
        $this->loadColumns();
        $where = [];
        $params = [];

        if (isset($filter['search']) && is_string($filter['search']) && trim($filter['search']) !== '') {
            $q = trim($filter['search']);
            $searchCols = array_values(array_filter(
                ['question'],
                fn($c) => $this->hasColumn($c)
            ));
            if ($searchCols) {
                $parts = [];
                foreach ($searchCols as $i => $col) {
                    $ph = ':q' . $i;
                    $parts[] = "{$col} ILIKE {$ph}";
                    $params[$ph] = '%' . $q . '%';
                }
                $where[] = '(' . implode(' OR ', $parts) . ')';
            }
        }

        $map = [
            'class' => 'class_id',
            'classId' => 'class_id',
            'subjectId' => 'subject_id',
            'difficulty' => 'level_id',
            'levelId' => 'level_id',
            'createdBy' => 'created_by',
            'examId' => 'exam_id',
        ];
        foreach ($map as $filterKey => $col) {
            if (!array_key_exists($filterKey, $filter)) continue;
            $val = $filter[$filterKey];
            if ($val === null || $val === '') continue;
            if (!$this->hasColumn($col)) continue;
            $ph = ':' . $col;
            $where[] = "{$col} = {$ph}";
            $params[$ph] = $val;
        }

        return [implode(' AND ', $where), $params];
    }

    /** @param array<string,mixed> $row */
    private function decodeJsonColumns(array $row): array
    {
        foreach ($row as $k => $v) {
            $type = $this->columnTypes[$k] ?? '';
            if (in_array($type, ['json', 'jsonb'], true) && is_string($v)) {
                $decoded = json_decode($v, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row[$k] = $decoded;
                }
            }
        }
        return $row;
    }
}