<?php
/**
 * Enhanced Components - BVOTE
 * Tổng hợp tất cả các enhanced components cho hệ thống
 */

// Include tất cả các enhanced components
require_once __DIR__ . '/EnhancedOtpDialog.php';
require_once __DIR__ . '/EnhancedWaitingScreen.php';
require_once __DIR__ . '/RealtimeAdminDashboard.php';
?>

<!-- Font Awesome for icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Enhanced Components Container -->
<div id="enhanced-components-container" style="display: none;">
    <!-- Enhanced OTP Dialog -->
    <?php include __DIR__ . '/EnhancedOtpDialog.php'; ?>

    <!-- Enhanced Waiting Screen -->
    <?php include __DIR__ . '/EnhancedWaitingScreen.php'; ?>

    <!-- Realtime Admin Dashboard -->
    <?php include __DIR__ . '/RealtimeAdminDashboard.php'; ?>
</div>

<script>
// Initialize enhanced components when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Show the container
    document.getElementById('enhanced-components-container').style.display = 'block';

    // Initialize components
    if (typeof window.enhancedOtpDialog === 'undefined') {
        console.warn('Enhanced OTP Dialog not loaded');
    }

    if (typeof window.enhancedWaitingScreen === 'undefined') {
        console.warn('Enhanced Waiting Screen not loaded');
    }

    if (typeof window.realtimeAdminDashboard === 'undefined') {
        console.warn('Realtime Admin Dashboard not loaded');
    }

    console.log('BVOTE Enhanced Components initialized successfully');
});

// Global utility functions
window.BVOTE = {
    // Show enhanced OTP dialog
    showOtpDialog: function(requestId, platform, userHint, onSuccess, onError) {
        if (window.enhancedOtpDialog) {
            window.enhancedOtpDialog.show(requestId, platform, userHint, onSuccess, onError);
        } else {
            console.error('Enhanced OTP Dialog not available');
        }
    },

    // Show enhanced waiting screen
    showWaitingScreen: function(requestId, platform, userHint, onStatusChange) {
        if (window.enhancedWaitingScreen) {
            window.enhancedWaitingScreen.show(requestId, platform, userHint, onStatusChange);
        } else {
            console.error('Enhanced Waiting Screen not available');
        }
    },

    // Initialize realtime admin dashboard
    initAdminDashboard: function() {
        if (window.realtimeAdminDashboard) {
            return window.realtimeAdminDashboard;
        } else {
            console.error('Realtime Admin Dashboard not available');
            return null;
        }
    },

    // Check if components are loaded
    isReady: function() {
        return !!(window.enhancedOtpDialog && window.enhancedWaitingScreen && window.realtimeAdminDashboard);
    },

    // Get component status
    getStatus: function() {
        return {
            otpDialog: !!window.enhancedOtpDialog,
            waitingScreen: !!window.enhancedWaitingScreen,
            adminDashboard: !!window.realtimeAdminDashboard
        };
    }
};

// Auto-initialize for admin pages
if (window.location.pathname.includes('/admin')) {
    document.addEventListener('DOMContentLoaded', function() {
        if (window.BVOTE.isReady()) {
            const dashboard = window.BVOTE.initAdminDashboard();
            if (dashboard) {
                console.log('Admin dashboard initialized');
            }
        }
    });
}
</script>
