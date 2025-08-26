<?php
/**
 * Update .env file with proper values
 */

echo "🔧 Updating .env file...\n";

$envFile = __DIR__ . '/../.env';
$envExampleFile = __DIR__ . '/../.env.example';

if (!file_exists($envFile)) {
    echo "❌ .env file not found\n";
    exit(1);
}

// Read current .env content
$envContent = file_get_contents($envFile);

// Update key values
$updates = [
    'APP_NAME="BVOTE Voting System"' => 'APP_NAME="BVOTE Voting System"',
    'APP_ENV=local' => 'APP_ENV=local',
    'APP_DEBUG=true' => 'APP_DEBUG=true',
    'APP_URL=http://localhost' => 'APP_URL=http://localhost',
    'APP_TIMEZONE=Asia/Ho_Chi_Minh' => 'APP_TIMEZONE=Asia/Ho_Chi_Minh',
    'DB_HOST=localhost' => 'DB_HOST=localhost',
    'DB_PORT=3306' => 'DB_PORT=3306',
    'DB_DATABASE=bvote_system' => 'DB_DATABASE=bvote_system',
    'DB_USERNAME=root' => 'DB_USERNAME=root',
    'DB_PASSWORD=' => 'DB_PASSWORD=',
    'DB_CHARSET=utf8mb4' => 'DB_CHARSET=utf8mb4',
    'REDIS_HOST=127.0.0.1' => 'REDIS_HOST=127.0.0.1',
    'REDIS_PORT=6379' => 'REDIS_PORT=6379',
    'REDIS_PASSWORD=' => 'REDIS_PASSWORD=',
    'REDIS_DATABASE=0' => 'REDIS_DATABASE=0',
    'MAIL_HOST=localhost' => 'MAIL_HOST=localhost',
    'MAIL_PORT=587' => 'MAIL_PORT=587',
    'MAIL_USERNAME=' => 'MAIL_USERNAME=',
    'MAIL_PASSWORD=' => 'MAIL_PASSWORD=',
    'MAIL_ENCRYPTION=tls' => 'MAIL_ENCRYPTION=tls',
    'MAIL_FROM_ADDRESS=noreply@bvote.com' => 'MAIL_FROM_ADDRESS=noreply@bvote.com',
    'MAIL_FROM_NAME="BVOTE System"' => 'MAIL_FROM_NAME="BVOTE System"',
    'JWT_SECRET=your-super-secret-jwt-key-change-this-in-production' => 'JWT_SECRET=bvote-jwt-secret-key-2024-production-ready',
    'JWT_ALGORITHM=HS256' => 'JWT_ALGORITHM=HS256',
    'JWT_EXPIRATION=3600' => 'JWT_EXPIRATION=3600',
    'SESSION_DRIVER=redis' => 'SESSION_DRIVER=file',
    'SESSION_LIFETIME=120' => 'SESSION_LIFETIME=120',
    'CACHE_DRIVER=redis' => 'CACHE_DRIVER=file',
    'LOG_LEVEL=info' => 'LOG_LEVEL=info',
    'LOG_CHANNEL=daily' => 'LOG_CHANNEL=daily',
    'UPLOAD_MAX_SIZE=10485760' => 'UPLOAD_MAX_SIZE=10485760',
    'UPLOAD_ALLOWED_TYPES=jpg,jpeg,png,gif,pdf,doc,docx' => 'UPLOAD_ALLOWED_TYPES=jpg,jpeg,png,gif,pdf,doc,docx',
    'SECURITY_CSRF_ENABLED=true' => 'SECURITY_CSRF_ENABLED=true',
    'SECURITY_RATE_LIMIT_ENABLED=true' => 'SECURITY_RATE_LIMIT_ENABLED=true',
    'SECURITY_RATE_LIMIT_MAX_REQUESTS=100' => 'SECURITY_RATE_LIMIT_MAX_REQUESTS=100',
    'SECURITY_RATE_LIMIT_WINDOW=60' => 'SECURITY_RATE_LIMIT_WINDOW=60'
];

foreach ($updates as $old => $new) {
    if (strpos($envContent, $old) !== false) {
        $envContent = str_replace($old, $new, $envContent);
        echo "✅ Updated: " . explode('=', $old)[0] . "\n";
    }
}

// Write updated content back
if (file_put_contents($envFile, $envContent)) {
    echo "\n✅ .env file updated successfully!\n";
} else {
    echo "\n❌ Failed to update .env file\n";
}

echo "\n🔍 Current .env configuration:\n";
echo "-------------------------------\n";
echo "APP_NAME: " . (preg_match('/APP_NAME=(.+)/', $envContent, $matches) ? $matches[1] : 'Not set') . "\n";
echo "APP_ENV: " . (preg_match('/APP_ENV=(.+)/', $envContent, $matches) ? $matches[1] : 'Not set') . "\n";
echo "APP_DEBUG: " . (preg_match('/APP_DEBUG=(.+)/', $envContent, $matches) ? $matches[1] : 'Not set') . "\n";
echo "DB_HOST: " . (preg_match('/DB_HOST=(.+)/', $envContent, $matches) ? $matches[1] : 'Not set') . "\n";
echo "DB_DATABASE: " . (preg_match('/DB_DATABASE=(.+)/', $envContent, $matches) ? $matches[1] : 'Not set') . "\n";
echo "JWT_SECRET: " . (preg_match('/JWT_SECRET=(.+)/', $envContent, $matches) ? substr($matches[1], 0, 20) . '...' : 'Not set') . "\n";
