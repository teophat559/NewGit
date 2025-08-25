<?php
// api.php - Chuyển đổi từ backend/src/routes/api.js
// Xử lý các API endpoint
header('Content-Type: application/json');
echo json_encode(['message' => 'API endpoint PHP']);
