<?php
// upload.php - Service xử lý upload file
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

class UploadService {
    private $db;
    private $uploadDir;
    private $maxFileSize;
    private $allowedTypes;

    public function __construct() {
        $this->db = db();
        $this->uploadDir = UPLOAD_DIR;
        $this->maxFileSize = MAX_FILE_SIZE;
        $this->allowedTypes = explode(',', ALLOWED_FILE_TYPES);

        // Tạo thư mục upload nếu chưa tồn tại
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    // ==================== UPLOAD FILE ====================

    public function uploadFile($file, $category = 'general', $description = '', $tags = '') {
        try {
            // Kiểm tra file
            if (!$this->validateFile($file)) {
                return ['success' => false, 'message' => 'File không hợp lệ'];
            }

            // Tạo tên file duy nhất
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $this->generateUniqueFilename($extension);
            $filepath = $this->uploadDir . $filename;

            // Upload file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return ['success' => false, 'message' => 'Không thể upload file'];
            }

            // Lưu thông tin vào database
            $fileId = $this->saveFileInfo($file, $filename, $category, $description, $tags);

            if (!$fileId) {
                // Xóa file nếu lưu database thất bại
                unlink($filepath);
                return ['success' => false, 'message' => 'Không thể lưu thông tin file'];
            }

            return [
                'success' => true,
                'message' => 'Upload file thành công',
                'file_id' => $fileId,
                'filename' => $filename,
                'url' => $this->getFileUrl($filename)
            ];

        } catch (Exception $e) {
            error_log("Upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi upload file'];
        }
    }

    // ==================== VALIDATION ====================

    private function validateFile($file) {
        // Kiểm tra file có tồn tại không
        if (!isset($file) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }

        // Kiểm tra kích thước
        if ($file['size'] > $this->maxFileSize) {
            return false;
        }

        // Kiểm tra loại file
        $fileType = $this->getFileType($file['name']);
        if (!$this->isAllowedType($fileType)) {
            return false;
        }

        // Kiểm tra lỗi upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        return true;
    }

    private function isAllowedType($fileType) {
        foreach ($this->allowedTypes as $allowedType) {
            if (strpos($fileType, trim($allowedType)) === 0) {
                return true;
            }
        }
        return false;
    }

    private function getFileType($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $typeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed'
        ];

        return $typeMap[$extension] ?? 'application/octet-stream';
    }

    // ==================== FILE MANAGEMENT ====================

    private function generateUniqueFilename($extension) {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return "{$timestamp}_{$random}.{$extension}";
    }

    private function saveFileInfo($file, $filename, $category, $description, $tags) {
        try {
            $sql = "INSERT INTO uploads (original_name, filename, file_path, file_type, file_size, category, description, tags, uploaded_by, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $this->db->execute($sql, [
                $file['name'],
                $filename,
                $this->uploadDir . $filename,
                $this->getFileType($file['name']),
                $file['size'],
                $category,
                $description,
                $tags,
                $this->getCurrentUserId()
            ]);

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Failed to save file info: " . $e->getMessage());
            return false;
        }
    }

    public function getFileList($category = null, $search = null, $page = 1, $limit = 20) {
        try {
            $where = [];
            $params = [];
            $offset = ($page - 1) * $limit;

            if ($category) {
                $where[] = "category = ?";
                $params[] = $category;
            }

            if ($search) {
                $where[] = "(original_name LIKE ? OR description LIKE ? OR tags LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $sql = "SELECT * FROM uploads {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $files = $this->db->fetchAll($sql, $params);

            // Thêm URL cho mỗi file
            foreach ($files as &$file) {
                $file['url'] = $this->getFileUrl($file['filename']);
            }

            return [
                'success' => true,
                'files' => $files
            ];

        } catch (Exception $e) {
            error_log("Failed to get file list: " . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể lấy danh sách file'];
        }
    }

    public function getFileById($fileId) {
        try {
            $file = $this->db->fetchOne(
                "SELECT * FROM uploads WHERE id = ?",
                [$fileId]
            );

            if (!$file) {
                return ['success' => false, 'message' => 'File không tồn tại'];
            }

            $file['url'] = $this->getFileUrl($file['filename']);

            return [
                'success' => true,
                'file' => $file
            ];

        } catch (Exception $e) {
            error_log("Failed to get file: " . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể lấy thông tin file'];
        }
    }

    public function deleteFile($fileId) {
        try {
            // Lấy thông tin file
            $file = $this->db->fetchOne(
                "SELECT * FROM uploads WHERE id = ?",
                [$fileId]
            );

            if (!$file) {
                return ['success' => false, 'message' => 'File không tồn tại'];
            }

            // Kiểm tra quyền xóa
            if (!$this->canDeleteFile($file)) {
                return ['success' => false, 'message' => 'Không có quyền xóa file này'];
            }

            // Xóa file vật lý
            $filepath = $this->uploadDir . $file['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            // Xóa record trong database
            $this->db->execute(
                "DELETE FROM uploads WHERE id = ?",
                [$fileId]
            );

            return [
                'success' => true,
                'message' => 'Xóa file thành công'
            ];

        } catch (Exception $e) {
            error_log("Failed to delete file: " . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể xóa file'];
        }
    }

    public function updateFileInfo($fileId, $data) {
        try {
            $updateFields = [];
            $params = [];

            if (isset($data['category'])) {
                $updateFields[] = "category = ?";
                $params[] = $data['category'];
            }

            if (isset($data['description'])) {
                $updateFields[] = "description = ?";
                $params[] = $data['description'];
            }

            if (isset($data['tags'])) {
                $updateFields[] = "tags = ?";
                $params[] = $data['tags'];
            }

            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'Không có dữ liệu để cập nhật'];
            }

            $updateFields[] = "updated_at = NOW()";
            $params[] = $fileId;

            $sql = "UPDATE uploads SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $this->db->execute($sql, $params);

            return [
                'success' => true,
                'message' => 'Cập nhật thông tin file thành công'
            ];

        } catch (Exception $e) {
            error_log("Failed to update file info: " . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể cập nhật thông tin file'];
        }
    }

    // ==================== UTILITY FUNCTIONS ====================

    private function getFileUrl($filename) {
        return getUploadUrl($filename);
    }

    private function getCurrentUserId() {
        // Lấy user ID từ session hoặc JWT token
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }

        // TODO: Implement JWT token parsing
        return 1; // Default admin user
    }

    private function canDeleteFile($file) {
        // Kiểm tra quyền xóa file
        $currentUserId = $this->getCurrentUserId();

        // Admin có thể xóa tất cả file
        if ($this->isAdmin()) {
            return true;
        }

        // User chỉ có thể xóa file của mình
        return $file['uploaded_by'] == $currentUserId;
    }

    private function isAdmin() {
        return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin']);
    }

    // ==================== STATISTICS ====================

    public function getUploadStats() {
        try {
            $stats = [];

            // Tổng số file
            $totalFiles = $this->db->fetchOne("SELECT COUNT(*) as count FROM uploads");
            $stats['total_files'] = $totalFiles['count'];

            // Tổng dung lượng
            $totalSize = $this->db->fetchOne("SELECT SUM(file_size) as total_size FROM uploads");
            $stats['total_size'] = $totalSize['total_size'] ?? 0;

            // Số file theo danh mục
            $categoryStats = $this->db->fetchAll(
                "SELECT category, COUNT(*) as count FROM uploads GROUP BY category"
            );
            $stats['by_category'] = $categoryStats;

            // Số file theo loại
            $typeStats = $this->db->fetchAll(
                "SELECT file_type, COUNT(*) as count FROM uploads GROUP BY file_type"
            );
            $stats['by_type'] = $typeStats;

            return [
                'success' => true,
                'stats' => $stats
            ];

        } catch (Exception $e) {
            error_log("Failed to get upload stats: " . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể lấy thống kê upload'];
        }
    }

    // ==================== CLEANUP ====================

    public function cleanupOrphanedFiles() {
        try {
            $count = 0;

            // Lấy danh sách file trong database
            $dbFiles = $this->db->fetchAll("SELECT filename FROM uploads");
            $dbFilenames = array_column($dbFiles, 'filename');

            // Quét thư mục upload
            $uploadFiles = scandir($this->uploadDir);

            foreach ($uploadFiles as $file) {
                if ($file === '.' || $file === '..') continue;

                // Nếu file không có trong database, xóa
                if (!in_array($file, $dbFilenames)) {
                    $filepath = $this->uploadDir . $file;
                    if (unlink($filepath)) {
                        $count++;
                    }
                }
            }

            return [
                'success' => true,
                'message' => "Đã dọn dẹp {$count} file orphaned"
            ];

        } catch (Exception $e) {
            error_log("Failed to cleanup orphaned files: " . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể dọn dẹp file orphaned'];
        }
    }
}

// Helper function để sử dụng upload service
function upload() {
    return new UploadService();
}
?>
