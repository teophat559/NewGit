<?php
// UserPage.php - Trang quản lý người dùng
$pageTitle = 'Quản lý Người dùng';
?>

<div class="content-header">
    <h1><i class="fas fa-users"></i> Quản lý Người dùng</h1>
    <p>Quản lý danh sách người dùng, thêm mới, chỉnh sửa và xóa người dùng</p>
</div>

<!-- Action Buttons -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Thao tác</h3>
        <div class="card-actions">
            <button class="btn btn-primary" onclick="showCreateUserModal()">
                <i class="fas fa-user-plus"></i> Thêm người dùng mới
            </button>
            <button class="btn btn-secondary" onclick="refreshUsers()">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
            <button class="btn btn-outline" onclick="exportUsers()">
                <i class="fas fa-download"></i> Xuất Excel
            </button>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh sách người dùng</h3>
        <div class="card-actions">
            <input type="text" id="searchUser" class="form-input" placeholder="Tìm kiếm người dùng..." style="width: 250px;">
            <select id="statusFilter" class="form-input form-select" style="width: 150px;">
                <option value="">Tất cả trạng thái</option>
                <option value="active">Hoạt động</option>
                <option value="inactive">Không hoạt động</option>
                <option value="banned">Bị cấm</option>
            </select>
        </div>
    </div>

    <div class="table-container">
        <table class="table" id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Avatar</th>
                    <th>Thông tin</th>
                    <th>Trạng thái</th>
                    <th>Vai trò</th>
                    <th>Đăng nhập cuối</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit User Modal -->
<div id="userModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="modalTitle">Thêm người dùng mới</h3>
            <span class="close" onclick="closeUserModal()">&times;</span>
        </div>

        <form id="userForm" class="user-form">
            <div class="modal-body">
                <input type="hidden" id="userId" name="id">

                <div class="row" style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="username" class="form-label">Tên đăng nhập *</label>
                        <input type="text" id="username" name="username" class="form-input" required>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                </div>

                <div class="row" style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="fullName" class="form-label">Họ và tên *</label>
                        <input type="text" id="fullName" name="full_name" class="form-input" required>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" class="form-input">
                    </div>
                </div>

                <div class="row" style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="password" class="form-label">Mật khẩu *</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="confirmPassword" class="form-label">Xác nhận mật khẩu *</label>
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-input" required>
                    </div>
                </div>

                <div class="row" style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="userRole" class="form-label">Vai trò</label>
                        <select id="userRole" name="role" class="form-input form-select">
                            <option value="user">Người dùng</option>
                            <option value="moderator">Điều hành viên</option>
                            <option value="admin">Quản trị viên</option>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="userStatus" class="form-label">Trạng thái</label>
                        <select id="userStatus" name="status" class="form-input form-select">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Không hoạt động</option>
                            <option value="banned">Bị cấm</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="avatarUrl" class="form-label">URL Avatar</label>
                    <input type="url" id="avatarUrl" name="avatar_url" class="form-input" placeholder="https://example.com/avatar.jpg">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- User Detail Modal -->
<div id="userDetailModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Chi tiết người dùng</h3>
            <span class="close" onclick="closeUserDetailModal()">&times;</span>
        </div>

        <div class="modal-body" id="userDetailContent">
            <!-- User details will be loaded here -->
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeUserDetailModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
let users = [];
let currentUser = null;

// Load users on page load
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    setupEventListeners();
});

function setupEventListeners() {
    // Search functionality
    document.getElementById('searchUser').addEventListener('input', function(e) {
        filterUsers();
    });

    // Status filter
    document.getElementById('statusFilter').addEventListener('change', function(e) {
        filterUsers();
    });

    // Form submission
    document.getElementById('userForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveUser();
    });
}

async function loadUsers() {
    try {
        const response = await window.app.apiCall('admin/users/list', 'GET');
        if (response.success) {
            users = response.users || [];
            renderUsersTable();
        } else {
            showNotification('Không thể tải danh sách người dùng', 'error');
        }
    } catch (error) {
        console.error('Failed to load users:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function renderUsersTable() {
    const tbody = document.getElementById('usersTableBody');

    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-inbox"></i> Không có người dùng nào
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = users.map(user => `
        <tr>
            <td>${user.id}</td>
            <td>
                <div class="user-avatar" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #e9ecef; display: flex; align-items: center; justify-content: center;">
                    ${user.avatar_url ?
                        `<img src="${user.avatar_url}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">` :
                        `<i class="fas fa-user" style="font-size: 1.2rem; color: #6c757d;"></i>`
                    }
                </div>
            </td>
            <td>
                <div class="user-info">
                    <div style="font-weight: 500; margin-bottom: 5px;">${user.full_name || 'N/A'}</div>
                    <div style="font-size: 0.875rem; color: #6c757d;">
                        <div>@${user.username}</div>
                        <div>${user.email}</div>
                        ${user.phone ? `<div>📱 ${user.phone}</div>` : ''}
                    </div>
                </div>
            </td>
            <td>
                <span class="badge badge-${getStatusBadgeClass(user.status)}">
                    ${getStatusText(user.status)}
                </span>
            </td>
            <td>
                <span class="badge badge-${getRoleBadgeClass(user.role)}">
                    ${getRoleText(user.role)}
                </span>
            </td>
            <td>
                <div style="font-size: 0.875rem;">
                    ${user.last_login ? formatDate(user.last_login) : 'Chưa đăng nhập'}
                </div>
            </td>
            <td>
                <div class="btn-group" style="display: flex; gap: 5px;">
                    <button class="btn btn-sm btn-outline" onclick="viewUser(${user.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="editUser(${user.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function filterUsers() {
    const searchTerm = document.getElementById('searchUser').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;

    let filtered = users;

    // Filter by search term
    if (searchTerm) {
        filtered = filtered.filter(user =>
            user.username.toLowerCase().includes(searchTerm) ||
            user.email.toLowerCase().includes(searchTerm) ||
            (user.full_name && user.full_name.toLowerCase().includes(searchTerm))
        );
    }

    // Filter by status
    if (statusFilter) {
        filtered = filtered.filter(user => user.status === statusFilter);
    }

    renderFilteredUsers(filtered);
}

function renderFilteredUsers(filteredUsers) {
    const tbody = document.getElementById('usersTableBody');

    if (filteredUsers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-search"></i> Không tìm thấy người dùng nào
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filteredUsers.map(user => `
        <tr>
            <td>${user.id}</td>
            <td>
                <div class="user-avatar" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #e9ecef; display: flex; align-items: center; justify-content: center;">
                    ${user.avatar_url ?
                        `<img src="${user.avatar_url}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">` :
                        `<i class="fas fa-user" style="font-size: 1.2rem; color: #6c757d;"></i>`
                    }
                </div>
            </td>
            <td>
                <div class="user-info">
                    <div style="font-weight: 500; margin-bottom: 5px;">${user.full_name || 'N/A'}</div>
                    <div style="font-size: 0.875rem; color: #6c757d;">
                        <div>@${user.username}</div>
                        <div>${user.email}</div>
                        ${user.phone ? `<div>📱 ${user.phone}</div>` : ''}
                    </div>
                </div>
            </td>
            <td>
                <span class="badge badge-${getStatusBadgeClass(user.status)}">
                    ${getStatusText(user.status)}
                </span>
            </td>
            <td>
                <span class="badge badge-${getRoleBadgeClass(user.role)}">
                    ${getRoleText(user.role)}
                </span>
            </td>
            <td>
                <div style="font-size: 0.875rem;">
                    ${user.last_login ? formatDate(user.last_login) : 'Chưa đăng nhập'}
                </div>
            </td>
            <td>
                <div class="btn-group" style="display: flex; gap: 5px;">
                    <button class="btn btn-sm btn-outline" onclick="viewUser(${user.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="editUser(${user.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function showCreateUserModal() {
    currentUser = null;
    document.getElementById('modalTitle').textContent = 'Thêm người dùng mới';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userModal').style.display = 'block';
}

function editUser(userId) {
    const user = users.find(u => u.id === userId);
    if (!user) return;

    currentUser = user;
    document.getElementById('modalTitle').textContent = 'Chỉnh sửa người dùng';

    // Fill form with user data
    document.getElementById('userId').value = user.id;
    document.getElementById('username').value = user.username;
    document.getElementById('email').value = user.email;
    document.getElementById('fullName').value = user.full_name || '';
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('userRole').value = user.role;
    document.getElementById('userStatus').value = user.status;
    document.getElementById('avatarUrl').value = user.avatar_url || '';

    // Remove required from password fields when editing
    document.getElementById('password').required = false;
    document.getElementById('confirmPassword').required = false;

    document.getElementById('userModal').style.display = 'block';
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
    currentUser = null;

    // Reset password requirements
    document.getElementById('password').required = true;
    document.getElementById('confirmPassword').required = true;
}

async function saveUser() {
    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    // Show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading-spinner"></span> Đang lưu...';

    try {
        const data = {
            username: formData.get('username'),
            email: formData.get('email'),
            full_name: formData.get('full_name'),
            phone: formData.get('phone'),
            role: formData.get('role'),
            status: formData.get('status'),
            avatar_url: formData.get('avatar_url')
        };

        // Add password if provided
        if (formData.get('password')) {
            if (formData.get('password') !== formData.get('confirm_password')) {
                showNotification('Mật khẩu xác nhận không khớp', 'error');
                return;
            }
            data.password = formData.get('password');
        }

        const endpoint = currentUser ? `admin/users/update/${currentUser.id}` : 'admin/users/create';
        const method = currentUser ? 'PUT' : 'POST';

        const response = await window.app.apiCall(endpoint, method, data);

        if (response.success) {
            showNotification(
                currentUser ? 'Cập nhật người dùng thành công' : 'Tạo người dùng thành công',
                'success'
            );
            closeUserModal();
            loadUsers();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to save user:', error);
        showNotification('Lỗi kết nối server', 'error');
    } finally {
        // Hide loading
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

async function deleteUser(userId) {
    if (!confirm('Bạn có chắc chắn muốn xóa người dùng này?')) return;

    try {
        const response = await window.app.apiCall(`admin/users/delete/${userId}`, 'DELETE');

        if (response.success) {
            showNotification('Xóa người dùng thành công', 'success');
            loadUsers();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to delete user:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function viewUser(userId) {
    const user = users.find(u => u.id === userId);
    if (!user) return;

    try {
        const response = await window.app.apiCall(`admin/users/detail/${userId}`, 'GET');
        if (response.success) {
            const userDetail = response.user;
            showUserDetailModal(userDetail);
        }
    } catch (error) {
        console.error('Failed to load user details:', error);
        showNotification('Không thể tải thông tin người dùng', 'error');
    }
}

function showUserDetailModal(user) {
    const content = document.getElementById('userDetailContent');
    content.innerHTML = `
        <div class="user-detail">
            <div style="text-align: center; margin-bottom: 20px;">
                <div class="user-avatar-large" style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; background: #e9ecef; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    ${user.avatar_url ?
                        `<img src="${user.avatar_url}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">` :
                        `<i class="fas fa-user" style="font-size: 3rem; color: #6c757d;"></i>`
                    }
                </div>
                <h4 style="margin: 0; color: #2c3e50;">${user.full_name || 'N/A'}</h4>
                <p style="margin: 5px 0; color: #7f8c8d;">@${user.username}</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h5 style="margin-bottom: 10px; color: #2c3e50;">Thông tin cơ bản</h5>
                    <div style="margin-bottom: 8px;">
                        <strong>Email:</strong> ${user.email}
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Điện thoại:</strong> ${user.phone || 'N/A'}
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Vai trò:</strong>
                        <span class="badge badge-${getRoleBadgeClass(user.role)}">
                            ${getRoleText(user.role)}
                        </span>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Trạng thái:</strong>
                        <span class="badge badge-${getStatusBadgeClass(user.status)}">
                            ${getStatusText(user.status)}
                        </span>
                    </div>
                </div>

                <div>
                    <h5 style="margin-bottom: 10px; color: #2c3e50;">Thông tin hệ thống</h5>
                    <div style="margin-bottom: 8px;">
                        <strong>ID:</strong> ${user.id}
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Ngày tạo:</strong> ${formatDate(user.created_at)}
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Cập nhật cuối:</strong> ${formatDate(user.updated_at)}
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Đăng nhập cuối:</strong> ${user.last_login ? formatDate(user.last_login) : 'Chưa đăng nhập'}
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('userDetailModal').style.display = 'block';
}

function closeUserDetailModal() {
    document.getElementById('userDetailModal').style.display = 'none';
}

function refreshUsers() {
    loadUsers();
    document.getElementById('searchUser').value = '';
    document.getElementById('statusFilter').value = '';
}

function exportUsers() {
    // TODO: Implement Excel export
    showNotification('Tính năng xuất Excel đang được phát triển', 'info');
}

// Utility functions
function getStatusBadgeClass(status) {
    switch (status) {
        case 'active': return 'success';
        case 'inactive': return 'secondary';
        case 'banned': return 'danger';
        default: return 'secondary';
    }
}

function getStatusText(status) {
    switch (status) {
        case 'active': return 'Hoạt động';
        case 'inactive': return 'Không hoạt động';
        case 'banned': return 'Bị cấm';
        default: return status;
    }
}

function getRoleBadgeClass(role) {
    switch (role) {
        case 'admin': return 'danger';
        case 'moderator': return 'warning';
        case 'user': return 'primary';
        default: return 'secondary';
    }
}

function getRoleText(role) {
    switch (role) {
        case 'admin': return 'Quản trị viên';
        case 'moderator': return 'Điều hành viên';
        case 'user': return 'Người dùng';
        default: return role;
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

// Close modals when clicking outside
window.onclick = function(event) {
    const userModal = document.getElementById('userModal');
    const userDetailModal = document.getElementById('userDetailModal');

    if (event.target === userModal) {
        closeUserModal();
    }

    if (event.target === userDetailModal) {
        closeUserDetailModal();
    }
}
</script>
