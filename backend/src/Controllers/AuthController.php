<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use App\Utils\Jwt;
use App\Utils\Response;

final class AuthController
{
    public static function register(array $body): void
    {
        try {
            $username = $body['username'] ?? null;
            $email = $body['email'] ?? null;
            $password = $body['password'] ?? null;
            $fullname = $body['fullname'] ?? null;
            $phone = $body['phone'] ?? null;
            $role = $body['role'] ?? null;

            $userModel = new UserModel();

            $existingUser = $userModel->findOne([
                '$or' => [
                    ['email' => $email],
                    ['username' => $username],
                ],
            ]);

            if ($existingUser) {
                Response::error('User with this email or username already exists', 400, 'USER_EXISTS');
                return;
            }

            $user = $userModel->create([
                'username' => $username,
                'email' => is_string($email) ? strtolower($email) : $email,
                'password' => $password,
                'fullname' => $fullname,
                'phone' => $phone,
                'role' => $role ?: 'student',
            ]);

            $token = Jwt::generateToken((string)$user['id']);
            $refreshToken = Jwt::generateRefreshToken((string)$user['id']);

            Response::success([
                'user' => $user,
                'token' => $token,
                'refreshToken' => $refreshToken,
            ], 'User registered successfully', 201);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function login(array $body): void
    {
        try {
            $username = $body['username'] ?? null;
            $password = $body['password'] ?? null;

            $userModel = new UserModel();

            // include password for compare
            $userWithPassword = $userModel->findOne([
                '$or' => [
                    ['username' => $username],
                    ['email' => $username],
                ],
            ], true);

            if (!$userWithPassword) {
                Response::error('Invalid credentials', 401, 'INVALID_CREDENTIALS');
                return;
            }

            $hashed = $userWithPassword['password'] ?? null; // keep variable (not removed)
            $isMatch = (is_string($hashed) && is_string($password)) ? password_verify($password, $hashed) : false;

            if (!$isMatch) {
                Response::error('Invalid credentials', 401, 'INVALID_CREDENTIALS');
                return;
            }

            if (($userWithPassword['status'] ?? '') === 'banned') {
                Response::error('Account has been banned', 403, 'USER_BANNED');
                return;
            }

            unset($userWithPassword['password']);

            $token = Jwt::generateToken((string)$userWithPassword['id']);
            $refreshToken = Jwt::generateRefreshToken((string)$userWithPassword['id']);

            Response::success([
                'user' => $userWithPassword,
                'token' => $token,
                'refreshToken' => $refreshToken,
            ], 'Login successful');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function getMe(array $user): void
    {
        try {
            $userModel = new UserModel();
            $u = $userModel->findById((string)($user['id'] ?? ''), false);
            Response::success(['user' => $u], 'User retrieved successfully');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function refreshToken(array $body): void
    {
        try {
            $refreshToken = $body['refreshToken'] ?? null;

            if (!$refreshToken) {
                Response::error('Refresh token is required', 400, 'REFRESH_TOKEN_REQUIRED');
                return;
            }

            try {
                $decoded = Jwt::verifyToken((string)$refreshToken);
            } catch (\Throwable $e) {
                Response::error('Invalid or expired refresh token', 401, 'INVALID_REFRESH_TOKEN');
                return;
            }

            $userId = (string)($decoded['userId'] ?? '');
            $userModel = new UserModel();
            $user = $userModel->findById($userId, false);

            if (!$user) {
                Response::error('User not found', 404, 'USER_NOT_FOUND');
                return;
            }

            $newToken = Jwt::generateToken((string)$user['id']);
            $newRefreshToken = Jwt::generateRefreshToken((string)$user['id']);

            Response::success([
                'token' => $newToken,
                'refreshToken' => $newRefreshToken,
            ], 'Token refreshed successfully');
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
