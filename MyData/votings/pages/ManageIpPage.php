<?php
// ManageIpPage.php - Trang quản lý IP hoàn chỉnh
$pageTitle = 'Quản lý IP';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quản lý IP</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="content-header">
    <h1><i class="fas fa-network-wired"></i> Quản lý IP</h1>
    <p>Quản lý danh sách IP, whitelist/blacklist và theo dõi hoạt động</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh sách IP</h3>
        <div class="card-actions">
            <button class="btn btn-primary" onclick="showAddIPModal()">
                <i class="fas fa-plus"></i> Thêm IP mới
            </button>
            <button class="btn btn-secondary" onclick="refreshIPList()">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
            <button class="btn btn-info" onclick="exportIPData()">
                <i class="fas fa-download"></i> Xuất dữ liệu
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-group">
            <label for="ipStatusFilter">Trạng thái:</label>
            <select id="ipStatusFilter" class="form-select" onchange="filterIPList()">
                <option value="">Tất cả</option>
                <option value="whitelist">Whitelist</option>
                <option value="blacklist">Blacklist</option>
                <option value="monitoring">Giám sát</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="ipCountryFilter">Quốc gia:</label>
            <select id="ipCountryFilter" class="form-select" onchange="filterIPList()">
                <option value="">Tất cả</option>
                <option value="VN">Việt Nam</option>
                <option value="US">Hoa Kỳ</option>
                <option value="CN">Trung Quốc</option>
                <option value="JP">Nhật Bản</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="ipSearchFilter">Tìm kiếm:</label>
            <input type="text" id="ipSearchFilter" class="form-input" placeholder="Tìm theo IP, ghi chú..." onkeyup="filterIPList()">
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon total">🌐</div>
            <div class="stat-content">
                <div class="stat-number" id="totalIPs">0</div>
                <div class="stat-label">Tổng IP</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon whitelist">✅</div>
            <div class="stat-content">
                <div class="stat-number" id="whitelistIPs">0</div>
                <div class="stat-label">Whitelist</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon blacklist">🚫</div>
            <div class="stat-content">
                <div class="stat-number" id="blacklistIPs">0</div>
                <div class="stat-label">Blacklist</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon monitoring">👁️</div>
            <div class="stat-content">
                <div class="stat-number" id="monitoringIPs">0</div>
                <div class="stat-label">Đang giám sát</div>
            </div>
        </div>
    </div>

    <!-- IP Table -->
    <div class="table-container">
        <table class="table" id="ipTable">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Quốc gia</th>
                    <th>Thành phố</th>
                    <th>Trạng thái</th>
                    <th>Lý do</th>
                    <th>Ngày thêm</th>
                    <th>Hoạt động gần nhất</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="ipTableBody">
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit IP Modal -->
<div id="ipModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="ipModalTitle">Thêm IP mới</h3>
            <span class="close" onclick="closeIPModal()">&times;</span>
        </div>
        
        <form id="ipForm">
            <div class="modal-body">
                <input type="hidden" id="ipId" name="id">
                
                <div class="form-group">
                    <label for="ipAddress" class="form-label">Địa chỉ IP *</label>
                    <input type="text" id="ipAddress" name="ip_address" class="form-input" required placeholder="192.168.1.1">
                </div>
                
                <div class="form-group">
                    <label for="ipStatus" class="form-label">Trạng thái *</label>
                    <select id="ipStatus" name="status" class="form-input form-select" required>
                        <option value="">Chọn trạng thái...</option>
                        <option value="whitelist">Whitelist</option>
                        <option value="blacklist">Blacklist</option>
                        <option value="monitoring">Giám sát</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ipReason" class="form-label">Lý do</label>
                    <textarea id="ipReason" name="reason" class="form-input form-textarea" rows="3" placeholder="Lý do thêm IP này..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="ipNotes" class="form-label">Ghi chú</label>
                    <textarea id="ipNotes" name="notes" class="form-input form-textarea" rows="2" placeholder="Ghi chú bổ sung..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeIPModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu IP</button>
            </div>
        </form>
    </div>
</div>

<script>
let ipList = [];

document.addEventListener('DOMContentLoaded', function() {
    loadIPList();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('ipForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveIP();
    });
}

async function loadIPList() {
    try {
        const response = await window.app.apiCall('ip-management/list', 'GET');
        if (response.success) {
            ipList = response.ips || [];
            renderIPTable();
            updateStats();
        } else {
            showNotification('Không thể tải danh sách IP', 'error');
        }
    } catch (error) {
        console.error('Failed to load IP list:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function renderIPTable() {
    const tbody = document.getElementById('ipTableBody');
    
    if (ipList.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted"><i class="fas fa-inbox"></i> Không có IP nào</td></tr>`;
        return;
    }
    
    tbody.innerHTML = ipList.map(ip => `
        <tr>
            <td>
                <div class="ip-info">
                    <div class="ip-address">${ip.ip_address}</div>
                    <div class="ip-type">${ip.type || 'IPv4'}</div>
                </div>
            </td>
            <td>${ip.country || 'N/A'}</td>
            <td>${ip.city || 'N/A'}</td>
            <td>
                <span class="status-badge status-${ip.status}">
                    ${getIPStatusIcon(ip.status)} ${getIPStatusText(ip.status)}
                </span>
            </td>
            <td>${ip.reason || 'N/A'}</td>
            <td>${formatDateTime(ip.created_at)}</td>
            <td>${ip.last_activity ? formatDateTime(ip.last_activity) : 'N/A'}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-outline" onclick="editIP('${ip.id}')" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-info" onclick="viewIPDetails('${ip.id}')" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteIP('${ip.id}')" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function updateStats() {
    const stats = {
        total: ipList.length,
        whitelist: ipList.filter(ip => ip.status === 'whitelist').length,
        blacklist: ipList.filter(ip => ip.status === 'blacklist').length,
        monitoring: ipList.filter(ip => ip.status === 'monitoring').length
    };
    
    document.getElementById('totalIPs').textContent = stats.total;
    document.getElementById('whitelistIPs').textContent = stats.whitelist;
    document.getElementById('blacklistIPs').textContent = stats.blacklist;
    document.getElementById('monitoringIPs').textContent = stats.monitoring;
}

function filterIPList() {
    const statusFilter = document.getElementById('ipStatusFilter').value;
    const countryFilter = document.getElementById('ipCountryFilter').value;
    const searchFilter = document.getElementById('ipSearchFilter').value.toLowerCase();
    
    let filteredIPs = ipList;
    
    if (statusFilter) {
        filteredIPs = filteredIPs.filter(ip => ip.status === statusFilter);
    }
    
    if (countryFilter) {
        filteredIPs = filteredIPs.filter(ip => ip.country === countryFilter);
    }
    
    if (searchFilter) {
        filteredIPs = filteredIPs.filter(ip => 
            ip.ip_address.toLowerCase().includes(searchFilter) ||
            (ip.reason && ip.reason.toLowerCase().includes(searchFilter)) ||
            (ip.notes && ip.notes.toLowerCase().includes(searchFilter))
        );
    }
    
    const tbody = document.getElementById('ipTableBody');
    if (filteredIPs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted"><i class="fas fa-search"></i> Không tìm thấy kết quả</td></tr>`;
    } else {
        tbody.innerHTML = filteredIPs.map(ip => `
            <tr>
                <td>
                    <div class="ip-info">
                        <div class="ip-address">${ip.ip_address}</div>
                        <div class="ip-type">${ip.type || 'IPv4'}</div>
                    </div>
                </td>
                <td>${ip.country || 'N/A'}</td>
                <td>${ip.city || 'N/A'}</td>
                <td>
                    <span class="status-badge status-${ip.status}">
                        ${getIPStatusIcon(ip.status)} ${getIPStatusText(ip.status)}
                    </span>
                </td>
                <td>${ip.reason || 'N/A'}</td>
                <td>${formatDateTime(ip.created_at)}</td>
                <td>${ip.last_activity ? formatDateTime(ip.last_activity) : 'N/A'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline" onclick="editIP('${ip.id}')" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-info" onclick="viewIPDetails('${ip.id}')" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteIP('${ip.id}')" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
}

// Modal functions
function showAddIPModal() {
    document.getElementById('ipModalTitle').textContent = 'Thêm IP mới';
    document.getElementById('ipForm').reset();
    document.getElementById('ipId').value = '';
    document.getElementById('ipModal').style.display = 'block';
}

function closeIPModal() {
    document.getElementById('ipModal').style.display = 'none';
}

function editIP(ipId) {
    const ip = ipList.find(i => i.id === ipId);
    if (!ip) return;
    
    document.getElementById('ipModalTitle').textContent = 'Chỉnh sửa IP';
    document.getElementById('ipId').value = ip.id;
    document.getElementById('ipAddress').value = ip.ip_address;
    document.getElementById('ipStatus').value = ip.status;
    document.getElementById('ipReason').value = ip.reason || '';
    document.getElementById('ipNotes').value = ip.notes || '';
    
    document.getElementById('ipModal').style.display = 'block';
}

// API functions
async function saveIP() {
    const form = document.getElementById('ipForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    window.app.showLoading(submitBtn);
    
    try {
        const ipId = formData.get('id');
        const endpoint = ipId ? `ip-management/${ipId}` : 'ip-management';
        const method = ipId ? 'PUT' : 'POST';
        
        const data = {
            ip_address: formData.get('ip_address'),
            status: formData.get('status'),
            reason: formData.get('reason'),
            notes: formData.get('notes')
        };
        
        const response = await window.app.apiCall(endpoint, method, data);
        
        if (response.success) {
            showNotification(ipId ? 'Cập nhật IP thành công' : 'Thêm IP thành công', 'success');
            closeIPModal();
            loadIPList();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to save IP:', error);
        showNotification('Lỗi kết nối server', 'error');
    } finally {
        window.app.hideLoading(submitBtn, originalText);
    }
}

async function deleteIP(ipId) {
    if (!confirm('Bạn có chắc chắn muốn xóa IP này?')) return;
    
    try {
        const response = await window.app.apiCall(`ip-management/${ipId}`, 'DELETE');
        
        if (response.success) {
            showNotification('Xóa IP thành công', 'success');
            loadIPList();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to delete IP:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function viewIPDetails(ipId) {
    // Implement IP details view
    showNotification('Tính năng xem chi tiết IP đang phát triển', 'info');
}

function refreshIPList() {
    loadIPList();
}

async function exportIPData() {
    try {
        const response = await window.app.apiCall('ip-management/export', 'GET');
        
        if (response.success && response.download_url) {
            const link = document.createElement('a');
            link.href = response.download_url;
            link.download = `ip_data_${new Date().toISOString().split('T')[0]}.xlsx`;
            link.click();
            
            showNotification('Xuất dữ liệu thành công', 'success');
        } else {
            showNotification('Không thể xuất dữ liệu', 'error');
        }
    } catch (error) {
        console.error('Failed to export IP data:', error);
        showNotification('Lỗi khi xuất dữ liệu', 'error');
    }
}

// Utility functions
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

function getIPStatusIcon(status) {
    const icons = {
        'whitelist': '✅',
        'blacklist': '🚫',
        'monitoring': '👁️'
    };
    return icons[status] || '❓';
}

function getIPStatusText(status) {
    const texts = {
        'whitelist': 'Whitelist',
        'blacklist': 'Blacklist',
        'monitoring': 'Giám sát'
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
    const modals = ['ipModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}
</script>
</body>
</html>
