<?php
// UploadPage.php - Trang quản lý upload file
$pageTitle = 'Upload File';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Trang tải lên</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="upload-page">
    <div class="content-header">
        <h1><i class="fas fa-upload"></i> Quản lý Upload File</h1>
        <p>Upload và quản lý các file trong hệ thống</p>
    </div>

    <!-- Upload Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Upload File mới</h3>
        </div>

        <form id="uploadForm" class="upload-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="fileInput" class="form-label">Chọn File *</label>
                <input type="file" id="fileInput" name="file" class="form-input" required accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                <small class="text-muted">Hỗ trợ: Hình ảnh, Video, PDF, Word, Excel, Text (Tối đa 10MB)</small>
            </div>

            <div class="form-group">
                <label for="fileCategory" class="form-label">Danh mục</label>
                <select id="fileCategory" name="category" class="form-input form-select">
                    <option value="general">Chung</option>
                    <option value="contest">Cuộc thi</option>
                    <option value="user">Người dùng</option>
                    <option value="document">Tài liệu</option>
                    <option value="media">Media</option>
                </select>
            </div>

            <div class="form-group">
                <label for="fileDescription" class="form-label">Mô tả</label>
                <textarea id="fileDescription" name="description" class="form-input form-textarea" rows="3" placeholder="Mô tả về file..."></textarea>
            </div>

            <div class="form-group">
                <label for="fileTags" class="form-label">Tags</label>
                <input type="text" id="fileTags" name="tags" class="form-input" placeholder="tag1, tag2, tag3...">
                <small class="text-muted">Phân cách các tag bằng dấu phẩy</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload File
                </button>
            </div>
        </form>
    </div>

    <!-- Upload Progress -->
    <div class="card" id="uploadProgressCard" style="display: none;">
        <div class="card-header">
            <h3 class="card-title">Tiến trình Upload</h3>
        </div>

        <div class="upload-progress">
            <div class="progress-bar" style="width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden;">
                <div id="progressFill" style="width: 0%; height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); transition: width 0.3s ease;"></div>
            </div>
            <div class="progress-text" style="text-align: center; margin-top: 10px; font-weight: 500;">
                <span id="progressPercent">0%</span> - <span id="progressStatus">Đang chuẩn bị...</span>
            </div>
        </div>
    </div>

    <!-- Files List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Danh sách File</h3>
            <div class="card-actions">
                <input type="text" id="searchFile" class="form-input" placeholder="Tìm kiếm file..." style="width: 250px;">
                <select id="categoryFilter" class="form-input form-select" style="width: 150px;">
                    <option value="">Tất cả danh mục</option>
                    <option value="general">Chung</option>
                    <option value="contest">Cuộc thi</option>
                    <option value="user">Người dùng</option>
                    <option value="document">Tài liệu</option>
                    <option value="media">Media</option>
                </select>
            </div>
        </div>

        <div class="files-grid" id="filesGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; padding: 20px;">
            <div class="text-center text-muted">
                <i class="fas fa-spinner fa-spin"></i> Đang tải danh sách file...
            </div>
        </div>
    </div>

    <!-- File Preview Modal -->
    <div id="filePreviewModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 id="previewModalTitle">Xem trước File</h3>
                <span class="close" onclick="closeFilePreviewModal()">&times;</span>
            </div>

            <div class="modal-body" id="filePreviewContent">
                <!-- File preview will be loaded here -->
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeFilePreviewModal()">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="downloadFile()">
                    <i class="fas fa-download"></i> Tải xuống
                </button>
            </div>
        </div>
    </div>

    <script>
    let files = [];
    let currentFile = null;

    // Load files on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadFiles();
        setupEventListeners();
    });

    function setupEventListeners() {
        // Upload form submission
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            uploadFile();
        });

        // Search functionality
        document.getElementById('searchFile').addEventListener('input', function(e) {
            filterFiles();
        });

        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', function(e) {
            filterFiles();
        });

        // File input change
        document.getElementById('fileInput').addEventListener('change', function(e) {
            validateFile(e.target.files[0]);
        });
    }

    async function loadFiles() {
        try {
            const response = await window.app.apiCall('upload/list', 'GET');
            if (response.success) {
                files = response.files || [];
                renderFilesGrid();
            } else {
                showNotification('Không thể tải danh sách file', 'error');
            }
        } catch (error) {
            console.error('Failed to load files:', error);
            showNotification('Lỗi kết nối server', 'error');
        }
    }

    function renderFilesGrid() {
        const container = document.getElementById('filesGrid');

        if (files.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted" style="grid-column: 1 / -1;">
                    <i class="fas fa-inbox"></i> Không có file nào
                </div>
            `;
            return;
        }

        container.innerHTML = files.map(file => `
            <div class="file-card" style="background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; transition: all 0.3s ease; cursor: pointer;" onclick="previewFile(${file.id})">
                <div class="file-icon" style="text-align: center; margin-bottom: 15px;">
                    <i class="fas ${getFileIcon(file.type)}" style="font-size: 3rem; color: ${getFileColor(file.type)};"></i>
                </div>

                <div class="file-info">
                    <div class="file-name" style="font-weight: 500; margin-bottom: 8px; word-break: break-word;">
                        ${file.original_name}
                    </div>

                    <div class="file-meta" style="font-size: 0.875rem; color: #6c757d;">
                        <div style="margin-bottom: 5px;">
                            <span class="badge badge-${getCategoryBadgeClass(file.category)}">
                                ${getCategoryText(file.category)}
                            </span>
                        </div>
                        <div style="margin-bottom: 5px;">
                            <i class="fas fa-calendar"></i> ${formatDate(file.created_at)}
                        </div>
                        <div style="margin-bottom: 5px;">
                            <i class="fas fa-weight"></i> ${formatFileSize(file.size)}
                        </div>
                        ${file.description ? `<div style="margin-bottom: 5px;"><i class="fas fa-info-circle"></i> ${file.description.substring(0, 50)}${file.description.length > 50 ? '...' : ''}</div>` : ''}
                    </div>
                </div>

                <div class="file-actions" style="margin-top: 15px; display: flex; gap: 10px;">
                    <button class="btn btn-sm btn-outline" onclick="event.stopPropagation(); downloadFile(${file.id})">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="event.stopPropagation(); copyFileUrl(${file.id})">
                        <i class="fas fa-link"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deleteFile(${file.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    function filterFiles() {
        const searchTerm = document.getElementById('searchFile').value.toLowerCase();
        const categoryFilter = document.getElementById('categoryFilter').value;

        let filtered = files;

        // Filter by search term
        if (searchTerm) {
            filtered = filtered.filter(file =>
                file.original_name.toLowerCase().includes(searchTerm) ||
                (file.description && file.description.toLowerCase().includes(searchTerm)) ||
                (file.tags && file.tags.toLowerCase().includes(searchTerm))
            );
        }

        // Filter by category
        if (categoryFilter) {
            filtered = filtered.filter(file => file.category === categoryFilter);
        }

        renderFilteredFiles(filtered);
    }

    function renderFilteredFiles(filteredFiles) {
        const container = document.getElementById('filesGrid');

        if (filteredFiles.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted" style="grid-column: 1 / -1;">
                    <i class="fas fa-search"></i> Không tìm thấy file nào
                </div>
            `;
            return;
        }

        container.innerHTML = filteredFiles.map(file => `
            <div class="file-card" style="background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; transition: all 0.3s ease; cursor: pointer;" onclick="previewFile(${file.id})">
                <div class="file-icon" style="text-align: center; margin-bottom: 15px;">
                    <i class="fas ${getFileIcon(file.type)}" style="font-size: 3rem; color: ${getFileColor(file.type)};"></i>
                </div>

                <div class="file-info">
                    <div class="file-name" style="font-weight: 500; margin-bottom: 8px; word-break: break-word;">
                        ${file.original_name}
                    </div>

                    <div class="file-meta" style="font-size: 0.875rem; color: #6c757d;">
                        <div style="margin-bottom: 5px;">
                            <span class="badge badge-${getCategoryBadgeClass(file.category)}">
                                ${getCategoryText(file.category)}
                            </span>
                        </div>
                        <div style="margin-bottom: 5px;">
                            <i class="fas fa-calendar"></i> ${formatDate(file.created_at)}
                        </div>
                        <div style="margin-bottom: 5px;">
                            <i class="fas fa-weight"></i> ${formatFileSize(file.size)}
                        </div>
                        ${file.description ? `<div style="margin-bottom: 5px;"><i class="fas fa-info-circle"></i> ${file.description.substring(0, 50)}${file.description.length > 50 ? '...' : ''}</div>` : ''}
                    </div>
                </div>

                <div class="file-actions" style="margin-top: 15px; display: flex; gap: 10px;">
                    <button class="btn btn-sm btn-outline" onclick="event.stopPropagation(); downloadFile(${file.id})">
                        <i class="btn btn-sm btn-outline" onclick="event.stopPropagation(); copyFileUrl(${file.id})">
                        <i class="fas fa-link"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deleteFile(${file.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    function validateFile(file) {
        if (!file) return;

        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['image/', 'video/', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/'];

        if (file.size > maxSize) {
            showNotification('File quá lớn. Kích thước tối đa là 10MB', 'error');
            document.getElementById('fileInput').value = '';
            return false;
        }

        const isValidType = allowedTypes.some(type => file.type.startsWith(type));
        if (!isValidType) {
            showNotification('Loại file không được hỗ trợ', 'error');
            document.getElementById('fileInput').value = '';
            return false;
        }

        return true;
    }

    async function uploadFile() {
        const fileInput = document.getElementById('fileInput');
        const file = fileInput.files[0];

        if (!file) {
            showNotification('Vui lòng chọn file để upload', 'error');
            return;
        }

        if (!validateFile(file)) return;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('category', document.getElementById('fileCategory').value);
        formData.append('description', document.getElementById('fileDescription').value);
        formData.append('tags', document.getElementById('fileTags').value);

        // Show progress
        document.getElementById('uploadProgressCard').style.display = 'block';
        document.getElementById('progressStatus').textContent = 'Đang upload...';

        try {
            const response = await fetch('/backend/upload/upload', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Upload file thành công', 'success');
                document.getElementById('uploadForm').reset();
                loadFiles();
            } else {
                showNotification(result.message || 'Upload file thất bại', 'error');
            }
        } catch (error) {
            console.error('Upload failed:', error);
            showNotification('Lỗi kết nối server', 'error');
        } finally {
            // Hide progress
            document.getElementById('uploadProgressCard').style.display = 'none';
            document.getElementById('progressFill').style.width = '0%';
            document.getElementById('progressPercent').textContent = '0%';
        }
    }

    async function previewFile(fileId) {
        const file = files.find(f => f.id === fileId);
        if (!file) return;

        currentFile = file;
        document.getElementById('previewModalTitle').textContent = `Xem trước: ${file.original_name}`;

        const content = document.getElementById('filePreviewContent');

        if (file.type.startsWith('image/')) {
            content.innerHTML = `
                <div style="text-align: center;">
                    <img src="${file.url}" alt="${file.original_name}" style="max-width: 100%; max-height: 500px; border-radius: 8px;">
                </div>
            `;
        } else if (file.type.startsWith('video/')) {
            content.innerHTML = `
                <div style="text-align: center;">
                    <video controls style="max-width: 100%; max-height: 500px;">
                        <source src="${file.url}" type="${file.type}">
                        Trình duyệt không hỗ trợ video.
                    </video>
                </div>
            `;
        } else {
            content.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas ${getFileIcon(file.type)}" style="font-size: 5rem; color: ${getFileColor(file.type)}; margin-bottom: 20px;"></i>
                    <h4>${file.original_name}</h4>
                    <p class="text-muted">Không thể xem trước loại file này</p>
                </div>
            `;
        }

        document.getElementById('filePreviewModal').style.display = 'block';
    }

    function closeFilePreviewModal() {
        document.getElementById('filePreviewModal').style.display = 'none';
        currentFile = null;
    }

    async function downloadFile(fileId) {
        const file = fileId ? files.find(f => f.id === fileId) : currentFile;
        if (!file) return;

        try {
            const link = document.createElement('a');
            link.href = file.url;
            link.download = file.original_name;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showNotification('Bắt đầu tải xuống file', 'success');
        } catch (error) {
            console.error('Download failed:', error);
            showNotification('Không thể tải xuống file', 'error');
        }
    }

    async function copyFileUrl(fileId) {
        const file = files.find(f => f.id === fileId);
        if (!file) return;

        try {
            await navigator.clipboard.writeText(file.url);
            showNotification('Đã sao chép URL file', 'success');
        } catch (error) {
            console.error('Copy failed:', error);
            showNotification('Không thể sao chép URL', 'error');
        }
    }

    async function deleteFile(fileId) {
        if (!confirm('Bạn có chắc chắn muốn xóa file này?')) return;

        try {
            const response = await window.app.apiCall(`upload/delete/${fileId}`, 'DELETE');

            if (response.success) {
                showNotification('Xóa file thành công', 'success');
                loadFiles();
            } else {
                showNotification(response.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (error) {
            console.error('Failed to delete file:', error);
            showNotification('Lỗi kết nối server', 'error');
        }
    }

    // Utility functions
    function getFileIcon(type) {
        if (type.startsWith('image/')) return 'fa-image';
        if (type.startsWith('video/')) return 'fa-video';
        if (type.includes('pdf')) return 'fa-file-pdf';
        if (type.includes('word') || type.includes('document')) return 'fa-file-word';
        if (type.includes('excel') || type.includes('spreadsheet')) return 'fa-file-excel';
        if (type.startsWith('text/')) return 'fa-file-alt';
        return 'fa-file';
    }

    function getFileColor(type) {
        if (type.startsWith('image/')) return '#28a745';
        if (type.startsWith('video/')) return '#dc3545';
        if (type.includes('pdf')) return '#e74c3c';
        if (type.includes('word') || type.includes('document')) return '#007bff';
        if (type.includes('excel') || type.includes('spreadsheet')) return '#28a745';
        if (type.startsWith('text/')) return '#6c757d';
        return '#6c757d';
    }

    function getCategoryBadgeClass(category) {
        switch (category) {
            case 'contest': return 'primary';
            case 'user': return 'success';
            case 'document': return 'warning';
            case 'media': return 'info';
            default: return 'secondary';
        }
    }

    function getCategoryText(category) {
        switch (category) {
            case 'contest': return 'Cuộc thi';
            case 'user': return 'Người dùng';
            case 'document': return 'Tài liệu';
            case 'media': return 'Media';
            default: return 'Chung';
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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

    // Close modal when clicking outside
    window.onclick = function(event) {
        const filePreviewModal = document.getElementById('filePreviewModal');

        if (event.target === filePreviewModal) {
            closeFilePreviewModal();
        }
    }
    </script>
  </div>
</body>
</html>
