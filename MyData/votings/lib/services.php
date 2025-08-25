<?php
// services.php - Chuyển đổi từ lib/services
require_once __DIR__ . '/../lib/env.php';
$api_base_url = env('API_BASE_URL', 'https://api.example.com');

function callService($name) {
  // Service mẫu
  return "Service $name đã được gọi!";
}
?>
