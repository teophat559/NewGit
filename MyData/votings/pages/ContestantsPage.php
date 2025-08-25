<?php
// ContestantsPage.php - Trang quản lý thí sinh hoàn chỉnh
$pageTitle = 'Quản lý Thí sinh';
?>

<div class="content-header">
    <h1><i class="fas fa-users"></i> Quản lý Thí sinh</h1>
    <p>Quản lý danh sách thí sinh tham gia cuộc thi, thêm mới, chỉnh sửa và xóa thông tin</p>
</div>

<!-- Contest Selection -->
<div class="contest-selector">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Chọn Cuộc thi</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="contestSelect" class="form-label">Cuộc thi:</label>
                <select id="contestSelect" class="form-input form-select" onchange="loadContestants()">
                    <option value="">Chọn cuộc thi...</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Contestants Management -->
<div class="contestants-management" id="contestantsManagement" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Danh sách Thí sinh</h3>
            <div class="card-actions">
                <button class="btn btn-primary" onclick="showAddContestantModal()">
                    <i class="fas fa-user-plus"></i> Thêm thí sinh mới
                </button>
                <button class="btn btn-secondary" onclick="refreshContestants()">
                    <i class="fas fa-sync-alt"></i> Làm mới
                </button>
                <button class="btn btn-info" onclick="exportContestants()">
                    <i class="fas fa-download"></i> Xuất Excel
                </button>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-section">
            <div class="filter-group">
                <label for="statusFilter">Trạng thái:</label>
                <select id="statusFilter" class="form-select" onchange="filterContestants()">
                    <option value="">Tất cả</option>
                    <option value="active">Hoạt động</option>
                    <option value="inactive">Không hoạt động</option>
                    <option value="disqualified">Bị loại</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="searchFilter">Tìm kiếm:</label>
                <input type="text" id="searchFilter" class="form-input" placeholder="Tìm theo tên, email, số điện thoại..." onkeyup="filterContestants()">
            </div>
            
            <div class="filter-group">
                <label for="categoryFilter">Danh mục:</label>
                <select id="categoryFilter" class="form-select" onchange="filterContestants()">
                    <option value="">Tất cả</option>
                </select>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">👥</div>
                <div class="stat-content">
                    <div class="stat-number" id="totalContestants">0</div>
                    <div class="stat-label">Tổng thí sinh</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">✅</div>
                <div class="stat-content">
                    <div class="stat-number" id="activeContestants">0</div>
                    <div class="stat-label">Đang hoạt động</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">🟡</div>
                <div class="stat-content">
                    <div class="stat-number" id="pendingContestants">0</div>
                    <div class="stat-label">Chờ duyệt</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon disqualified">❌</div>
                <div class="stat-content">
                    <div class="stat-number" id="disqualifiedContestants">0</div>
                    <div class="stat-label">Bị loại</div>
                </div>
            </div>
        </div>

        <!-- Contestants Table -->
        <div class="table-container">
            <table class="table" id="contestantsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ảnh</th>
                        <th>Họ và tên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Ngày sinh</th>
                        <th>Danh mục</th>
                        <th>Trạng thái</th>
                        <th>Điểm</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="contestantsTableBody">
                    <tr>
                        <td colspan="10" class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="contestantsPagination" style="display: none;">
            <!-- Pagination controls will be generated here -->
        </div>
    </div>
</div>

<!-- Add/Edit Contestant Modal -->
<div id="contestantModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="contestantModalTitle">Thêm thí sinh mới</h3>
            <span class="close" onclick="closeContestantModal()">&times;</span>
        </div>
        
        <form id="contestantForm" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" id="contestantId" name="id">
                <input type="hidden" id="contestId" name="contest_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fullName" class="form-label">Họ và tên *</label>
                        <input type="text" id="fullName" name="full_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="birthDate" class="form-label">Ngày sinh</label>
                        <input type="date" id="birthDate" name="birth_date" class="form-input">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category" class="form-label">Danh mục *</label>
                        <select id="category" name="category" class="form-input form-select" required>
                            <option value="">Chọn danh mục...</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select id="status" name="status" class="form-input form-select">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Không hoạt động</option>
                            <option value="disqualified">Bị loại</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea id="description" name="description" class="form-input form-textarea" rows="3" placeholder="Mô tả về thí sinh..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="avatar" class="form-label">Ảnh đại diện</label>
                    <input type="file" id="avatar" name="avatar" class="form-input" accept="image/*">
                    <small class="text-muted">Hỗ trợ: JPG, PNG, GIF. Kích thước tối đa: 5MB</small>
                </div>
                
                <div class="form-group">
                    <label for="socialLinks" class="form-label">Liên kết mạng xã hội</label>
                    <div class="social-links-inputs">
                        <div class="social-link-input">
                            <input type="url" name="social_links[facebook]" class="form-input" placeholder="Facebook URL">
                        </div>
                        <div class="social-link-input">
                            <input type="url" name="social_links[instagram]" class="form-input" placeholder="Instagram URL">
                        </div>
                        <div class="social-link-input">
                            <input type="url" name="social_links[youtube]" class="form-input" placeholder="YouTube URL">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeContestantModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thí sinh</button>
            </div>
        </form>
    </div>
</div>

<!-- Contestant Details Modal -->
<div id="contestantDetailsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="contestantDetailsModalTitle">Chi tiết thí sinh</h3>
            <span class="close" onclick="closeContestantDetailsModal()">&times;</span>
        </div>
        
        <div class="modal-body" id="contestantDetailsContent">
            <!-- Contestant details will be loaded here -->
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeContestantDetailsModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
let contests = [];
let contestants = [];
let currentPage = 1;
const itemsPerPage = 20;

document.addEventListener('DOMContentLoaded', function() {
    loadContests();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('contestantForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveContestant();
    });
}

async function loadContests() {
    try {
        const response = await window.app.apiCall('contests/list', 'GET');
        if (response.success) {
            contests = response.contests || [];
            updateContestSelect();
        } else {
            showNotification('Không thể tải danh sách cuộc thi', 'error');
        }
    } catch (error) {
        console.error('Failed to load contests:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function updateContestSelect() {
    const select = document.getElementById('contestSelect');
    select.innerHTML = '<option value="">Chọn cuộc thi...</option>';
    
    contests.forEach(contest => {
        if (contest.status === 'active' || contest.status === 'voting') {
            select.innerHTML += `<option value="${contest.id}">${contest.title}</option>`;
        }
    });
}

async function loadContestants() {
    const contestId = document.getElementById('contestSelect').value;
    if (!contestId) {
        document.getElementById('contestantsManagement').style.display = 'none';
        return;
    }
    
    try {
        const response = await window.app.apiCall(`contests/${contestId}/contestants`, 'GET');
        if (response.success) {
            contestants = response.contestants || [];
            renderContestantsTable();
            updateStats();
            document.getElementById('contestantsManagement').style.display = 'block';
        } else {
            showNotification('Không thể tải danh sách thí sinh', 'error');
        }
    } catch (error) {
        console.error('Failed to load contestants:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function renderContestantsTable() {
    const tbody = document.getElementById('contestantsTableBody');
    
    if (contestants.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted"><i class="fas fa-inbox"></i> Không có thí sinh nào</td></tr>`;
        return;
    }
    
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageContestants = contestants.slice(startIndex, endIndex);
    
    tbody.innerHTML = pageContestants.map(contestant => `
        <tr class="contestant-row" data-contestant-id="${contestant.id}">
            <td>${contestant.id}</td>
            <td>
                <div class="contestant-avatar">
                    ${contestant.avatar_url ? 
                        `<img src="${contestant.avatar_url}" alt="Avatar" class="avatar-img">` : 
                        `<div class="avatar-placeholder">${contestant.full_name.charAt(0).toUpperCase()}</div>`
                    }
                </div>
            </td>
            <td>
                <div class="contestant-info">
                    <div class="contestant-name">${contestant.full_name}</div>
                    <div class="contestant-email">${contestant.email}</div>
                </div>
            </td>
            <td>${contestant.email}</td>
            <td>${contestant.phone || 'N/A'}</td>
            <td>${contestant.birth_date ? formatDate(contestant.birth_date) : 'N/A'}</td>
            <td>
                <span class="category-badge category-${contestant.category}">
                    ${getCategoryText(contestant.category)}
                </span>
            </td>
            <td>
                <span class="status-badge status-${contestant.status}">
                    ${getStatusIcon(contestant.status)} ${getStatusText(contestant.status)}
                </span>
            </td>
            <td>
                <div class="score-display">
                    <span class="score-number">${contestant.score || 0}</span>
                    <span class="score-label">điểm</span>
                </div>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-outline" onclick="viewContestantDetails(${contestant.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="editContestant(${contestant.id})" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteContestant(${contestant.id})" title="Xóa">
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
        total: contestants.length,
        active: contestants.filter(c => c.status === 'active').length,
        pending: contestants.filter(c => c.status === 'inactive').length,
        disqualified: contestants.filter(c => c.status === 'disqualified').length
    };
    
    document.getElementById('totalContestants').textContent = stats.total;
    document.getElementById('activeContestants').textContent = stats.active;
    document.getElementById('pendingContestants').textContent = stats.pending;
    document.getElementById('disqualifiedContestants').textContent = stats.disqualified;
}

function filterContestants() {
    const statusFilter = document.getElementById('statusFilter').value;
    const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    
    let filteredContestants = contestants;
    
    if (statusFilter) {
        filteredContestants = filteredContestants.filter(c => c.status === statusFilter);
    }
    
    if (categoryFilter) {
        filteredContestants = filteredContestants.filter(c => c.category === categoryFilter);
    }
    
    if (searchFilter) {
        filteredContestants = filteredContestants.filter(c => 
            c.full_name.toLowerCase().includes(searchFilter) ||
            c.email.toLowerCase().includes(searchFilter) ||
            (c.phone && c.phone.includes(searchFilter))
        );
    }
    
    // Cập nhật bảng với dữ liệu đã lọc
    const tbody = document.getElementById('contestantsTableBody');
    if (filteredContestants.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted"><i class="fas fa-search"></i> Không tìm thấy kết quả</td></tr>`;
    } else {
        const startIndex = 0;
        const endIndex = itemsPerPage;
        const pageContestants = filteredContestants.slice(startIndex, endIndex);
        
        tbody.innerHTML = pageContestants.map(contestant => `
            <tr class="contestant-row" data-contestant-id="${contestant.id}">
                <td>${contestant.id}</td>
                <td>
                    <div class="contestant-avatar">
                        ${contestant.avatar_url ? 
                            `<img src="${contestant.avatar_url}" alt="Avatar" class="avatar-img">` : 
                            `<div class="avatar-placeholder">${contestant.full_name.charAt(0).toUpperCase()}</div>`
                        }
                    </div>
                </td>
                <td>
                    <div class="contestant-info">
                        <div class="contestant-name">${contestant.full_name}</div>
                        <div class="contestant-email">${contestant.email}</div>
                    </div>
                </td>
                <td>${contestant.email}</td>
                <td>${contestant.phone || 'N/A'}</td>
                <td>${contestant.birth_date ? formatDate(contestant.birth_date) : 'N/A'}</td>
                <td>
                    <span class="category-badge category-${contestant.category}">
                        ${getCategoryText(contestant.category)}
                    </span>
                </td>
                <td>
                    <span class="status-badge status-${contestant.status}">
                        ${getStatusIcon(contestant.status)} ${getStatusText(contestant.status)}
                    </span>
                </td>
                <td>
                    <div class="score-display">
                        <span class="score-number">${contestant.score || 0}</span>
                        <span class="score-label">điểm</span>
                    </div>
                </div>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline" onclick="viewContestantDetails(${contestant.id})" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline" onclick="editContestant(${contestant.id})" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteContestant(${contestant.id})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
}

function updatePagination() {
    const totalPages = Math.ceil(contestants.length / itemsPerPage);
    const pagination = document.getElementById('contestantsPagination');
    
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
    renderContestantsTable();
}

// Modal functions
function showAddContestantModal() {
    const contestId = document.getElementById('contestSelect').value;
    if (!contestId) {
        showNotification('Vui lòng chọn cuộc thi trước', 'warning');
        return;
    }
    
    document.getElementById('contestantModalTitle').textContent = 'Thêm thí sinh mới';
    document.getElementById('contestantForm').reset();
    document.getElementById('contestantId').value = '';
    document.getElementById('contestId').value = contestId;
    document.getElementById('contestantModal').style.display = 'block';
}

function closeContestantModal() {
    document.getElementById('contestantModal').style.display = 'none';
}

function editContestant(contestantId) {
    const contestant = contestants.find(c => c.id === contestantId);
    if (!contestant) return;
    
    document.getElementById('contestantModalTitle').textContent = 'Chỉnh sửa thí sinh';
    document.getElementById('contestantId').value = contestant.id;
    document.getElementById('contestId').value = contestant.contest_id;
    document.getElementById('fullName').value = contestant.full_name;
    document.getElementById('email').value = contestant.email;
    document.getElementById('phone').value = contestant.phone || '';
    document.getElementById('birthDate').value = contestant.birth_date || '';
    document.getElementById('category').value = contestant.category;
    document.getElementById('status').value = contestant.status;
    document.getElementById('description').value = contestant.description || '';
    
    document.getElementById('contestantModal').style.display = 'block';
}

// API functions
async function saveContestant() {
    const form = document.getElementById('contestantForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    window.app.showLoading(submitBtn);
    
    try {
        const contestantId = formData.get('id');
        const endpoint = contestantId ? `contests/contestants/${contestantId}` : 'contests/contestants';
        const method = contestantId ? 'PUT' : 'POST';
        
        const response = await window.app.apiCall(endpoint, method, formData);
        
        if (response.success) {
            showNotification(contestantId ? 'Cập nhật thí sinh thành công' : 'Thêm thí sinh thành công', 'success');
            closeContestantModal();
            loadContestants();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to save contestant:', error);
        showNotification('Lỗi kết nối server', 'error');
    } finally {
        window.app.hideLoading(submitBtn, originalText);
    }
}

async function deleteContestant(contestantId) {
    if (!confirm('Bạn có chắc chắn muốn xóa thí sinh này?')) return;
    
    try {
        const response = await window.app.apiCall(`contests/contestants/${contestantId}`, 'DELETE');
        
        if (response.success) {
            showNotification('Xóa thí sinh thành công', 'success');
            loadContestants();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to delete contestant:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function viewContestantDetails(contestantId) {
    try {
        const response = await window.app.apiCall(`contests/contestants/${contestantId}`, 'GET');
        
        if (response.success) {
            const contestant = response.contestant;
            showContestantDetailsModal(contestant);
        } else {
            showNotification('Không thể tải thông tin thí sinh', 'error');
        }
    } catch (error) {
        console.error('Failed to load contestant details:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function showContestantDetailsModal(contestant) {
    const modal = document.getElementById('contestantDetailsModal');
    const content = document.getElementById('contestantDetailsContent');
    
    content.innerHTML = `
        <div class="contestant-details">
            <div class="contestant-header">
                <div class="contestant-avatar-large">
                    ${contestant.avatar_url ? 
                        `<img src="${contestant.avatar_url}" alt="Avatar" class="avatar-img-large">` : 
                        `<div class="avatar-placeholder-large">${contestant.full_name.charAt(0).toUpperCase()}</div>`
                    }
                </div>
                <div class="contestant-info-large">
                    <h4>${contestant.full_name}</h4>
                    <p class="contestant-email-large">${contestant.email}</p>
                    <div class="contestant-status-large">
                        <span class="status-badge status-${contestant.status}">
                            ${getStatusIcon(contestant.status)} ${getStatusText(contestant.status)}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="contestant-details-grid">
                <div class="detail-item">
                    <label>ID:</label>
                    <span>${contestant.id}</span>
                </div>
                <div class="detail-item">
                    <label>Số điện thoại:</label>
                    <span>${contestant.phone || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <label>Ngày sinh:</label>
                    <span>${contestant.birth_date ? formatDate(contestant.birth_date) : 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <label>Danh mục:</label>
                    <span class="category-badge category-${contestant.category}">
                        ${getCategoryText(contestant.category)}
                    </span>
                </div>
                <div class="detail-item">
                    <label>Điểm:</label>
                    <span class="score-display-large">
                        <span class="score-number">${contestant.score || 0}</span>
                        <span class="score-label">điểm</span>
                    </span>
                </div>
                <div class="detail-item">
                    <label>Ngày tham gia:</label>
                    <span>${formatDateTime(contestant.created_at)}</span>
                </div>
            </div>
            
            ${contestant.description ? `
                <div class="detail-section">
                    <label>Mô tả:</label>
                    <p>${contestant.description}</p>
                </div>
            ` : ''}
            
            ${contestant.social_links ? `
                <div class="detail-section">
                    <label>Liên kết mạng xã hội:</label>
                    <div class="social-links-display">
                        ${Object.entries(contestant.social_links).map(([platform, url]) => 
                            url ? `<a href="${url}" target="_blank" class="social-link ${platform}">${platform}</a>` : ''
                        ).join('')}
                    </div>
                </div>
            ` : ''}
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeContestantDetailsModal() {
    document.getElementById('contestantDetailsModal').style.display = 'none';
}

function refreshContestants() {
    loadContestants();
}

async function exportContestants() {
    try {
        const contestId = document.getElementById('contestSelect').value;
        if (!contestId) {
            showNotification('Vui lòng chọn cuộc thi trước', 'warning');
            return;
        }
        
        const response = await window.app.apiCall(`contests/${contestId}/contestants/export`, 'GET');
        
        if (response.success && response.download_url) {
            // Tạo link download
            const link = document.createElement('a');
            link.href = response.download_url;
            link.download = `contestants_${contestId}_${new Date().toISOString().split('T')[0]}.xlsx`;
            link.click();
            
            showNotification('Xuất dữ liệu thành công', 'success');
        } else {
            showNotification('Không thể xuất dữ liệu', 'error');
        }
    } catch (error) {
        console.error('Failed to export contestants:', error);
        showNotification('Lỗi khi xuất dữ liệu', 'error');
    }
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

function getCategoryText(category) {
    const categories = {
        'singing': 'Ca hát',
        'dancing': 'Nhảy múa',
        'acting': 'Diễn xuất',
        'modeling': 'Người mẫu',
        'other': 'Khác'
    };
    return categories[category] || category;
}

function getStatusIcon(status) {
    const icons = {
        'active': '✅',
        'inactive': '🟡',
        'disqualified': '❌'
    };
    return icons[status] || '❓';
}

function getStatusText(status) {
    const texts = {
        'active': 'Hoạt động',
        'inactive': 'Không hoạt động',
        'disqualified': 'Bị loại'
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
    const modals = ['contestantModal', 'contestantDetailsModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}
</script>
