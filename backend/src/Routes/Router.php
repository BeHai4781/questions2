<?php
declare(strict_types=1);

namespace App\Routes;

use App\Controllers\AuthController;
use App\Controllers\ExamAttemptController;
use App\Controllers\ExamController;
use App\Controllers\NotificationController;
use App\Controllers\QuestionController;
use App\Controllers\UserController;
use App\Middleware\Auth;
use App\Middleware\Validator;
use App\Utils\Response;

final class Router
{
    /** @var array<string,callable> */
    private array $routes = [];

    public function registerRoutes(): void
    {
        // Health
        $this->map('GET', '/api/health', function () {
            Response::success([
                'success' => true, // keep as in JS route payload shape
                'message' => 'Server is running',
                'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ], 'Success');
        });

        // Auth routes
        $this->map('POST', '/api/auth/register', function () {
            $body = $this->jsonBody();
            $messages = [];

            // registerValidation (same rules)
            $u = trim((string)($body['username'] ?? ''));
            if (strlen($u) < 3 || strlen($u) > 50) $messages[] = 'Username must be between 3 and 50 characters';

            $email = (string)($body['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $messages[] = 'Please provide a valid email';

            $pw = (string)($body['password'] ?? '');
            if (strlen($pw) < 6) $messages[] = 'Password must be at least 6 characters';

            $fn = trim((string)($body['fullname'] ?? ''));
            if ($fn === '') $messages[] = 'Full name is required';

            Validator::failIf($messages);
            AuthController::register($body);
        });

        $this->map('POST', '/api/auth/login', function () {
            $body = $this->jsonBody();
            $messages = [];

            $username = (string)($body['username'] ?? '');
            if (trim($username) === '') $messages[] = 'Username or email is required';
            $password = (string)($body['password'] ?? '');
            if (trim($password) === '') $messages[] = 'Password is required';

            Validator::failIf($messages);
            AuthController::login($body);
        });

        $this->map('GET', '/api/auth/me', function () {
            $user = Auth::authenticate();
            AuthController::getMe($user);
        });

        $this->map('PATCH', '/api/auth/me', function () {
            $user = Auth::authenticate();
            $body = $this->jsonBody();
            $messages = [];
            if (isset($body['newPassword']) && strlen(trim((string)$body['newPassword'])) > 0 && strlen(trim((string)$body['newPassword'])) < 6) {
                $messages[] = 'newPassword must be at least 6 characters';
            }
            Validator::failIf($messages);
            AuthController::updateMe($user, $body);
        });

        $this->map('POST', '/api/auth/refresh', function () {
            $body = $this->jsonBody();
            $messages = [];
            if (trim((string)($body['refreshToken'] ?? '')) === '') $messages[] = 'Refresh token is required';
            Validator::failIf($messages);
            AuthController::refreshToken($body);
        });

        // User routes
        $this->map('GET', '/api/users', function () {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin');

            $messages = [];
            if (isset($_GET['page']) && (!ctype_digit((string)$_GET['page']) || (int)$_GET['page'] < 1)) {
                $messages[] = 'page must be a positive integer';
            }
            if (isset($_GET['limit'])) {
                $l = (string)$_GET['limit'];
                if (!ctype_digit($l) || (int)$l < 1 || (int)$l > 100) $messages[] = 'limit must be 1..100';
            }
            if (isset($_GET['status']) && !in_array($_GET['status'], ['actived','active','banned'], true)) {
                $messages[] = "status must be 'actived' or 'banned'";
            }
            if (isset($_GET['role']) && !in_array($_GET['role'], ['admin','teacher','student'], true)) {
                $messages[] = "role must be 'admin', 'teacher' or 'student'";
            }
            Validator::failIf($messages);

            UserController::getUsers($_GET);
        });

        $this->map('GET', '/api/users/:id', function (string $id) {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin');

            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid user id';
            Validator::failIf($messages);

            UserController::getUserById($id);
        });

        $this->map('POST', '/api/users', function () {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin');

            $body = $this->jsonBody();
            $messages = [];

            $u = trim((string)($body['username'] ?? ''));
            if (strlen($u) < 3 || strlen($u) > 50) $messages[] = 'Username must be between 3 and 50 characters';

            $email = (string)($body['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $messages[] = 'Please provide a valid email';

            $pw = (string)($body['password'] ?? '');
            if (strlen($pw) < 6) $messages[] = 'Password must be at least 6 characters';

            $fn = trim((string)($body['fullname'] ?? ''));
            if ($fn === '') $messages[] = 'Full name is required';

            if (isset($body['role']) && !in_array((string)$body['role'], ['admin','teacher','student'], true)) {
                $messages[] = "role must be 'admin', 'teacher' or 'student'";
            }
            if (isset($body['status']) && !in_array((string)$body['status'], ['actived','active','banned'], true)) {
                $messages[] = "status must be 'actived' or 'banned'";
            }

            Validator::failIf($messages);
            UserController::createUser($body);
        });

        $this->map('PUT', '/api/users/:id', function (string $id) {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin');

            $body = $this->jsonBody();
            $messages = [];

            if (!ctype_digit($id)) $messages[] = 'Invalid user id';
            if (isset($body['email']) && !filter_var((string)$body['email'], FILTER_VALIDATE_EMAIL)) {
                $messages[] = 'Please provide a valid email';
            }
            if (isset($body['role']) && !in_array((string)$body['role'], ['admin','teacher','student'], true)) {
                $messages[] = "role must be 'admin', 'teacher' or 'student'";
            }
            if (isset($body['status']) && !in_array((string)$body['status'], ['actived','active','banned'], true)) {
                $messages[] = "status must be 'actived' or 'banned'";
            }
            if (isset($body['newPassword']) && strlen((string)$body['newPassword']) < 6) {
                $messages[] = 'newPassword must be at least 6 characters';
            }

            Validator::failIf($messages);
            UserController::updateUser($id, $body);
        });

        $this->map('PATCH', '/api/users/:id/status', function (string $id) {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin');

            $body = $this->jsonBody();
            $messages = [];

            if (!ctype_digit($id)) $messages[] = 'Invalid user id';
            $st = (string)($body['status'] ?? '');
            if (!in_array($st, ['actived','active','banned'], true)) $messages[] = "status must be 'actived' or 'banned'";

            Validator::failIf($messages);
            UserController::updateUserStatus($id, $body, $user);
        });

        $this->map('DELETE', '/api/users/:id', function (string $id) {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin');

            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid user id';
            Validator::failIf($messages);

            UserController::deleteUser($id, $user);
        });

        $this->map('GET', '/api/exams', function () {
            $authDisabled = (($_ENV['AUTH_DISABLED'] ?? getenv('AUTH_DISABLED') ?: 'false') === 'true');
            $user = $authDisabled ? [] : Auth::authenticate();
            $messages = [];
            $this->validatePagination($_GET, $messages);
            if (isset($_GET['createdBy']) && !ctype_digit((string)$_GET['createdBy'])) {
                $messages[] = 'createdBy must be a numeric id';
            }
            $this->validateOptionalString($_GET['search'] ?? null, 'search', 0, 200, $messages);
            $this->validateOptionalString($_GET['class'] ?? null, 'class', 1, 50, $messages);
            $this->validateOptionalString($_GET['type'] ?? null, 'type', 1, 50, $messages);
            $this->validateOptionalString($_GET['status'] ?? null, 'status', 1, 30, $messages);
            Validator::failIf($messages);
            ExamController::getExams($_GET, $user);
        });

        $this->map('GET', '/api/exams/:id', function (string $id) {
            $authDisabled = (($_ENV['AUTH_DISABLED'] ?? getenv('AUTH_DISABLED') ?: 'false') === 'true');
            $user = $authDisabled ? [] : Auth::authenticate();
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid exam id';
            Validator::failIf($messages);
            ExamController::getExamById($id, $user);
        });

        $this->map('POST', '/api/exams', function () {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin', 'teacher');
            $body = $this->jsonBody();
            $messages = [];
            $this->requireAnyField($body, ['title', 'name'], 'Exam title is required', $messages);
            $this->validateOptionalString($body['title'] ?? null, 'title', 1, 200, $messages);
            $this->validateOptionalString($body['name'] ?? null, 'name', 1, 200, $messages);
            $this->validateOptionalString($body['description'] ?? null, 'description', 0, 2000, $messages);
            $this->validateOptionalString($body['class'] ?? null, 'class', 1, 50, $messages);
            $this->validateOptionalString($body['type'] ?? null, 'type', 1, 50, $messages);
            $this->validateOptionalEnum($body['questionType'] ?? null, 'questionType', ['random', 'specific', 'shuffle'], $messages);
            $this->validateOptionalInt($body['duration'] ?? null, 'duration', 1, 1000, $messages);
            $this->validateOptionalArray($body['questions'] ?? null, 'questions', $messages);
            Validator::failIf($messages);
            ExamController::createExam($body, $user);
        });

        $this->map('PUT', '/api/exams/:id', function (string $id) {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin', 'teacher');
            $body = $this->jsonBody();
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid exam id';
            $this->validateOptionalString($body['title'] ?? null, 'title', 1, 200, $messages);
            $this->validateOptionalString($body['name'] ?? null, 'name', 1, 200, $messages);
            $this->validateOptionalString($body['description'] ?? null, 'description', 0, 2000, $messages);
            $this->validateOptionalString($body['class'] ?? null, 'class', 1, 50, $messages);
            $this->validateOptionalString($body['type'] ?? null, 'type', 1, 50, $messages);
            $this->validateOptionalEnum($body['questionType'] ?? null, 'questionType', ['random', 'specific', 'shuffle'], $messages);
            $this->validateOptionalInt($body['duration'] ?? null, 'duration', 1, 1000, $messages);
            $this->validateOptionalArray($body['questions'] ?? null, 'questions', $messages);
            Validator::failIf($messages);
            ExamController::updateExam($id, $body, $user);
        });

        $this->map('DELETE', '/api/exams/:id', function (string $id) {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin', 'teacher');
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid exam id';
            Validator::failIf($messages);
            ExamController::deleteExam($id, $user);
        });

        $this->map('GET', '/api/questions', function () {
            $user = Auth::authenticate();
            $messages = [];
            $this->validatePagination($_GET, $messages);
            if (isset($_GET['createdBy']) && !ctype_digit((string)$_GET['createdBy'])) {
                $messages[] = 'createdBy must be a numeric id';
            }
            $this->validateOptionalString($_GET['search'] ?? null, 'search', 0, 200, $messages);
            $this->validateOptionalString($_GET['class'] ?? null, 'class', 1, 50, $messages);
            $this->validateOptionalString($_GET['difficulty'] ?? null, 'difficulty', 1, 50, $messages);
            $this->validateOptionalString($_GET['type'] ?? null, 'type', 1, 50, $messages);
            Validator::failIf($messages);
            QuestionController::getQuestions($_GET, $user);
        });

        $this->map('GET', '/api/questions/:id', function (string $id) {
            $user = Auth::authenticate();
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid question id';
            Validator::failIf($messages);
            QuestionController::getQuestionById($id, $user);
        });

        $this->map('POST', '/api/questions', function () {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin', 'teacher');
            $body = $this->jsonBody();
            $messages = [];
            $this->requireAnyField($body, ['content', 'question'], 'Question content is required', $messages);
            $this->validateOptionalString($body['content'] ?? null, 'content', 1, 2000, $messages);
            $this->validateOptionalString($body['question'] ?? null, 'question', 1, 2000, $messages);
            $this->validateOptionalString($body['class'] ?? null, 'class', 1, 50, $messages);
            $this->validateOptionalString($body['difficulty'] ?? null, 'difficulty', 1, 50, $messages);
            $this->validateOptionalString($body['type'] ?? null, 'type', 1, 50, $messages);
            $this->validateOptionalInt($body['correctAnswer'] ?? null, 'correctAnswer', 0, 100, $messages);
            $this->validateOptionalArray($body['answers'] ?? null, 'answers', $messages);
            Validator::failIf($messages);
            QuestionController::createQuestion($body, $user);
        });

        $this->map('PUT', '/api/questions/:id', function (string $id) {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin', 'teacher');
            $body = $this->jsonBody();
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid question id';
            $this->validateOptionalString($body['content'] ?? null, 'content', 1, 2000, $messages);
            $this->validateOptionalString($body['question'] ?? null, 'question', 1, 2000, $messages);
            $this->validateOptionalString($body['class'] ?? null, 'class', 1, 50, $messages);
            $this->validateOptionalString($body['difficulty'] ?? null, 'difficulty', 1, 50, $messages);
            $this->validateOptionalString($body['type'] ?? null, 'type', 1, 50, $messages);
            $this->validateOptionalInt($body['correctAnswer'] ?? null, 'correctAnswer', 0, 100, $messages);
            $this->validateOptionalArray($body['answers'] ?? null, 'answers', $messages);
            Validator::failIf($messages);
            QuestionController::updateQuestion($id, $body, $user);
        });

        $this->map('DELETE', '/api/questions/:id', function (string $id) {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin', 'teacher');
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid question id';
            Validator::failIf($messages);
            QuestionController::deleteQuestion($id, $user);
        });

        $this->map('GET', '/api/exam-attempts', function () {
            $authDisabled = (($_ENV['AUTH_DISABLED'] ?? getenv('AUTH_DISABLED') ?: 'false') === 'true');
            $user = $authDisabled ? [] : Auth::authenticate();
            $messages = [];
            $this->validatePagination($_GET, $messages);
            if (isset($_GET['examId']) && !ctype_digit((string)$_GET['examId'])) {
                $messages[] = 'examId must be a numeric id';
            }
            if (isset($_GET['userId']) && !ctype_digit((string)$_GET['userId'])) {
                $messages[] = 'userId must be a numeric id';
            }
            $this->validateOptionalString($_GET['status'] ?? null, 'status', 1, 50, $messages);
            Validator::failIf($messages);
            ExamAttemptController::getAttempts($_GET, $user);
        });

        $this->map('GET', '/api/exam-attempts/:id', function (string $id) {
            $authDisabled = (($_ENV['AUTH_DISABLED'] ?? getenv('AUTH_DISABLED') ?: 'false') === 'true');
            $user = $authDisabled ? [] : Auth::authenticate();
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid exam attempt id';
            Validator::failIf($messages);
            ExamAttemptController::getAttemptById($id, $user);
        });

        $this->map('POST', '/api/exam-attempts', function () {
            $user = Auth::authenticate();
            $body = $this->jsonBody();
            $messages = [];
            $this->requireAnyField($body, ['examId'], 'examId is required', $messages);
            if (isset($body['examId']) && !ctype_digit((string)$body['examId'])) {
                $messages[] = 'examId must be a numeric id';
            }
            if (isset($body['userId']) && !ctype_digit((string)$body['userId'])) {
                $messages[] = 'userId must be a numeric id';
            }
            $this->validateOptionalString($body['status'] ?? null, 'status', 1, 50, $messages);
            $this->validateOptionalArray($body['answers'] ?? null, 'answers', $messages);
            $this->validateOptionalInt($body['score'] ?? null, 'score', 0, 10000, $messages);
            Validator::failIf($messages);
            ExamAttemptController::createAttempt($body, $user);
        });

        $this->map('PUT', '/api/exam-attempts/:id', function (string $id) {
            $user = Auth::authenticate();
            $body = $this->jsonBody();
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid exam attempt id';
            if (isset($body['examId']) && !ctype_digit((string)$body['examId'])) {
                $messages[] = 'examId must be a numeric id';
            }
            if (isset($body['userId']) && !ctype_digit((string)$body['userId'])) {
                $messages[] = 'userId must be a numeric id';
            }
            $this->validateOptionalString($body['status'] ?? null, 'status', 1, 50, $messages);
            $this->validateOptionalArray($body['answers'] ?? null, 'answers', $messages);
            $this->validateOptionalInt($body['score'] ?? null, 'score', 0, 10000, $messages);
            Validator::failIf($messages);
            ExamAttemptController::updateAttempt($id, $body, $user);
        });

        $this->map('DELETE', '/api/exam-attempts/:id', function (string $id) {
            $user = Auth::authenticate();
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid exam attempt id';
            Validator::failIf($messages);
            ExamAttemptController::deleteAttempt($id, $user);
        });

        $this->map('GET', '/api/notifications', function () {
            $user = Auth::authenticate();
            $messages = [];
            $this->validatePagination($_GET, $messages);
            if (isset($_GET['userId']) && !ctype_digit((string)$_GET['userId'])) {
                $messages[] = 'userId must be a numeric id';
            }
            $this->validateOptionalString($_GET['type'] ?? null, 'type', 1, 50, $messages);
            $this->validateOptionalBoolString($_GET['isRead'] ?? null, 'isRead', $messages);
            Validator::failIf($messages);
            NotificationController::getNotifications($_GET, $user);
        });

        $this->map('GET', '/api/notifications/:id', function (string $id) {
            $user = Auth::authenticate();
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid notification id';
            Validator::failIf($messages);
            NotificationController::getNotificationById($id, $user);
        });

        $this->map('POST', '/api/notifications', function () {
            $user = Auth::authenticate();
            Auth::authorize($user, 'admin', 'teacher');
            $body = $this->jsonBody();
            $messages = [];
            if (!isset($body['userId']) || !ctype_digit((string)$body['userId'])) {
                $messages[] = 'userId must be a numeric id';
            }
            $this->requireAnyField($body, ['title', 'message', 'content'], 'Notification content is required', $messages);
            $this->validateOptionalString($body['title'] ?? null, 'title', 1, 200, $messages);
            $this->validateOptionalString($body['message'] ?? null, 'message', 1, 2000, $messages);
            $this->validateOptionalString($body['content'] ?? null, 'content', 1, 2000, $messages);
            $this->validateOptionalString($body['type'] ?? null, 'type', 1, 50, $messages);
            $this->validateOptionalBool($body['isRead'] ?? null, 'isRead', $messages);
            Validator::failIf($messages);
            NotificationController::createNotification($body, $user);
        });

        $this->map('PATCH', '/api/notifications/:id/read', function (string $id) {
            $user = Auth::authenticate();
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid notification id';
            Validator::failIf($messages);
            NotificationController::markAsRead($id, $user);
        });

        $this->map('DELETE', '/api/notifications/:id', function (string $id) {
            $user = Auth::authenticate();
            $messages = [];
            if (!ctype_digit($id)) $messages[] = 'Invalid notification id';
            Validator::failIf($messages);
            NotificationController::deleteNotification($id, $user);
        });
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $key = $method . ' ' . $path;
        if (isset($this->routes[$key])) {
            ($this->routes[$key])();
            return;
        }

        foreach ($this->routes as $rk => $handler) {
            [$rm, $rpath] = explode(' ', $rk, 2);
            if ($rm !== $method) continue;
            if (!str_contains($rpath, ':id')) continue;

            $regex = '#^' . preg_quote($rpath, '#') . '$#';
            $regex = str_replace('\:id', '([^/]+)', $regex);

            if (preg_match($regex, $path, $m)) {
                $id = $m[1] ?? '';
                $handler($id);
                return;
            }
        }

        Response::json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => "Not Found - {$path}",
            ],
        ], 404);
    }

    /** @param callable $handler */
    private function map(string $method, string $path, callable $handler): void
    {
        $this->routes[$method . ' ' . $path] = $handler;
    }

    /** @return array<string,mixed> */
    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string,mixed> $query */
    private function validatePagination(array $query, array &$messages): void
    {
        if (isset($query['page']) && (!ctype_digit((string)$query['page']) || (int)$query['page'] < 1)) {
            $messages[] = 'page must be a positive integer';
        }
        if (isset($query['limit'])) {
            $l = (string)$query['limit'];
            if (!ctype_digit($l) || (int)$l < 1 || (int)$l > 100) $messages[] = 'limit must be 1..100';
        }
    }

    private function validateOptionalString(mixed $value, string $name, int $min, int $max, array &$messages): void
    {
        if ($value === null) return;
        if (!is_string($value)) {
            $messages[] = "{$name} must be a string";
            return;
        }
        $len = strlen(trim($value));
        if ($len < $min || $len > $max) {
            $messages[] = "{$name} length must be {$min}..{$max}";
        }
    }

    private function validateOptionalEnum(mixed $value, string $name, array $allowed, array &$messages): void
    {
        if ($value === null) return;
        if (!is_string($value) || !in_array($value, $allowed, true)) {
            $messages[] = "{$name} must be one of: " . implode(', ', $allowed);
        }
    }

    private function validateOptionalInt(mixed $value, string $name, int $min, int $max, array &$messages): void
    {
        if ($value === null) return;
        if (is_int($value)) {
            $intVal = $value;
        } elseif (is_string($value) && ctype_digit($value)) {
            $intVal = (int)$value;
        } else {
            $messages[] = "{$name} must be an integer";
            return;
        }
        if ($intVal < $min || $intVal > $max) {
            $messages[] = "{$name} must be between {$min} and {$max}";
        }
    }

    private function validateOptionalArray(mixed $value, string $name, array &$messages): void
    {
        if ($value === null) return;
        if (!is_array($value)) {
            $messages[] = "{$name} must be an array";
        }
    }

    private function validateOptionalBoolString(mixed $value, string $name, array &$messages): void
    {
        if ($value === null) return;
        $v = is_string($value) ? strtolower($value) : $value;
        if (!in_array($v, ['true', 'false', '1', '0', 1, 0, true, false], true)) {
            $messages[] = "{$name} must be a boolean";
        }
    }

    private function validateOptionalBool(mixed $value, string $name, array &$messages): void
    {
        if ($value === null) return;
        if (!is_bool($value)) {
            $messages[] = "{$name} must be a boolean";
        }
    }

    /** @param array<string,mixed> $body */
    private function requireAnyField(array $body, array $fields, string $message, array &$messages): void
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $body) && trim((string)$body[$field]) !== '') {
                return;
            }
        }
        $messages[] = $message;
    }
}
