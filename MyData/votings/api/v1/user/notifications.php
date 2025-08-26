<?php
/**
 * BVOTE API Endpoint: user/notifications
 */

header('Content-Type: application/json');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Basic endpoint response
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'endpoint' => 'user/notifications',
    'message' => 'Endpoint operational',
    'timestamp' => date('Y-m-d H:i:s')
]);
