<?php
// NotificationTemplatesPage.php - Trang quản lý mẫu thông báo hoàn chỉnh
$pageTitle = 'Mẫu thông báo';
?>

<div class="content-header">
    <h1><i class="fas fa-envelope"></i> Mẫu thông báo</h1>
    <p>Quản lý các mẫu thông báo cho hệ thống, tạo mới và chỉnh sửa template</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh sách Mẫu thông báo</h3>
        <div class="card-actions">
            <button class="btn btn-primary" onclick="showCreateTemplateModal()">
                <i class="fas fa-plus"></i> Tạo mẫu mới
            </button>
            <button class="btn btn-secondary" onclick="refreshTemplates()">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-group">
            <label for="typeFilter">Loại:</label>
            <select id="typeFilter" class="form-select" onchange="filterTemplates()">
                <option value="">Tất cả</option>
                <option value="email">Email</option>
                <option value="sms">SMS</option>
                <option value="push">Push Notification</option>
                <option value="telegram">Telegram</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="searchFilter">Tìm kiếm:</label>
            <input type="text" id="searchFilter" class="form-input" placeholder="Tìm theo tên, mô tả..." onkeyup="filterTemplates()">
        </div>
    </div>

    <!-- Templates Table -->
    <div class="table-container">
        <table class="table" id="templatesTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên mẫu</th>
                    <th>Loại</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="templatesTableBody">
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Template Modal -->
<div id="templateModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="templateModalTitle">Tạo mẫu thông báo mới</h3>
            <span class="close" onclick="closeTemplateModal()">&times;</span>
        </div>
        
        <form id="templateForm">
            <div class="modal-body">
                <input type="hidden" id="templateId" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="templateName" class="form-label">Tên mẫu *</label>
                        <input type="text" id="templateName" name="name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="templateType" class="form-label">Loại *</label>
                        <select id="templateType" name="type" class="form-input form-select" required>
                            <option value="">Chọn loại...</option>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="push">Push Notification</option>
                            <option value="telegram">Telegram</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="templateDescription" class="form-label">Mô tả</label>
                    <textarea id="templateDescription" name="description" class="form-input form-textarea" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="templateSubject" class="form-label">Tiêu đề *</label>
                    <input type="text" id="templateSubject" name="subject" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="templateContent" class="form-label">Nội dung *</label>
                    <textarea id="templateContent" name="content" class="form-input form-textarea" rows="8" required></textarea>
                    <small class="text-muted">Sử dụng {{variable}} để chèn biến động</small>
                </div>
                
                <div class="form-group">
                    <label for="templateVariables" class="form-label">Biến có sẵn</label>
                    <div class="variables-list">
                        <span class="variable-tag" onclick="insertVariable('{{user_name}}')">{{user_name}}</span>
                        <span class="variable-tag" onclick="insertVariable('{{email}}')">{{email}}</span>
                        <span class="variable-tag" onclick="insertVariable('{{contest_name}}')">{{contest_name}}</span>
                        <span class="variable-tag" onclick="insertVariable('{{score}}')">{{score}}</span>
                        <span class="variable-tag" onclick="insertVariable('{{date}}')">{{date}}</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="templateStatus" class="form-label">Trạng thái</label>
                    <select id="templateStatus" name="status" class="form-input form-select">
                        <option value="active">Hoạt động</option>
                        <option value="inactive">Không hoạt động</option>
                        <option value="draft">Bản nháp</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTemplateModal()">Hủy</button>
                <button type="button" class="btn btn-info" onclick="previewTemplate()">Xem trước</button>
                <button type="submit" class="btn btn-primary">Lưu mẫu</button>
            </div>
        </form>
    </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Xem trước mẫu thông báo</h3>
            <span class="close" onclick="closePreviewModal()">&times;</span>
        </div>
        
        <div class="modal-body">
            <div id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePreviewModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
let templates = [];

document.addEventListener('DOMContentLoaded', function() {
    loadTemplates();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('templateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveTemplate();
    });
}

async function loadTemplates() {
    try {
        const response = await window.app.apiCall('notifications/templates', 'GET');
        if (response.success) {
            templates = response.templates || [];
            renderTemplatesTable();
        } else {
            showNotification('Không thể tải danh sách mẫu thông báo', 'error');
        }
    } catch (error) {
        console.error('Failed to load templates:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function renderTemplatesTable() {
    const tbody = document.getElementById('templatesTableBody');
    
    if (templates.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted"><i class="fas fa-inbox"></i> Không có mẫu thông báo nào</td></tr>`;
        return;
    }
    
    tbody.innerHTML = templates.map(template => `
        <tr>
            <td>${template.id}</td>
            <td>
                <div class="template-info">
                    <div class="template-name">${template.name}</div>
                    <div class="template-subject">${template.subject}</div>
                </div>
            </td>
            <td>
                <span class="type-badge type-${template.type}">
                    ${getTypeIcon(template.type)} ${getTypeText(template.type)}
                </span>
            </td>
            <td>${template.description || 'N/A'}</td>
            <td>
                <span class="status-badge status-${template.status}">
                    ${getStatusIcon(template.status)} ${getStatusText(template.status)}
                </span>
            </td>
            <td>${formatDateTime(template.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-outline" onclick="editTemplate(${template.id})" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-info" onclick="previewTemplateById(${template.id})" title="Xem trước">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteTemplate(${template.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function filterTemplates() {
    const typeFilter = document.getElementById('typeFilter').value;
    const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
    
    let filteredTemplates = templates;
    
    if (typeFilter) {
        filteredTemplates = filteredTemplates.filter(t => t.type === typeFilter);
    }
    
    if (searchFilter) {
        filteredTemplates = filteredTemplates.filter(t => 
            t.name.toLowerCase().includes(searchFilter) ||
            t.description.toLowerCase().includes(searchFilter) ||
            t.subject.toLowerCase().includes(searchFilter)
        );
    }
    
    const tbody = document.getElementById('templatesTableBody');
    if (filteredTemplates.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted"><i class="fas fa-search"></i> Không tìm thấy kết quả</td></tr>`;
    } else {
        tbody.innerHTML = filteredTemplates.map(template => `
            <tr>
                <td>${template.id}</td>
                <td>
                    <div class="template-info">
                        <div class="template-name">${template.name}</div>
                        <div class="template-subject">${template.subject}</div>
                    </div>
                </td>
                <td>
                    <span class="type-badge type-${template.type}">
                        ${getTypeIcon(template.type)} ${getTypeText(template.type)}
                    </span>
                </td>
                <td>${template.description || 'N/A'}</td>
                <td>
                    <span class="status-badge status-${template.status}">
                        ${getStatusIcon(template.status)} ${getStatusText(template.status)}
                    </span>
                </td>
                <td>${formatDateTime(template.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline" onclick="editTemplate(${template.id})" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-info" onclick="previewTemplateById(${template.id})" title="Xem trước">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteTemplate(${template.id})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
}

// Modal functions
function showCreateTemplateModal() {
    document.getElementById('templateModalTitle').textContent = 'Tạo mẫu thông báo mới';
    document.getElementById('templateForm').reset();
    document.getElementById('templateId').value = '';
    document.getElementById('templateModal').style.display = 'block';
}

function closeTemplateModal() {
    document.getElementById('templateModal').style.display = 'none';
}

function editTemplate(templateId) {
    const template = templates.find(t => t.id === templateId);
    if (!template) return;
    
    document.getElementById('templateModalTitle').textContent = 'Chỉnh sửa mẫu thông báo';
    document.getElementById('templateId').value = template.id;
    document.getElementById('templateName').value = template.name;
    document.getElementById('templateType').value = template.type;
    document.getElementById('templateDescription').value = template.description || '';
    document.getElementById('templateSubject').value = template.subject;
    document.getElementById('templateContent').value = template.content;
    document.getElementById('templateStatus').value = template.status;
    
    document.getElementById('templateModal').style.display = 'block';
}

function insertVariable(variable) {
    const contentField = document.getElementById('templateContent');
    const cursorPos = contentField.selectionStart;
    const textBefore = contentField.value.substring(0, cursorPos);
    const textAfter = contentField.value.substring(cursorPos);
    
    contentField.value = textBefore + variable + textAfter;
    contentField.focus();
    contentField.setSelectionRange(cursorPos + variable.length, cursorPos + variable.length);
}

function previewTemplate() {
    const form = document.getElementById('templateForm');
    const formData = new FormData(form);
    
    const previewData = {
        name: formData.get('name'),
        type: formData.get('type'),
        subject: formData.get('subject'),
        content: formData.get('content')
    };
    
    showPreviewModal(previewData);
}

async function previewTemplateById(templateId) {
    try {
        const response = await window.app.apiCall(`notifications/templates/${templateId}`, 'GET');
        
        if (response.success) {
            const template = response.template;
            showPreviewModal(template);
        } else {
            showNotification('Không thể tải thông tin mẫu', 'error');
        }
    } catch (error) {
        console.error('Failed to load template:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function showPreviewModal(template) {
    const modal = document.getElementById('previewModal');
    const content = document.getElementById('previewContent');
    
    // Thay thế biến bằng dữ liệu mẫu
    let previewContent = template.content;
    previewContent = previewContent.replace(/{{user_name}}/g, 'Nguyễn Văn A');
    previewContent = previewContent.replace(/{{email}}/g, 'user@example.com');
    previewContent = previewContent.replace(/{{contest_name}}/g, 'Cuộc thi Ca hát 2024');
    previewContent = previewContent.replace(/{{score}}/g, '95');
    previewContent = previewContent.replace(/{{date}}/g, new Date().toLocaleDateString('vi-VN'));
    
    content.innerHTML = `
        <div class="template-preview">
            <div class="preview-header">
                <h4>${template.name}</h4>
                <span class="type-badge type-${template.type}">
                    ${getTypeIcon(template.type)} ${getTypeText(template.type)}
                </span>
            </div>
            
            <div class="preview-subject">
                <strong>Tiêu đề:</strong> ${template.subject}
            </div>
            
            <div class="preview-content">
                <strong>Nội dung:</strong>
                <div class="content-preview">${previewContent}</div>
            </div>
        </div>
    `;
    
    modal.style.display = 'block';
}

function closePreviewModal() {
    document.getElementById('previewModal').style.display = 'none';
}

// API functions
async function saveTemplate() {
    const form = document.getElementById('templateForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    window.app.showLoading(submitBtn);
    
    try {
        const templateId = formData.get('id');
        const endpoint = templateId ? `notifications/templates/${templateId}` : 'notifications/templates';
        const method = templateId ? 'PUT' : 'POST';
        
        const data = {
            name: formData.get('name'),
            type: formData.get('type'),
            description: formData.get('description'),
            subject: formData.get('subject'),
            content: formData.get('content'),
            status: formData.get('status')
        };
        
        const response = await window.app.apiCall(endpoint, method, data);
        
        if (response.success) {
            showNotification(templateId ? 'Cập nhật mẫu thành công' : 'Tạo mẫu thành công', 'success');
            closeTemplateModal();
            loadTemplates();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to save template:', error);
        showNotification('Lỗi kết nối server', 'error');
    } finally {
        window.app.hideLoading(submitBtn, originalText);
    }
}

async function deleteTemplate(templateId) {
    if (!confirm('Bạn có chắc chắn muốn xóa mẫu thông báo này?')) return;
    
    try {
        const response = await window.app.apiCall(`notifications/templates/${templateId}`, 'DELETE');
        
        if (response.success) {
            showNotification('Xóa mẫu thông báo thành công', 'success');
            loadTemplates();
        } else {
            showNotification(response.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Failed to delete template:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function refreshTemplates() {
    loadTemplates();
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
        'active': '✅',
        'inactive': '⏸️',
        'draft': '📝'
    };
    return icons[status] || '❓';
}

function getStatusText(status) {
    const texts = {
        'active': 'Hoạt động',
        'inactive': 'Không hoạt động',
        'draft': 'Bản nháp'
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
    const modals = ['templateModal', 'previewModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}
</script>
