<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use App\Utils\Response;

final class UserController
{
    public static function getUsers(array $query): void
    {
        try {
            $search = $query['search'] ?? '';
            $status = $query['status'] ?? null; 
            $role = $query['role'] ?? null;     
            $page = $query['page'] ?? 1;
            $limit = $query['limit'] ?? 10;

            $pageNum = max((int)$page, 1);
            $limitNum = min(max((int)$limit, 1), 100);

            $filter = [
                'search' => (string)$search,
            ];

            if (is_string($status) && in_array($status, ['actived', 'active', 'banned'], true)) {
                $filter['status'] = $status;
            }

            if (is_string($role) && in_array($role, ['admin', 'teacher', 'student'], true)) {
                $filter['role'] = $role;
            }

            $userModel = new UserModel();
            $total = $userModel->countDocuments($filter);
            $totalPages = (int)ceil($total / $limitNum) ?: 1;
            $skip = ($pageNum - 1) * $limitNum;

            $users = $userModel->find($filter, $skip, $limitNum);

            Response::paginated($users, [
                'page' => $pageNum,
                'limit' => $limitNum,
                'total' => $total,
                'totalPages' => $totalPages,
            ], 'Users retrieved successfully');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function getUserById(string $id): void
    {
        try {
            $userModel = new UserModel();
            $user = $userModel->findById($id, false);
            if (!$user) {
                Response::error('User not found', 404, 'USER_NOT_FOUND');
                return;
            }
            Response::success(['user' => $user], 'User retrieved successfully');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function createUser(array $body): void
    {
        try {
            $username = $body['username'] ?? null;
            $email = $body['email'] ?? null;
            $password = $body['password'] ?? null;
            $fullname = $body['fullname'] ?? null;
            $phone = $body['phone'] ?? null;
            $role = $body['role'] ?? null;
            $status = $body['status'] ?? null;

            $userModel = new UserModel();

            $existingUser = $userModel->findOne([
                '$or' => [
                    ['email' => $email],
                    ['username' => $username],
                ],
            ]);

            if ($existingUser) {
                Response::error('Username or email already exists', 400, 'USER_EXISTS');
                return;
            }

            $user = $userModel->create([
                'username' => $username,
                'email' => is_string($email) ? strtolower($email) : $email,
                'password' => $password,
                'fullname' => $fullname,
                'phone' => $phone,
                'role' => $role ?: 'student',
                // PostgreSQL schema: actived|banned (accepts active for compatibility)
                'status' => (is_string($status) && in_array($status, ['actived','active','banned'], true)) ? $status : 'actived',
            ]);

            Response::success(['user' => $user], 'User created successfully', 201);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function updateUser(string $id, array $body): void
    {
        try {
            $fullname = $body['fullname'] ?? null;
            $email = $body['email'] ?? null;
            $phone = $body['phone'] ?? null;
            $role = $body['role'] ?? null;
            $status = $body['status'] ?? null;
            $newPassword = $body['newPassword'] ?? null;

            $userModel = new UserModel();
            $existing = $userModel->findById($id, true);
            if (!$existing) {
                Response::error('User not found', 404, 'USER_NOT_FOUND');
                return;
            }

            $updates = [];
            // not allowing username update here (same logic)
            if (is_string($fullname)) $updates['fullname'] = $fullname;
            if (is_string($email)) $updates['email'] = strtolower($email);
            if (is_string($phone)) $updates['phone'] = $phone;
            if (is_string($role) && in_array($role, ['admin', 'teacher', 'student'], true)) $updates['role'] = $role;
            if (is_string($status) && in_array($status, ['actived','active','banned'], true)) $updates['status'] = $status;

            if ($newPassword && strlen(trim((string)$newPassword)) >= 6) {
                // stored as 'password' for hashing
                $updates['password'] = (string)$newPassword;
            }

            $updatedUser = $userModel->updateById($id, $updates);
            Response::success(['user' => $updatedUser], 'User updated successfully');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function updateUserStatus(string $id, array $body, array $currentUser): void
    {
        try {
            $status = $body['status'] ?? null;
            if (!is_string($status) || !in_array($status, ['actived','active','banned'], true)) {
                Response::error('Invalid status', 400, 'INVALID_STATUS');
                return;
            }

            // cannot ban self
            if (($currentUser['id'] ?? null) && (string)$currentUser['id'] === (string)$id && $status === 'banned') {
                Response::error('You cannot ban yourself', 400, 'CANNOT_BAN_SELF');
                return;
            }

            $userModel = new UserModel();
            $existing = $userModel->findById($id, false);
            if (!$existing) {
                Response::error('User not found', 404, 'USER_NOT_FOUND');
                return;
            }

            $updated = $userModel->updateById($id, ['status' => $status]);
            Response::success(['user' => $updated], 'User status updated successfully');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function deleteUser(string $id, array $currentUser): void
    {
        try {
            // cannot delete self
            if (($currentUser['id'] ?? null) && (string)$currentUser['id'] === (string)$id) {
                Response::error('You cannot delete yourself', 400, 'CANNOT_DELETE_SELF');
                return;
            }

            $userModel = new UserModel();
            $existing = $userModel->findById($id, false);
            if (!$existing) {
                Response::error('User not found', 404, 'USER_NOT_FOUND');
                return;
            }

            $ok = $userModel->deleteById($id);
            if (!$ok) {
                Response::error('User not found', 404, 'USER_NOT_FOUND');
                return;
            }

            Response::success(['id' => $id], 'User deleted successfully');
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
