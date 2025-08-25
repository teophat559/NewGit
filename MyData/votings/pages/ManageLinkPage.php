<?php
// ManageLinkPage.php - Trang quản lý link hoàn chỉnh
$pageTitle = 'Quản lý liên kết';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quản lý liên kết</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="content-header">
    <h1><i class="fas fa-link"></i> Quản lý liên kết</h1>
    <p>Quản lý tất cả liên kết trong hệ thống, tạo mới, chỉnh sửa và theo dõi hiệu suất</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh sách Liên kết</h3>
        <div class="card-actions">
            <button class="btn btn-primary" onclick="showCreateLinkModal()">
                <i class="fas fa-plus"></i> Tạo liên kết mới
            </button>
            <button class="btn btn-secondary" onclick="refreshLinks()">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
            <button class="btn btn-info" onclick="exportLinks()">
                <i class="fas fa-download"></i> Xuất dữ liệu
            </button>
            <button class="btn btn-warning" onclick="bulkActions()">
                <i class="fas fa-tasks"></i> Thao tác hàng loạt
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-group">
            <label for="categoryFilter">Danh mục:</label>
            <select id="categoryFilter" class="form-select" onchange="filterLinks()">
                <option value="">Tất cả</option>
                <option value="contest">Cuộc thi</option>
                <option value="user">Người dùng</option>
                <option value="admin">Admin</option>
                <option value="external">Bên ngoài</option>
                <option value="internal">Nội bộ</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="statusFilter">Trạng thái:</label>
            <select id="statusFilter" class="form-select" onchange="filterLinks()">
                <option value="">Tất cả</option>
                <option value="active">Hoạt động</option>
                <option value="inactive">Không hoạt động</option>
                <option value="expired">Hết hạn</option>
                <option value="blocked">Bị chặn</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="typeFilter">Loại:</label>
            <select id="typeFilter" class="form-select" onchange="filterLinks()">
                <option value="">Tất cả</option>
                <option value="public">Công khai</option>
                <option value="private">Riêng tư</option>
                <option value="temporary">Tạm thời</option>
                <option value="permanent">Vĩnh viễn</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="searchFilter">Tìm kiếm:</label>
            <input type="text" id="searchFilter" class="form-input" placeholder="Tìm theo tên, URL, mô tả..." onkeyup="filterLinks()">
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon total">🔗</div>
            <div class="stat-content">
                <div class="stat-number" id="totalLinks">0</div>
                <div class="stat-label">Tổng liên kết</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon active">✅</div>
            <div class="stat-content">
                <div class="stat-number" id="activeLinks">0</div>
                <div class="stat-label">Đang hoạt động</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon clicks">👆</div>
            <div class="stat-content">
                <div class="stat-number" id="totalClicks">0</div>
                <div class="stat-label">Tổng lượt click</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon conversion">📊</div>
            <div class="stat-content">
                <div class="stat-number" id="conversionRate">0%</div>
                <div class="stat-label">Tỷ lệ chuyển đổi</div>
            </div>
        </div>
    </div>

    <!-- Links Table -->
    <div class="table-container">
        <table class="table" id="linksTable">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                    </th>
                    <th>ID</th>
                    <th>Tên liên kết</th>
                    <th>URL gốc</th>
                    <th>URL rút gọn</th>
                    <th>Danh mục</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th>Lượt click</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="linksTableBody">
                <tr>
                    <td colspan="11" class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination" id="linksPagination" style="display: none;">
        <!-- Pagination controls will be generated here -->
    </div>
</div>

<!-- Create/Edit Link Modal -->
<div id="linkModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="linkModalTitle">Tạo liên kết mới</h3>
            <span class="close" onclick="closeLinkModal()">&times;</span>
        </div>
        
        <form id="linkForm">
            <div class="modal-body">
                <input type="hidden" id="linkId" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="linkName" class="form-label">Tên liên kết *</label>
                        <input type="text" id="linkName" name="name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="linkCategory" class="form-label">Danh mục *</label>
                        <select id="linkCategory" name="category" class="form-input form-select" required>
                            <option value="">Chọn danh mục...</option>
                            <option value="contest">Cuộc thi</option>
                            <option value="user">Người dùng</option>
                            <option value="admin">Admin</option>
                            <option value="external">Bên ngoài</option>
                            <option value="internal">Nội bộ</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="originalUrl" class="form-label">URL gốc *</label>
                        <input type="url" id="originalUrl" name="original_url" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customSlug" class="form-label">Slug tùy chỉnh</label>
                        <input type="text" id="customSlug" name="custom_slug" class="form-input" placeholder="Để trống để tự động tạo">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="linkType" class="form-label">Loại liên kết</label>
                        <select id="linkType" name="type" class="form-input form-select">
                            <option value="public">Công khai</option>
                            <option value="private">Riêng tư</option>
                            <option value="temporary">Tạm thời</option>
                            <option value="permanent">Vĩnh viễn</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="linkStatus" class="form-label">Trạng thái</label>
                        <select id="linkStatus" name="status" class="form-input form-select">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Không hoạt động</option>
                            <option value="expired">Hết hạn</option>
                            <option value="blocked">Bị chặn</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="linkDescription" class="form-label">Mô tả</label>
                    <textarea id="linkDescription" name="description" class="form-input form-textarea" rows="3" placeholder="Mô tả về liên kết..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiryDate" class="form-label">Ngày hết hạn</label>
                        <input type="datetime-local" id="expiryDate" name="expiry_date" class="form-input">
                        <small class="text-muted">Để trống nếu không có hạn</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="maxClicks" class="form-label">Giới hạn lượt click</label>
                        <input type="number" id="maxClicks" name="max_clicks" class="form-input" min="0" placeholder="0 = không giới hạn">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="linkTags" class="form-label">Tags</label>
                    <input type="text" id="linkTags" name="tags" class="form-input" placeholder="Nhập tags, phân cách bằng dấu phẩy">
                    <small class="text-muted">Ví dụ: contest, voting, user</small>
                </div>
                
                <div class="form-group">
                    <label for="trackingEnabled" class="form-label">
                        <input type="checkbox" id="trackingEnabled" name="tracking_enabled" checked>
                        Bật theo dõi thống kê
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeLinkModal()">Hủy</button>
                <button type="button" class="btn btn-info" onclick="previewLink()">Xem trước</button>
                <button type="submit" class="btn btn-primary">Lưu liên kết</button>
            </div>
        </form>
    </div>
</div>

<!-- Link Details Modal -->
<div id="linkDetailsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Chi tiết liên kết</h3>
            <span class="close" onclick="closeLinkDetailsModal()">&times;</span>
        </div>
        
        <div class="modal-body" id="linkDetailsContent">
            <!-- Link details will be loaded here -->
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeLinkDetailsModal()">Đóng</button>
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div id="bulkActionsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Thao tác hàng loạt</h3>
            <span class="close" onclick="closeBulkActionsModal()">&times;</span>
        </div>
        
        <div class="modal-body">
            <div class="form-group">
                <label for="bulkAction" class="form-label">Chọn hành động:</label>
                <select id="bulkAction" class="form-input form-select">
                    <option value="">Chọn hành động...</option>
                    <option value="activate">Kích hoạt</option>
                    <option value="deactivate">Vô hiệu hóa</option>
                    <option value="delete">Xóa</option>
                    <option value="change_category">Thay đổi danh mục</option>
                    <option value="change_status">Thay đổi trạng thái</option>
                </select>
            </div>
            
            <div id="bulkActionOptions" style="display: none;">
                <div class="form-group" id="bulkCategoryOption" style="display: none;">
                    <label for="bulkCategory" class="form-label">Danh mục mới:</label>
                    <select id="bulkCategory" class="form-input form-select">
                        <option value="contest">Cuộc thi</option>
                        <option value="user">Người dùng</option>
                        <option value="admin">Admin</option>
                        <option value="external">Bên ngoài</option>
                        <option value="internal">Nội bộ</option>
                    </select>
                </div>
                
                <div class="form-group" id="bulkStatusOption" style="display: none;">
                    <label for="bulkStatus" class="form-label">Trạng thái mới:</label>
                    <select id="bulkStatus" class="form-input form-select">
                        <option value="active">Hoạt động</option>
                        <option value="inactive">Không hoạt động</option>
                        <option value="expired">Hết hạn</option>
                        <option value="blocked">Bị chặn</option>
                    </select>
                </div>
            </div>
            
            <div class="bulk-selected-info">
                <p>Đã chọn <span id="bulkSelectedCount">0</span> liên kết</p>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeBulkActionsModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="executeBulkAction()">Thực hiện</button>
        </div>
    </div>
</div>

<script>
let links = [];
let selectedLinks = [];
let currentPage = 1;
const itemsPerPage = 20;

document.addEventListener('DOMContentLoaded', function() {
    loadLinks();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('linkForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveLink();
    });
    
    document.getElementById('bulkAction').addEventListener('change', function() {
        toggleBulkActionOptions();
    });
}

async function loadLinks() {
    try {
        const response = await window.app.apiCall('links/list', 'GET');
        if (response.success) {
            links = response.links || [];
            renderLinksTable();
            updateStats();
        } else {
            showNotification('Không thể tải danh sách liên kết', 'error');
        }
    } catch (error) {
        console.error('Failed to load links:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function renderLinksTable() {
    const tbody = document.getElementById('linksTableBody');
    
    if (links.length === 0) {
        tbody.innerHTML = `<tr><td colspan="11" class="text-center text-muted"><i class="fas fa-inbox"></i> Không có liên kết nào</td></tr>`;
        return;
    }
    
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageLinks = links.slice(startIndex, endIndex);
    
    tbody.innerHTML = pageLinks.map(link => `
        <tr>
            <td>
                <input type="checkbox" class="link-checkbox" value="${link.id}" onchange="toggleLinkSelection(${link.id})">
            </td>
            <td>${link.id}</td>
            <td>
                <div class="link-info">
                    <div class="link-name">${link.name}</div>
                    <div class="link-description">${link.description || 'N/A'}</div>
                </div>
            </td>
            <td>
                <div class="url-display">
                    <a href="${link.original_url}" target="_blank" class="url-link">${link.original_url}</a>
                </div>
            </td>
            <td>
                <div class="short-url">
                    <a href="${link.short_url}" target="_blank" class="short-url-link">${link.short_url}</a>
                    <button class="btn btn-sm btn-outline" onclick="copyToClipboard('${link.short_url}')" title="Sao chép">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </td>
            <td>
                <span class="category-badge category-${link.category}">
                    ${getCategoryText(link.category)}
                </span>
            </td>
            <td>
                <span class="type-badge type-${link.type}">
                    ${getTypeText(link.type)}
                </span>
            </td>
            <td>
                <span class="status-badge status-${link.status}">
                    ${getStatusIcon(link.status)} ${getStatusText(link.status)}
                </span>
            </td>
            <td>
                <div class="click-stats">
                    <span class="click-count">${link.click_count || 0}</span>
                    ${link.max_clicks ? `<span class="click-limit">/ ${link.max_clicks}</span>` : ''}
                </div>
            </td>
            <td>${formatDateTime(link.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-outline" onclick="viewLinkDetails(${link.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="editLink(${link.id})" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-info" onclick="viewLinkAnalytics(${link.id})" title="Thống kê">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteLink(${link.id})" title="Xóa">
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
        total: links.length,
        active: links.filter(l => l.status === 'active').length,
        totalClicks: links.reduce((sum, l) => sum + (l.click_count || 0), 0),
        conversionRate: calculateConversionRate()
    };
    
    document.getElementById('totalLinks').textContent = stats.total;
    document.getElementById('activeLinks').textContent = stats.active;
    document.getElementById('totalClicks').textContent = stats.totalClicks;
    document.getElementById('conversionRate').textContent = `${stats.conversionRate}%`;
}

function calculateConversionRate() {
    // Implement conversion rate calculation logic
    return Math.round(Math.random() * 100);
}

function filterLinks() {
    const categoryFilter = document.getElementById('categoryFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
    
    let filteredLinks = links;
    
    if (categoryFilter) {
        filteredLinks = filteredLinks.filter(l => l.category === categoryFilter);
    }
    
    if (statusFilter) {
        filteredLinks = filteredLinks.filter(l => l.status === statusFilter);
    }
    
    if (typeFilter) {
        filteredLinks = filteredLinks.filter(l => l.type === typeFilter);
    }
    
    if (searchFilter) {
        filteredLinks = filteredLinks.filter(l => 
            l.name.toLowerCase().includes(searchFilter) ||
            l.description.toLowerCase().includes(searchFilter) ||
            l.original_url.toLowerCase().includes(searchFilter) ||
            l.short_url.toLowerCase().includes(searchFilter)
        );
    }
    
    const tbody = document.getElementById('linksTableBody');
    if (filteredLinks.length === 0) {
        tbody.innerHTML = `<tr><td colspan="11" class="text-center text-muted"><i class="fas fa-search"></i> Không tìm thấy kết quả</td></tr>`;
    } else {
        const startIndex = 0;
        const endIndex = itemsPerPage;
        const pageLinks = filteredLinks.slice(startIndex, endIndex);
        
        tbody.innerHTML = pageLinks.map(link => `
            <tr>
                <td>
                    <input type="checkbox" class="link-checkbox" value="${link.id}" onchange="toggleLinkSelection(${link.id})">
                </td>
                <td>${link.id}</td>
                <td>
                    <div class="link-info">
                        <div class="link-name">${link.name}</div>
                        <div class="link-description">${link.description || 'N/A'}</div>
                    </div>
                </td>
                <td>
                    <div class="url-display">
                        <a href="${link.original_url}" target="_blank" class="url-link">${link.original_url}</a>
                    </div>
                </td>
                <td>
                    <div class="short-url">
                        <a href="${link.short_url}" target="_blank" class="short-url-link">${link.short_url}</a>
                        <button class="btn btn-sm btn-outline" onclick="copyToClipboard('${link.short_url}')" title="Sao chép">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </td>
                <td>
                    <span class="category-badge category-${link.category}">
                        ${getCategoryText(link.category)}
                    </span>
                </td>
                <td>
                    <span class="type-badge type-${link.type}">
                        ${getTypeText(link.type)}
                    </span>
                </td>
                <td>
                    <span class="status-badge status-${link.status}">
                        ${getStatusIcon(link.status)} ${getStatusText(link.status)}
                    </span>
                </td>
                <td>
                    <div class="click-stats">
                        <span class="click-count">${link.click_count || 0}</span>
                        ${link.max_clicks ? `<span class="click-limit">/ ${link.max_clicks}</span>` : ''}
                    </div>
                </td>
                <td>${formatDateTime(link.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline" onclick="viewLinkDetails(${link.id})" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline" onclick="editLink(${link.id})" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-info" onclick="viewLinkAnalytics(${link.id})" title="Thống kê">
                            <i class="fas fa-chart-bar"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteLink(${link.id})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
}

function updatePagination() {
    const totalPages = Math.ceil(links.length / itemsPerPage);
    const pagination = document.getElementById('linksPagination');
    
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
    renderLinksTable();
}

// Selection management
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.link-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        if (selectAll.checked) {
            selectedLinks.push(parseInt(checkbox.value));
        } else {
            selectedLinks = [];
        }
    });
    
    updateBulkActionsButton();
}

function toggleLinkSelection(linkId) {
    const index = selectedLinks.indexOf(linkId);
    if (index > -1) {
        selectedLinks.splice(index, 1);
    } else {
        selectedLinks.push(linkId);
    }
    
    updateBulkActionsButton();
}

function updateBulkActionsButton() {
    const bulkButton = document.querySelector('button[onclick="bulkActions()"]');
    if (selectedLinks.length > 0) {
        bulkButton.textContent = `Thao tác hàng loạt (${selectedLinks.length})`;
        bulkButton.disabled = false;
    } else {
        bulkButton.textContent = 'Thao tác hàng loạt';
        bulkButton.disabled = true;
    }
}

// Modal functions
function showCreateLinkModal() {
    document.getElementById('linkModalTitle').textContent = 'Tạo liên kết mới';
    document.getElementById('linkForm').reset();
    document.getElementById('linkId').value = '';
    document.getElementById('linkModal').style.display = 'block';
}

function closeLinkModal() {
    document.getElementById('linkModal').style.display = 'none';
}

function editLink(linkId) {
    const link = links.find(l => l.id === linkId);
    if (!link) return;
    
    document.getElementById('linkModalTitle').textContent = 'Chỉnh sửa liên kết';
    document.getElementById('linkId').value = link.id;
    document.getElementById('linkName').value = link.name;
    document.getElementById('linkCategory').value = link.category;
    document.getElementById('originalUrl').value = link.original_url;
    document.getElementById('customSlug').value = link.custom_slug || '';
    document.getElementById('linkType').value = link.type;
    document.getElementById('linkStatus').value = link.status;
    document.getElementById('linkDescription').value = link.description || '';
    document.getElementById('expiryDate').value = link.expiry_date || '';
    document.getElementById('maxClicks').value = link.max_clicks || '';
    document.getElementById('linkTags').value = link.tags || '';
    document.getElementById('trackingEnabled').checked = link.tracking_enabled !== false;
    
    document.getElementById('linkModal').style.display = 'block';
}

function previewLink() {
    const form = document.getElementById('linkForm');
    const formData = new FormData(form);
    
    const previewData = {
        name: formData.get('name'),
        category: formData.get('category'),
        original_url: formData.get('original_url'),
        type: formData.get('type'),
        status: formData.get('status')
    };
    
    alert(`Xem trước liên kết:\n\nTên: ${previewData.name}\nDanh mục: ${getCategoryText(previewData.category)}\nURL: ${previewData.original_url}\nLoại: ${getTypeText(previewData.type)}\nTrạng thái: ${getStatusText(previewData.status)}`);
}

// API functions
async function saveLink() {
    const form = document.getElementById('linkForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    window.app.showLoading(submitBtn);
    
    try {
        const linkId = formData.get('id');
        const endpoint = linkId ? `links/${linkId}` : 'links';
        const method = linkId ? 'PUT' : 'POST';
        
        const data = {
            name: formData.get('name'),
            category: formData.get('category'),
            original_url: formData.get('original_url'),
            custom_slug: formData.get('custom_slug'),
            type: formData.get('type'),
            status: formData.get('status'),
            description: formData.get('description'),
            expiry_date: formData.get('expiry_date') || null,
            max_clicks: formData.get('max_clicks') || null,
            tags: formData.get('tags'),
            tracking_enabled: formData.get('tracking_enabled') === 'on'
        };
        
        const response = await window.app.apiCall(endpoint, method, data);
        
        if (response.success) {
            showNotification(linkId ? 'Cập nhật liên kết thành công' : 'Tạo liên kết thành công', 'success');
            closeLinkModal();
            loadLinks();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to save link:', error);
        showNotification('Lỗi kết nối server', 'error');
    } finally {
        window.app.hideLoading(submitBtn, originalText);
    }
}

async function viewLinkDetails(linkId) {
    try {
        const response = await window.app.apiCall(`links/${linkId}`, 'GET');
        
        if (response.success) {
            const link = response.link;
            showLinkDetailsModal(link);
        } else {
            showNotification('Không thể tải thông tin liên kết', 'error');
        }
    } catch (error) {
        console.error('Failed to load link details:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function showLinkDetailsModal(link) {
    const modal = document.getElementById('linkDetailsModal');
    const content = document.getElementById('linkDetailsContent');
    
    content.innerHTML = `
        <div class="link-details">
            <div class="detail-row">
                <div class="detail-label">ID:</div>
                <div class="detail-value">${link.id}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Tên:</div>
                <div class="detail-value">${link.name}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Danh mục:</div>
                <div class="detail-value">
                    <span class="category-badge category-${link.category}">
                        ${getCategoryText(link.category)}
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">URL gốc:</div>
                <div class="detail-value">
                    <a href="${link.original_url}" target="_blank">${link.original_url}</a>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">URL rút gọn:</div>
                <div class="detail-value">
                    <a href="${link.short_url}" target="_blank">${link.short_url}</a>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Loại:</div>
                <div class="detail-value">
                    <span class="type-badge type-${link.type}">
                        ${getTypeText(link.type)}
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Trạng thái:</div>
                <div class="detail-value">
                    <span class="status-badge status-${link.status}">
                        ${getStatusIcon(link.status)} ${getStatusText(link.status)}
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Lượt click:</div>
                <div class="detail-value">${link.click_count || 0}${link.max_clicks ? ` / ${link.max_clicks}` : ''}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Ngày tạo:</div>
                <div class="detail-value">${formatDateTime(link.created_at)}</div>
            </div>
            ${link.expiry_date ? `
                <div class="detail-row">
                    <div class="detail-label">Ngày hết hạn:</div>
                    <div class="detail-value">${formatDateTime(link.expiry_date)}</div>
                </div>
            ` : ''}
            ${link.description ? `
                <div class="detail-row">
                    <div class="detail-label">Mô tả:</div>
                    <div class="detail-value">${link.description}</div>
                </div>
            ` : ''}
            ${link.tags ? `
                <div class="detail-row">
                    <div class="detail-label">Tags:</div>
                    <div class="detail-value">${link.tags}</div>
                </div>
            ` : ''}
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeLinkDetailsModal() {
    document.getElementById('linkDetailsModal').style.display = 'none';
}

async function deleteLink(linkId) {
    if (!confirm('Bạn có chắc chắn muốn xóa liên kết này?')) return;
    
    try {
        const response = await window.app.apiCall(`links/${linkId}`, 'DELETE');
        
        if (response.success) {
            showNotification('Xóa liên kết thành công', 'success');
            loadLinks();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to delete link:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function viewLinkAnalytics(linkId) {
    // Implement link analytics view
    showNotification('Tính năng xem thống kê đang phát triển', 'info');
}

// Bulk actions
function bulkActions() {
    if (selectedLinks.length === 0) {
        showNotification('Vui lòng chọn ít nhất một liên kết', 'warning');
        return;
    }
    
    document.getElementById('bulkSelectedCount').textContent = selectedLinks.length;
    document.getElementById('bulkActionsModal').style.display = 'block';
}

function closeBulkActionsModal() {
    document.getElementById('bulkActionsModal').style.display = 'none';
}

function toggleBulkActionOptions() {
    const action = document.getElementById('bulkAction').value;
    const options = document.getElementById('bulkActionOptions');
    const categoryOption = document.getElementById('bulkCategoryOption');
    const statusOption = document.getElementById('bulkStatusOption');
    
    if (action === 'change_category') {
        categoryOption.style.display = 'block';
        statusOption.style.display = 'none';
        options.style.display = 'block';
    } else if (action === 'change_status') {
        categoryOption.style.display = 'none';
        statusOption.style.display = 'block';
        options.style.display = 'block';
    } else {
        options.style.display = 'none';
    }
}

async function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    if (!action) {
        showNotification('Vui lòng chọn hành động', 'warning');
        return;
    }
    
    try {
        let data = { link_ids: selectedLinks };
        
        if (action === 'change_category') {
            data.category = document.getElementById('bulkCategory').value;
        } else if (action === 'change_status') {
            data.status = document.getElementById('bulkStatus').value;
        }
        
        const response = await window.app.apiCall(`links/bulk/${action}`, 'POST', data);
        
        if (response.success) {
            showNotification(`Thực hiện hành động hàng loạt thành công`, 'success');
            closeBulkActionsModal();
            selectedLinks = [];
            updateBulkActionsButton();
            loadLinks();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to execute bulk action:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

// Utility functions
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Đã sao chép vào clipboard', 'success');
    }).catch(() => {
        showNotification('Không thể sao chép', 'error');
    });
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

function getCategoryText(category) {
    const texts = {
        'contest': 'Cuộc thi',
        'user': 'Người dùng',
        'admin': 'Admin',
        'external': 'Bên ngoài',
        'internal': 'Nội bộ'
    };
    return texts[category] || category;
}

function getTypeText(type) {
    const texts = {
        'public': 'Công khai',
        'private': 'Riêng tư',
        'temporary': 'Tạm thời',
        'permanent': 'Vĩnh viễn'
    };
    return texts[type] || type;
}

function getStatusIcon(status) {
    const icons = {
        'active': '✅',
        'inactive': '⏸️',
        'expired': '⏰',
        'blocked': '🚫'
    };
    return icons[status] || '❓';
}

function getStatusText(status) {
    const texts = {
        'active': 'Hoạt động',
        'inactive': 'Không hoạt động',
        'expired': 'Hết hạn',
        'blocked': 'Bị chặn'
    };
    return texts[status] || status;
}

function refreshLinks() {
    loadLinks();
}

async function exportLinks() {
    try {
        const response = await window.app.apiCall('links/export', 'GET');
        
        if (response.success && response.download_url) {
            const link = document.createElement('a');
            link.href = response.download_url;
            link.download = `links_${new Date().toISOString().split('T')[0]}.xlsx`;
            link.click();
            
            showNotification('Xuất dữ liệu thành công', 'success');
        } else {
            showNotification('Không thể xuất dữ liệu', 'error');
        }
    } catch (error) {
        console.error('Failed to export links:', error);
        showNotification('Lỗi khi xuất dữ liệu', 'error');
    }
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
    const modals = ['linkModal', 'linkDetailsModal', 'bulkActionsModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}
</script>
