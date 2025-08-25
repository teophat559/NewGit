<?php
// NotificationsPage.php - Trang quản lý lịch sử thông báo hoàn chỉnh
$pageTitle = 'Lịch sử thông báo';
?>

<div class="content-header">
    <h1><i class="fas fa-bell"></i> Lịch sử thông báo</h1>
    <p>Quản lý và theo dõi tất cả thông báo đã gửi trong hệ thống</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh sách Thông báo</h3>
        <div class="card-actions">
            <button class="btn btn-primary" onclick="showSendNotificationModal()">
                <i class="fas fa-paper-plane"></i> Gửi thông báo mới
            </button>
            <button class="btn btn-secondary" onclick="refreshNotifications()">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
            <button class="btn btn-info" onclick="exportNotifications()">
                <i class="fas fa-download"></i> Xuất dữ liệu
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-group">
            <label for="typeFilter">Loại:</label>
            <select id="typeFilter" class="form-select" onchange="filterNotifications()">
                <option value="">Tất cả</option>
                <option value="email">Email</option>
                <option value="sms">SMS</option>
                <option value="push">Push Notification</option>
                <option value="telegram">Telegram</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="statusFilter">Trạng thái:</label>
            <select id="statusFilter" class="form-select" onchange="filterNotifications()">
                <option value="">Tất cả</option>
                <option value="sent">Đã gửi</option>
                <option value="delivered">Đã nhận</option>
                <option value="failed">Thất bại</option>
                <option value="pending">Đang chờ</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="dateFilter">Ngày:</label>
            <input type="date" id="dateFilter" class="form-input" onchange="filterNotifications()">
        </div>
        
        <div class="filter-group">
            <label for="searchFilter">Tìm kiếm:</label>
            <input type="text" id="searchFilter" class="form-input" placeholder="Tìm theo người nhận, tiêu đề..." onkeyup="filterNotifications()">
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon total">📊</div>
            <div class="stat-content">
                <div class="stat-number" id="totalNotifications">0</div>
                <div class="stat-label">Tổng thông báo</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon sent">✅</div>
            <div class="stat-content">
                <div class="stat-number" id="sentNotifications">0</div>
                <div class="stat-label">Đã gửi</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon delivered">📨</div>
            <div class="stat-content">
                <div class="stat-number" id="deliveredNotifications">0</div>
                <div class="stat-label">Đã nhận</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon failed">❌</div>
            <div class="stat-content">
                <div class="stat-number" id="failedNotifications">0</div>
                <div class="stat-label">Thất bại</div>
            </div>
        </div>
    </div>

    <!-- Notifications Table -->
    <div class="table-container">
        <table class="table" id="notificationsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Người nhận</th>
                    <th>Loại</th>
                    <th>Tiêu đề</th>
                    <th>Trạng thái</th>
                    <th>Thời gian gửi</th>
                    <th>Thời gian nhận</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="notificationsTableBody">
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination" id="notificationsPagination" style="display: none;">
        <!-- Pagination controls will be generated here -->
    </div>
</div>

<!-- Send Notification Modal -->
<div id="sendNotificationModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Gửi thông báo mới</h3>
            <span class="close" onclick="closeSendNotificationModal()">&times;</span>
        </div>
        
        <form id="sendNotificationForm">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="notificationType" class="form-label">Loại thông báo *</label>
                        <select id="notificationType" name="type" class="form-input form-select" required onchange="loadTemplates()">
                            <option value="">Chọn loại...</option>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="push">Push Notification</option>
                            <option value="telegram">Telegram</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notificationTemplate" class="form-label">Mẫu thông báo</label>
                        <select id="notificationTemplate" name="template_id" class="form-input form-select" onchange="loadTemplateContent()">
                            <option value="">Chọn mẫu...</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notificationRecipients" class="form-label">Người nhận *</label>
                    <textarea id="notificationRecipients" name="recipients" class="form-input form-textarea" rows="3" placeholder="Nhập email, số điện thoại hoặc ID người dùng (mỗi dòng một người)" required></textarea>
                    <small class="text-muted">Hỗ trợ: email, số điện thoại, user ID. Mỗi dòng một người nhận.</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="notificationSubject" class="form-label">Tiêu đề *</label>
                        <input type="text" id="notificationSubject" name="subject" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notificationPriority" class="form-label">Độ ưu tiên</label>
                        <select id="notificationPriority" name="priority" class="form-input form-select">
                            <option value="low">Thấp</option>
                            <option value="normal" selected>Bình thường</option>
                            <option value="high">Cao</option>
                            <option value="urgent">Khẩn cấp</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notificationContent" class="form-label">Nội dung *</label>
                    <textarea id="notificationContent" name="content" class="form-input form-textarea" rows="6" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="notificationSchedule" class="form-label">Lên lịch gửi</label>
                    <input type="datetime-local" id="notificationSchedule" name="scheduled_at" class="form-input">
                    <small class="text-muted">Để trống để gửi ngay lập tức</small>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSendNotificationModal()">Hủy</button>
                <button type="button" class="btn btn-info" onclick="previewNotification()">Xem trước</button>
                <button type="submit" class="btn btn-primary">Gửi thông báo</button>
            </div>
        </form>
    </div>
</div>

<!-- Notification Details Modal -->
<div id="notificationDetailsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3>Chi tiết thông báo</h3>
            <span class="close" onclick="closeNotificationDetailsModal()">&times;</span>
        </div>
        
        <div class="modal-body" id="notificationDetailsContent">
            <!-- Notification details will be loaded here -->
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeNotificationDetailsModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
let notifications = [];
let templates = [];
let currentPage = 1;
const itemsPerPage = 20;

document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('sendNotificationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        sendNotification();
    });
}

async function loadNotifications() {
    try {
        const response = await window.app.apiCall('notifications/history', 'GET');
        if (response.success) {
            notifications = response.notifications || [];
            renderNotificationsTable();
            updateStats();
        } else {
            showNotification('Không thể tải danh sách thông báo', 'error');
        }
    } catch (error) {
        console.error('Failed to load notifications:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function renderNotificationsTable() {
    const tbody = document.getElementById('notificationsTableBody');
    
    if (notifications.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted"><i class="fas fa-inbox"></i> Không có thông báo nào</td></tr>`;
        return;
    }
    
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageNotifications = notifications.slice(startIndex, endIndex);
    
    tbody.innerHTML = pageNotifications.map(notification => `
        <tr>
            <td>${notification.id}</td>
            <td>
                <div class="recipient-info">
                    <div class="recipient-name">${notification.recipient_name || 'N/A'}</div>
                    <div class="recipient-contact">${notification.recipient_contact}</div>
                </div>
            </td>
            <td>
                <span class="type-badge type-${notification.type}">
                    ${getTypeIcon(notification.type)} ${getTypeText(notification.type)}
                </span>
            </td>
            <td>
                <div class="notification-title">${notification.subject}</div>
                <div class="notification-preview">${notification.content.substring(0, 50)}${notification.content.length > 50 ? '...' : ''}</div>
            </td>
            <td>
                <span class="status-badge status-${notification.status}">
                    ${getStatusIcon(notification.status)} ${getStatusText(notification.status)}
                </span>
            </td>
            <td>${formatDateTime(notification.sent_at)}</td>
            <td>${notification.delivered_at ? formatDateTime(notification.delivered_at) : 'N/A'}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-outline" onclick="viewNotificationDetails(${notification.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-info" onclick="resendNotification(${notification.id})" title="Gửi lại" ${notification.status === 'failed' ? '' : 'disabled'}>
                        <i class="fas fa-redo"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteNotification(${notification.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    
    updatePagination();
}

function updateStats() {
    const stats = {
        total: notifications.length,
        sent: notifications.filter(n => n.status === 'sent').length,
        delivered: notifications.filter(n => n.status === 'delivered').length,
        failed: notifications.filter(n => n.status === 'failed').length
    };
    
    document.getElementById('totalNotifications').textContent = stats.total;
    document.getElementById('sentNotifications').textContent = stats.sent;
    document.getElementById('deliveredNotifications').textContent = stats.delivered;
    document.getElementById('failedNotifications').textContent = stats.failed;
}

function filterNotifications() {
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
    
    let filteredNotifications = notifications;
    
    if (typeFilter) {
        filteredNotifications = filteredNotifications.filter(n => n.type === typeFilter);
    }
    
    if (statusFilter) {
        filteredNotifications = filteredNotifications.filter(n => n.status === statusFilter);
    }
    
    if (dateFilter) {
        filteredNotifications = filteredNotifications.filter(n => 
            n.sent_at && n.sent_at.startsWith(dateFilter)
        );
    }
    
    if (searchFilter) {
        filteredNotifications = filteredNotifications.filter(n => 
            n.recipient_name.toLowerCase().includes(searchFilter) ||
            n.recipient_contact.toLowerCase().includes(searchFilter) ||
            n.subject.toLowerCase().includes(searchFilter) ||
            n.content.toLowerCase().includes(searchFilter)
        );
    }
    
    const tbody = document.getElementById('notificationsTableBody');
    if (filteredNotifications.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted"><i class="fas fa-search"></i> Không tìm thấy kết quả</td></tr>`;
    } else {
        const startIndex = 0;
        const endIndex = itemsPerPage;
        const pageNotifications = filteredNotifications.slice(startIndex, endIndex);
        
        tbody.innerHTML = pageNotifications.map(notification => `
            <tr>
                <td>${notification.id}</td>
                <td>
                    <div class="recipient-info">
                        <div class="recipient-name">${notification.recipient_name || 'N/A'}</div>
                        <div class="recipient-contact">${notification.recipient_contact}</div>
                    </div>
                </td>
                <td>
                    <span class="type-badge type-${notification.type}">
                        ${getTypeIcon(notification.type)} ${getTypeText(notification.type)}
                    </span>
                </td>
                <td>
                    <div class="notification-title">${notification.subject}</div>
                    <div class="notification-preview">${notification.content.substring(0, 50)}${notification.content.length > 50 ? '...' : ''}</div>
                </td>
                <td>
                    <span class="status-badge status-${notification.status}">
                        ${getStatusIcon(notification.status)} ${getStatusText(notification.status)}
                    </span>
                </td>
                <td>${formatDateTime(notification.sent_at)}</td>
                <td>${notification.delivered_at ? formatDateTime(notification.delivered_at) : 'N/A'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline" onclick="viewNotificationDetails(${notification.id})" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-info" onclick="resendNotification(${notification.id})" title="Gửi lại" ${notification.status === 'failed' ? '' : 'disabled'}>
                            <i class="fas fa-redo"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteNotification(${notification.id})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
}

function updatePagination() {
    const totalPages = Math.ceil(notifications.length / itemsPerPage);
    const pagination = document.getElementById('notificationsPagination');
    
    if (totalPages <= 1) {
        pagination.style.display = 'none';
        return;
    }
    
    pagination.style.display = 'block';
    
    let paginationHTML = '';
    
    if (currentPage > 1) {
        paginationHTML += `<button class="btn btn-sm btn-outline" onclick="goToPage(${currentPage - 1})">Trước</button>`;
    }
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            paginationHTML += `<button class="btn btn-sm btn-primary">${i}</button>`;
        } else if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            paginationHTML += `<button class="btn btn-sm btn-outline" onclick="goToPage(${i})">${i}</button>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            paginationHTML += `<span class="pagination-ellipsis">...</span>`;
        }
    }
    
    if (currentPage < totalPages) {
        paginationHTML += `<button class="btn btn-sm btn-outline" onclick="goToPage(${currentPage + 1})">Sau</button>`;
    }
    
    pagination.innerHTML = paginationHTML;
}

function goToPage(page) {
    currentPage = page;
    renderNotificationsTable();
}

// Modal functions
function showSendNotificationModal() {
    document.getElementById('sendNotificationModal').style.display = 'block';
    document.getElementById('sendNotificationForm').reset();
}

function closeSendNotificationModal() {
    document.getElementById('sendNotificationModal').style.display = 'none';
}

async function loadTemplates() {
    const type = document.getElementById('notificationType').value;
    if (!type) return;
    
    try {
        const response = await window.app.apiCall(`notifications/templates?type=${type}`, 'GET');
        if (response.success) {
            templates = response.templates || [];
            updateTemplateSelect();
        }
    } catch (error) {
        console.error('Failed to load templates:', error);
    }
}

function updateTemplateSelect() {
    const select = document.getElementById('notificationTemplate');
    select.innerHTML = '<option value="">Chọn mẫu...</option>';
    
    templates.forEach(template => {
        if (template.status === 'active') {
            select.innerHTML += `<option value="${template.id}">${template.name}</option>`;
        }
    });
}

function loadTemplateContent() {
    const templateId = document.getElementById('notificationTemplate').value;
    if (!templateId) return;
    
    const template = templates.find(t => t.id == templateId);
    if (template) {
        document.getElementById('notificationSubject').value = template.subject;
        document.getElementById('notificationContent').value = template.content;
    }
}

function previewNotification() {
    const form = document.getElementById('sendNotificationForm');
    const formData = new FormData(form);
    
    const previewData = {
        type: formData.get('type'),
        subject: formData.get('subject'),
        content: formData.get('content'),
        recipients: formData.get('recipients')
    };
    
    // Hiển thị preview đơn giản
    alert(`Xem trước thông báo:\n\nLoại: ${previewData.type}\nTiêu đề: ${previewData.subject}\nNội dung: ${previewData.content.substring(0, 100)}...\n\nNgười nhận: ${previewData.recipients}`);
}

// API functions
async function sendNotification() {
    const form = document.getElementById('sendNotificationForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    window.app.showLoading(submitBtn);
    
    try {
        const data = {
            type: formData.get('type'),
            recipients: formData.get('recipients').split('\n').filter(r => r.trim()),
            subject: formData.get('subject'),
            content: formData.get('content'),
            priority: formData.get('priority'),
            template_id: formData.get('template_id') || null,
            scheduled_at: formData.get('scheduled_at') || null
        };
        
        const response = await window.app.apiCall('notifications/send', 'POST', data);
        
        if (response.success) {
            showNotification('Gửi thông báo thành công', 'success');
            closeSendNotificationModal();
            loadNotifications();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to send notification:', error);
        showNotification('Lỗi kết nối server', 'error');
    } finally {
        window.app.hideLoading(submitBtn, originalText);
    }
}

async function viewNotificationDetails(notificationId) {
    try {
        const response = await window.app.apiCall(`notifications/${notificationId}`, 'GET');
        
        if (response.success) {
            const notification = response.notification;
            showNotificationDetailsModal(notification);
        } else {
            showNotification('Không thể tải thông tin thông báo', 'error');
        }
    } catch (error) {
        console.error('Failed to load notification details:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function showNotificationDetailsModal(notification) {
    const modal = document.getElementById('notificationDetailsModal');
    const content = document.getElementById('notificationDetailsContent');
    
    content.innerHTML = `
        <div class="notification-details">
            <div class="detail-row">
                <div class="detail-label">ID:</div>
                <div class="detail-value">${notification.id}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Loại:</div>
                <div class="detail-value">
                    <span class="type-badge type-${notification.type}">
                        ${getTypeIcon(notification.type)} ${getTypeText(notification.type)}
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Người nhận:</div>
                <div class="detail-value">${notification.recipient_name || 'N/A'} (${notification.recipient_contact})</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Tiêu đề:</div>
                <div class="detail-value">${notification.subject}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Nội dung:</div>
                <div class="detail-value">${notification.content}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Trạng thái:</div>
                <div class="detail-value">
                    <span class="status-badge status-${notification.status}">
                        ${getStatusIcon(notification.status)} ${getStatusText(notification.status)}
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Thời gian gửi:</div>
                <div class="detail-value">${formatDateTime(notification.sent_at)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Thời gian nhận:</div>
                <div class="detail-value">${notification.delivered_at ? formatDateTime(notification.delivered_at) : 'N/A'}</div>
            </div>
            ${notification.error_message ? `
                <div class="detail-row">
                    <div class="detail-label">Lỗi:</div>
                    <div class="detail-value error-message">${notification.error_message}</div>
                </div>
            ` : ''}
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeNotificationDetailsModal() {
    document.getElementById('notificationDetailsModal').style.display = 'none';
}

async function resendNotification(notificationId) {
    if (!confirm('Bạn có chắc chắn muốn gửi lại thông báo này?')) return;
    
    try {
        const response = await window.app.apiCall(`notifications/${notificationId}/resend`, 'POST');
        
        if (response.success) {
            showNotification('Gửi lại thông báo thành công', 'success');
            loadNotifications();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to resend notification:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function deleteNotification(notificationId) {
    if (!confirm('Bạn có chắc chắn muốn xóa thông báo này?')) return;
    
    try {
        const response = await window.app.apiCall(`notifications/${notificationId}`, 'DELETE');
        
        if (response.success) {
            showNotification('Xóa thông báo thành công', 'success');
            loadNotifications();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to delete notification:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function refreshNotifications() {
    loadNotifications();
}

async function exportNotifications() {
    try {
        const response = await window.app.apiCall('notifications/export', 'GET');
        
        if (response.success && response.download_url) {
            const link = document.createElement('a');
            link.href = response.download_url;
            link.download = `notifications_${new Date().toISOString().split('T')[0]}.xlsx`;
            link.click();
            
            showNotification('Xuất dữ liệu thành công', 'success');
        } else {
            showNotification('Không thể xuất dữ liệu', 'error');
        }
    } catch (error) {
        console.error('Failed to export notifications:', error);
        showNotification('Lỗi khi xuất dữ liệu', 'error');
    }
}

// Utility functions
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

function getTypeIcon(type) {
    const icons = {
        'email': '📧',
        'sms': '📱',
        'push': '🔔',
        'telegram': '📬'
    };
    return icons[type] || '📄';
}

function getTypeText(type) {
    const texts = {
        'email': 'Email',
        'sms': 'SMS',
        'push': 'Push',
        'telegram': 'Telegram'
    };
    return texts[type] || type;
}

function getStatusIcon(status) {
    const icons = {
        'sent': '✅',
        'delivered': '📨',
        'failed': '❌',
        'pending': '🟡'
    };
    return icons[status] || '❓';
}

function getStatusText(status) {
    const texts = {
        'sent': 'Đã gửi',
        'delivered': 'Đã nhận',
        'failed': 'Thất bại',
        'pending': 'Đang chờ'
    };
    return texts[status] || status;
}

function showNotification(message, type) {
    if (window.showNotification) {
        window.showNotification(message, type);
    } else {
        alert(message);
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = ['sendNotificationModal', 'notificationDetailsModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}
</script>
