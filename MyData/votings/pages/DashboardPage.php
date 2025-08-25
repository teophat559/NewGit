<?php
// DashboardPage.php - Trang bảng điều khiển chính
$pageTitle = 'Bảng Điều Khiển';
?>

<div class="content-header">
    <h1><i class="fas fa-tachometer-alt"></i> Bảng Điều Khiển</h1>
    <p>Chào mừng bạn đến với hệ thống quản lý cuộc thi</p>
</div>

<!-- Stats Cards -->
<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card">
        <div class="card-body" style="text-align: center;">
            <div class="stat-icon" style="font-size: 3rem; color: #667eea; margin-bottom: 15px;">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-number" id="totalContests" style="font-size: 2.5rem; font-weight: bold; color: #2c3e50; margin-bottom: 10px;">-</div>
            <div class="stat-label" style="color: #7f8c8d; font-size: 1.1rem;">Tổng số cuộc thi</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body" style="text-align: center;">
            <div class="stat-icon" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number" id="totalUsers" style="font-size: 2.5rem; font-weight: bold; color: #2c3e50; margin-bottom: 10px;">-</div>
            <div class="stat-label" style="color: #7f8c8d; font-size: 1.1rem;">Tổng số người dùng</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body" style="text-align: center;">
            <div class="stat-icon" style="font-size: 3rem; color: #ffc107; margin-bottom: 15px;">
                <i class="fas fa-vote-yea"></i>
            </div>
            <div class="stat-number" id="totalVotes" style="font-size: 2.5rem; font-weight: bold; color: #2c3e50; margin-bottom: 10px;">-</div>
            <div class="stat-label" style="color: #7f8c8d; font-size: 1.1rem;">Tổng số lượt bình chọn</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body" style="text-align: center;">
            <div class="stat-icon" style="font-size: 3rem; color: #dc3545; margin-bottom: 15px;">
                <i class="fas fa-bell"></i>
            </div>
            <div class="stat-number" id="totalNotifications" style="font-size: 2.5rem; font-weight: bold; color: #2c3e50; margin-bottom: 10px;">-</div>
            <div class="stat-label" style="color: #7f8c8d; font-size: 1.1rem;">Thông báo mới</div>
        </div>
    </div>
</div>

<!-- Recent Activity & Quick Actions -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-history"></i> Hoạt động gần đây
            </h3>
        </div>
        <div class="activity-list" id="recentActivity">
            <div class="activity-item" style="padding: 15px 0; border-bottom: 1px solid #e9ecef;">
                <div class="activity-icon" style="float: left; margin-right: 15px; width: 40px; height: 40px; background: #667eea; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text" style="font-weight: 500; margin-bottom: 5px;">Đang tải...</div>
                    <div class="activity-time" style="font-size: 0.875rem; color: #7f8c8d;">-</div>
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-bolt"></i> Thao tác nhanh
            </h3>
        </div>
        <div class="quick-actions">
            <a href="?page=ContestsPage" class="btn btn-outline w-100 mb-2">
                <i class="fas fa-plus"></i> Tạo cuộc thi mới
            </a>
            <a href="?page=UserPage" class="btn btn-outline w-100 mb-2">
                <i class="fas fa-user-plus"></i> Thêm người dùng
            </a>
            <a href="?page=NotificationTemplatesPage" class="btn btn-outline w-100 mb-2">
                <i class="fas fa-bell"></i> Gửi thông báo
            </a>
            <a href="?page=UploadPage" class="btn btn-outline w-100 mb-2">
                <i class="fas fa-upload"></i> Upload file
            </a>
        </div>
    </div>
</div>

<!-- System Status & Charts -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- System Status -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-server"></i> Trạng thái hệ thống
            </h3>
        </div>
        <div class="system-status">
            <div class="status-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #e9ecef;">
                <span>Database</span>
                <span class="status-badge" id="dbStatus">
                    <span class="status-indicator status-pending"></span>Kiểm tra...
                </span>
            </div>
            <div class="status-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #e9ecef;">
                <span>API Backend</span>
                <span class="status-badge" id="apiStatus">
                    <span class="status-indicator status-pending"></span>Kiểm tra...
                </span>
            </div>
            <div class="status-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #e9ecef;">
                <span>File Storage</span>
                <span class="status-badge" id="storageStatus">
                    <span class="status-indicator status-pending"></span>Kiểm tra...
                </span>
            </div>
            <div class="status-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0;">
                <span>Chrome Automation</span>
                <span class="status-badge" id="chromeStatus">
                    <span class="status-indicator status-pending"></span>Kiểm tra...
                </span>
            </div>
        </div>
    </div>
    
    <!-- Recent Contests -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-trophy"></i> Cuộc thi gần đây
            </h3>
        </div>
        <div class="recent-contests" id="recentContests">
            <div class="contest-item" style="padding: 15px 0; border-bottom: 1px solid #e9ecef;">
                <div class="contest-title" style="font-weight: 500; margin-bottom: 5px;">Đang tải...</div>
                <div class="contest-meta" style="font-size: 0.875rem; color: #7f8c8d;">-</div>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Message for New Users -->
<div class="card" id="welcomeCard" style="display: none;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-hand-wave"></i> Chào mừng bạn!
        </h3>
    </div>
    <div class="card-body">
        <p>Chào mừng bạn đến với hệ thống quản lý cuộc thi. Đây là một số bước để bắt đầu:</p>
        <ol style="margin-left: 20px; margin-top: 15px;">
            <li>Tạo cuộc thi đầu tiên của bạn</li>
            <li>Thêm người dùng và thí sinh</li>
            <li>Cấu hình hệ thống thông báo</li>
            <li>Thiết lập tự động hóa Chrome</li>
        </ol>
        <div style="margin-top: 20px;">
            <a href="?page=ContestsPage" class="btn btn-primary">
                <i class="fas fa-rocket"></i> Bắt đầu ngay
            </a>
        </div>
    </div>
</div>

<script>
// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    checkSystemStatus();
    setupAutoRefresh();
});

async function loadDashboardData() {
    try {
        // Load stats
        const statsResponse = await window.app.apiCall('admin/dashboard/stats', 'GET');
        if (statsResponse.success) {
            updateStats(statsResponse.stats);
        }
        
        // Load recent activity
        const activityResponse = await window.app.apiCall('admin/dashboard/activity', 'GET');
        if (activityResponse.success) {
            updateRecentActivity(activityResponse.activities);
        }
        
        // Load recent contests
        const contestsResponse = await window.app.apiCall('contests/list', 'GET');
        if (contestsResponse.success) {
            updateRecentContests(contestsResponse.contests);
        }
        
        // Show welcome message for new users
        if (statsResponse.success && statsResponse.stats.total_contests === 0) {
            document.getElementById('welcomeCard').style.display = 'block';
        }
        
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
        showNotification('Không thể tải dữ liệu dashboard', 'error');
    }
}

function updateStats(stats) {
    document.getElementById('totalContests').textContent = stats.total_contests || 0;
    document.getElementById('totalUsers').textContent = stats.total_users || 0;
    document.getElementById('totalVotes').textContent = stats.total_votes || 0;
    document.getElementById('totalNotifications').textContent = stats.new_notifications || 0;
}

function updateRecentActivity(activities) {
    const container = document.getElementById('recentActivity');
    
    if (!activities || activities.length === 0) {
        container.innerHTML = `
            <div class="activity-item" style="padding: 15px 0; text-align: center; color: #7f8c8d;">
                <i class="fas fa-inbox"></i> Không có hoạt động nào
            </div>
        `;
        return;
    }
    
    container.innerHTML = activities.map(activity => `
        <div class="activity-item" style="padding: 15px 0; border-bottom: 1px solid #e9ecef;">
            <div class="activity-icon" style="float: left; margin-right: 15px; width: 40px; height: 40px; background: ${getActivityColor(activity.type)}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                <i class="fas ${getActivityIcon(activity.type)}"></i>
            </div>
            <div class="activity-content">
                <div class="activity-text" style="font-weight: 500; margin-bottom: 5px;">${activity.description}</div>
                <div class="activity-time" style="font-size: 0.875rem; color: #7f8c8d;">${formatDate(activity.created_at)}</div>
            </div>
            <div style="clear: both;"></div>
        </div>
    `).join('');
}

function updateRecentContests(contests) {
    const container = document.getElementById('recentContests');
    
    if (!contests || contests.length === 0) {
        container.innerHTML = `
            <div class="contest-item" style="padding: 15px 0; text-align: center; color: #7f8c8d;">
                <i class="fas fa-inbox"></i> Không có cuộc thi nào
            </div>
        `;
        return;
    }
    
    const recentContests = contests.slice(0, 5); // Show only 5 most recent
    
    container.innerHTML = recentContests.map(contest => `
        <div class="contest-item" style="padding: 15px 0; border-bottom: 1px solid #e9ecef;">
            <div class="contest-title" style="font-weight: 500; margin-bottom: 5px;">
                <a href="?page=ContestDetailPage&id=${contest.id}" style="color: #667eea; text-decoration: none;">
                    ${contest.title}
                </a>
            </div>
            <div class="contest-meta" style="font-size: 0.875rem; color: #7f8c8d;">
                <span class="badge badge-${getStatusBadgeClass(contest.status)}">
                    ${getStatusText(contest.status)}
                </span>
                • ${formatDate(contest.created_at)}
            </div>
        </div>
    `).join('');
}

async function checkSystemStatus() {
    try {
        // Check database
        const dbResponse = await window.app.apiCall('health', 'GET');
        updateStatus('dbStatus', dbResponse.status === 'healthy' ? 'success' : 'error');
        
        // Check API
        updateStatus('apiStatus', 'success');
        
        // Check storage
        updateStatus('storageStatus', 'success');
        
        // Check Chrome automation
        updateStatus('chromeStatus', 'success');
        
    } catch (error) {
        console.error('System status check failed:', error);
        updateStatus('dbStatus', 'error');
        updateStatus('apiStatus', 'error');
        updateStatus('storageStatus', 'error');
        updateStatus('chromeStatus', 'error');
    }
}

function updateStatus(elementId, status) {
    const element = document.getElementById(elementId);
    const indicator = element.querySelector('.status-indicator');
    
    // Remove all status classes
    indicator.classList.remove('status-success', 'status-error', 'status-pending');
    
    // Add new status class
    switch (status) {
        case 'success':
            indicator.classList.add('status-active');
            element.innerHTML = '<span class="status-indicator status-active"></span>Hoạt động';
            break;
        case 'error':
            indicator.classList.add('status-error');
            element.innerHTML = '<span class="status-indicator status-error"></span>Lỗi';
            break;
        default:
            indicator.classList.add('status-pending');
            element.innerHTML = '<span class="status-indicator status-pending"></span>Kiểm tra...';
    }
}

function setupAutoRefresh() {
    // Refresh dashboard data every 30 seconds
    setInterval(() => {
        loadDashboardData();
    }, 30000);
    
    // Refresh system status every 60 seconds
    setInterval(() => {
        checkSystemStatus();
    }, 60000);
}

// Utility functions
function getActivityColor(type) {
    switch (type) {
        case 'contest': return '#667eea';
        case 'user': return '#28a745';
        case 'vote': return '#ffc107';
        case 'notification': return '#dc3545';
        default: return '#6c757d';
    }
}

function getActivityIcon(type) {
    switch (type) {
        case 'contest': return 'fa-trophy';
        case 'user': return 'fa-user';
        case 'vote': return 'fa-vote-yea';
        case 'notification': return 'fa-bell';
        default: return 'fa-info-circle';
    }
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'active': return 'success';
        case 'draft': return 'secondary';
        case 'voting': return 'warning';
        case 'ended': return 'danger';
        default: return 'secondary';
    }
}

function getStatusText(status) {
    switch (status) {
        case 'active': return 'Hoạt động';
        case 'draft': return 'Nháp';
        case 'voting': return 'Đang bình chọn';
        case 'ended': return 'Kết thúc';
        default: return status;
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return window.formatDate ? window.formatDate(dateString) : new Date(dateString).toLocaleDateString('vi-VN');
}

function showNotification(message, type) {
    if (window.showNotification) {
        window.showNotification(message, type);
    } else {
        alert(message);
    }
}
</script>

