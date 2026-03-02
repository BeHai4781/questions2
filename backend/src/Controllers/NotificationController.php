<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\NotificationModel;
use App\Utils\Response;

final class NotificationController
{
    public static function getNotifications(array $query, array $currentUser): void
    {
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 10;

        $pageNum = max((int)$page, 1);
        $limitNum = min(max((int)$limit, 1), 100);
        $skip = ($pageNum - 1) * $limitNum;

        $filter = [
            'type' => $query['type'] ?? null,
            'isRead' => $query['isRead'] ?? null,
        ];

        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'student') {
            $filter['userId'] = (string)($currentUser['id'] ?? '');
        } elseif (isset($query['userId'])) {
            $filter['userId'] = $query['userId'];
        }

        $model = new NotificationModel();
        $total = $model->countDocuments($filter);
        $totalPages = (int)ceil($total / $limitNum) ?: 1;
        $items = $model->find($filter, $skip, $limitNum);

        Response::paginated($items, [
            'page' => $pageNum,
            'limit' => $limitNum,
            'total' => $total,
            'totalPages' => $totalPages,
        ], 'Notifications retrieved successfully');
    }

    public static function getNotificationById(string $id, array $currentUser): void
    {
        $model = new NotificationModel();
        $notification = $model->findById($id);
        if (!$notification) {
            Response::error('Notification not found', 404, 'NOTIFICATION_NOT_FOUND');
            return;
        }

        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'student' && (string)($notification['user_id'] ?? '') !== (string)($currentUser['id'] ?? '')) {
            Response::error('You do not have permission to access this resource', 403, 'FORBIDDEN');
            return;
        }

        Response::success(['notification' => $notification], 'Notification retrieved successfully');
    }

    public static function createNotification(array $body, array $currentUser): void
    {
        $payload = $body;
        if (!isset($payload['userId'])) {
            Response::error('userId is required', 400, 'USER_ID_REQUIRED');
            return;
        }

        $model = new NotificationModel();
        $created = $model->create($payload);
        if (!$created) {
            Response::error('Unable to create notification', 400, 'NOTIFICATION_CREATE_FAILED');
            return;
        }
        Response::success(['notification' => $created], 'Notification created successfully', 201);
    }

    public static function markAsRead(string $id, array $currentUser): void
    {
        $model = new NotificationModel();
        $existing = $model->findById($id);
        if (!$existing) {
            Response::error('Notification not found', 404, 'NOTIFICATION_NOT_FOUND');
            return;
        }

        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'student' && (string)($existing['user_id'] ?? '') !== (string)($currentUser['id'] ?? '')) {
            Response::error('You do not have permission to access this resource', 403, 'FORBIDDEN');
            return;
        }

        $payload = [
            'isRead' => true,
            'readAt' => date('c'),
        ];
        $updated = $model->updateById($id, $payload);
        Response::success(['notification' => $updated], 'Notification marked as read');
    }

    public static function deleteNotification(string $id, array $currentUser): void
    {
        $model = new NotificationModel();
        $existing = $model->findById($id);
        if (!$existing) {
            Response::error('Notification not found', 404, 'NOTIFICATION_NOT_FOUND');
            return;
        }

        $role = (string)($currentUser['role'] ?? '');
        if ($role === 'student' && (string)($existing['user_id'] ?? '') !== (string)($currentUser['id'] ?? '')) {
            Response::error('You do not have permission to access this resource', 403, 'FORBIDDEN');
            return;
        }

        $ok = $model->deleteById($id);
        if (!$ok) {
            Response::error('Notification not found', 404, 'NOTIFICATION_NOT_FOUND');
            return;
        }
        Response::success(['id' => $id], 'Notification deleted successfully');
    }
}
