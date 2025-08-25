<?php
// OtpRequestPage.php - Trang yêu cầu OTP hoàn chỉnh
$pageTitle = 'Yêu cầu OTP';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Yêu cầu OTP</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="content-header">
    <h1><i class="fas fa-key"></i> Yêu cầu OTP</h1>
    <p>Quản lý và xử lý yêu cầu mã OTP cho người dùng</p>
</div>

<!-- OTP Request Form -->
<div class="otp-request-form">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Yêu cầu mã OTP</h3>
        </div>
        
        <div class="card-body">
            <form id="otpRequestForm">
                <div class="form-group">
                    <label for="requestType">Loại yêu cầu:</label>
                    <select id="requestType" name="request_type" class="form-input form-select" required onchange="updateRequestForm()">
                        <option value="">Chọn loại yêu cầu...</option>
                        <option value="login">Đăng nhập</option>
                        <option value="password_reset">Đặt lại mật khẩu</option>
                        <option value="account_verification">Xác thực tài khoản</option>
                        <option value="transaction">Giao dịch</option>
                        <option value="two_factor">Xác thực 2 yếu tố</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="identifier">Email/Số điện thoại:</label>
                    <input type="text" id="identifier" name="identifier" class="form-input" required placeholder="Nhập email hoặc số điện thoại">
                </div>
                
                <div class="form-group">
                    <label for="otpMethod">Phương thức gửi:</label>
                    <div class="otp-method-options">
                        <label class="method-option">
                            <input type="radio" name="otp_method" value="email" checked>
                            <span class="method-icon">📧</span>
                            <span class="method-text">Email</span>
                        </label>
                        <label class="method-option">
                            <input type="radio" name="otp_method" value="sms">
                            <span class="method-icon">📱</span>
                            <span class="method-text">SMS</span>
                        </label>
                        <label class="method-option">
                            <input type="radio" name="otp_method" value="telegram">
                            <span class="method-icon">📬</span>
                            <span class="method-text">Telegram</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="otpLength">Độ dài mã OTP:</label>
                    <select id="otpLength" name="otp_length" class="form-input form-select">
                        <option value="4">4 ký tự</option>
                        <option value="6" selected>6 ký tự</option>
                        <option value="8">8 ký tự</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="otpExpiry">Thời gian hết hạn:</label>
                    <select id="otpExpiry" name="otp_expiry" class="form-input form-select">
                        <option value="300">5 phút</option>
                        <option value="600" selected>10 phút</option>
                        <option value="900">15 phút</option>
                        <option value="1800">30 phút</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="requestNotes">Ghi chú:</label>
                    <textarea id="requestNotes" name="notes" class="form-input form-textarea" rows="3" placeholder="Ghi chú về yêu cầu OTP..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Gửi yêu cầu OTP
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-undo"></i> Làm mới
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- OTP Verification Form -->
<div class="otp-verification-form" id="otpVerificationForm" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Xác thực mã OTP</h3>
        </div>
        
        <div class="card-body">
            <div class="verification-info">
                <p>Mã OTP đã được gửi đến <strong id="verificationIdentifier"></strong></p>
                <p>Thời gian còn lại: <span id="otpCountdown" class="countdown">10:00</span></p>
            </div>
            
            <form id="otpVerificationForm">
                <div class="otp-input-container">
                    <div class="otp-input-group">
                        <input type="text" class="otp-input" maxlength="1" data-index="0" onkeyup="moveToNext(this)" onkeydown="moveToPrevious(this)">
                        <input type="text" class="otp-input" maxlength="1" data-index="1" onkeyup="moveToNext(this)" onkeydown="moveToPrevious(this)">
                        <input type="text" class="otp-input" maxlength="1" data-index="2" onkeyup="moveToNext(this)" onkeydown="moveToPrevious(this)">
                        <input type="text" class="otp-input" maxlength="1" data-index="3" onkeyup="moveToNext(this)" onkeydown="moveToPrevious(this)">
                        <input type="text" class="otp-input" maxlength="1" data-index="4" onkeyup="moveToNext(this)" onkeydown="moveToPrevious(this)">
                        <input type="text" class="otp-input" maxlength="1" data-index="5" onkeyup="moveToNext(this)" onkeydown="moveToPrevious(this)">
                    </div>
                </div>
                
                <div class="verification-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Xác thực
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resendOTP()">
                        <i class="fas fa-redo"></i> Gửi lại
                    </button>
                    <button type="button" class="btn btn-outline" onclick="cancelVerification()">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- OTP Management -->
<div class="otp-management">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Quản lý OTP</h3>
            <div class="card-actions">
                <button class="btn btn-secondary" onclick="refreshOTPList()">
                    <i class="fas fa-sync-alt"></i> Làm mới
                </button>
                <button class="btn btn-info" onclick="exportOTPData()">
                    <i class="fas fa-download"></i> Xuất dữ liệu
                </button>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-section">
            <div class="filter-group">
                <label for="otpStatusFilter">Trạng thái:</label>
                <select id="otpStatusFilter" class="form-select" onchange="filterOTPList()">
                    <option value="">Tất cả</option>
                    <option value="pending">Đang chờ</option>
                    <option value="verified">Đã xác thực</option>
                    <option value="expired">Hết hạn</option>
                    <option value="failed">Thất bại</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="otpTypeFilter">Loại:</label>
                <select id="otpTypeFilter" class="form-select" onchange="filterOTPList()">
                    <option value="">Tất cả</option>
                    <option value="login">Đăng nhập</option>
                    <option value="password_reset">Đặt lại mật khẩu</option>
                    <option value="account_verification">Xác thực tài khoản</option>
                    <option value="transaction">Giao dịch</option>
                    <option value="two_factor">Xác thực 2 yếu tố</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="otpDateFilter">Ngày:</label>
                <input type="date" id="otpDateFilter" class="form-input" onchange="filterOTPList()">
            </div>
            
            <div class="filter-group">
                <label for="otpSearchFilter">Tìm kiếm:</label>
                <input type="text" id="otpSearchFilter" class="form-input" placeholder="Tìm theo email, số điện thoại..." onkeyup="filterOTPList()">
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">🔑</div>
                <div class="stat-content">
                    <div class="stat-number" id="totalOTP">0</div>
                    <div class="stat-label">Tổng OTP</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">⏳</div>
                <div class="stat-content">
                    <div class="stat-number" id="pendingOTP">0</div>
                    <div class="stat-label">Đang chờ</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon verified">✅</div>
                <div class="stat-content">
                    <div class="stat-number" id="verifiedOTP">0</div>
                    <div class="stat-label">Đã xác thực</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon expired">⏰</div>
                <div class="stat-content">
                    <div class="stat-number" id="expiredOTP">0</div>
                    <div class="stat-label">Hết hạn</div>
                </div>
            </div>
        </div>

        <!-- OTP Table -->
        <div class="table-container">
            <table class="table" id="otpTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Loại yêu cầu</th>
                        <th>Người dùng</th>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                        <th>Thời gian tạo</th>
                        <th>Thời gian hết hạn</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="otpTableBody">
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="otpPagination" style="display: none;">
            <!-- Pagination controls will be generated here -->
        </div>
    </div>
</div>

<!-- OTP Details Modal -->
<div id="otpDetailsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3>Chi tiết OTP</h3>
            <span class="close" onclick="closeOTPDetailsModal()">&times;</span>
        </div>
        
        <div class="modal-body" id="otpDetailsContent">
            <!-- OTP details will be loaded here -->
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeOTPDetailsModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
let otpRequests = [];
let currentOTP = null;
let otpCountdownInterval = null;
let currentPage = 1;
const itemsPerPage = 20;

document.addEventListener('DOMContentLoaded', function() {
    loadOTPList();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('otpRequestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        requestOTP();
    });
    
    document.getElementById('otpVerificationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        verifyOTP();
    });
}

async function loadOTPList() {
    try {
        const response = await window.app.apiCall('otp/list', 'GET');
        if (response.success) {
            otpRequests = response.otp_requests || [];
            renderOTPTable();
            updateStats();
        } else {
            showNotification('Không thể tải danh sách OTP', 'error');
        }
    } catch (error) {
        console.error('Failed to load OTP list:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function renderOTPTable() {
    const tbody = document.getElementById('otpTableBody');
    
    if (otpRequests.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted"><i class="fas fa-inbox"></i> Không có yêu cầu OTP nào</td></tr>`;
        return;
    }
    
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageOTP = otpRequests.slice(startIndex, endIndex);
    
    tbody.innerHTML = pageOTP.map(otp => `
        <tr>
            <td>${otp.id}</td>
            <td>
                <span class="otp-type-badge type-${otp.request_type}">
                    ${getOTPTypeIcon(otp.request_type)} ${getOTPTypeText(otp.request_type)}
                </span>
            </td>
            <td>
                <div class="user-info">
                    <div class="user-identifier">${otp.identifier}</div>
                    <div class="user-method">${getMethodText(otp.method)}</div>
                </div>
            </td>
            <td>
                <span class="method-badge method-${otp.method}">
                    ${getMethodIcon(otp.method)} ${getMethodText(otp.method)}
                </span>
            </td>
            <td>
                <span class="status-badge status-${otp.status}">
                    ${getOTPStatusIcon(otp.status)} ${getOTPStatusText(otp.status)}
                </span>
            </td>
            <td>${formatDateTime(otp.created_at)}</td>
            <td>${formatDateTime(otp.expires_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-outline" onclick="viewOTPDetails(${otp.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-info" onclick="resendOTPById(${otp.id})" title="Gửi lại" ${otp.status === 'pending' ? '' : 'disabled'}>
                        <i class="fas fa-redo"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteOTP(${otp.id})" title="Xóa">
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
        total: otpRequests.length,
        pending: otpRequests.filter(o => o.status === 'pending').length,
        verified: otpRequests.filter(o => o.status === 'verified').length,
        expired: otpRequests.filter(o => o.status === 'expired').length
    };
    
    document.getElementById('totalOTP').textContent = stats.total;
    document.getElementById('pendingOTP').textContent = stats.pending;
    document.getElementById('verifiedOTP').textContent = stats.verified;
    document.getElementById('expiredOTP').textContent = stats.expired;
}

function filterOTPList() {
    const statusFilter = document.getElementById('otpStatusFilter').value;
    const typeFilter = document.getElementById('otpTypeFilter').value;
    const dateFilter = document.getElementById('otpDateFilter').value;
    const searchFilter = document.getElementById('otpSearchFilter').value.toLowerCase();
    
    let filteredOTP = otpRequests;
    
    if (statusFilter) {
        filteredOTP = filteredOTP.filter(o => o.status === statusFilter);
    }
    
    if (typeFilter) {
        filteredOTP = filteredOTP.filter(o => o.request_type === typeFilter);
    }
    
    if (dateFilter) {
        filteredOTP = filteredOTP.filter(o => 
            o.created_at && o.created_at.startsWith(dateFilter)
        );
    }
    
    if (searchFilter) {
        filteredOTP = filteredOTP.filter(o => 
            o.identifier.toLowerCase().includes(searchFilter)
        );
    }
    
    const tbody = document.getElementById('otpTableBody');
    if (filteredOTP.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted"><i class="fas fa-search"></i> Không tìm thấy kết quả</td></tr>`;
    } else {
        const startIndex = 0;
        const endIndex = itemsPerPage;
        const pageOTP = filteredOTP.slice(startIndex, endIndex);
        
        tbody.innerHTML = pageOTP.map(otp => `
            <tr>
                <td>${otp.id}</td>
                <td>
                    <span class="otp-type-badge type-${otp.request_type}">
                        ${getOTPTypeIcon(otp.request_type)} ${getOTPTypeText(otp.request_type)}
                    </span>
                </td>
                <td>
                    <div class="user-info">
                        <div class="user-identifier">${otp.identifier}</div>
                        <div class="user-method">${getMethodText(otp.method)}</div>
                    </div>
                </td>
                <td>
                    <span class="method-badge method-${otp.method}">
                        ${getMethodIcon(otp.method)} ${getMethodText(otp.method)}
                    </span>
                </td>
                <td>
                    <span class="status-badge status-${otp.status}">
                        ${getOTPStatusIcon(otp.status)} ${getOTPStatusText(otp.status)}
                    </span>
                </td>
                <td>${formatDateTime(otp.created_at)}</td>
                <td>${formatDateTime(otp.expires_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline" onclick="viewOTPDetails(${otp.id})" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-info" onclick="resendOTPById(${otp.id})" title="Gửi lại" ${otp.status === 'pending' ? '' : 'disabled'}>
                            <i class="fas fa-redo"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteOTP(${otp.id})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
}

function updatePagination() {
    const totalPages = Math.ceil(otpRequests.length / itemsPerPage);
    const pagination = document.getElementById('otpPagination');
    
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
    renderOTPTable();
}

// OTP Request functions
function updateRequestForm() {
    const requestType = document.getElementById('requestType').value;
    const otpLength = document.getElementById('otpLength');
    const otpExpiry = document.getElementById('otpExpiry');
    
    // Adjust OTP length and expiry based on request type
    if (requestType === 'login' || requestType === 'two_factor') {
        otpLength.value = '6';
        otpExpiry.value = '300'; // 5 minutes
    } else if (requestType === 'password_reset') {
        otpLength.value = '6';
        otpExpiry.value = '600'; // 10 minutes
    } else if (requestType === 'transaction') {
        otpLength.value = '8';
        otpExpiry.value = '300'; // 5 minutes
    }
}

async function requestOTP() {
    const form = document.getElementById('otpRequestForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    window.app.showLoading(submitBtn);
    
    try {
        const data = {
            request_type: formData.get('request_type'),
            identifier: formData.get('identifier'),
            method: formData.get('otp_method'),
            otp_length: parseInt(formData.get('otp_length')),
            expiry_time: parseInt(formData.get('otp_expiry')),
            notes: formData.get('requestNotes')
        };
        
        const response = await window.app.apiCall('otp/request', 'POST', data);
        
        if (response.success) {
            currentOTP = response.otp;
            showOTPVerification();
            showNotification('Mã OTP đã được gửi', 'success');
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to request OTP:', error);
        showNotification('Lỗi kết nối server', 'error');
    } finally {
        window.app.hideLoading(submitBtn, originalText);
    }
}

function showOTPVerification() {
    document.getElementById('otpRequestForm').style.display = 'none';
    document.getElementById('otpVerificationForm').style.display = 'block';
    
    // Update verification info
    document.getElementById('verificationIdentifier').textContent = currentOTP.identifier;
    
    // Start countdown
    startOTPCountdown();
    
    // Focus first OTP input
    document.querySelector('.otp-input').focus();
}

function startOTPCountdown() {
    const countdownElement = document.getElementById('otpCountdown');
    let timeLeft = currentOTP.expiry_time || 600; // 10 minutes default
    
    function updateCountdown() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            clearInterval(otpCountdownInterval);
            showNotification('Mã OTP đã hết hạn', 'warning');
            cancelVerification();
            return;
        }
        
        timeLeft--;
    }
    
    updateCountdown();
    otpCountdownInterval = setInterval(updateCountdown, 1000);
}

function moveToNext(input) {
    const index = parseInt(input.dataset.index);
    const nextIndex = index + 1;
    
    if (input.value.length === 1 && nextIndex < 6) {
        document.querySelector(`[data-index="${nextIndex}"]`).focus();
    }
}

function moveToPrevious(input) {
    const index = parseInt(input.dataset.index);
    
    if (input.value.length === 0 && index > 0) {
        const prevIndex = index - 1;
        document.querySelector(`[data-index="${prevIndex}"]`).focus();
    }
}

async function verifyOTP() {
    const otpInputs = document.querySelectorAll('.otp-input');
    const otpCode = Array.from(otpInputs).map(input => input.value).join('');
    
    if (otpCode.length !== 6) {
        showNotification('Vui lòng nhập đầy đủ 6 ký tự OTP', 'warning');
        return;
    }
    
    try {
        const response = await window.app.apiCall('otp/verify', 'POST', {
            otp_id: currentOTP.id,
            otp_code: otpCode
        });
        
        if (response.success) {
            showNotification('Xác thực OTP thành công', 'success');
            
            // Handle successful verification based on request type
            handleSuccessfulVerification(currentOTP.request_type);
            
            // Reset form and hide verification
            cancelVerification();
            resetForm();
            
            // Refresh OTP list
            loadOTPList();
            
        } else {
            showNotification(response.message || 'Mã OTP không đúng', 'error');
            // Clear OTP inputs
            otpInputs.forEach(input => input.value = '');
            otpInputs[0].focus();
        }
    } catch (error) {
        console.error('Failed to verify OTP:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function handleSuccessfulVerification(requestType) {
    switch (requestType) {
        case 'login':
            // Redirect to dashboard or show success message
            showNotification('Đăng nhập thành công', 'success');
            break;
        case 'password_reset':
            // Show password reset form
            showNotification('Vui lòng nhập mật khẩu mới', 'info');
            break;
        case 'account_verification':
            // Mark account as verified
            showNotification('Tài khoản đã được xác thực', 'success');
            break;
        case 'transaction':
            // Proceed with transaction
            showNotification('Giao dịch được xác thực', 'success');
            break;
        case 'two_factor':
            // Complete 2FA setup
            showNotification('Xác thực 2 yếu tố thành công', 'success');
            break;
    }
}

function resendOTP() {
    if (currentOTP) {
        resendOTPById(currentOTP.id);
    }
}

async function resendOTPById(otpId) {
    try {
        const response = await window.app.apiCall(`otp/${otpId}/resend`, 'POST');
        
        if (response.success) {
            showNotification('Mã OTP đã được gửi lại', 'success');
            
            // Reset countdown if currently verifying
            if (otpCountdownInterval) {
                clearInterval(otpCountdownInterval);
                startOTPCountdown();
            }
            
            // Refresh OTP list
            loadOTPList();
            
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to resend OTP:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function cancelVerification() {
    if (otpCountdownInterval) {
        clearInterval(otpCountdownInterval);
        otpCountdownInterval = null;
    }
    
    document.getElementById('otpRequestForm').style.display = 'block';
    document.getElementById('otpVerificationForm').style.display = 'none';
    
    // Clear OTP inputs
    document.querySelectorAll('.otp-input').forEach(input => input.value = '');
    
    currentOTP = null;
}

function resetForm() {
    document.getElementById('otpRequestForm').reset();
    document.getElementById('otpRequestForm').style.display = 'block';
    document.getElementById('otpVerificationForm').style.display = 'none';
    
    if (otpCountdownInterval) {
        clearInterval(otpCountdownInterval);
        otpCountdownInterval = null;
    }
    
    currentOTP = null;
}

// OTP Management functions
async function viewOTPDetails(otpId) {
    try {
        const response = await window.app.apiCall(`otp/${otpId}`, 'GET');
        
        if (response.success) {
            const otp = response.otp;
            showOTPDetailsModal(otp);
        } else {
            showNotification('Không thể tải thông tin OTP', 'error');
        }
    } catch (error) {
        console.error('Failed to load OTP details:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function showOTPDetailsModal(otp) {
    const modal = document.getElementById('otpDetailsModal');
    const content = document.getElementById('otpDetailsContent');
    
    content.innerHTML = `
        <div class="otp-details">
            <div class="detail-row">
                <div class="detail-label">ID:</div>
                <div class="detail-value">${otp.id}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Loại yêu cầu:</div>
                <div class="detail-value">
                    <span class="otp-type-badge type-${otp.request_type}">
                        ${getOTPTypeIcon(otp.request_type)} ${getOTPTypeText(otp.request_type)}
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Người dùng:</div>
                <div class="detail-value">${otp.identifier}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Phương thức:</div>
                <div class="detail-value">
                    <span class="method-badge method-${otp.method}">
                        ${getMethodIcon(otp.method)} ${getMethodText(otp.method)}
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Trạng thái:</div>
                <div class="detail-value">
                    <span class="status-badge status-${otp.status}">
                        ${getOTPStatusIcon(otp.status)} ${getOTPStatusText(otp.status)}
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Thời gian tạo:</div>
                <div class="detail-value">${formatDateTime(otp.created_at)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Thời gian hết hạn:</div>
                <div class="detail-value">${formatDateTime(otp.expires_at)}</div>
            </div>
            ${otp.verified_at ? `
                <div class="detail-row">
                    <div class="detail-label">Thời gian xác thực:</div>
                    <div class="detail-value">${formatDateTime(otp.verified_at)}</div>
                </div>
            ` : ''}
            ${otp.notes ? `
                <div class="detail-row">
                    <div class="detail-label">Ghi chú:</div>
                    <div class="detail-value">${otp.notes}</div>
                </div>
            ` : ''}
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeOTPDetailsModal() {
    document.getElementById('otpDetailsModal').style.display = 'none';
}

async function deleteOTP(otpId) {
    if (!confirm('Bạn có chắc chắn muốn xóa yêu cầu OTP này?')) return;
    
    try {
        const response = await window.app.apiCall(`otp/${otpId}`, 'DELETE');
        
        if (response.success) {
            showNotification('Xóa OTP thành công', 'success');
            loadOTPList();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to delete OTP:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function refreshOTPList() {
    loadOTPList();
}

async function exportOTPData() {
    try {
        const response = await window.app.apiCall('otp/export', 'GET');
        
        if (response.success && response.download_url) {
            const link = document.createElement('a');
            link.href = response.download_url;
            link.download = `otp_data_${new Date().toISOString().split('T')[0]}.xlsx`;
            link.click();
            
            showNotification('Xuất dữ liệu thành công', 'success');
        } else {
            showNotification('Không thể xuất dữ liệu', 'error');
        }
    } catch (error) {
        console.error('Failed to export OTP data:', error);
        showNotification('Lỗi khi xuất dữ liệu', 'error');
    }
}

// Utility functions
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

function getOTPTypeIcon(type) {
    const icons = {
        'login': '🔐',
        'password_reset': '🔑',
        'account_verification': '✅',
        'transaction': '💳',
        'two_factor': '🔒'
    };
    return icons[type] || '🔑';
}

function getOTPTypeText(type) {
    const texts = {
        'login': 'Đăng nhập',
        'password_reset': 'Đặt lại mật khẩu',
        'account_verification': 'Xác thực tài khoản',
        'transaction': 'Giao dịch',
        'two_factor': 'Xác thực 2 yếu tố'
    };
    return texts[type] || type;
}

function getMethodIcon(method) {
    const icons = {
        'email': '📧',
        'sms': '📱',
        'telegram': '📬'
    };
    return icons[method] || '📧';
}

function getMethodText(method) {
    const texts = {
        'email': 'Email',
        'sms': 'SMS',
        'telegram': 'Telegram'
    };
    return texts[method] || method;
}

function getOTPStatusIcon(status) {
    const icons = {
        'pending': '⏳',
        'verified': '✅',
        'expired': '⏰',
        'failed': '❌'
    };
    return icons[status] || '❓';
}

function getOTPStatusText(status) {
    const texts = {
        'pending': 'Đang chờ',
        'verified': 'Đã xác thực',
        'expired': 'Hết hạn',
        'failed': 'Thất bại'
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
    const modals = ['otpDetailsModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}
</script>
