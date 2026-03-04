<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;


final class ExamAttemptModel
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

        // Schema mới (questions3.sql): sử dụng bảng exam_results
        $st = $this->pdo->prepare('SELECT * FROM exam_results WHERE id = :id LIMIT 1');
        $st->execute([':id' => (int)$id]);
        $row = $st->fetch();
        if (!$row) return null;

        return $this->toJson($row);
    }

    public function countDocuments(array $filter): int
    {
        [$whereSql, $params] = $this->buildListWhere($filter);
        $sql = 'SELECT COUNT(*) FROM exam_results ' . ($whereSql ? "WHERE {$whereSql}" : '');
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function find(array $filter, int $skip, int $limit): array
    {
        [$whereSql, $params] = $this->buildListWhere($filter);
        // exam_results có submit_time, duration_mins...
        $orderBy = $this->hasColumn('submit_time') ? 'submit_time DESC' : 'id DESC';

        $sql = 'SELECT * FROM exam_results '
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
            'studentId' => 'student_id',
            'score' => 'total_score',
            'result' => 'total_score',
            'durationMins' => 'duration_mins',
            'duration' => 'duration_mins',
            'time' => 'duration_mins',
            'submittedAt' => 'submit_time',
            'submitTime' => 'submit_time',
            'startTime' => 'start_time',
        ];

        $payload = $this->filterData($data, $map);

        if (!$payload) {
            return [];
        }

        $cols = array_keys($payload);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = 'INSERT INTO exam_results (' . implode(', ', $cols) . ')
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
            'studentId' => 'student_id',
            'score' => 'total_score',
            'result' => 'total_score',
            'durationMins' => 'duration_mins',
            'duration' => 'duration_mins',
            'time' => 'duration_mins',
            'submittedAt' => 'submit_time',
            'submitTime' => 'submit_time',
            'startTime' => 'start_time',
        ];

        $payload = $this->filterData($updates, $map);

        if (!$payload) {
            return $this->findById($id);
        }

        $set = [];
        foreach ($payload as $col => $val) {
            $set[] = "{$col} = :{$col}";
        }

        $sql = 'UPDATE exam_results SET ' . implode(', ', $set) . ' WHERE id = :id';
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
        $st = $this->pdo->prepare('DELETE FROM exam_results WHERE id = :id');
        $st->execute([':id' => (int)$id]);
        return $st->rowCount() > 0;
    }

    /** @param array<string,mixed> $row */
    private function toJson(array $row): array
    {
        $this->loadColumns();
        $row = $this->decodeJsonColumns($row);
        // Schema cũ: submitted_at; schema mới: submit_time
        if (isset($row['submitted_at']) && $row['submitted_at'] !== null) {
            $row['submittedAt'] = is_string($row['submitted_at']) ? $row['submitted_at'] : (string)$row['submitted_at'];
        } elseif (isset($row['submit_time']) && $row['submit_time'] !== null) {
            $row['submitted_at'] = is_string($row['submit_time']) ? $row['submit_time'] : (string)$row['submit_time'];
            $row['submittedAt'] = $row['submitted_at'];
        }
        if (isset($row['duration_mins'])) {
            $row['durationMins'] = $row['duration_mins'];
        }
        if (isset($row['total_score'])) {
            $row['result'] = $row['total_score'];
            $row['score'] = $row['total_score'];
        }
        if (isset($row['start_time']) && $row['start_time'] !== null) {
            $row['startTime'] = is_string($row['start_time']) ? $row['start_time'] : (string)$row['start_time'];
        }
        if (isset($row['id'])) {
            $row['id'] = (string)$row['id'];
        }
        foreach ($row as $k => $v) {
            if (str_ends_with((string)$k, '_id') && $v !== null) {
                $row[$k] = (string)$v;
            }
        }
        // Alias để controller/FE cũ dùng được với schema mới
        if (isset($row['student_id']) && !isset($row['user_id'])) {
            $row['user_id'] = $row['student_id'];
        }
        return $row;
    }

    private function loadColumns(): void
    {
        if ($this->columns !== null && $this->columnTypes !== null) return;

        $st = $this->pdo->prepare("SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :t");
        $st->execute([':t' => 'exam_results']);
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
            // userId từ FE map sang student_id trong exam_results
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
