<?php
// Notification service - Quản lý thông báo và template
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

class NotificationService {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = db();
        $this->auth = auth();
    }

    // ==================== NOTIFICATION CREATION ====================

    public function createNotification($data) {
        try {
            // Validation
            if (!isset($data['title']) || !isset($data['message'])) {
                return ['success' => false, 'message' => 'Thiếu tiêu đề hoặc nội dung thông báo'];
            }

            // Tạo thông báo
            $this->db->execute(
                "INSERT INTO notifications (user_id, admin_id, title, message, type, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)",
                [
                    $data['user_id'] ?? null,
                    $data['admin_id'] ?? null,
                    $data['title'],
                    $data['message'],
                    $data['type'] ?? 'info',
                    'unread'
                ]
            );

            $notificationId = $this->db->lastInsertId();

            // Log activity
            $this->logNotificationActivity($notificationId, 'created', $data);

            return [
                'success' => true,
                'message' => 'Tạo thông báo thành công',
                'notification_id' => $notificationId
            ];

        } catch (Exception $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi tạo thông báo: ' . $e->getMessage()];
        }
    }

    public function sendNotificationFromTemplate($templateName, $userId, $variables = []) {
        try {
            // Lấy template
            $template = $this->db->fetchOne(
                "SELECT * FROM notification_templates WHERE name = ? AND is_active = 1",
                [$templateName]
            );

            if (!$template) {
                return ['success' => false, 'message' => 'Template không tồn tại hoặc đã bị vô hiệu hóa'];
            }

            // Thay thế variables trong template
            $title = $this->replaceTemplateVariables($template['title_template'], $variables);
            $message = $this->replaceTemplateVariables($template['message_template'], $variables);

            // Tạo thông báo
            return $this->createNotification([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $template['type']
            ]);

        } catch (Exception $e) {
            error_log("Send notification from template failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi gửi thông báo từ template: ' . $e->getMessage()];
        }
    }

    public function sendBulkNotification($userIds, $data) {
        try {
            $successCount = 0;
            $errorCount = 0;
            $results = [];

            foreach ($userIds as $userId) {
                $result = $this->createNotification(array_merge($data, ['user_id' => $userId]));
                $results[] = $result;

                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }

            return [
                'success' => $errorCount === 0,
                'total' => count($userIds),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'results' => $results
            ];

        } catch (Exception $e) {
            error_log("Bulk notification failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi gửi thông báo hàng loạt: ' . $e->getMessage()];
        }
    }

    // ==================== NOTIFICATION RETRIEVAL ====================

    public function getNotifications($userId = null, $filters = [], $page = 1, $limit = 10) {
        try {
            $whereConditions = [];
            $params = [];

            if ($userId) {
                $whereConditions[] = "user_id = ?";
                $params[] = $userId;
            }

            // Apply filters
            if (isset($filters['status']) && $filters['status']) {
                $whereConditions[] = "status = ?";
                $params[] = $filters['status'];
            }

            if (isset($filters['type']) && $filters['type']) {
                $whereConditions[] = "type = ?";
                $params[] = $filters['type'];
            }

            if (isset($filters['unread_only']) && $filters['unread_only']) {
                $whereConditions[] = "status = 'unread'";
            }

            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM notifications $whereClause";
            $countResult = $this->db->fetchOne($countSql, $params);
            $total = $countResult['total'];

            // Get notifications
            $offset = ($page - 1) * $limit;
            $params[] = $limit;
            $params[] = $offset;

            $notifications = $this->db->fetchAll(
                "SELECT * FROM notifications $whereClause
                 ORDER BY created_at DESC
                 LIMIT ? OFFSET ?",
                $params
            );

            return [
                'notifications' => $notifications,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];

        } catch (Exception $e) {
            error_log("Get notifications failed: " . $e->getMessage());
            return ['notifications' => [], 'pagination' => ['page' => 1, 'limit' => 10, 'total' => 0, 'pages' => 0]];
        }
    }

    public function getNotificationById($notificationId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM notifications WHERE id = ?",
                [$notificationId]
            );
        } catch (Exception $e) {
            error_log("Get notification by ID failed: " . $e->getMessage());
            return null;
        }
    }

    public function getUnreadCount($userId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'",
                [$userId]
            );

            return $result ? $result['count'] : 0;

        } catch (Exception $e) {
            error_log("Get unread count failed: " . $e->getMessage());
            return 0;
        }
    }

    // ==================== NOTIFICATION STATUS MANAGEMENT ====================

    public function markAsRead($notificationId, $userId = null) {
        try {
            $whereConditions = ["id = ?"];
            $params = [$notificationId];

            if ($userId) {
                $whereConditions[] = "user_id = ?";
                $params[] = $userId;
            }

            $whereClause = "WHERE " . implode(" AND ", $whereConditions);

            $this->db->execute(
                "UPDATE notifications SET status = 'read', read_at = CURRENT_TIMESTAMP $whereClause",
                $params
            );

            return ['success' => true, 'message' => 'Đã đánh dấu đã đọc'];

        } catch (Exception $e) {
            error_log("Mark as read failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi đánh dấu đã đọc: ' . $e->getMessage()];
        }
    }

    public function markAllAsRead($userId) {
        try {
            $this->db->execute(
                "UPDATE notifications SET status = 'read', read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND status = 'unread'",
                [$userId]
            );

            return ['success' => true, 'message' => 'Đã đánh dấu tất cả đã đọc'];

        } catch (Exception $e) {
            error_log("Mark all as read failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi đánh dấu tất cả đã đọc: ' . $e->getMessage()];
        }
    }

    public function deleteNotification($notificationId, $userId = null) {
        try {
            $whereConditions = ["id = ?"];
            $params = [$notificationId];

            if ($userId) {
                $whereConditions[] = "user_id = ?";
                $params[] = $userId;
            }

            $whereClause = "WHERE " . implode(" AND ", $whereConditions);

            $this->db->execute("DELETE FROM notifications $whereClause", $params);

            return ['success' => true, 'message' => 'Xóa thông báo thành công'];

        } catch (Exception $e) {
            error_log("Delete notification failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi xóa thông báo: ' . $e->getMessage()];
        }
    }

    // ==================== TEMPLATE MANAGEMENT ====================

    public function createTemplate($data) {
        try {
            // Validation
            if (!isset($data['name']) || !isset($data['title_template']) || !isset($data['message_template'])) {
                return ['success' => false, 'message' => 'Thiếu thông tin template'];
            }

            // Kiểm tra tên template đã tồn tại
            $existingTemplate = $this->db->fetchOne(
                "SELECT id FROM notification_templates WHERE name = ?",
                [$data['name']]
            );

            if ($existingTemplate) {
                return ['success' => false, 'message' => 'Tên template đã tồn tại'];
            }

            // Tạo template
            $this->db->execute(
                "INSERT INTO notification_templates (name, title_template, message_template, type, variables, is_active, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['name'],
                    $data['title_template'],
                    $data['message_template'],
                    $data['type'] ?? 'info',
                    json_encode($data['variables'] ?? []),
                    $data['is_active'] ?? true,
                    $data['created_by'] ?? null
                ]
            );

            $templateId = $this->db->lastInsertId();

            return [
                'success' => true,
                'message' => 'Tạo template thành công',
                'template_id' => $templateId
            ];

        } catch (Exception $e) {
            error_log("Template creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi tạo template: ' . $e->getMessage()];
        }
    }

    public function updateTemplate($templateId, $data) {
        try {
            // Kiểm tra template tồn tại
            $existingTemplate = $this->db->fetchOne(
                "SELECT id FROM notification_templates WHERE id = ?",
                [$templateId]
            );

            if (!$existingTemplate) {
                return ['success' => false, 'message' => 'Template không tồn tại'];
            }

            // Build update fields
            $updateFields = [];
            $params = [];

            $fields = ['name', 'title_template', 'message_template', 'type', 'variables', 'is_active'];

            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $field === 'variables' ? json_encode($data[$field]) : $data[$field];
                }
            }

            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'Không có dữ liệu để cập nhật'];
            }

            $params[] = $templateId;

            $this->db->execute(
                "UPDATE notification_templates SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                $params
            );

            return [
                'success' => true,
                'message' => 'Cập nhật template thành công'
            ];

        } catch (Exception $e) {
            error_log("Template update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi cập nhật template: ' . $e->getMessage()];
        }
    }

    public function getTemplates($filters = [], $page = 1, $limit = 10) {
        try {
            $whereConditions = [];
            $params = [];

            // Apply filters
            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $whereConditions[] = "is_active = ?";
                $params[] = $filters['is_active'];
            }

            if (isset($filters['type']) && $filters['type']) {
                $whereConditions[] = "type = ?";
                $params[] = $filters['type'];
            }

            if (isset($filters['search']) && $filters['search']) {
                $whereConditions[] = "(name LIKE ? OR title_template LIKE ? OR message_template LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM notification_templates $whereClause";
            $countResult = $this->db->fetchOne($countSql, $params);
            $total = $countResult['total'];

            // Get templates
            $offset = ($page - 1) * $limit;
            $params[] = $limit;
            $params[] = $offset;

            $templates = $this->db->fetchAll(
                "SELECT * FROM notification_templates $whereClause
                 ORDER BY created_at DESC
                 LIMIT ? OFFSET ?",
                $params
            );

            return [
                'templates' => $templates,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];

        } catch (Exception $e) {
            error_log("Get templates failed: " . $e->getMessage());
            return ['templates' => [], 'pagination' => ['page' => 1, 'limit' => 10, 'total' => 0, 'pages' => 0]];
        }
    }

    public function getTemplateById($templateId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM notification_templates WHERE id = ?",
                [$templateId]
            );
        } catch (Exception $e) {
            error_log("Get template by ID failed: " . $e->getMessage());
            return null;
        }
    }

    public function deleteTemplate($templateId) {
        try {
            // Kiểm tra template tồn tại
            $existingTemplate = $this->db->fetchOne(
                "SELECT id FROM notification_templates WHERE id = ?",
                [$templateId]
            );

            if (!$existingTemplate) {
                return ['success' => false, 'message' => 'Template không tồn tại'];
            }

            // Xóa template
            $this->db->execute("DELETE FROM notification_templates WHERE id = ?", [$templateId]);

            return ['success' => true, 'message' => 'Xóa template thành công'];

        } catch (Exception $e) {
            error_log("Template deletion failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi xóa template: ' . $e->getMessage()];
        }
    }

    // ==================== NOTIFICATION STATISTICS ====================

    public function getNotificationStats($userId = null) {
        try {
            $whereConditions = [];
            $params = [];

            if ($userId) {
                $whereConditions[] = "user_id = ?";
                $params[] = $userId;
            }

            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

            $stats = [
                'total' => 0,
                'unread' => 0,
                'read' => 0,
                'by_type' => [],
                'recent' => []
            ];

            // Total count
            $totalResult = $this->db->fetchOne("SELECT COUNT(*) as total FROM notifications $whereClause", $params);
            $stats['total'] = $totalResult['total'];

            // Unread count
            $unreadResult = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM notifications $whereClause AND status = 'unread'",
                $params
            );
            $stats['unread'] = $unreadResult['total'];

            // Read count
            $stats['read'] = $stats['total'] - $stats['unread'];

            // By type
            $typeStats = $this->db->fetchAll(
                "SELECT type, COUNT(*) as count FROM notifications $whereClause GROUP BY type",
                $params
            );
            $stats['by_type'] = $typeStats;

            // Recent notifications
            $recentParams = $params;
            $recentParams[] = 5;
            $recentNotifications = $this->db->fetchAll(
                "SELECT * FROM notifications $whereClause ORDER BY created_at DESC LIMIT ?",
                $recentParams
            );
            $stats['recent'] = $recentNotifications;

            return $stats;

        } catch (Exception $e) {
            error_log("Get notification stats failed: " . $e->getMessage());
            return [
                'total' => 0,
                'unread' => 0,
                'read' => 0,
                'by_type' => [],
                'recent' => []
            ];
        }
    }

    // ==================== UTILITY FUNCTIONS ====================

    private function replaceTemplateVariables($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }

    private function logNotificationActivity($notificationId, $action, $data) {
        try {
            $this->db->execute(
                "INSERT INTO audit_logs (action, table_name, record_id, new_values, created_at)
                 VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)",
                [
                    $action,
                    'notifications',
                    $notificationId,
                    json_encode($data)
                ]
            );

            return true;

        } catch (Exception $e) {
            error_log("Notification activity logging failed: " . $e->getMessage());
            return false;
        }
    }
}

// Helper function để sử dụng notification service
function notification() {
    return new NotificationService();
}
?>
