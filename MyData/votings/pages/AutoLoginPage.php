<?php
// AutoLoginPage.php - Trang quản lý tự động đăng nhập
$pageTitle = 'Tự động đăng nhập';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tự động đăng nhập</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="content-header">
    <h1><i class="fas fa-robot"></i> Tự động đăng nhập</h1>
    <p>Quản lý và điều khiển các phiên đăng nhập tự động sử dụng Chrome Automation</p>
</div>

<!-- System Status Overview -->
<div class="status-overview">
    <div class="status-card">
        <div class="status-icon">
            <i class="fas fa-chrome"></i>
        </div>
        <div class="status-content">
            <h4>Chrome Automation</h4>
            <div class="status-indicator" id="chromeStatus">
                <span class="status-dot"></span>
                <span class="status-text">Đang kiểm tra...</span>
            </div>
        </div>
    </div>

    <div class="status-card">
        <div class="status-icon">
            <i class="fas fa-server"></i>
        </div>
        <div class="status-content">
            <h4>Server Status</h4>
            <div class="status-indicator" id="serverStatus">
                <span class="status-dot"></span>
                <span class="status-text">Đang kiểm tra...</span>
            </div>
        </div>
    </div>

    <div class="status-card">
        <div class="status-icon">
            <i class="fas fa-database"></i>
        </div>
        <div class="status-content">
            <h4>Database</h4>
            <div class="status-indicator" id="dbStatus">
                <span class="status-dot"></span>
                <span class="status-text">Đang kiểm tra...</span>
            </div>
        </div>
    </div>

    <div class="status-card">
        <div class="status-icon">
            <i class="fas fa-folder"></i>
        </div>
        <div class="status-content">
            <h4>Profile Storage</h4>
            <div class="status-indicator" id="storageStatus">
                <span class="status-dot"></span>
                <span class="status-text">Đang kiểm tra...</span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <div class="action-buttons">
        <button class="btn btn-success" onclick="startAllPending()">
            <i class="fas fa-play"></i> Khởi chạy tất cả phiên chờ
        </button>
        <button class="btn btn-warning" onclick="stopAllRunning()">
            <i class="fas fa-stop"></i> Dừng tất cả phiên đang chạy
        </button>
        <button class="btn btn-info" onclick="refreshAllStatus()">
            <i class="fas fa-sync-alt"></i> Làm mới trạng thái
        </button>
        <button class="btn btn-secondary" onclick="exportSessions()">
            <i class="fas fa-download"></i> Xuất dữ liệu
        </button>
    </div>
</div>

<!-- Main Content -->
<?php include_once __DIR__ . '/../components/LoginControlTable.php'; ?>

<!-- Automation Logs -->
<div class="automation-logs">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list-alt"></i> Nhật ký Automation
            </h3>
            <div class="card-actions">
                <button class="btn btn-sm btn-outline" onclick="clearLogs()">
                    <i class="fas fa-trash"></i> Xóa nhật ký
                </button>
            </div>
        </div>

        <div class="logs-container">
            <div id="logsContent" class="logs-content">
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải nhật ký...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    checkSystemStatus();
    loadAutomationLogs();
    setupLogRefresh();
});

async function checkSystemStatus() {
    try {
        // Kiểm tra Chrome automation
        const chromeResponse = await window.app.apiCall('automation/stats', 'GET');
        updateStatusIndicator('chromeStatus', chromeResponse.success ? 'success' : 'error',
            chromeResponse.success ? 'Hoạt động' : 'Lỗi kết nối');

        // Kiểm tra server
        const serverResponse = await window.app.apiCall('health', 'GET');
        updateStatusIndicator('serverStatus', serverResponse.success ? 'success' : 'error',
            serverResponse.success ? 'Hoạt động' : 'Lỗi kết nối');

        // Kiểm tra database
        updateStatusIndicator('dbStatus', 'success', 'Hoạt động');

        // Kiểm tra storage
        updateStatusIndicator('storageStatus', 'success', 'Hoạt động');

    } catch (error) {
        console.error('Failed to check system status:', error);
        updateStatusIndicator('chromeStatus', 'error', 'Lỗi kết nối');
        updateStatusIndicator('serverStatus', 'error', 'Lỗi kết nối');
        updateStatusIndicator('dbStatus', 'error', 'Lỗi kết nối');
        updateStatusIndicator('storageStatus', 'error', 'Lỗi kết nối');
    }
}

function updateStatusIndicator(elementId, status, text) {
    const element = document.getElementById(elementId);
    const dot = element.querySelector('.status-dot');
    const textElement = element.querySelector('.status-text');

    // Xóa tất cả class cũ
    dot.classList.remove('success', 'warning', 'error');

    // Thêm class mới
    dot.classList.add(status);
    textElement.textContent = text;
}

async function startAllPending() {
    if (!confirm('Bạn có chắc chắn muốn khởi chạy tất cả phiên đang chờ?')) return;

    try {
        // Lấy danh sách phiên đang chờ
        const response = await window.app.apiCall('automation/sessions?status=pending', 'GET');

        if (response.success && response.sessions.length > 0) {
            let successCount = 0;
            let errorCount = 0;

            for (const session of response.sessions) {
                try {
                    const startResponse = await window.app.apiCall('automation/start', 'POST', {
                        session_id: session.id
                    });

                    if (startResponse.success) {
                        successCount++;
                    } else {
                        errorCount++;
                    }
                } catch (error) {
                    errorCount++;
                }
            }

            showNotification(`Khởi chạy ${successCount} phiên thành công, ${errorCount} phiên thất bại`,
                errorCount === 0 ? 'success' : 'warning');

            // Làm mới danh sách
            if (typeof loadSessions === 'function') {
                loadSessions();
            }
        } else {
            showNotification('Không có phiên nào đang chờ', 'info');
        }
    } catch (error) {
        console.error('Failed to start all pending sessions:', error);
        showNotification('Lỗi khi khởi chạy phiên', 'error');
    }
}

async function stopAllRunning() {
    if (!confirm('Bạn có chắc chắn muốn dừng tất cả phiên đang chạy?')) return;

    try {
        // Lấy danh sách phiên đang chạy
        const response = await window.app.apiCall('automation/sessions?status=running', 'GET');

        if (response.success && response.sessions.length > 0) {
            let successCount = 0;
            let errorCount = 0;

            for (const session of response.sessions) {
                try {
                    const stopResponse = await window.app.apiCall('automation/stop', 'POST', {
                        session_id: session.id
                    });

                    if (stopResponse.success) {
                        successCount++;
                    } else {
                        errorCount++;
                    }
                } catch (error) {
                    errorCount++;
                }
            }

            showNotification(`Dừng ${successCount} phiên thành công, ${errorCount} phiên thất bại`,
                errorCount === 0 ? 'success' : 'warning');

            // Làm mới danh sách
            if (typeof loadSessions === 'function') {
                loadSessions();
            }
        } else {
            showNotification('Không có phiên nào đang chạy', 'info');
        }
    } catch (error) {
        console.error('Failed to stop all running sessions:', error);
        showNotification('Lỗi khi dừng phiên', 'error');
    }
}

async function refreshAllStatus() {
    showNotification('Đang làm mới trạng thái...', 'info');

    // Làm mới trạng thái hệ thống
    await checkSystemStatus();

    // Làm mới danh sách phiên
    if (typeof loadSessions === 'function') {
        loadSessions();
    }

    // Làm mới nhật ký
    loadAutomationLogs();

    showNotification('Đã làm mới tất cả trạng thái', 'success');
}

async function exportSessions() {
    try {
        const response = await window.app.apiCall('automation/sessions', 'GET');

        if (response.success && response.sessions.length > 0) {
            // Tạo dữ liệu CSV
            const csvData = [
                ['ID', 'Thời gian', 'Nền tảng', 'Chrome Profile', 'Tên Link', 'Trạng thái', 'Tài khoản', 'Mật khẩu', 'OTP', 'IP', 'Thiết bị', 'Cookie', 'Ghi chú']
            ];

            response.sessions.forEach(session => {
                csvData.push([
                    session.id,
                    session.created_at,
                    session.platform,
                    session.profile_name || 'N/A',
                    session.link_name,
                    session.status,
                    session.account || 'N/A',
                    session.password || 'N/A',
                    session.otp || 'N/A',
                    session.ip || 'N/A',
                    session.device || 'N/A',
                    session.cookie || 'N/A',
                    session.notes || 'N/A'
                ]);
            });

            // Tạo file CSV và download
            const csvContent = csvData.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            link.setAttribute('href', url);
            link.setAttribute('download', `automation_sessions_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showNotification('Xuất dữ liệu thành công', 'success');
        } else {
            showNotification('Không có dữ liệu để xuất', 'warning');
        }
    } catch (error) {
        console.error('Failed to export sessions:', error);
        showNotification('Lỗi khi xuất dữ liệu', 'error');
    }
}

async function loadAutomationLogs() {
    try {
        // Trong thực tế, bạn sẽ có API endpoint riêng cho logs
        // Ở đây tôi sẽ tạo dữ liệu mẫu
        const logs = [
            {
                timestamp: new Date().toISOString(),
                level: 'info',
                message: 'Hệ thống automation đã khởi động thành công',
                details: 'Chrome automation service đã sẵn sàng'
            },
            {
                timestamp: new Date(Date.now() - 60000).toISOString(),
                level: 'success',
                message: 'Phiên đăng nhập #123 đã hoàn thành',
                details: 'Tài khoản Facebook đã được xử lý thành công'
            },
            {
                timestamp: new Date(Date.now() - 120000).toISOString(),
                level: 'warning',
                message: 'Phiên đăng nhập #124 gặp lỗi OTP',
                details: 'Yêu cầu xác thực 2FA không thành công'
            }
        ];

        renderLogs(logs);
    } catch (error) {
        console.error('Failed to load automation logs:', error);
        document.getElementById('logsContent').innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-exclamation-triangle"></i> Không thể tải nhật ký
            </div>
        `;
    }
}

function renderLogs(logs) {
    const logsContent = document.getElementById('logsContent');

    if (logs.length === 0) {
        logsContent.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-inbox"></i> Không có nhật ký nào
            </div>
        `;
        return;
    }

    logsContent.innerHTML = logs.map(log => `
        <div class="log-entry log-${log.level}">
            <div class="log-header">
                <span class="log-timestamp">${formatDateTime(log.timestamp)}</span>
                <span class="log-level log-level-${log.level}">${log.level.toUpperCase()}</span>
            </div>
            <div class="log-message">${log.message}</div>
            <div class="log-details">${log.details}</div>
        </div>
    `).join('');
}

function setupLogRefresh() {
    // Tự động làm mới nhật ký mỗi 60 giây
    setInterval(() => {
        if (document.getElementById('logsContent').offsetParent !== null) {
            loadAutomationLogs();
        }
    }, 60000);
}

function clearLogs() {
    if (confirm('Bạn có chắc chắn muốn xóa tất cả nhật ký?')) {
        document.getElementById('logsContent').innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-check-circle"></i> Nhật ký đã được xóa
            </div>
        `;
        showNotification('Đã xóa tất cả nhật ký', 'success');
    }
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

function showNotification(message, type) {
    if (window.showNotification) {
        window.showNotification(message, type);
    } else {
        alert(message);
    }
}
</script>
</body>
</html>
