<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

final class UserModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /** @return array<string,mixed>|null */
    public function findOne(array $filter, bool $includePassword = false): ?array
    {
        $where = [];
        $params = [];

        if (isset($filter['id'])) {
            $where[] = 'id = :id';
            $params[':id'] = (int)$filter['id'];
        }
        if (isset($filter['username'])) {
            $where[] = 'username = :username';
            $params[':username'] = (string)$filter['username'];
        }
        if (isset($filter['email'])) {
            $where[] = 'email = :email';
            $params[':email'] = (string)$filter['email'];
        }

        if (isset($filter['$or']) && is_array($filter['$or'])) {
            $orParts = [];
            $i = 0;
            foreach ($filter['$or'] as $cond) {
                if (!is_array($cond)) continue;
                if (isset($cond['username'])) {
                    $k = ':or_u_' . $i;
                    $orParts[] = "username = {$k}";
                    $params[$k] = (string)$cond['username'];
                    $i++;
                }
                if (isset($cond['email'])) {
                    $k = ':or_e_' . $i;
                    $orParts[] = "email = {$k}";
                    $params[$k] = (string)$cond['email'];
                    $i++;
                }
            }
            if ($orParts) {
                $where[] = '(' . implode(' OR ', $orParts) . ')';
            }
        }

        if (!$where) return null;

        $sql = 'SELECT id, fullname, username, password, email, phone, status, role, created_at, is_first_login
                FROM users
                WHERE ' . implode(' AND ', $where) . '
                LIMIT 1';

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        if (!$row) return null;

        return $this->toJson($row, $includePassword);
    }

    /** @return array<string,mixed>|null */
    public function findById(string $id, bool $includePassword = false): ?array
    {
        if (!ctype_digit($id)) return null;

        $sql = 'SELECT id, fullname, username, password, email, phone, status, role, created_at, is_first_login
                FROM users
                WHERE id = :id
                LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => (int)$id]);
        $row = $st->fetch();
        if (!$row) return null;

        return $this->toJson($row, $includePassword);
    }

    /** @return array<string,mixed> */
    public function create(array $data): array
    {
        $password = (string)($data['password'] ?? '');
        $hash = $password !== '' ? password_hash($password, PASSWORD_BCRYPT) : null;

        $sql = 'INSERT INTO users (fullname, username, password, email, phone, status, role, is_first_login)
                VALUES (:fullname, :username, :password, :email, :phone, :status, :role, :is_first_login)
                RETURNING id';

        $status = (string)($data['status'] ?? 'actived');
        if ($status === 'active') $status = 'actived';

        $isFirstLogin = $data['is_first_login'] ?? $data['isFirstLogin'] ?? false;
        $isFirstLoginBool = $isFirstLogin === true || $isFirstLogin === 'true' || $isFirstLogin === '1';

        $params = [
            ':fullname' => $data['fullname'] ?? null,
            ':username' => $data['username'] ?? null,
            ':password' => $hash,
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':status' => $status,
            ':role' => $data['role'] ?? null,
            ':is_first_login' => $isFirstLoginBool ? 1 : 0,
        ];

        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            if ($k === ':is_first_login') {
                $st->bindValue($k, $v, PDO::PARAM_INT);
            } else {
                $st->bindValue($k, $v);
            }
        }
        $st->execute();
        $newId = (string)$st->fetchColumn();

        $user = $this->findById($newId, false);
        return $user ?? ['id' => (int)$newId] + $data;
    }

    public function countDocuments(array $filter): int
    {
        [$whereSql, $params] = $this->buildListWhere($filter);

        $sql = 'SELECT COUNT(*) FROM users ' . ($whereSql ? "WHERE {$whereSql}" : '');
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function find(array $filter, int $skip, int $limit): array
    {
        [$whereSql, $params] = $this->buildListWhere($filter);

        $sql = 'SELECT id, fullname, username, email, phone, status, role, created_at, is_first_login
                FROM users
                ' . ($whereSql ? "WHERE {$whereSql}" : '') . '
                ORDER BY created_at DESC
                OFFSET :skip
                LIMIT :limit';

        $st = $this->pdo->prepare($sql);

        // bind list params
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':skip', $skip, PDO::PARAM_INT);
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);

        $st->execute();
        $rows = $st->fetchAll() ?: [];

        $out = [];
        foreach ($rows as $r) {
            $out[] = $this->toJson($r, false);
        }
        return $out;
    }

    /** @return array<string,mixed>|null */
    public function updateById(string $id, array $updates): ?array
    {
        if (!ctype_digit($id)) return null;

        $set = [];
        $params = [':id' => (int)$id];

        $map = [
            'fullname' => 'fullname',
            'username' => 'username',
            'email' => 'email',
            'phone' => 'phone',
            'role' => 'role',
            'status' => 'status',
            'is_first_login' => 'is_first_login',
            'isFirstLogin' => 'is_first_login',
        ];

        foreach ($map as $inKey => $col) {
            if (array_key_exists($inKey, $updates)) {
                $val = $updates[$inKey];
                if ($col === 'status' && $val === 'active') $val = 'actived';
                $ph = ':' . $col;
                // avoid duplicates if both is_first_login and isFirstLogin provided
                if (!in_array("{$col} = {$ph}", $set, true)) {
                    $set[] = "{$col} = {$ph}";
                    $params[$ph] = ($col === 'is_first_login') ? (bool)$val : $val;
                }
            }
        }

        if (isset($updates['password']) && is_string($updates['password']) && $updates['password'] !== '') {
            $set[] = "password = :password";
            $params[':password'] = password_hash($updates['password'], PASSWORD_BCRYPT);
        }
        if (isset($updates['newPassword']) && is_string($updates['newPassword']) && $updates['newPassword'] !== '') {
            $set[] = "password = :password";
            $params[':password'] = password_hash($updates['newPassword'], PASSWORD_BCRYPT);
        }

        if (!$set) {
            return $this->findById((string)$id, false);
        }

        $sql = 'UPDATE users SET ' . implode(', ', $set) . ' WHERE id = :id';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $this->findById((string)$id, false);
    }

    public function deleteById(string $id): bool
    {
        if (!ctype_digit($id)) return false;

        $st = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $st->execute([':id' => (int)$id]);
        return $st->rowCount() > 0;
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function buildListWhere(array $filter): array
    {
        $where = [];
        $params = [];

        // Support filters from UserController:
        // search: regex on fullname/username/email
        if (isset($filter['search']) && is_string($filter['search']) && trim($filter['search']) !== '') {
            $q = trim($filter['search']);
            $where[] = '(fullname ILIKE :q OR username ILIKE :q OR email ILIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        if (isset($filter['status']) && is_string($filter['status']) && $filter['status'] !== '') {
            $st = $filter['status'];
            if ($st === 'active') $st = 'actived';
            $where[] = 'status = :status';
            $params[':status'] = $st;
        }

        if (isset($filter['role']) && is_string($filter['role']) && $filter['role'] !== '') {
            $where[] = 'role = :role';
            $params[':role'] = $filter['role'];
        }

        return [implode(' AND ', $where), $params];
    }

    /** @param array<string,mixed> $row */
    private function toJson(array $row, bool $includePassword): array
    {

        $out = $row;


        if (isset($out['created_at']) && $out['created_at'] !== null) {
            $created = is_string($out['created_at']) ? $out['created_at'] : (string)$out['created_at'];
            $out['createdAt'] = $created;
        }

        if (isset($out['status']) && $out['status'] === 'actived') {
            $out['status'] = 'actived';
        }

        if (!$includePassword) {
            unset($out['password']);
        }

        if (isset($out['id'])) {
            $out['id'] = (string)$out['id'];
        }

        return $out;
    }
}
