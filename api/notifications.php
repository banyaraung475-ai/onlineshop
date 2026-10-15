<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');
ini_set('display_errors', '0');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = new Database();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_unread':
            $notifications = getUnreadNotifications($_SESSION['user_id']);
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'count' => count($notifications)
            ]);
            break;

        case 'mark_read':
            $notification_id = (int)($_POST['notification_id'] ?? 0);
            if ($notification_id <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'notification_id is required'
                ]);
                break;
            }

            if (markNotificationAsRead($notification_id, $_SESSION['user_id'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to mark notification as read'
                ]);
            }
            break;

        case 'mark_all_read':
            if (markAllNotificationsAsRead($_SESSION['user_id'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'All notifications marked as read'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to mark all notifications as read'
                ]);
            }
            break;

        case 'get_latest':
            $latest = getLatestNotification($_SESSION['user_id']);
            echo json_encode([
                'success' => true,
                'notification' => $latest
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Throwable $e) {
    error_log('Notifications API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to process notifications request'
    ]);
}

function getUnreadNotifications($user_id) {
    global $db;
    return $db->fetchAll(
        "SELECT * FROM notifications 
         WHERE user_id = ? AND is_read = 0 
         ORDER BY created_at DESC 
         LIMIT 10",
        [$user_id]
    );
}

function markNotificationAsRead($notification_id, $user_id) {
    global $db;
    return $db->update('notifications', ['is_read' => 1], 'notification_id = ? AND user_id = ?', [$notification_id, $user_id]);
}

function markAllNotificationsAsRead($user_id) {
    global $db;
    return $db->update('notifications', ['is_read' => 1], 'user_id = ? AND is_read = 0', [$user_id]);
}

function getLatestNotification($user_id) {
    global $db;
    return $db->fetchOne(
        "SELECT * FROM notifications 
         WHERE user_id = ? 
         ORDER BY created_at DESC 
         LIMIT 1",
        [$user_id]
    );
}
?>
