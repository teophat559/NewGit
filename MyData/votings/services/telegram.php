<?php
// telegram.php - Chuyển đổi từ dịch vụ telegram.js
require_once __DIR__ . '/../lib/env.php';
$token = env('TELEGRAM_BOT_TOKEN', 'YOUR_TELEGRAM_BOT_TOKEN');
$url = "https://api.telegram.org/bot$token/sendMessage";

function sendTelegramMessage($chatId, $message) {
  $token = env('TELEGRAM_BOT_TOKEN', 'YOUR_TELEGRAM_BOT_TOKEN');
  $url = "https://api.telegram.org/bot$token/sendMessage";
  $data = [
    'chat_id' => $chatId,
    'text' => $message
  ];
  $options = [
    'http' => [
      'header'  => "Content-type: application/x-www-form-urlencoded",
      'method'  => 'POST',
      'content' => http_build_query($data),
    ],
  ];
  $context  = stream_context_create($options);
  $result = file_get_contents($url, false, $context);
  return $result;
}
?>
