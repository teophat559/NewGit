<?php
/**
 * LoginControlTable Component
 * Bảng điều khiển các phiên đăng nhập tự động
 */
?>

<div class="login-control-table">
    <div class="table-header">
        <div class="table-title">
            <h3><i class="fas fa-robot"></i> Bảng Điều Khiển Đăng Nhập Tự Động</h3>
            <p>Quản lý và theo dõi các phiên đăng nhập tự động</p>
        </div>
        
        <div class="table-actions">
            <button class="btn btn-primary" onclick="showCreateSessionModal()">
                <i class="fas fa-plus"></i> Tạo Phiên Mới
            </button>
            <button class="btn btn-secondary" onclick="refreshSessions()">
                <i class="fas fa-sync-alt"></i> Làm Mới
            </button>
            <button class="btn btn-info" onclick="showChromeProfileModal()">
                <i class="fas fa-cog"></i> Quản Lý Profile
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-group">
            <label for="statusFilter">Trạng thái:</label>
            <select id="statusFilter" class="form-select" onchange="filterSessions()">
                <option value="">Tất cả</option>
                <option value="pending">🟡 Chờ xử lý</option>
                <option value="running">🟢 Đang chạy</option>
                <option value="completed">✅ Hoàn thành</option>
                <option value="failed">❌ Thất bại</option>
                <option value="stopped">⏹️ Dừng</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="platformFilter">Nền tảng:</label>
            <select id="platformFilter" class="form-select" onchange="filterSessions()">
                <option value="">Tất cả</option>
                <option value="Facebook">Facebook</option>
                <option value="Gmail">Gmail</option>
                <option value="Zalo">Zalo</option>
                <option value="Instagram">Instagram</option>
                <option value="Hotmail">Hotmail</option>
                <option value="Yahoo">Yahoo</option>
                <option value="Khác">Khác</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="searchFilter">Tìm kiếm:</label>
            <input type="text" id="searchFilter" class="form-input" placeholder="Tìm theo tên link, profile..." onkeyup="filterSessions()">
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon pending">🟡</div>
            <div class="stat-content">
                <div class="stat-number" id="pendingCount">0</div>
                <div class="stat-label">Chờ xử lý</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon running">🟢</div>
            <div class="stat-content">
                <div class="stat-number" id="runningCount">0</div>
                <div class="stat-label">Đang chạy</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon completed">✅</div>
            <div class="stat-content">
                <div class="stat-number" id="completedCount">0</div>
                <div class="stat-label">Hoàn thành</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon failed">❌</div>
            <div class="stat-content">
                <div class="stat-number" id="failedCount">0</div>
                <div class="stat-label">Thất bại</div>
            </div>
        </div>
    </div>

    <!-- Sessions Table -->
    <div class="table-container">
        <table class="table" id="sessionsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Thời gian</th>
                    <th>Nền tảng</th>
                    <th>Chrome Profile</th>
                    <th>Tên Link</th>
                    <th>Trạng thái</th>
                    <th>Tài khoản</th>
                    <th>Mật khẩu</th>
                    <th>OTP</th>
                    <th>IP</th>
                    <th>Thiết bị</th>
                    <th>Cookie</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="sessionsTableBody">
                <tr>
                    <td colspan="13" class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination" id="sessionsPagination" style="display: none;">
        <!-- Pagination controls will be generated here -->
    </div>
</div>

<!-- Create Session Modal -->
<div id="createSessionModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="createSessionModalTitle">Tạo Phiên Đăng Nhập Mới</h3>
            <span class="close" onclick="closeCreateSessionModal()">&times;</span>
        </div>
        
        <form id="createSessionForm">
            <div class="modal-body">
                <div class="form-group">
                    <label for="sessionPlatform" class="form-label">Nền tảng *</label>
                    <select id="sessionPlatform" name="platform" class="form-input form-select" required>
                        <option value="">Chọn nền tảng...</option>
                        <option value="Facebook">Facebook</option>
                        <option value="Gmail">Gmail</option>
                        <option value="Zalo">Zalo</option>
                        <option value="Instagram">Instagram</option>
                        <option value="Hotmail">Hotmail</option>
                        <option value="Yahoo">Yahoo</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sessionChromeProfile" class="form-label">Chrome Profile *</label>
                    <select id="sessionChromeProfile" name="chrome_profile_id" class="form-input form-select" required>
                        <option value="">Chọn Chrome profile...</option>
                    </select>
                    <small class="text-muted">
                        <a href="#" onclick="showChromeProfileModal()">Tạo profile mới</a>
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="sessionLinkName" class="form-label">Tên Link *</label>
                    <input type="text" id="sessionLinkName" name="link_name" class="form-input" placeholder="Nhập tên link..." required>
                </div>
                
                <div class="form-group">
                    <label for="sessionNotes" class="form-label">Ghi chú</label>
                    <textarea id="sessionNotes" name="notes" class="form-input form-textarea" placeholder="Ghi chú bổ sung..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateSessionModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Tạo Phiên</button>
            </div>
        </form>
    </div>
</div>

<!-- Chrome Profile Modal -->
<div id="chromeProfileModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 id="chromeProfileModalTitle">Quản Lý Chrome Profile</h3>
            <span class="close" onclick="closeChromeProfileModal()">&times;</span>
        </div>
        
        <div class="modal-body">
            <div class="profile-actions">
                <button class="btn btn-primary" onclick="showCreateProfileForm()">
                    <i class="fas fa-plus"></i> Tạo Profile Mới
                </button>
            </div>
            
            <div id="profileForm" style="display: none;">
                <form id="createProfileForm">
                    <div class="form-group">
                        <label for="profileName" class="form-label">Tên Profile *</label>
                        <input type="text" id="profileName" name="name" class="form-input" placeholder="Profile 1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="profileDescription" class="form-label">Mô tả</label>
                        <textarea id="profileDescription" name="description" class="form-input form-textarea" placeholder="Mô tả profile..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Tạo Profile</button>
                        <button type="button" class="btn btn-secondary" onclick="hideCreateProfileForm()">Hủy</button>
                    </div>
                </form>
            </div>
            
            <div id="profilesList">
                <h4>Danh sách Profile</h4>
                <div id="profilesListContent">
                    <div class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Session Details Modal -->
<div id="sessionDetailsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="sessionDetailsModalTitle">Chi Tiết Phiên Đăng Nhập</h3>
            <span class="close" onclick="closeSessionDetailsModal()">&times;</span>
        </div>
        
        <div class="modal-body" id="sessionDetailsContent">
            <!-- Session details will be loaded here -->
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeSessionDetailsModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
let sessions = [];
let chromeProfiles = [];
let currentPage = 1;
const itemsPerPage = 20;

document.addEventListener('DOMContentLoaded', function() {
    loadSessions();
    loadChromeProfiles();
    setupEventListeners();
    setupAutoRefresh();
});

function setupEventListeners() {
    document.getElementById('createSessionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        createSession();
    });
    
    document.getElementById('createProfileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        createChromeProfile();
    });
}

function setupAutoRefresh() {
    // Tự động làm mới dữ liệu mỗi 30 giây
    setInterval(() => {
        if (document.getElementById('sessionsTable').offsetParent !== null) {
            loadSessions();
        }
    }, 30000);
}

async function loadSessions() {
    try {
        const response = await window.app.apiCall('automation/sessions', 'GET');
        if (response.success) {
            sessions = response.sessions || [];
            renderSessionsTable();
            updateStats();
        } else {
            showNotification('Không thể tải danh sách phiên đăng nhập', 'error');
        }
    } catch (error) {
        console.error('Failed to load sessions:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function loadChromeProfiles() {
    try {
        const response = await window.app.apiCall('automation/profiles', 'GET');
        if (response.success) {
            chromeProfiles = response.profiles || [];
            updateChromeProfileSelect();
        }
    } catch (error) {
        console.error('Failed to load Chrome profiles:', error);
    }
}

function renderSessionsTable() {
    const tbody = document.getElementById('sessionsTableBody');
    
    if (sessions.length === 0) {
        tbody.innerHTML = `<tr><td colspan="13" class="text-center text-muted"><i class="fas fa-inbox"></i> Không có phiên đăng nhập nào</td></tr>`;
        return;
    }
    
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageSessions = sessions.slice(startIndex, endIndex);
    
    tbody.innerHTML = pageSessions.map(session => `
        <tr class="session-row" data-session-id="${session.id}">
            <td>${session.id}</td>
            <td>${formatDateTime(session.created_at)}</td>
            <td>
                <span class="platform-badge platform-${session.platform.toLowerCase()}">
                    ${getPlatformIcon(session.platform)} ${session.platform}
                </span>
            </td>
            <td>${session.profile_name || 'N/A'}</td>
            <td>${session.link_name}</td>
            <td>
                <span class="status-badge status-${session.status}">
                    ${getStatusIcon(session.status)} ${getStatusText(session.status)}
                </span>
            </td>
            <td>${session.account || 'N/A'}</td>
            <td>${session.password || 'N/A'}</td>
            <td>${session.otp || 'N/A'}</td>
            <td>${session.ip || 'N/A'}</td>
            <td>${session.device || 'N/A'}</td>
            <td>
                <span class="cookie-status ${session.cookie ? 'has-cookie' : 'no-cookie'}">
                    ${session.cookie ? '✅ Có' : '❌ Không'}
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-outline" onclick="viewSessionDetails(${session.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${session.status === 'pending' ? `
                        <button class="btn btn-sm btn-success" onclick="startAutomation(${session.id})" title="Khởi chạy">
                            <i class="fas fa-play"></i>
                        </button>
                    ` : ''}
                    ${session.status === 'running' ? `
                        <button class="btn btn-sm btn-warning" onclick="stopAutomation(${session.id})" title="Dừng">
                            <i class="fas fa-stop"></i>
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-danger" onclick="deleteSession(${session.id})" title="Xóa">
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
        pending: sessions.filter(s => s.status === 'pending').length,
        running: sessions.filter(s => s.status === 'running').length,
        completed: sessions.filter(s => s.status === 'completed').length,
        failed: sessions.filter(s => s.status === 'failed').length
    };
    
    document.getElementById('pendingCount').textContent = stats.pending;
    document.getElementById('runningCount').textContent = stats.running;
    document.getElementById('completedCount').textContent = stats.completed;
    document.getElementById('failedCount').textContent = stats.failed;
}

function updateChromeProfileSelect() {
    const select = document.getElementById('sessionChromeProfile');
    select.innerHTML = '<option value="">Chọn Chrome profile...</option>';
    
    chromeProfiles.forEach(profile => {
        if (profile.status === 'active') {
            select.innerHTML += `<option value="${profile.id}">${profile.name} - ${profile.description}</option>`;
        }
    });
}

function filterSessions() {
    const statusFilter = document.getElementById('statusFilter').value;
    const platformFilter = document.getElementById('platformFilter').value;
    const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
    
    let filteredSessions = sessions;
    
    if (statusFilter) {
        filteredSessions = filteredSessions.filter(s => s.status === statusFilter);
    }
    
    if (platformFilter) {
        filteredSessions = filteredSessions.filter(s => s.platform === platformFilter);
    }
    
    if (searchFilter) {
        filteredSessions = filteredSessions.filter(s => 
            s.link_name.toLowerCase().includes(searchFilter) ||
            (s.profile_name && s.profile_name.toLowerCase().includes(searchFilter))
        );
    }
    
    // Cập nhật bảng với dữ liệu đã lọc
    const tbody = document.getElementById('sessionsTableBody');
    if (filteredSessions.length === 0) {
        tbody.innerHTML = `<tr><td colspan="13" class="text-center text-muted"><i class="fas fa-search"></i> Không tìm thấy kết quả</td></tr>`;
    } else {
        const startIndex = 0;
        const endIndex = itemsPerPage;
        const pageSessions = filteredSessions.slice(startIndex, endIndex);
        
        tbody.innerHTML = pageSessions.map(session => `
            <tr class="session-row" data-session-id="${session.id}">
                <td>${session.id}</td>
                <td>${formatDateTime(session.created_at)}</td>
                <td>
                    <span class="platform-badge platform-${session.platform.toLowerCase()}">
                        ${getPlatformIcon(session.platform)} ${session.platform}
                    </span>
                </td>
                <td>${session.profile_name || 'N/A'}</td>
                <td>${session.link_name}</td>
                <td>
                    <span class="status-badge status-${session.status}">
                        ${getStatusIcon(session.status)} ${getStatusText(session.status)}
                    </span>
                </td>
                <td>${session.account || 'N/A'}</td>
                <td>${session.password || 'N/A'}</td>
                <td>${session.otp || 'N/A'}</td>
                <td>${session.ip || 'N/A'}</td>
                <td>${session.device || 'N/A'}</td>
                <td>
                    <span class="cookie-status ${session.cookie ? 'has-cookie' : 'no-cookie'}">
                        ${session.cookie ? '✅ Có' : '❌ Không'}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline" onclick="viewSessionDetails(${session.id})" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${session.status === 'pending' ? `
                            <button class="btn btn-sm btn-success" onclick="startAutomation(${session.id})" title="Khởi chạy">
                                <i class="fas fa-play"></i>
                            </button>
                        ` : ''}
                        ${session.status === 'running' ? `
                            <button class="btn btn-sm btn-warning" onclick="stopAutomation(${session.id})" title="Dừng">
                                <i class="fas fa-stop"></i>
                            </button>
                        ` : ''}
                        <button class="btn btn-sm btn-danger" onclick="deleteSession(${session.id})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
}

function updatePagination() {
    const totalPages = Math.ceil(sessions.length / itemsPerPage);
    const pagination = document.getElementById('sessionsPagination');
    
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
    renderSessionsTable();
}

// Modal functions
function showCreateSessionModal() {
    document.getElementById('createSessionModal').style.display = 'block';
    document.getElementById('createSessionForm').reset();
}

function closeCreateSessionModal() {
    document.getElementById('createSessionModal').style.display = 'none';
}

function showChromeProfileModal() {
    document.getElementById('chromeProfileModal').style.display = 'block';
    loadChromeProfiles();
}

function closeChromeProfileModal() {
    document.getElementById('chromeProfileModal').style.display = 'none';
}

function showCreateProfileForm() {
    document.getElementById('profileForm').style.display = 'block';
    document.getElementById('profilesList').style.display = 'none';
}

function hideCreateProfileForm() {
    document.getElementById('profileForm').style.display = 'none';
    document.getElementById('profilesList').style.display = 'block';
    document.getElementById('createProfileForm').reset();
}

// API functions
async function createSession() {
    const form = document.getElementById('createSessionForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    window.app.showLoading(submitBtn);
    
    try {
        const data = {
            platform: formData.get('platform'),
            chrome_profile_id: formData.get('chrome_profile_id'),
            link_name: formData.get('link_name'),
            notes: formData.get('notes')
        };
        
        const response = await window.app.apiCall('automation/sessions', 'POST', data);
        
        if (response.success) {
            showNotification('Tạo phiên đăng nhập thành công', 'success');
            closeCreateSessionModal();
            loadSessions();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to create session:', error);
        showNotification('Lỗi kết nối server', 'error');
    } finally {
        window.app.hideLoading(submitBtn, originalText);
    }
}

async function createChromeProfile() {
    const form = document.getElementById('createProfileForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    window.app.showLoading(submitBtn);
    
    try {
        const data = {
            name: formData.get('name'),
            description: formData.get('description')
        };
        
        const response = await window.app.apiCall('automation/profiles', 'POST', data);
        
        if (response.success) {
            showNotification('Tạo Chrome profile thành công', 'success');
            hideCreateProfileForm();
            loadChromeProfiles();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to create profile:', error);
        showNotification('Lỗi kết nối server', 'error');
    } finally {
        window.app.hideLoading(submitBtn, originalText);
    }
}

async function startAutomation(sessionId) {
    if (!confirm('Bạn có chắc chắn muốn khởi chạy automation cho phiên này?')) return;
    
    try {
        const response = await window.app.apiCall('automation/start', 'POST', { session_id: sessionId });
        
        if (response.success) {
            showNotification('Khởi chạy automation thành công', 'success');
            loadSessions();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to start automation:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function stopAutomation(sessionId) {
    if (!confirm('Bạn có chắc chắn muốn dừng automation cho phiên này?')) return;
    
    try {
        const response = await window.app.apiCall('automation/stop', 'POST', { session_id: sessionId });
        
        if (response.success) {
            showNotification('Dừng automation thành công', 'success');
            loadSessions();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to stop automation:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function deleteSession(sessionId) {
    if (!confirm('Bạn có chắc chắn muốn xóa phiên đăng nhập này?')) return;
    
    try {
        const response = await window.app.apiCall(`automation/sessions?id=${sessionId}`, 'DELETE');
        
        if (response.success) {
            showNotification('Xóa phiên đăng nhập thành công', 'success');
            loadSessions();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to delete session:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function viewSessionDetails(sessionId) {
    try {
        const response = await window.app.apiCall(`automation/session?id=${sessionId}`, 'GET');
        
        if (response.success) {
            const session = response.session;
            showSessionDetailsModal(session);
        } else {
            showNotification('Không thể tải thông tin phiên', 'error');
        }
    } catch (error) {
        console.error('Failed to load session details:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function showSessionDetailsModal(session) {
    const modal = document.getElementById('sessionDetailsModal');
    const content = document.getElementById('sessionDetailsContent');
    
    content.innerHTML = `
        <div class="session-details">
            <div class="detail-row">
                <div class="detail-label">ID:</div>
                <div class="detail-value">${session.id}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Thời gian tạo:</div>
                <div class="detail-value">${formatDateTime(session.created_at)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Cập nhật lần cuối:</div>
                <div class="detail-value">${formatDateTime(session.updated_at)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Nền tảng:</div>
                <div class="detail-value">${session.platform}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Chrome Profile:</div>
                <div class="detail-value">${session.profile_name || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Tên Link:</div>
                <div class="detail-value">${session.link_name}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Trạng thái:</div>
                <div class="detail-value">
                    <span class="status-badge status-${session.status}">
                        ${getStatusIcon(session.status)} ${getStatusText(session.status)}
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Tài khoản:</div>
                <div class="detail-value">${session.account || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Mật khẩu:</div>
                <div class="detail-value">${session.password || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">OTP:</div>
                <div class="detail-value">${session.otp || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">IP:</div>
                <div class="detail-value">${session.ip || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Thiết bị:</div>
                <div class="detail-value">${session.device || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Cookie:</div>
                <div class="detail-value">${session.cookie || 'N/A'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Ghi chú:</div>
                <div class="detail-value">${session.notes || 'N/A'}</div>
            </div>
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeSessionDetailsModal() {
    document.getElementById('sessionDetailsModal').style.display = 'none';
}

function refreshSessions() {
    loadSessions();
}

// Utility functions
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

function getPlatformIcon(platform) {
    const icons = {
        'Facebook': '📘',
        'Gmail': '📧',
        'Zalo': '💬',
        'Instagram': '📷',
        'Hotmail': '📧',
        'Yahoo': '📧',
        'Khác': '🌐'
    };
    return icons[platform] || '🌐';
}

function getStatusIcon(status) {
    const icons = {
        'pending': '🟡',
        'running': '🟢',
        'completed': '✅',
        'failed': '❌',
        'stopped': '⏹️'
    };
    return icons[status] || '❓';
}

function getStatusText(status) {
    const texts = {
        'pending': 'Chờ xử lý',
        'running': 'Đang chạy',
        'completed': 'Hoàn thành',
        'failed': 'Thất bại',
        'stopped': 'Dừng'
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
    const modals = ['createSessionModal', 'chromeProfileModal', 'sessionDetailsModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}
</script>
