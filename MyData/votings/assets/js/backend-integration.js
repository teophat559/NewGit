// BVOTE 2025 - Frontend Backend Integration Script
// Tăng cường kết nối giữa giao diện và backend mà không thay đổi UI

class BVoteBackendIntegration {
    constructor() {
        this.backendURL = './user-interface-backend.php';
        this.adminBackendURL = './admin/admin-backend.php';
        this.connectionURL = './admin/admin-user-connection.php';
        this.sessionId = this.generateSessionId();
        this.isOnline = navigator.onLine;
        this.heartbeatInterval = null;
        this.performanceMetrics = [];

        this.init();
    }

    /**
     * Initialize backend connection
     */
    init() {
        console.log('🔗 BVOTE Backend Integration initializing...');

        // Setup database
        this.setupBackend();

        // Start heartbeat
        this.startHeartbeat();

        // Track page performance
        this.trackPagePerformance();

        // Listen for admin messages
        this.listenForAdminMessages();

        // Track user activity
        this.trackUserActivity();

        // Handle offline/online events
        this.handleConnectionEvents();

        console.log('✅ Backend integration ready');
    }

    /**
     * Setup backend database
     */
    async setupBackend() {
        try {
            const response = await fetch(this.backendURL + '?setup=1');
            const data = await response.json();
            console.log('📊 Backend setup:', data);
        } catch (error) {
            console.warn('⚠️ Backend setup failed, continuing in offline mode');
        }
    }

    /**
     * Start heartbeat to maintain connection
     */
    startHeartbeat() {
        this.heartbeatInterval = setInterval(async () => {
            try {
                const response = await fetch(this.backendURL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=heartbeat'
                });
                const data = await response.json();

                if (data.success) {
                    this.isOnline = true;
                    // console.log('💓 Heartbeat:', data.timestamp);
                }
            } catch (error) {
                this.isOnline = false;
                console.warn('💔 Heartbeat failed');
            }
        }, 30000); // Every 30 seconds
    }

    /**
     * Track page performance
     */
    trackPagePerformance() {
        // Track page load time
        window.addEventListener('load', () => {
            const loadTime = performance.now();
            this.logPageView(window.location.pathname, Math.round(loadTime));
        });

        // Track navigation timing
        if (performance.navigation) {
            const perfData = performance.getEntriesByType('navigation')[0];
            if (perfData) {
                this.performanceMetrics.push({
                    page: window.location.pathname,
                    load_time: Math.round(perfData.loadEventEnd - perfData.loadEventStart),
                    dom_ready: Math.round(perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart),
                    dns_time: Math.round(perfData.domainLookupEnd - perfData.domainLookupStart)
                });
            }
        }
    }

    /**
     * Log page view to backend
     */
    async logPageView(page, loadTime) {
        if (!this.isOnline) return;

        try {
            const formData = new FormData();
            formData.append('action', 'log_page_view');
            formData.append('page', page);
            formData.append('load_time', loadTime);

            await fetch(this.backendURL, {
                method: 'POST',
                body: formData
            });

            console.log(`📄 Page view logged: ${page} (${loadTime}ms)`);
        } catch (error) {
            console.warn('⚠️ Failed to log page view');
        }
    }

    /**
     * Submit vote với backend integration
     */
    async submitVote(campaignId, contestantId) {
        try {
            const formData = new FormData();
            formData.append('action', 'submit_vote');
            formData.append('campaign_id', campaignId);
            formData.append('contestant_id', contestantId);

            const response = await fetch(this.backendURL, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                console.log('✅ Vote submitted:', data);
                this.showNotification('success', 'Vote đã được ghi nhận!');
                return { success: true, vote_id: data.vote_id };
            } else {
                console.error('❌ Vote failed:', data.error);
                this.showNotification('error', data.error || 'Có lỗi xảy ra khi vote');
                return { success: false, error: data.error };
            }
        } catch (error) {
            console.error('❌ Vote submission error:', error);
            this.showNotification('error', 'Không thể kết nối đến server');
            return { success: false, error: 'Network error' };
        }
    }

    /**
     * Get campaigns from backend
     */
    async getCampaigns() {
        try {
            const response = await fetch(this.backendURL + '?action=get_campaigns');
            const data = await response.json();

            if (data.success) {
                return data.campaigns;
            }
        } catch (error) {
            console.warn('⚠️ Failed to get campaigns from backend');
        }

        return [];
    }

    /**
     * Get contestants for a campaign
     */
    async getContestants(campaignId) {
        try {
            const response = await fetch(this.backendURL + `?action=get_contestants&campaign_id=${campaignId}`);
            const data = await response.json();

            if (data.success) {
                return data.contestants;
            }
        } catch (error) {
            console.warn('⚠️ Failed to get contestants from backend');
        }

        return [];
    }

    /**
     * Check if user has voted
     */
    async checkVoteStatus(campaignId) {
        try {
            const response = await fetch(this.backendURL + `?action=check_vote_status&campaign_id=${campaignId}`);
            const data = await response.json();

            if (data.success) {
                return data.has_voted;
            }
        } catch (error) {
            console.warn('⚠️ Failed to check vote status');
        }

        return false;
    }

    /**
     * Send feedback to admin
     */
    async sendFeedback(type, message, metadata = {}) {
        try {
            const formData = new FormData();
            formData.append('action', 'send_feedback');
            formData.append('type', type);
            formData.append('message', message);
            formData.append('metadata', JSON.stringify(metadata));

            const response = await fetch(this.backendURL, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                console.log('📤 Feedback sent to admin');
                this.showNotification('success', 'Feedback đã được gửi đến admin');
                return true;
            }
        } catch (error) {
            console.warn('⚠️ Failed to send feedback');
        }

        return false;
    }

    /**
     * Listen for admin messages
     */
    async listenForAdminMessages() {
        try {
            const response = await fetch(this.backendURL + '?action=get_admin_messages');
            const data = await response.json();

            if (data.success && data.messages.length > 0) {
                data.messages.forEach(message => {
                    this.handleAdminMessage(message);
                });
            }
        } catch (error) {
            console.warn('⚠️ Failed to get admin messages');
        }

        // Schedule next check
        setTimeout(() => {
            this.listenForAdminMessages();
        }, 10000); // Check every 10 seconds
    }

    /**
     * Handle admin messages
     */
    handleAdminMessage(message) {
        console.log('📨 Admin message:', message);

        const messageData = JSON.parse(message.data || '{}');

        switch (message.command) {
            case 'campaign_paused':
                this.showNotification('warning', 'Cuộc bình chọn đã tạm dừng');
                this.disableVoting();
                break;

            case 'campaign_resumed':
                this.showNotification('success', 'Cuộc bình chọn đã được tiếp tục');
                this.enableVoting();
                break;

            case 'broadcast_message':
                this.showNotification('info', messageData.message || 'Thông báo từ admin');
                break;

            case 'emergency_stop':
                this.showNotification('error', 'Hệ thống đã tạm ngưng hoạt động');
                this.emergencyStop();
                break;
        }
    }

    /**
     * Track user activity
     */
    trackUserActivity() {
        // Track clicks
        document.addEventListener('click', (e) => {
            if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                const button = e.target.closest('button') || e.target;
                console.log('👆 Button clicked:', button.textContent?.trim());
            }
        });

        // Track form submissions
        document.addEventListener('submit', (e) => {
            console.log('📝 Form submitted:', e.target.id || 'unknown');
        });

        // Track page visibility
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('👁️ Page hidden');
            } else {
                console.log('👁️ Page visible');
                this.logPageView(window.location.pathname, 0);
            }
        });
    }

    /**
     * Handle connection events
     */
    handleConnectionEvents() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            console.log('🌐 Connection restored');
            this.showNotification('success', 'Kết nối đã được khôi phục');
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            console.log('📵 Connection lost');
            this.showNotification('warning', 'Mất kết nối mạng');
        });
    }

    /**
     * Show notification (không thay đổi UI style)
     */
    showNotification(type, message) {
        // Simple console notification - không thay đổi giao diện
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        console.log(`${icons[type]} ${message}`);

        // Có thể trigger existing notification system nếu có
        if (window.showExistingNotification) {
            window.showExistingNotification(type, message);
        }
    }

    /**
     * Voting control methods
     */
    disableVoting() {
        const voteButtons = document.querySelectorAll('button[onclick*="vote"], .vote-btn');
        voteButtons.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = '0.5';
        });
    }

    enableVoting() {
        const voteButtons = document.querySelectorAll('button[onclick*="vote"], .vote-btn');
        voteButtons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
    }

    emergencyStop() {
        // Disable all interactions
        const allButtons = document.querySelectorAll('button');
        const allInputs = document.querySelectorAll('input, select, textarea');

        allButtons.forEach(btn => btn.disabled = true);
        allInputs.forEach(input => input.disabled = true);

        // Show emergency message
        const emergencyDiv = document.createElement('div');
        emergencyDiv.style.cssText = `
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8); color: white; z-index: 9999;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; text-align: center;
        `;
        emergencyDiv.innerHTML = '🚨 Hệ thống đang bảo trì<br>Vui lòng thử lại sau';
        document.body.appendChild(emergencyDiv);
    }

    /**
     * Generate session ID
     */
    generateSessionId() {
        return 'bvote_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    }

    /**
     * Log performance metrics
     */
    async logPerformanceMetrics() {
        if (this.performanceMetrics.length === 0 || !this.isOnline) return;

        try {
            const formData = new FormData();
            formData.append('action', 'log_performance');
            formData.append('metrics', JSON.stringify(this.performanceMetrics));

            await fetch(this.backendURL, {
                method: 'POST',
                body: formData
            });

            this.performanceMetrics = []; // Clear logged metrics
        } catch (error) {
            console.warn('⚠️ Failed to log performance metrics');
        }
    }

    /**
     * Cleanup on page unload
     */
    cleanup() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
        }

        // Log final metrics
        this.logPerformanceMetrics();

        console.log('🧹 Backend integration cleanup completed');
    }
}

// Initialize backend integration khi DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.BVoteBackend = new BVoteBackendIntegration();

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (window.BVoteBackend) {
            window.BVoteBackend.cleanup();
        }
    });
});

// Enhanced voting function với backend integration
window.enhancedVote = async function(contestantId, campaignId = 1) {
    if (!window.BVoteBackend) {
        console.warn('⚠️ Backend not initialized, using fallback');
        return false;
    }

    // Check if already voted
    const hasVoted = await window.BVoteBackend.checkVoteStatus(campaignId);
    if (hasVoted) {
        window.BVoteBackend.showNotification('warning', 'Bạn đã vote cho cuộc thi này rồi!');
        return false;
    }

    // Submit vote
    const result = await window.BVoteBackend.submitVote(campaignId, contestantId);

    if (result.success) {
        // Update UI nếu cần (không thay đổi style)
        const voteButton = document.querySelector(`button[onclick*="${contestantId}"]`);
        if (voteButton) {
            voteButton.textContent = 'Đã Vote ✓';
            voteButton.disabled = true;
        }

        return true;
    }

    return false;
};

// Enhanced feedback function
window.sendFeedbackToAdmin = async function(message, type = 'general') {
    if (!window.BVoteBackend) {
        console.warn('⚠️ Backend not initialized');
        return false;
    }

    return await window.BVoteBackend.sendFeedback(type, message, {
        page: window.location.pathname,
        timestamp: new Date().toISOString(),
        user_agent: navigator.userAgent
    });
};

console.log('🚀 BVOTE Backend Integration Script Loaded');
