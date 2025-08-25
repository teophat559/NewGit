<?php
// WebSettingsPage.php - Trang cài đặt web
$pageTitle = 'Cài đặt Web';
?>

<div class="content-header">
    <h1><i class="fas fa-cog"></i> Cài đặt Web</h1>
    <p>Quản lý cấu hình hệ thống, cài đặt chung và thông số kỹ thuật</p>
</div>

<!-- Settings Tabs -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Cài đặt hệ thống</h3>
    </div>

    <div class="settings-tabs">
        <div class="tab-buttons" style="display: flex; border-bottom: 1px solid #e9ecef; margin-bottom: 20px;">
            <button class="tab-btn active" onclick="showTab('general')" data-tab="general">
                <i class="fas fa-cog"></i> Cài đặt chung
            </button>
            <button class="tab-btn" onclick="showTab('security')" data-tab="security">
                <i class="fas fa-shield-alt"></i> Bảo mật
            </button>
            <button class="tab-btn" onclick="showTab('email')" data-tab="email">
                <i class="fas fa-envelope"></i> Email
            </button>
            <button class="tab-btn" onclick="showTab('database')" data-tab="database">
                <i class="fas fa-database"></i> Database
            </button>
            <button class="tab-btn" onclick="showTab('advanced')" data-tab="advanced">
                <i class="fas fa-tools"></i> Nâng cao
            </button>
        </div>

        <!-- General Settings Tab -->
        <div id="general-tab" class="tab-content active">
            <form id="generalSettingsForm" class="settings-form">
                <div class="form-group">
                    <label for="appName" class="form-label">Tên ứng dụng</label>
                    <input type="text" id="appName" name="app_name" class="form-input" value="Contest Management System">
                </div>

                <div class="form-group">
                    <label for="appUrl" class="form-label">URL ứng dụng</label>
                    <input type="url" id="appUrl" name="app_url" class="form-input" value="http://localhost">
                </div>

                <div class="form-group">
                    <label for="appTimezone" class="form-label">Múi giờ</label>
                    <select id="appTimezone" name="app_timezone" class="form-input form-select">
                        <option value="Asia/Ho_Chi_Minh" selected>Asia/Ho_Chi_Minh (GMT+7)</option>
                        <option value="UTC">UTC (GMT+0)</option>
                        <option value="America/New_York">America/New_York (GMT-5)</option>
                        <option value="Europe/London">Europe/London (GMT+0)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="appLanguage" class="form-label">Ngôn ngữ</label>
                    <select id="appLanguage" name="app_language" class="form-input form-select">
                        <option value="vi" selected>Tiếng Việt</option>
                        <option value="en">English</option>
                        <option value="zh">中文</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="maintenanceMode" class="form-label">Chế độ bảo trì</label>
                    <div class="form-checkbox">
                        <input type="checkbox" id="maintenanceMode" name="maintenance_mode">
                        <label for="maintenanceMode">Kích hoạt chế độ bảo trì</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="maintenanceMessage" class="form-label">Thông báo bảo trì</label>
                    <textarea id="maintenanceMessage" name="maintenance_message" class="form-input form-textarea" rows="3" placeholder="Thông báo hiển thị khi bảo trì..."></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu cài đặt chung
                    </button>
                </div>
            </form>
        </div>

        <!-- Security Settings Tab -->
        <div id="security-tab" class="tab-content">
            <form id="securitySettingsForm" class="settings-form">
                <div class="form-group">
                    <label for="sessionTimeout" class="form-label">Thời gian timeout session (phút)</label>
                    <input type="number" id="sessionTimeout" name="session_timeout" class="form-input" value="1440" min="15" max="10080">
                    <small class="text-muted">Từ 15 phút đến 1 tuần (10080 phút)</small>
                </div>

                <div class="form-group">
                    <label for="maxLoginAttempts" class="form-label">Số lần đăng nhập tối đa</label>
                    <input type="number" id="maxLoginAttempts" name="max_login_attempts" class="form-input" value="5" min="3" max="10">
                </div>

                <div class="form-group">
                    <label for="lockoutDuration" class="form-label">Thời gian khóa tài khoản (phút)</label>
                    <input type="number" id="lockoutDuration" name="lockout_duration" class="form-input" value="30" min="5" max="1440">
                </div>

                <div class="form-group">
                    <label for="passwordMinLength" class="form-label">Độ dài mật khẩu tối thiểu</label>
                    <input type="number" id="passwordMinLength" name="password_min_length" class="form-input" value="8" min="6" max="20">
                </div>

                <div class="form-group">
                    <label for="requireStrongPassword" class="form-label">Yêu cầu mật khẩu mạnh</label>
                    <div class="form-checkbox">
                        <input type="checkbox" id="requireStrongPassword" name="require_strong_password" checked>
                        <label for="requireStrongPassword">Bắt buộc mật khẩu có chữ hoa, chữ thường, số và ký tự đặc biệt</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="enableTwoFactor" class="form-label">Xác thực 2 yếu tố</label>
                    <div class="form-checkbox">
                        <input type="checkbox" id="enableTwoFactor" name="enable_two_factor">
                        <label for="enableTwoFactor">Kích hoạt xác thực 2 yếu tố cho admin</label>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu cài đặt bảo mật
                    </button>
                </div>
            </form>
        </div>

        <!-- Email Settings Tab -->
        <div id="email-tab" class="tab-content">
            <form id="emailSettingsForm" class="settings-form">
                <div class="form-group">
                    <label for="mailDriver" class="form-label">Mail Driver</label>
                    <select id="mailDriver" name="mail_driver" class="form-input form-select">
                        <option value="smtp" selected>SMTP</option>
                        <option value="sendmail">Sendmail</option>
                        <option value="mail">PHP Mail</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mailHost" class="form-label">SMTP Host</label>
                    <input type="text" id="mailHost" name="mail_host" class="form-input" value="smtp.gmail.com">
                </div>

                <div class="form-group">
                    <label for="mailPort" class="form-label">SMTP Port</label>
                    <input type="number" id="mailPort" name="mail_port" class="form-input" value="587" min="1" max="65535">
                </div>

                <div class="form-group">
                    <label for="mailUsername" class="form-label">SMTP Username</label>
                    <input type="email" id="mailUsername" name="mail_username" class="form-input" placeholder="your-email@gmail.com">
                </div>

                <div class="form-group">
                    <label for="mailPassword" class="form-label">SMTP Password</label>
                    <input type="password" id="mailPassword" name="mail_password" class="form-input" placeholder="App password hoặc mật khẩu">
                </div>

                <div class="form-group">
                    <label for="mailEncryption" class="form-label">Mã hóa</label>
                    <select id="mailEncryption" name="mail_encryption" class="form-input form-select">
                        <option value="tls" selected>TLS</option>
                        <option value="ssl">SSL</option>
                        <option value="">Không mã hóa</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mailFromAddress" class="form-label">Địa chỉ email gửi</label>
                    <input type="email" id="mailFromAddress" name="mail_from_address" class="form-input" placeholder="noreply@example.com">
                </div>

                <div class="form-group">
                    <label for="mailFromName" class="form-label">Tên người gửi</label>
                    <input type="text" id="mailFromName" name="mail_from_name" class="form-input" value="Contest System">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu cài đặt email
                    </button>
                    <button type="button" class="btn btn-outline" onclick="testEmailSettings()">
                        <i class="fas fa-paper-plane"></i> Test email
                    </button>
                </div>
            </form>
        </div>

        <!-- Database Settings Tab -->
        <div id="database-tab" class="tab-content">
            <form id="databaseSettingsForm" class="settings-form">
                <div class="form-group">
                    <label for="dbHost" class="form-label">Database Host</label>
                    <input type="text" id="dbHost" name="db_host" class="form-input" value="localhost">
                </div>

                <div class="form-group">
                    <label for="dbName" class="form-label">Database Name</label>
                    <input type="text" id="dbName" name="db_name" class="form-input" value="contest_system">
                </div>

                <div class="form-group">
                    <label for="dbUsername" class="form-label">Database Username</label>
                    <input type="text" id="dbUsername" name="db_username" class="form-input" value="root">
                </div>

                <div class="form-group">
                    <label for="dbPassword" class="form-label">Database Password</label>
                    <input type="password" id="dbPassword" name="db_password" class="form-input">
                </div>

                <div class="form-group">
                    <label for="dbCharset" class="form-label">Database Charset</label>
                    <select id="dbCharset" name="db_charset" class="form-input form-select">
                        <option value="utf8mb4" selected>utf8mb4</option>
                        <option value="utf8">utf8</option>
                        <option value="latin1">latin1</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="dbBackupEnabled" class="form-label">Tự động backup</label>
                    <div class="form-checkbox">
                        <input type="checkbox" id="dbBackupEnabled" name="db_backup_enabled">
                        <label for="dbBackupEnabled">Kích hoạt backup tự động</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="dbBackupFrequency" class="form-label">Tần suất backup</label>
                    <select id="dbBackupFrequency" name="db_backup_frequency" class="form-input form-select">
                        <option value="daily">Hàng ngày</option>
                        <option value="weekly" selected>Hàng tuần</option>
                        <option value="monthly">Hàng tháng</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu cài đặt database
                    </button>
                    <button type="button" class="btn btn-outline" onclick="testDatabaseConnection()">
                        <i class="fas fa-database"></i> Test kết nối
                    </button>
                </div>
            </form>
        </div>

        <!-- Advanced Settings Tab -->
        <div id="advanced-tab" class="tab-content">
            <form id="advancedSettingsForm" class="settings-form">
                <div class="form-group">
                    <label for="debugMode" class="form-label">Chế độ debug</label>
                    <div class="form-checkbox">
                        <input type="checkbox" id="debugMode" name="debug_mode">
                        <label for="debugMode">Kích hoạt chế độ debug (chỉ dùng trong development)</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="logLevel" class="form-label">Mức độ log</label>
                    <select id="logLevel" name="log_level" class="form-input form-select">
                        <option value="debug">Debug</option>
                        <option value="info" selected>Info</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cacheDriver" class="form-label">Cache Driver</label>
                    <select id="cacheDriver" name="cache_driver" class="form-input form-select">
                        <option value="file" selected>File</option>
                        <option value="redis">Redis</option>
                        <option value="memcached">Memcached</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="queueDriver" class="form-label">Queue Driver</label>
                    <select id="queueDriver" name="queue_driver" class="form-input form-select">
                        <option value="sync" selected>Synchronous</option>
                        <option value="database">Database</option>
                        <option value="redis">Redis</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sessionDriver" class="form-label">Session Driver</label>
                    <select id="sessionDriver" name="session_driver" class="form-input form-select">
                        <option value="file" selected>File</option>
                        <option value="database">Database</option>
                        <option value="redis">Redis</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu cài đặt nâng cao
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-info-circle"></i> Thông tin hệ thống
        </h3>
    </div>

    <div class="system-info" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div class="info-section">
            <h4 style="margin-bottom: 15px; color: #2c3e50;">Thông tin PHP</h4>
            <div class="info-item" style="margin-bottom: 10px;">
                <strong>Phiên bản PHP:</strong> <span id="phpVersion"><?php echo PHP_VERSION; ?></span>
            </div>
            <div class="info-item" style="margin-bottom: 10px;">
                <strong>Extensions:</strong> <span id="phpExtensions"><?php echo implode(', ', get_loaded_extensions()); ?></span>
            </div>
            <div class="info-item" style="margin-bottom: 10px;">
                <strong>Memory Limit:</strong> <span id="memoryLimit"><?php echo ini_get('memory_limit'); ?></span>
            </div>
        </div>

        <div class="info-section">
            <h4 style="margin-bottom: 15px; color: #2c3e50;">Thông tin Server</h4>
            <div class="info-item" style="margin-bottom: 10px;">
                <strong>Server Software:</strong> <span id="serverSoftware"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
            </div>
            <div class="info-item" style="margin-bottom: 10px;">
                <strong>OS:</strong> <span id="osInfo"><?php echo PHP_OS; ?></span>
            </div>
            <div class="info-item" style="margin-bottom: 10px;">
                <strong>Document Root:</strong> <span id="docRoot"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></span>
            </div>
        </div>

        <div class="info-section">
            <h4 style="margin-bottom: 15px; color: #2c3e50;">Thông tin Database</h4>
            <div class="info-item" style="margin-bottom: 10px;">
                <strong>Status:</strong> <span id="dbStatus" class="status-badge">
                    <span class="status-indicator status-pending"></span>Kiểm tra...
                </span>
            </div>
            <div class="info-item" style="margin-bottom: 10px;">
                <strong>Tables:</strong> <span id="dbTables">-</span>
            </div>
            <div class="info-item" style="margin-bottom: 10px;">
                <strong>Size:</strong> <span id="dbSize">-</span>
            </div>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Thao tác hệ thống</h3>
    </div>

    <div class="system-actions" style="display: flex; gap: 15px; flex-wrap: wrap;">
        <button class="btn btn-outline" onclick="clearCache()">
            <i class="fas fa-broom"></i> Xóa cache
        </button>
        <button class="btn btn-outline" onclick="clearLogs()">
            <i class="fas fa-trash"></i> Xóa logs
        </button>
        <button class="btn btn-outline" onclick="optimizeDatabase()">
            <i class="fas fa-database"></i> Tối ưu database
        </button>
        <button class="btn btn-outline" onclick="backupDatabase()">
            <i class="fas fa-download"></i> Backup database
        </button>
        <button class="btn btn-outline" onclick="restartServices()">
            <i class="fas fa-redo"></i> Khởi động lại services
        </button>
    </div>
</div>

<style>
.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.tab-btn {
    background: none;
    border: none;
    padding: 12px 20px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    color: #6c757d;
}

.tab-btn.active {
    color: #667eea;
    border-bottom-color: #667eea;
}

.tab-btn:hover {
    color: #667eea;
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-checkbox input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
}

.status-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-active {
    background: #28a745;
}

.status-pending {
    background: #ffc107;
}

.status-error {
    background: #dc3545;
}
</style>

<script>
// Tab functionality
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');

    // Add active class to clicked button
    event.target.classList.add('active');
}

// Load settings on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSettings();
    checkSystemStatus();
    setupFormHandlers();
});

function setupFormHandlers() {
    // General settings form
    document.getElementById('generalSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveGeneralSettings();
    });

    // Security settings form
    document.getElementById('securitySettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSecuritySettings();
    });

    // Email settings form
    document.getElementById('emailSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveEmailSettings();
    });

    // Database settings form
    document.getElementById('databaseSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveDatabaseSettings();
    });

    // Advanced settings form
    document.getElementById('advancedSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveAdvancedSettings();
    });
}

async function loadSettings() {
    try {
        const response = await window.app.apiCall('admin/settings', 'GET');
        if (response.success) {
            populateSettings(response.settings);
        }
    } catch (error) {
        console.error('Failed to load settings:', error);
        showNotification('Không thể tải cài đặt', 'error');
    }
}

function populateSettings(settings) {
    // Populate general settings
    if (settings.general) {
        document.getElementById('appName').value = settings.general.app_name || '';
        document.getElementById('appUrl').value = settings.general.app_url || '';
        document.getElementById('appTimezone').value = settings.general.app_timezone || 'Asia/Ho_Chi_Minh';
        document.getElementById('appLanguage').value = settings.general.app_language || 'vi';
        document.getElementById('maintenanceMode').checked = settings.general.maintenance_mode || false;
        document.getElementById('maintenanceMessage').value = settings.general.maintenance_message || '';
    }

    // Populate security settings
    if (settings.security) {
        document.getElementById('sessionTimeout').value = settings.security.session_timeout || 1440;
        document.getElementById('maxLoginAttempts').value = settings.security.max_login_attempts || 5;
        document.getElementById('lockoutDuration').value = settings.security.lockout_duration || 30;
        document.getElementById('passwordMinLength').value = settings.security.password_min_length || 8;
        document.getElementById('requireStrongPassword').checked = settings.security.require_strong_password || false;
        document.getElementById('enableTwoFactor').checked = settings.security.enable_two_factor || false;
    }

    // Populate email settings
    if (settings.email) {
        document.getElementById('mailDriver').value = settings.email.mail_driver || 'smtp';
        document.getElementById('mailHost').value = settings.email.mail_host || '';
        document.getElementById('mailPort').value = settings.email.mail_port || 587;
        document.getElementById('mailUsername').value = settings.email.mail_username || '';
        document.getElementById('mailPassword').value = settings.email.mail_password || '';
        document.getElementById('mailEncryption').value = settings.email.mail_encryption || 'tls';
        document.getElementById('mailFromAddress').value = settings.email.mail_from_address || '';
        document.getElementById('mailFromName').value = settings.email.mail_from_name || '';
    }

    // Populate database settings
    if (settings.database) {
        document.getElementById('dbHost').value = settings.database.db_host || 'localhost';
        document.getElementById('dbName').value = settings.database.db_name || '';
        document.getElementById('dbUsername').value = settings.database.db_username || '';
        document.getElementById('dbPassword').value = settings.database.db_password || '';
        document.getElementById('dbCharset').value = settings.database.db_charset || 'utf8mb4';
        document.getElementById('dbBackupEnabled').checked = settings.database.db_backup_enabled || false;
        document.getElementById('dbBackupFrequency').value = settings.database.db_backup_frequency || 'weekly';
    }

    // Populate advanced settings
    if (settings.advanced) {
        document.getElementById('debugMode').checked = settings.advanced.debug_mode || false;
        document.getElementById('logLevel').value = settings.advanced.log_level || 'info';
        document.getElementById('cacheDriver').value = settings.advanced.cache_driver || 'file';
        document.getElementById('queueDriver').value = settings.advanced.queue_driver || 'sync';
        document.getElementById('sessionDriver').value = settings.advanced.session_driver || 'file';
    }
}

async function saveGeneralSettings() {
    const formData = new FormData(document.getElementById('generalSettingsForm'));
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await window.app.apiCall('admin/settings/general', 'POST', data);
        if (response.success) {
            showNotification('Lưu cài đặt chung thành công', 'success');
        } else {
            showNotification(response.message || 'Lưu cài đặt thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to save general settings:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function saveSecuritySettings() {
    const formData = new FormData(document.getElementById('securitySettingsForm'));
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await window.app.apiCall('admin/settings/security', 'POST', data);
        if (response.success) {
            showNotification('Lưu cài đặt bảo mật thành công', 'success');
        } else {
            showNotification(response.message || 'Lưu cài đặt thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to save security settings:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function saveEmailSettings() {
    const formData = new FormData(document.getElementById('emailSettingsForm'));
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await window.app.apiCall('admin/settings/email', 'POST', data);
        if (response.success) {
            showNotification('Lưu cài đặt email thành công', 'success');
        } else {
            showNotification(response.message || 'Lưu cài đặt thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to save email settings:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function saveDatabaseSettings() {
    const formData = new FormData(document.getElementById('databaseSettingsForm'));
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await window.app.apiCall('admin/settings/database', 'POST', data);
        if (response.success) {
            showNotification('Lưu cài đặt database thành công', 'success');
        } else {
            showNotification(response.message || 'Lưu cài đặt thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to save database settings:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function saveAdvancedSettings() {
    const formData = new FormData(document.getElementById('advancedSettingsForm'));
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await window.app.apiCall('admin/settings/advanced', 'POST', data);
        if (response.success) {
            showNotification('Lưu cài đặt nâng cao thành công', 'success');
        } else {
            showNotification(response.message || 'Lưu cài đặt thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to save advanced settings:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function checkSystemStatus() {
    try {
        // Check database status
        const dbResponse = await window.app.apiCall('health', 'GET');
        updateDbStatus(dbResponse.status === 'healthy' ? 'success' : 'error');

        // Get database info
        if (dbResponse.status === 'healthy') {
            const dbInfoResponse = await window.app.apiCall('admin/system/database-info', 'GET');
            if (dbInfoResponse.success) {
                document.getElementById('dbTables').textContent = dbInfoResponse.info.tables || '-';
                document.getElementById('dbSize').textContent = dbInfoResponse.info.size || '-';
            }
        }

    } catch (error) {
        console.error('System status check failed:', error);
        updateDbStatus('error');
    }
}

function updateDbStatus(status) {
    const element = document.getElementById('dbStatus');
    const indicator = element.querySelector('.status-indicator');

    indicator.classList.remove('status-active', 'status-pending', 'status-error');

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

// System actions
async function clearCache() {
    if (!confirm('Bạn có chắc chắn muốn xóa cache?')) return;

    try {
        const response = await window.app.apiCall('admin/system/clear-cache', 'POST');
        if (response.success) {
            showNotification('Xóa cache thành công', 'success');
        } else {
            showNotification(response.message || 'Xóa cache thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to clear cache:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function clearLogs() {
    if (!confirm('Bạn có chắc chắn muốn xóa logs?')) return;

    try {
        const response = await window.app.apiCall('admin/system/clear-logs', 'POST');
        if (response.success) {
            showNotification('Xóa logs thành công', 'success');
        } else {
            showNotification(response.message || 'Xóa logs thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to clear logs:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function optimizeDatabase() {
    if (!confirm('Bạn có chắc chắn muốn tối ưu database?')) return;

    try {
        const response = await window.app.apiCall('admin/system/optimize-database', 'POST');
        if (response.success) {
            showNotification('Tối ưu database thành công', 'success');
        } else {
            showNotification(response.message || 'Tối ưu database thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to optimize database:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function backupDatabase() {
    try {
        const response = await window.app.apiCall('admin/system/backup-database', 'POST');
        if (response.success) {
            showNotification('Backup database thành công', 'success');
            // Trigger download
            if (response.download_url) {
                window.open(response.download_url, '_blank');
            }
        } else {
            showNotification(response.message || 'Backup database thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to backup database:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function restartServices() {
    if (!confirm('Bạn có chắc chắn muốn khởi động lại services?')) return;

    try {
        const response = await window.app.apiCall('admin/system/restart-services', 'POST');
        if (response.success) {
            showNotification('Khởi động lại services thành công', 'success');
        } else {
            showNotification(response.message || 'Khởi động lại services thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to restart services:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

// Test functions
async function testEmailSettings() {
    try {
        const response = await window.app.apiCall('admin/system/test-email', 'POST');
        if (response.success) {
            showNotification('Test email thành công', 'success');
        } else {
            showNotification(response.message || 'Test email thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to test email:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

async function testDatabaseConnection() {
    try {
        const response = await window.app.apiCall('admin/system/test-database', 'POST');
        if (response.success) {
            showNotification('Test kết nối database thành công', 'success');
        } else {
            showNotification(response.message || 'Test kết nối database thất bại', 'error');
        }
    } catch (error) {
        console.error('Failed to test database connection:', error);
        showNotification('Lỗi kết nối server', 'error');
    }
}

function showNotification(message, type) {
    if (window.showNotification) {
        window.showNotification(message, type);
    } else {
        alert(message);
    }
}
</script>
