<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;


final class ExamModel
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

    /** SQL dùng khi list/get exam: JOIN classes, types và đếm số câu (schema questions3) */
    private function getListSelectSql(): string
    {
        return 'SELECT e.*, c.name AS class_name, t.name AS type_name, '
            . '(SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) AS total_questions '
            . 'FROM exams e '
            . 'LEFT JOIN classes c ON e.class_id = c.id '
            . 'LEFT JOIN types t ON e.type_id = t.id ';
    }

    public function findById(string $id): ?array
    {
        if (!ctype_digit($id)) return null;

        $sql = $this->getListSelectSql() . 'WHERE e.id = :id LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => (int)$id]);
        $row = $st->fetch();
        if (!$row) return null;

        return $this->toJson($row);
    }

    public function countDocuments(array $filter): int
    {
        [$whereSql, $params] = $this->buildListWhere($filter, 'e');
        $sql = 'SELECT COUNT(*) FROM exams e '
            . 'LEFT JOIN classes c ON e.class_id = c.id '
            . 'LEFT JOIN types t ON e.type_id = t.id '
            . ($whereSql ? "WHERE {$whereSql}" : '');
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function find(array $filter, int $skip, int $limit): array
    {
        [$whereSql, $params] = $this->buildListWhere($filter, 'e');
        $orderBy = $this->hasColumn('created_at') ? 'e.created_at DESC' : 'e.id DESC';

        $sql = $this->getListSelectSql()
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
            'createdBy' => 'created_by',
            'classId' => 'class_id',
            'typeId' => 'type_id',
            'totalQuestions' => 'total_questions',
            'duration' => 'duration',
            'nb' => 'nb',
            'vd' => 'vd',
            'vdc' => 'vdc',
            'views' => 'views',
        ];

        $payload = $this->filterData($data, $map);

        if ($this->hasColumn('created_at') && !array_key_exists('created_at', $payload)) {
            $payload['created_at'] = date('c');
        }

        if (!$payload) {
            return [];
        }

        $cols = array_keys($payload);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = 'INSERT INTO exams (' . implode(', ', $cols) . ')
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
            'createdBy' => 'created_by',
            'classId' => 'class_id',
            'typeId' => 'type_id',
            'totalQuestions' => 'total_questions',
            'duration' => 'duration',
            'nb' => 'nb',
            'vd' => 'vd',
            'vdc' => 'vdc',
            'views' => 'views',
            'title' => 'title',
        ];

        $payload = $this->filterData($updates, $map);

        if (!$payload) {
            return $this->findById($id);
        }

        $set = [];
        foreach ($payload as $col => $val) {
            $set[] = "{$col} = :{$col}";
        }

        $sql = 'UPDATE exams SET ' . implode(', ', $set) . ' WHERE id = :id';
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
        $st = $this->pdo->prepare('DELETE FROM exams WHERE id = :id');
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
        if (isset($row['id'])) {
            $row['id'] = (string)$row['id'];
        }
        foreach ($row as $k => $v) {
            if (str_ends_with((string)$k, '_id') && $v !== null) {
                $row[$k] = (string)$v;
            }
        }
        // Schema questions3: map tên lớp/loại và số câu cho frontend
        if (array_key_exists('class_name', $row)) {
            $row['class'] = $row['className'] = $row['class_name'];
        }
        if (array_key_exists('type_name', $row)) {
            $row['type'] = $row['typeName'] = $row['type_name'];
        }
        if (array_key_exists('total_questions', $row)) {
            $row['total_questions'] = (int)$row['total_questions'];
            $row['totalQuestions'] = $row['total_questions'];
        }
        return $row;
    }

    private function loadColumns(): void
    {
        if ($this->columns !== null && $this->columnTypes !== null) return;

        $st = $this->pdo->prepare("SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :t");
        $st->execute([':t' => 'exams']);
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
    private function buildListWhere(array $filter, string $tableAlias = ''): array
    {
        $this->loadColumns();
        $where = [];
        $params = [];
        $pre = $tableAlias !== '' ? $tableAlias . '.' : '';

        if (isset($filter['search']) && is_string($filter['search']) && trim($filter['search']) !== '') {
            $q = trim($filter['search']);
            $searchCols = array_values(array_filter(
                ['title'],
                fn($c) => $this->hasColumn($c)
            ));
            if ($searchCols) {
                $parts = [];
                foreach ($searchCols as $i => $col) {
                    $ph = ':q' . $i;
                    $parts[] = "{$pre}{$col} ILIKE {$ph}";
                    $params[$ph] = '%' . $q . '%';
                }
                $where[] = '(' . implode(' OR ', $parts) . ')';
            }
        }

        $map = [
            'createdBy' => 'created_by',
        ];
        foreach ($map as $filterKey => $col) {
            if (!array_key_exists($filterKey, $filter)) continue;
            $val = $filter[$filterKey];
            if ($val === null || $val === '') continue;
            if (!$this->hasColumn($col)) continue;
            $ph = ':' . $col;
            $where[] = "{$pre}{$col} = {$ph}";
            $params[$ph] = $val;
        }

        // Lọc theo tên lớp / loại (schema questions3)
        if ($tableAlias !== '' && isset($filter['class']) && is_string($filter['class']) && trim($filter['class']) !== '') {
            $where[] = 'c.name = :filter_class';
            $params[':filter_class'] = trim($filter['class']);
        }
        if ($tableAlias !== '' && isset($filter['type']) && is_string($filter['type']) && trim($filter['type']) !== '') {
            $where[] = 't.name = :filter_type';
            $params[':filter_type'] = trim($filter['type']);
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
