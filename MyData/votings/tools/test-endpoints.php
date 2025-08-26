<?php
/**
 * Test BVOTE Endpoints
 */

echo "🔍 Testing BVOTE Endpoints...\n";
echo "============================\n\n";

$baseUrl = 'http://localhost:8000';
$endpoints = [
    '/' => 'Home Page',
    '/health' => 'Health Check',
    '/vote' => 'Vote System'
];

foreach ($endpoints as $endpoint => $description) {
    echo "📋 Testing: $description ($endpoint)\n";
    echo "----------------------------------------\n";

    $url = $baseUrl . $endpoint;

    // Sử dụng file_get_contents thay vì curl
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: BVOTE-Test/1.0'
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo "❌ Failed to connect to $url\n";
        echo "   Error: " . error_get_last()['message'] . "\n";
    } else {
        echo "✅ Response received\n";
        echo "📏 Response length: " . strlen($response) . " characters\n";

        // Kiểm tra nội dung
        if (strpos($response, 'BVOTE') !== false) {
            echo "✅ Contains 'BVOTE' content\n";
        }

        if (strpos($response, 'error') !== false || strpos($response, 'Error') !== false) {
            echo "⚠️  Contains error content\n";
        }

        // Hiển thị một phần response
        $preview = substr($response, 0, 200);
        echo "📄 Preview: " . $preview . "...\n";
    }

    echo "\n";
}

echo "🎯 Endpoint testing completed!\n";
