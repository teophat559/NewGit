<?php
session_start();
header('Content-Type: application/json');

$response = [
    'logged_in' => false,
    'user' => null
];

if (isset($_SESSION['user_id'])) {
    $response['logged_in'] = true;
    $response['user'] = [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user'
    ];
}

echo json_encode($response);
?>
