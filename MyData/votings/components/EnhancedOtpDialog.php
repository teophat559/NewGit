<?php
/**
 * Enhanced OTP Dialog - BVOTE
 * Giao diện nhập OTP hoàn thiện với UX tốt nhất
 */
?>
<div id="enhanced-otp-dialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-white text-lg font-semibold">Xác thực OTP</h3>
                        <p class="text-blue-100 text-sm">Nhập mã xác thực 6 số</p>
                    </div>
                </div>
                <button onclick="closeEnhancedOtpDialog()" class="text-white hover:text-blue-100 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Platform Info -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 mb-2">
                    <i class="fas fa-globe mr-2"></i>
                    <span id="otp-platform-name">Facebook</span>
                </div>
                <p class="text-gray-600 text-sm">
                    Mã OTP đã được gửi đến <span id="otp-user-hint" class="font-medium">user@example.com</span>
                </p>
            </div>

            <!-- OTP Input -->
            <div class="mb-6">
                <div class="flex justify-center space-x-2" id="otp-input-container">
                    <!-- OTP inputs will be generated here -->
                </div>
                <p class="text-center text-xs text-gray-500 mt-2">
                    Nhập từng số để tự động chuyển ô tiếp theo
                </p>
            </div>

            <!-- Timer & Actions -->
            <div class="text-center mb-6">
                <div class="mb-3">
                    <span class="text-sm text-gray-600">Mã OTP sẽ hết hạn sau</span>
                    <div class="text-2xl font-bold text-red-500 mt-1" id="otp-countdown">02:00</div>
                </div>

                <div class="flex space-x-3">
                    <button type="button" onclick="submitEnhancedOtp()"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            id="otp-submit-btn">
                        <i class="fas fa-check mr-2"></i>Xác nhận
                    </button>
                    <button type="button" onclick="resendEnhancedOtp()"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-lg font-medium transition-colors"
                            id="otp-resend-btn">
                        <i class="fas fa-redo mr-2"></i>Gửi lại
                    </button>
                </div>
            </div>

            <!-- Error Message -->
            <div id="otp-error-message" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                    <span class="text-red-700 text-sm" id="otp-error-text"></span>
                </div>
            </div>

            <!-- Success Message -->
            <div id="otp-success-message" class="hidden bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span class="text-green-700 text-sm">Xác thực OTP thành công!</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
class EnhancedOtpDialog {
    constructor() {
        this.otpLength = 6;
        this.currentOtp = '';
        this.countdownInterval = null;
        this.timeLeft = 120; // 2 minutes
        this.requestId = null;
        this.platform = null;
        this.userHint = null;
        this.onSuccess = null;
        this.onError = null;
        this.retryCount = 0;
        this.maxRetries = 3;
    }

    show(requestId, platform, userHint, onSuccess, onError) {
        this.requestId = requestId;
        this.platform = platform;
        this.userHint = userHint;
        this.onSuccess = onSuccess;
        this.onError = onError;
        this.retryCount = 0;
        this.timeLeft = 120;

        // Update UI
        document.getElementById('otp-platform-name').textContent = this.getPlatformDisplayName(platform);
        document.getElementById('otp-user-hint').textContent = userHint;

        // Generate OTP inputs
        this.generateOtpInputs();

        // Show dialog
        document.getElementById('enhanced-otp-dialog').classList.remove('hidden');

        // Start countdown
        this.startCountdown();

        // Focus first input
        setTimeout(() => {
            document.querySelector('#otp-input-container input').focus();
        }, 100);

        // Hide messages
        this.hideMessages();
    }

    hide() {
        document.getElementById('enhanced-otp-dialog').classList.add('hidden');
        this.stopCountdown();
        this.clearOtpInputs();
    }

    generateOtpInputs() {
        const container = document.getElementById('otp-input-container');
        container.innerHTML = '';

        for (let i = 0; i < this.otpLength; i++) {
            const input = document.createElement('input');
            input.type = 'text';
            input.maxLength = 1;
            input.className = 'w-12 h-12 text-center text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all';
            input.dataset.index = i;
            input.dataset.value = '';

            input.addEventListener('input', (e) => this.handleOtpInput(e, i));
            input.addEventListener('keydown', (e) => this.handleOtpKeydown(e, i));
            input.addEventListener('paste', (e) => this.handleOtpPaste(e));

            container.appendChild(input);
        }
    }

    handleOtpInput(e, index) {
        const input = e.target;
        const value = e.data || input.value;

        if (value && /^\d$/.test(value)) {
            input.value = value;
            input.dataset.value = value;
            input.classList.remove('border-red-500');
            input.classList.add('border-green-500');

            // Move to next input
            if (index < this.otpLength - 1) {
                const nextInput = document.querySelector(`[data-index="${index + 1}"]`);
                nextInput.focus();
            }
        } else {
            input.value = '';
            input.dataset.value = '';
        }

        // Check if OTP is complete
        this.checkOtpComplete();
    }

    handleOtpKeydown(e, index) {
        if (e.key === 'Backspace' && !input.value && index > 0) {
            const prevInput = document.querySelector(`[data-index="${index - 1}"]`);
            prevInput.focus();
            prevInput.value = '';
            prevInput.dataset.value = '';
            prevInput.classList.remove('border-green-500');
            prevInput.classList.add('border-gray-300');
        }
    }

    handleOtpPaste(e) {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text');
        const numbers = pastedData.replace(/\D/g, '').slice(0, this.otpLength);

        if (numbers.length === this.otpLength) {
            const inputs = document.querySelectorAll('#otp-input-container input');
            inputs.forEach((input, index) => {
                if (numbers[index]) {
                    input.value = numbers[index];
                    input.dataset.value = numbers[index];
                    input.classList.remove('border-red-500');
                    input.classList.add('border-green-500');
                }
            });
            this.checkOtpComplete();
        }
    }

    checkOtpComplete() {
        const inputs = document.querySelectorAll('#otp-input-container input');
        const otp = Array.from(inputs).map(input => input.dataset.value).join('');

        if (otp.length === this.otpLength) {
            this.currentOtp = otp;
            document.getElementById('otp-submit-btn').disabled = false;
        } else {
            document.getElementById('otp-submit-btn').disabled = true;
        }
    }

    startCountdown() {
        this.countdownInterval = setInterval(() => {
            this.timeLeft--;
            const minutes = Math.floor(this.timeLeft / 60);
            const seconds = this.timeLeft % 60;

            document.getElementById('otp-countdown').textContent =
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            if (this.timeLeft <= 0) {
                this.stopCountdown();
                this.showError('Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.');
                document.getElementById('otp-resend-btn').disabled = false;
            }
        }, 1000);
    }

    stopCountdown() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }
    }

    async submitOtp() {
        if (!this.currentOtp || this.currentOtp.length !== this.otpLength) {
            this.showError('Vui lòng nhập đầy đủ mã OTP');
            return;
        }

        try {
            document.getElementById('otp-submit-btn').disabled = true;
            document.getElementById('otp-submit-btn').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';

            const response = await fetch(`/api/social-login/${this.requestId}/otp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    otp_code: this.currentOtp
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess();
                setTimeout(() => {
                    this.hide();
                    if (this.onSuccess) {
                        this.onSuccess(data);
                    }
                }, 1500);
            } else {
                this.retryCount++;
                if (this.retryCount >= this.maxRetries) {
                    this.showError('Bạn đã nhập sai OTP quá nhiều lần. Yêu cầu đăng nhập bị từ chối.');
                    setTimeout(() => {
                        this.hide();
                        if (this.onError) {
                            this.onError('OTP_MAX_RETRIES');
                        }
                    }, 3000);
                } else {
                    this.showError(`Mã OTP không đúng. Còn ${this.maxRetries - this.retryCount} lần thử.`);
                    this.clearOtpInputs();
                    document.querySelector('#otp-input-container input').focus();
                }
            }
        } catch (error) {
            console.error('OTP submission error:', error);
            this.showError('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            document.getElementById('otp-submit-btn').disabled = false;
            document.getElementById('otp-submit-btn').innerHTML = '<i class="fas fa-check mr-2"></i>Xác nhận';
        }
    }

    async resendOtp() {
        try {
            document.getElementById('otp-resend-btn').disabled = true;
            document.getElementById('otp-resend-btn').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang gửi...';

            const response = await fetch(`/api/admin/auth/requests/${this.requestId}/require-otp`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'require_otp',
                    otp_length: this.otpLength
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Mã OTP mới đã được gửi!');
                this.retryCount = 0;
                this.timeLeft = 120;
                this.startCountdown();
                this.clearOtpInputs();
                document.querySelector('#otp-input-container input').focus();
                document.getElementById('otp-resend-btn').disabled = true;

                // Re-enable resend button after 60 seconds
                setTimeout(() => {
                    document.getElementById('otp-resend-btn').disabled = false;
                }, 60000);
            } else {
                this.showError('Không thể gửi lại mã OTP. Vui lòng thử lại.');
            }
        } catch (error) {
            console.error('OTP resend error:', error);
            this.showError('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            document.getElementById('otp-resend-btn').innerHTML = '<i class="fas fa-redo mr-2"></i>Gửi lại';
        }
    }

    clearOtpInputs() {
        const inputs = document.querySelectorAll('#otp-input-container input');
        inputs.forEach(input => {
            input.value = '';
            input.dataset.value = '';
            input.classList.remove('border-green-500', 'border-red-500');
            input.classList.add('border-gray-300');
        });
        this.currentOtp = '';
        document.getElementById('otp-submit-btn').disabled = true;
    }

    showError(message) {
        document.getElementById('otp-error-text').textContent = message;
        document.getElementById('otp-error-message').classList.remove('hidden');
        document.getElementById('otp-success-message').classList.add('hidden');
    }

    showSuccess(message = 'Xác thực OTP thành công!') {
        document.getElementById('otp-success-message').querySelector('span').textContent = message;
        document.getElementById('otp-success-message').classList.remove('hidden');
        document.getElementById('otp-error-message').classList.add('hidden');
    }

    hideMessages() {
        document.getElementById('otp-error-message').classList.add('hidden');
        document.getElementById('otp-success-message').classList.add('hidden');
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
}

// Global instance
window.enhancedOtpDialog = new EnhancedOtpDialog();

// Global functions
function showEnhancedOtpDialog(requestId, platform, userHint, onSuccess, onError) {
    window.enhancedOtpDialog.show(requestId, platform, userHint, onSuccess, onError);
}

function closeEnhancedOtpDialog() {
    window.enhancedOtpDialog.hide();
}

function submitEnhancedOtp() {
    window.enhancedOtpDialog.submitOtp();
}

function resendEnhancedOtp() {
    window.enhancedOtpDialog.resendOtp();
}
</script>
