<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;


final class ExamAttemptModel
{
    private const TABLE = 'exam_results';

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

        $st = $this->pdo->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = :id LIMIT 1');
        $st->execute([':id' => (int)$id]);
        $row = $st->fetch();
        if (!$row) return null;

        return $this->toJson($row);
    }

    public function countDocuments(array $filter): int
    {
        [$whereSql, $params] = $this->buildListWhere($filter);
        $sql = 'SELECT COUNT(*) FROM ' . self::TABLE . ' ' . ($whereSql ? "WHERE {$whereSql}" : '');
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function find(array $filter, int $skip, int $limit): array
    {
        [$whereSql, $params] = $this->buildListWhere($filter);
        $orderBy = $this->hasColumn('submit_time') ? 'submit_time DESC' : 'id DESC';

        $sql = 'SELECT * FROM ' . self::TABLE . ' '
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
            'examId' => 'exam_id',
            'userId' => 'student_id',
            'result' => 'total_score',
            'score' => 'total_score',
            'totalScore' => 'total_score',
            'totalQuestions' => 'total_questions',
            'correctCount' => 'correct_count',
            'durationMins' => 'duration_mins',
        ];

        $payload = $this->filterData($data, $map);

        if (!isset($payload['exam_id']) || !isset($payload['student_id'])) {
            return [];
        }

        if ($this->hasColumn('submit_time') && !isset($payload['submit_time'])) {
            $payload['submit_time'] = date('Y-m-d H:i:s');
        }
        if ($this->hasColumn('total_score') && !isset($payload['total_score'])) {
            $payload['total_score'] = (float)($data['score'] ?? $data['result'] ?? 0);
        }

        $cols = array_keys($payload);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = 'INSERT INTO ' . self::TABLE . ' (' . implode(', ', $cols) . ')
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
            'examId' => 'exam_id',
            'userId' => 'student_id',
            'result' => 'total_score',
            'score' => 'total_score',
            'totalScore' => 'total_score',
            'totalQuestions' => 'total_questions',
            'correctCount' => 'correct_count',
            'durationMins' => 'duration_mins',
            'submitTime' => 'submit_time',
        ];

        $payload = $this->filterData($updates, $map);

        if (!$payload) {
            return $this->findById($id);
        }

        $set = [];
        foreach ($payload as $col => $val) {
            $set[] = "{$col} = :{$col}";
        }

        $sql = 'UPDATE ' . self::TABLE . ' SET ' . implode(', ', $set) . ' WHERE id = :id';
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
        $st = $this->pdo->prepare('DELETE FROM ' . self::TABLE . ' WHERE id = :id');
        $st->execute([':id' => (int)$id]);
        return $st->rowCount() > 0;
    }

    /** @param array<string,mixed> $row */
    private function toJson(array $row): array
    {
        $this->loadColumns();
        $row = $this->decodeJsonColumns($row);
        if (isset($row['submit_time']) && $row['submit_time'] !== null) {
            $row['submittedAt'] = is_string($row['submit_time']) ? $row['submit_time'] : (string)$row['submit_time'];
        }
        if (isset($row['student_id']) && $row['student_id'] !== null) {
            $row['user_id'] = (string)$row['student_id'];
        }
        if (isset($row['total_score']) && $row['total_score'] !== null) {
            $row['result'] = (float)$row['total_score'];
        }
        if (isset($row['id'])) {
            $row['id'] = (string)$row['id'];
        }
        foreach ($row as $k => $v) {
            if (str_ends_with((string)$k, '_id') && $v !== null) {
                $row[$k] = (string)$v;
            }
        }
        return $row;
    }

    private function loadColumns(): void
    {
        if ($this->columns !== null && $this->columnTypes !== null) return;

        $st = $this->pdo->prepare("SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :t");
        $st->execute([':t' => self::TABLE]);
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

        $map = [
            'examId' => 'exam_id',
            'userId' => 'student_id',
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
