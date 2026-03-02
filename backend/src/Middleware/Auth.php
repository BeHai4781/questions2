<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Models\UserModel;
use App\Utils\Response;
use App\Utils\Jwt;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use UnexpectedValueException;

final class Auth
{
    /** @return array<string,mixed> */
    public static function authenticate(): array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'No token provided or invalid format',
                ],
            ], 401);
            exit;
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = Jwt::verifyToken($token);
            $userId = (string)($decoded['userId'] ?? '');

            $userModel = new UserModel();
            $user = $userModel->findById($userId, false); // exclude password

            if (!$user) {
                Response::error('User not found', 401, 'USER_NOT_FOUND');
                exit;
            }

            if (($user['status'] ?? 'actived') === 'banned') {
                Response::error('User account has been banned', 403, 'USER_BANNED');
                exit;
            }

            return $user;
        } catch (ExpiredException) {
            Response::error('Token has expired', 401, 'TOKEN_EXPIRED');
            exit;
        } catch (SignatureInvalidException|UnexpectedValueException) {
            Response::error('Invalid token', 401, 'INVALID_TOKEN');
            exit;
        } catch (\Throwable) {
            Response::error('Authentication error', 500, 'AUTH_ERROR');
            exit;
        }
    }

    public static function authorize(array $user, string ...$roles): void
    {
        if (!$user) {
            Response::error('Authentication required', 401, 'UNAUTHORIZED');
            exit;
        }

        $role = (string)($user['role'] ?? '');
        if (!in_array($role, $roles, true)) {
            Response::error('You do not have permission to access this resource', 403, 'FORBIDDEN');
            exit;
        }
    }
}
