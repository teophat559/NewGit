<?php
/**
 * Auto Login Management Page - BVOTE Admin
 * Quản lý yêu cầu đăng nhập tự động với realtime updates
 */
session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../../../includes/database.php';
$db = getConnection();

// Xử lý actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);

    switch ($action) {
        case 'approve':
            $stmt = $db->prepare("
                UPDATE login_requests
                SET status = 'APPROVED', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$requestId]);

            // Tạo session cho user
            $stmt = $db->prepare("SELECT user_hint, platform FROM login_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if ($request) {
                // Tạo hoặc cập nhật user
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, platform, status, last_login)
                    VALUES (?, ?, ?, 'active', NOW())
                    ON DUPLICATE KEY UPDATE
                    last_login = NOW(), status = 'active'
                ");
                $stmt->execute([$request['user_hint'], $request['user_hint'], $request['platform']]);

                $userId = $db->lastInsertId() ?: $db->query("SELECT id FROM users WHERE username = '{$request['user_hint']}'")->fetch()['id'];

                // Tạo session
                $sessionToken = bin2hex(random_bytes(32));
                $stmt = $db->prepare("
                    INSERT INTO auth_sessions (user_id, token_hash, ip, ua, issued_at, expires_at)
                    VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR))
                ");
                $stmt->execute([$userId, hash('sha256', $sessionToken), $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
            }

            // Ghi audit log
            $stmt = $db->prepare("
                INSERT INTO audit_logs (actor_type, actor_id, action, details_json)
                VALUES ('admin', ?, 'approve_login', ?)
            ");
            $stmt->execute([$_SESSION['admin_id'], json_encode(['request_id' => $requestId, 'platform' => $request['platform']])]);

            header('Location: /admin/auto-login/management?success=approved');
            exit;

        case 'reject':
            $reason = $_POST['reason'] ?? 'Không có lý do cụ thể';

            $stmt = $db->prepare("
                UPDATE login_requests
                SET status = 'REJECTED', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$requestId]);

            // Ghi audit log
            $stmt = $db->prepare("
                INSERT INTO audit_logs (actor_type, actor_id, action, details_json)
                VALUES ('admin', ?, 'reject_login', ?)
            ");
            $stmt->execute([$_SESSION['admin_id'], json_encode(['request_id' => $requestId, 'reason' => $reason])]);

            header('Location: /admin/auto-login/management?success=rejected');
            exit;

        case 'require_otp':
            $otpLength = (int)($_POST['otp_length'] ?? 6);
            $otpRetries = (int)($_POST['otp_retries'] ?? 3);

            // Tạo OTP
            $otp = str_pad(rand(0, pow(10, $otpLength) - 1), $otpLength, '0', STR_PAD_LEFT);

            $stmt = $db->prepare("
                UPDATE login_requests
                SET status = 'OTP_REQUIRED', otp_required = 1, otp_length = ?,
                    otp_retries = ?, otp_code = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$otpLength, $otpRetries, $otp, $requestId]);

            // Ghi audit log
            $stmt = $db->prepare("
                INSERT INTO audit_logs (actor_type, actor_id, action, details_json)
                VALUES ('admin', ?, 'require_otp', ?)
            ");
            $stmt->execute([$_SESSION['admin_id'], json_encode(['request_id' => $requestId, 'otp_length' => $otpLength])]);

            header('Location: /admin/auto-login/management?success=otp_required');
            exit;
    }
}

// Lấy danh sách yêu cầu với filter
$status = $_GET['status'] ?? '';
$platform = $_GET['platform'] ?? '';
$search = $_GET['search'] ?? '';

$where = "WHERE 1=1";
$params = [];

if ($status) {
    $where .= " AND status = ?";
    $params[] = $status;
}

if ($platform) {
    $where .= " AND platform = ?";
    $params[] = $platform;
}

if ($search) {
    $where .= " AND user_hint LIKE ?";
    $params[] = "%$search%";
}

$stmt = $db->prepare("
    SELECT lr.*,
           TIMESTAMPDIFF(SECOND, NOW(), lr.ttl_expires_at) as ttl_remaining,
           CASE
               WHEN lr.status = 'PENDING_REVIEW' AND lr.ttl_expires_at < NOW() THEN 'EXPIRED'
               ELSE lr.status
           END as display_status
    FROM login_requests lr
    $where
    ORDER BY lr.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Thống kê theo ngày
$stmt = $db->prepare("
    SELECT
        DATE(created_at) as date,
        COUNT(*) as total_requests,
        COUNT(CASE WHEN status = 'APPROVED' THEN 1 END) as approved,
        COUNT(CASE WHEN status = 'REJECTED' THEN 1 END) as rejected,
        COUNT(CASE WHEN status = 'OTP_REQUIRED' THEN 1 END) as otp_required,
        AVG(CASE WHEN status IN ('APPROVED', 'REJECTED') THEN TIMESTAMPDIFF(SECOND, created_at, updated_at) END) as avg_response_time
    FROM login_requests
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$stmt->execute();
$dailyStats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Auto Login - BVOTE Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-white text-lg font-bold">BV</span>
                    </div>
                    <h1 class="ml-3 text-xl font-semibold text-gray-900">BVOTE Admin</h1>
                </div>

                <nav class="flex space-x-8">
                    <a href="/admin/dashboard" class="text-gray-500 hover:text-gray-700 px-3 py-2">Dashboard</a>
                    <a href="/admin/auto-login/management" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2">Auto Login</a>
                    <a href="/admin/contest-management/contests" class="text-gray-500 hover:text-gray-700 px-3 py-2">Cuộc thi</a>
                    <a href="/admin/user-management/appearance" class="text-gray-500 hover:text-gray-700 px-3 py-2">Người dùng</a>
                    <a href="/admin/settings/web-config" class="text-gray-500 hover:text-gray-700 px-3 py-2">Cài đặt</a>
                </nav>

                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Admin</span>
                    <a href="/admin/logout" class="text-red-600 hover:text-red-800">Đăng xuất</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Quản lý Auto Login</h2>
                <p class="text-gray-600">Xem và xử lý yêu cầu đăng nhập tự động</p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-500">Auto-refresh: <span id="refreshTimer">30</span>s</span>
                <button onclick="location.reload()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>Làm mới
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <?php
            $totalRequests = count($requests);
            $pendingRequests = count(array_filter($requests, fn($r) => $r['display_status'] === 'PENDING_REVIEW'));
            $otpRequests = count(array_filter($requests, fn($r) => $r['display_status'] === 'OTP_REQUIRED'));
            $expiredRequests = count(array_filter($requests, fn($r) => $r['display_status'] === 'EXPIRED'));
            ?>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Chờ phê duyệt</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $pendingRequests; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-key text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Yêu cầu OTP</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $otpRequests; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Hết hạn</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $expiredRequests; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-gray-100 text-gray-600">
                        <i class="fas fa-list text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tổng yêu cầu</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $totalRequests; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select name="status" class="border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Tất cả</option>
                        <option value="PENDING_REVIEW" <?php echo $status === 'PENDING_REVIEW' ? 'selected' : ''; ?>>Chờ phê duyệt</option>
                        <option value="OTP_REQUIRED" <?php echo $status === 'OTP_REQUIRED' ? 'selected' : ''; ?>>Yêu cầu OTP</option>
                        <option value="APPROVED" <?php echo $status === 'APPROVED' ? 'selected' : ''; ?>>Đã phê duyệt</option>
                        <option value="REJECTED" <?php echo $status === 'REJECTED' ? 'selected' : ''; ?>>Đã từ chối</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nền tảng</label>
                    <select name="platform" class="border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Tất cả</option>
                        <option value="facebook" <?php echo $platform === 'facebook' ? 'selected' : ''; ?>>Facebook</option>
                        <option value="google" <?php echo $platform === 'google' ? 'selected' : ''; ?>>Google</option>
                        <option value="instagram" <?php echo $platform === 'instagram' ? 'selected' : ''; ?>>Instagram</option>
                        <option value="zalo" <?php echo $platform === 'zalo' ? 'selected' : ''; ?>>Zalo</option>
                        <option value="yahoo" <?php echo $platform === 'yahoo' ? 'selected' : ''; ?>>Yahoo</option>
                        <option value="microsoft" <?php echo $platform === 'microsoft' ? 'selected' : ''; ?>>Microsoft</option>
                        <option value="email" <?php echo $platform === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="apple" <?php echo $platform === 'apple' ? 'selected' : ''; ?>>Apple</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Tên/Email..."
                           class="border border-gray-300 rounded-md px-3 py-2">
                </div>

                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </button>

                <a href="/admin/auto-login/management" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-2"></i>Xóa filter
                </a>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thông tin</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nền tảng</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TTL</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($requests as $request): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($request['user_hint']); ?></div>
                                    <div class="text-sm text-gray-500">ID: <?php echo $request['id']; ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mr-2">
                                        <i class="fas fa-<?php echo getPlatformIcon($request['platform']); ?> text-gray-600"></i>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 capitalize"><?php echo $request['platform']; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <div>Tạo: <?php echo date('H:i:s', strtotime($request['created_at'])); ?></div>
                                    <div>Ngày: <?php echo date('d/m/Y', strtotime($request['created_at'])); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo getStatusBadge($request['display_status']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($request['ttl_remaining'] > 0): ?>
                                    <div class="text-sm text-gray-900">
                                        <div class="font-semibold text-green-600"><?php echo formatTime($request['ttl_remaining']); ?></div>
                                        <div class="text-xs text-gray-500">còn lại</div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-red-600 font-semibold">Hết hạn</div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($request['display_status'] === 'PENDING_REVIEW'): ?>
                                    <div class="flex space-x-2">
                                        <button onclick="showActionModal('approve', <?php echo $request['id']; ?>)"
                                                class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition-colors">
                                            <i class="fas fa-check mr-1"></i>Phê duyệt
                                        </button>
                                        <button onclick="showActionModal('reject', <?php echo $request['id']; ?>)"
                                                class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition-colors">
                                            <i class="fas fa-times mr-1"></i>Từ chối
                                        </button>
                                        <button onclick="showActionModal('otp', <?php echo $request['id']; ?>)"
                                                class="bg-yellow-600 text-white px-3 py-1 rounded text-sm hover:bg-yellow-700 transition-colors">
                                            <i class="fas fa-key mr-1"></i>Yêu cầu OTP
                                        </button>
                                    </div>
                                <?php elseif ($request['display_status'] === 'OTP_REQUIRED'): ?>
                                    <div class="text-sm text-gray-500">
                                        OTP: <?php echo $request['otp_code']; ?><br>
                                        Còn: <?php echo $request['otp_retries']; ?> lần
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Daily Stats -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mt-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Thống kê 7 ngày gần nhất</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng yêu cầu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phê duyệt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Từ chối</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Yêu cầu OTP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian xử lý TB</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($dailyStats as $stat): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo date('d/m/Y', strtotime($stat['date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $stat['total_requests']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                                <?php echo $stat['approved']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                                <?php echo $stat['rejected']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600 font-semibold">
                                <?php echo $stat['otp_required']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo round($stat['avg_response_time'] ?? 0, 1); ?>s
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Success Messages -->
        <?php if (isset($_GET['success'])): ?>
        <div class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <?php
            $message = '';
            switch ($_GET['success']) {
                case 'approved': $message = 'Yêu cầu đã được phê duyệt!'; break;
                case 'rejected': $message = 'Yêu cầu đã bị từ chối!'; break;
                case 'otp_required': $message = 'Đã yêu cầu OTP!'; break;
            }
            ?>
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?php echo $message; ?></span>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Action Modal -->
    <div id="actionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900 mb-4">Thao tác</h3>

                <form id="actionForm" method="POST">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="request_id" id="requestId">

                    <div id="rejectReasonField" class="hidden mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lý do từ chối</label>
                        <textarea name="reason" rows="3"
                                  class="w-full border border-gray-300 rounded-md px-3 py-2"
                                  placeholder="Nhập lý do từ chối..."></textarea>
                    </div>

                    <div id="otpFields" class="hidden mb-4 space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Độ dài OTP</label>
                            <select name="otp_length" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="6">6 số</option>
                                <option value="8">8 số</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số lần nhập sai tối đa</label>
                            <select name="otp_retries" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="3">3 lần</option>
                                <option value="5">5 lần</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="hideModal()"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                            Hủy
                        </button>
                        <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            Xác nhận
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let refreshCountdown = 30;

        function showActionModal(action, requestId) {
            document.getElementById('formAction').value = action;
            document.getElementById('requestId').value = requestId;

            // Ẩn tất cả field
            document.getElementById('rejectReasonField').classList.add('hidden');
            document.getElementById('otpFields').classList.add('hidden');

            // Hiển thị field tương ứng
            if (action === 'reject') {
                document.getElementById('modalTitle').textContent = 'Từ chối yêu cầu';
                document.getElementById('rejectReasonField').classList.remove('hidden');
            } else if (action === 'otp') {
                document.getElementById('modalTitle').textContent = 'Yêu cầu OTP';
                document.getElementById('otpFields').classList.remove('hidden');
            } else {
                document.getElementById('modalTitle').textContent = 'Phê duyệt yêu cầu';
            }

            document.getElementById('actionModal').classList.remove('hidden');
        }

        function hideModal() {
            document.getElementById('actionModal').classList.add('hidden');
        }

        // Auto-refresh countdown
        setInterval(() => {
            refreshCountdown--;
            document.getElementById('refreshTimer').textContent = refreshCountdown;

            if (refreshCountdown <= 0) {
                location.reload();
            }
        }, 1000);

        // Auto-hide success messages
        setTimeout(() => {
            const messages = document.querySelectorAll('.fixed');
            messages.forEach(msg => msg.remove());
        }, 5000);

        // Close modal when clicking outside
        document.getElementById('actionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideModal();
            }
        });
    </script>
</body>
</html>

<?php
function getPlatformIcon($platform) {
    $icons = [
        'facebook' => 'facebook',
        'google' => 'google',
        'instagram' => 'instagram',
        'zalo' => 'comment',
        'yahoo' => 'envelope',
        'microsoft' => 'windows',
        'email' => 'envelope',
        'apple' => 'apple'
    ];
    return $icons[$platform] ?? 'user';
}

function getStatusBadge($status) {
    $configs = [
        'PENDING_REVIEW' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => 'Chờ phê duyệt'],
        'OTP_REQUIRED' => ['class' => 'bg-blue-100 text-blue-800', 'text' => 'Yêu cầu OTP'],
        'APPROVED' => ['class' => 'bg-green-100 text-green-800', 'text' => 'Đã phê duyệt'],
        'REJECTED' => ['class' => 'bg-red-100 text-red-800', 'text' => 'Đã từ chối'],
        'EXPIRED' => ['class' => 'bg-gray-100 text-gray-800', 'text' => 'Hết hạn']
    ];

    $config = $configs[$status] ?? $configs['PENDING_REVIEW'];

    return sprintf(
        '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full %s">%s</span>',
        $config['class'],
        $config['text']
    );
}

function formatTime($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }
}
?>
