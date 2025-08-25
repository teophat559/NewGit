<?php
/**
 * BVOTE Deployment Script for CyberPanel
 * Script chuẩn bị code trước khi upload lên VPS
 */

echo "🚀 Chuẩn bị code BVOTE cho CyberPanel...\n\n";

// Thông tin thực tế từ user
$REAL_DOMAIN = 'votingnew2025s.site';
$REAL_IP = '31.97.48.96';
$REAL_DB_NAME = 'voti_votingnew2025s';
$REAL_DB_USER = 'voti_voting';
$REAL_DB_PASSWORD = '123123zz@';
$REAL_ADMIN_KEY = 'admin-key-2025-secure';
$REAL_JWT_SECRET = 'votingnew2025s-jwt-secret-2025';
$REAL_TELEGRAM_BOT_TOKEN = '7001751139:AAFCC83DPRn1larWNjd_ms9xvY9rl0KJlGE';
$REAL_TELEGRAM_CHAT_ID = '6936181519';

echo "🌐 Domain: $REAL_DOMAIN\n";
echo "🖥️  VPS IP: $REAL_IP\n";
echo "🗄️  Database: $REAL_DB_NAME\n";
echo "👤 DB User: $REAL_DB_USER\n";
echo "🔐 Admin Key: $REAL_ADMIN_KEY\n";
echo "🔑 JWT Secret: $REAL_JWT_SECRET\n";
echo "📱 Telegram Bot: $REAL_TELEGRAM_BOT_TOKEN\n";
echo "💬 Chat ID: $REAL_TELEGRAM_CHAT_ID\n\n";

// Tạo thư mục deployment
$deployDir = __DIR__ . '/../deploy';
if (!is_dir($deployDir)) {
    mkdir($deployDir, 0755, true);
    echo "✅ Đã tạo thư mục deploy/\n";
}

// Danh sách file và thư mục cần upload
$includePaths = [
    'admin/',
    'api/',
    'assets/',
    'backend/',
    'components/',
    'config/',
    'core/',
    'data/',
    'hooks/',
    'includes/',
    'lib/',
    'modules/',
    'pages/',
    'plugins/',
    'puppeteer/',
    'services/',
    'templates/',
    'tools/',
    'uploads/',
    'app.php',
    'database.php',
    'index.html',
    'router.php',
    'vite.config.php',
    'vote.php'
];

echo "📁 Đang copy files...\n";

foreach ($includePaths as $path) {
    $sourcePath = __DIR__ . '/../' . $path;
    $destPath = $deployDir . '/' . $path;

    if (file_exists($sourcePath)) {
        if (is_dir($sourcePath)) {
            if (!is_dir($destPath)) {
                mkdir($destPath, 0755, true);
            }
            copyDirectory($sourcePath, $destPath);
        } else {
            copy($sourcePath, $destPath);
        }
        echo "✅ $path\n";
    } else {
        echo "⚠️  $path (không tìm thấy)\n";
    }
}

// Tạo file .htaccess cho CyberPanel
$htaccessContent = "RewriteEngine On\n\n";
$htaccessContent .= "# Bảo mật\n";
$htaccessContent .= "<Files \"*.php\">\n";
$htaccessContent .= "    Order Allow,Deny\n";
$htaccessContent .= "    Allow from all\n";
$htaccessContent .= "</Files>\n\n";
$htaccessContent .= "# Bảo vệ file config\n";
$htaccessContent .= "<Files \"config.php\">\n";
$htaccessContent .= "    Order Deny,Allow\n";
$htaccessContent .= "    Deny from all\n";
$htaccessContent .= "</Files>\n\n";
$htaccessContent .= "# Bảo vệ thư mục tools\n";
$htaccessContent .= "<Directory \"tools\">\n";
$htaccessContent .= "    Order Deny,Allow\n";
$htaccessContent .= "    Deny from all\n";
$htaccessContent .= "</Directory>\n\n";
$htaccessContent .= "# Bảo vệ thư mục data\n";
$htaccessContent .= "<Directory \"data\">\n";
$htaccessContent .= "    Order Deny,Allow\n";
$htaccessContent .= "    Deny from all\n";
$htaccessContent .= "</Directory>\n\n";
$htaccessContent .= "# Redirect tất cả request về app.php\n";
$htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccessContent .= "RewriteRule ^(.*)$ app.php [QSA,L]\n";

file_put_contents($deployDir . '/.htaccess', $htaccessContent);
echo "✅ Đã tạo .htaccess\n";

// Tạo file config production với thông tin thực tế
$productionConfig = "<?php\n";
$productionConfig .= "/**\n";
$productionConfig .= " * BVOTE Production Configuration for CyberPanel\n";
$productionConfig .= " * Domain: $REAL_DOMAIN\n";
$productionConfig .= " * VPS IP: $REAL_IP\n";
$productionConfig .= " */\n\n";
$productionConfig .= "// Database configuration\n";
$productionConfig .= "define('BVOTE_DB_HOST', 'localhost');\n";
$productionConfig .= "define('BVOTE_DB_NAME', '$REAL_DB_NAME');\n";
$productionConfig .= "define('BVOTE_DB_USER', '$REAL_DB_USER');\n";
$productionConfig .= "define('BVOTE_DB_PASS', '$REAL_DB_PASSWORD');\n";
$productionConfig .= "define('BVOTE_DB_CHARSET', 'utf8mb4');\n\n";
$productionConfig .= "// System configuration\n";
$productionConfig .= "define('BVOTE_ENV', 'production');\n";
$productionConfig .= "define('BVOTE_DEBUG', false);\n";
$productionConfig .= "define('BVOTE_URL', 'https://$REAL_DOMAIN');\n";
$productionConfig .= "define('BVOTE_ADMIN_KEY', '$REAL_ADMIN_KEY');\n";
$productionConfig .= "define('BVOTE_JWT_SECRET', '$REAL_JWT_SECRET');\n\n";
$productionConfig .= "// Security configuration\n";
$productionConfig .= "define('BVOTE_SESSION_SECURE', true);\n";
$productionConfig .= "define('BVOTE_SESSION_HTTPONLY', true);\n";
$productionConfig .= "define('BVOTE_SESSION_SAMESITE', 'Strict');\n\n";
$productionConfig .= "// Upload configuration\n";
$productionConfig .= "define('BVOTE_UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB\n";
$productionConfig .= "define('BVOTE_ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);\n\n";
$productionConfig .= "// Rate limiting\n";
$productionConfig .= "define('BVOTE_RATE_LIMIT_ENABLED', true);\n";
$productionConfig .= "define('BVOTE_RATE_LIMIT_REQUESTS', 100);\n";
$productionConfig .= "define('BVOTE_RATE_LIMIT_WINDOW', 3600); // 1 hour\n\n";
$productionConfig .= "// OTP configuration\n";
$productionConfig .= "define('BVOTE_OTP_LENGTH', 6);\n";
$productionConfig .= "define('BVOTE_OTP_RETRIES', 3);\n";
$productionConfig .= "define('BVOTE_OTP_TTL', 300); // 5 minutes\n\n";
$productionConfig .= "// Logging\n";
$productionConfig .= "define('BVOTE_LOG_ENABLED', true);\n";
$productionConfig .= "define('BVOTE_LOG_LEVEL', 'ERROR');\n";
$productionConfig .= "define('BVOTE_LOG_PATH', __DIR__ . '/data/logs');\n\n";
$productionConfig .= "// Telegram bot configuration\n";
$productionConfig .= "define('BVOTE_TELEGRAM_BOT_TOKEN', '$REAL_TELEGRAM_BOT_TOKEN');\n";
$productionConfig .= "define('BVOTE_TELEGRAM_CHAT_ID', '$REAL_TELEGRAM_CHAT_ID');\n";
$productionConfig .= "?>\n";

file_put_contents($deployDir . '/config/production.php', $productionConfig);
echo "✅ Đã tạo config production với thông tin thực tế\n";

// Tạo file database production với thông tin thực tế
$productionDatabase = "<?php\n";
$productionDatabase .= "/**\n";
$productionDatabase .= " * BVOTE Production Database Connection\n";
$productionDatabase .= " * Database: $REAL_DB_NAME\n";
$productionDatabase .= " */\n\n";
$productionDatabase .= "function getConnection() {\n";
$productionDatabase .= "    static \$pdo = null;\n\n";
$productionDatabase .= "    if (\$pdo === null) {\n";
$productionDatabase .= "        try {\n";
$productionDatabase .= "            \$host = BVOTE_DB_HOST;\n";
$productionDatabase .= "            \$dbname = BVOTE_DB_NAME;\n";
$productionDatabase .= "            \$username = BVOTE_DB_USER;\n";
$productionDatabase .= "            \$password = BVOTE_DB_PASS;\n";
$productionDatabase .= "            \$charset = BVOTE_DB_CHARSET;\n\n";
$productionDatabase .= "            \$dsn = \"mysql:host=\$host;dbname=\$dbname;charset=\$charset\";\n\n";
$productionDatabase .= "            \$options = [\n";
$productionDatabase .= "                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
$productionDatabase .= "                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
$productionDatabase .= "                PDO::ATTR_EMULATE_PREPARES => false,\n";
$productionDatabase .= "                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES \$charset COLLATE {\$charset}_unicode_ci\"\n";
$productionDatabase .= "            ];\n\n";
$productionDatabase .= "            \$pdo = new PDO(\$dsn, \$username, \$password, \$options);\n\n";
$productionDatabase .= "        } catch (PDOException \$e) {\n";
$productionDatabase .= "            error_log(\"BVOTE Database Error: \" . \$e->getMessage());\n";
$productionConfig .= "            die(\"Database connection error. Please try again later.\");\n";
$productionDatabase .= "        }\n";
$productionDatabase .= "    }\n\n";
$productionDatabase .= "    return \$pdo;\n";
$productionDatabase .= "}\n";
$productionDatabase .= "?>\n";

file_put_contents($deployDir . '/includes/database.php', $productionDatabase);
echo "✅ Đã tạo database production với thông tin thực tế\n";

// Tạo file .env với thông tin thực tế
$envContent = "# BVOTE Environment Configuration - PRODUCTION\n";
$envContent .= "# Domain: $REAL_DOMAIN\n";
$envContent .= "# VPS IP: $REAL_IP\n\n";
$envContent .= "# Database\n";
$envContent .= "DB_HOST=localhost\n";
$envContent .= "DB_NAME=$REAL_DB_NAME\n";
$envContent .= "DB_USER=$REAL_DB_USER\n";
$envContent .= "DB_PASS=$REAL_DB_PASSWORD\n\n";
$envContent .= "# System\n";
$envContent .= "ENV=production\n";
$envContent .= "DEBUG=false\n";
$envContent .= "URL=https://$REAL_DOMAIN\n";
$envContent .= "ADMIN_KEY=$REAL_ADMIN_KEY\n";
$envContent .= "JWT_SECRET=$REAL_JWT_SECRET\n\n";
$envContent .= "# Security\n";
$envContent .= "SESSION_SECURE=true\n";
$envContent .= "SESSION_HTTPONLY=true\n";
$envContent .= "SESSION_SAMESITE=Strict\n\n";
$envContent .= "# Upload\n";
$envContent .= "UPLOAD_MAX_SIZE=10485760\n";
$envContent .= "ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,webp\n\n";
$envContent .= "# Rate Limiting\n";
$envContent .= "RATE_LIMIT_ENABLED=true\n";
$envContent .= "RATE_LIMIT_REQUESTS=100\n";
$envContent .= "RATE_LIMIT_WINDOW=3600\n\n";
$envContent .= "# OTP\n";
$envContent .= "OTP_LENGTH=6\n";
$envContent .= "OTP_RETRIES=3\n";
$envContent .= "OTP_TTL=300\n\n";
$envContent .= "# Logging\n";
$envContent .= "LOG_ENABLED=true\n";
$envContent .= "LOG_LEVEL=ERROR\n";
$envContent .= "LOG_PATH=/path/to/logs\n\n";
$envContent .= "# Telegram\n";
$envContent .= "TELEGRAM_BOT_TOKEN=$REAL_TELEGRAM_BOT_TOKEN\n";
$envContent .= "TELEGRAM_CHAT_ID=$REAL_TELEGRAM_CHAT_ID\n";

file_put_contents($deployDir . '/.env', $envContent);
echo "✅ Đã tạo .env với thông tin thực tế\n";

// Tạo file deployment guide với thông tin thực tế
$deploymentGuide = "# 🚀 Hướng dẫn Deploy BVOTE lên CyberPanel\n\n";
$deploymentGuide .= "## 📋 Thông tin thực tế\n\n";
$deploymentGuide .= "- **Domain**: $REAL_DOMAIN\n";
$deploymentGuide .= "- **VPS IP**: $REAL_IP\n";
$deploymentGuide .= "- **Database**: $REAL_DB_NAME\n";
$deploymentGuide .= "- **DB User**: $REAL_DB_USER\n";
$deploymentGuide .= "- **Admin Key**: $REAL_ADMIN_KEY\n";
$deploymentGuide .= "- **JWT Secret**: $REAL_JWT_SECRET\n";
$deploymentGuide .= "- **Telegram Bot**: $REAL_TELEGRAM_BOT_TOKEN\n";
$deploymentGuide .= "- **Chat ID**: $REAL_TELEGRAM_CHAT_ID\n\n";
$deploymentGuide .= "## 🔧 Cài đặt nhanh\n\n";
$deploymentGuide .= "### 1. Đăng nhập CyberPanel\n";
$deploymentGuide .= "- **URL**: https://$REAL_IP:8090\n";
$deploymentGuide .= "- **Username**: admin\n";
$deploymentGuide .= "- **Password**: 123123zz#Bong\n\n";
$deploymentGuide .= "### 2. Tạo Website\n";
$deploymentGuide .= "- Vào \"Websites\" → \"Create Website\"\n";
$deploymentGuide .= "- **Domain**: $REAL_DOMAIN\n";
$deploymentGuide .= "- **PHP Version**: 8.1+ (khuyến nghị)\n";
$deploymentGuide .= "- **SSL**: Let's Encrypt (miễn phí)\n\n";
$deploymentGuide .= "### 3. Tạo Database\n";
$deploymentGuide .= "- Vào \"Databases\" → \"Create Database\"\n";
$deploymentGuide .= "- **Database Name**: $REAL_DB_NAME\n";
$deploymentGuide .= "- **Username**: $REAL_DB_USER\n";
$deploymentGuide .= "- **Password**: $REAL_DB_PASSWORD\n\n";
$deploymentGuide .= "### 4. Upload Code\n";
$deploymentGuide .= "- Upload tất cả files từ thư mục deploy/\n";
$deploymentGuide .= "- Giải nén vào thư mục gốc website\n\n";
$deploymentGuide .= "### 5. Import Database Schema\n";
$deploymentGuide .= "```bash\n";
$deploymentGuide .= "mysql -u $REAL_DB_USER -p $REAL_DB_NAME < tools/create-database-schema.sql\n";
$deploymentGuide .= "```\n\n";
$deploymentGuide .= "### 6. Cập nhật quyền\n";
$deploymentGuide .= "```bash\n";
$deploymentGuide .= "chmod 755 uploads/ data/\n";
$deploymentGuide .= "chmod 644 .htaccess\n";
$deploymentGuide .= "chmod 600 config/production.php\n";
$deploymentGuide .= "```\n\n";
$deploymentGuide .= "## 🌐 Truy cập\n\n";
$deploymentGuide .= "- **Website**: https://$REAL_DOMAIN\n";
$deploymentGuide .= "- **Admin**: https://$REAL_DOMAIN/admin/login\n";
$deploymentGuide .= "- **User Login**: https://$REAL_DOMAIN/user/login\n\n";
$deploymentGuide .= "## 🔐 Thông tin đăng nhập\n\n";
$deploymentGuide .= "- **Admin Panel**: admin/admin123\n";
$deploymentGuide .= "- **CyberPanel**: admin/123123zz#Bong\n";
$deploymentGuide .= "- **Database**: $REAL_DB_USER/$REAL_DB_PASSWORD\n\n";
$deploymentGuide .= "## 🚨 Lưu ý bảo mật\n\n";
$deploymentGuide .= "1. **Đổi mật khẩu admin** ngay sau khi cài đặt\n";
$deploymentGuide .= "2. **Bảo vệ file config** và database\n";
$deploymentGuide .= "3. **Kích hoạt SSL/HTTPS**\n";
$deploymentGuide .= "4. **Backup database** định kỳ\n\n";
$deploymentGuide .= "## 📞 Hỗ trợ\n\n";
$deploymentGuide .= "- **Error logs**: /home/username/public_html/data/logs/\n";
$deploymentGuide .= "- **CyberPanel logs**: /usr/local/CyberCP/logs/\n";
$deploymentGuide .= "- **PHP logs**: /var/log/php-fpm/\n\n";
$deploymentGuide .= "**🎉 Hệ thống BVOTE đã sẵn sàng trên $REAL_DOMAIN!** 🚀\n";

file_put_contents($deployDir . '/DEPLOYMENT-GUIDE.md', $deploymentGuide);
echo "✅ Đã tạo hướng dẫn deployment với thông tin thực tế\n";

// Tạo file README.md cho deployment
$readmeDeploy = "# 🚀 BVOTE System - Production Ready\n\n";
$readmeDeploy .= "## 📋 Thông tin cài đặt\n\n";
$readmeDeploy .= "- **Domain**: $REAL_DOMAIN\n";
$readmeDeploy .= "- **VPS IP**: $REAL_IP\n";
$readmeDeploy .= "- **Database**: $REAL_DB_NAME\n";
$readmeDeploy .= "- **DB User**: $REAL_DB_USER\n";
$readmeDeploy .= "- **Admin Key**: $REAL_ADMIN_KEY\n";
$readmeDeploy .= "- **JWT Secret**: $REAL_JWT_SECRET\n";
$readmeDeploy .= "- **Telegram Bot**: $REAL_TELEGRAM_BOT_TOKEN\n";
$readmeDeploy .= "- **Chat ID**: $REAL_TELEGRAM_CHAT_ID\n\n";
$readmeDeploy .= "## 🔧 Cài đặt nhanh\n\n";
$readmeDeploy .= "### 1. Upload code\n";
$readmeDeploy .= "Upload tất cả files từ thư mục deploy/ lên VPS\n\n";
$readmeDeploy .= "### 2. Tạo database\n";
$readmeDeploy .= "```sql\n";
$readmeDeploy .= "CREATE DATABASE $REAL_DB_NAME;\n";
$readmeDeploy .= "CREATE USER '$REAL_DB_USER'@'localhost' IDENTIFIED BY '$REAL_DB_PASSWORD';\n";
$readmeDeploy .= "GRANT ALL PRIVILEGES ON $REAL_DB_NAME.* TO '$REAL_DB_USER'@'localhost';\n";
$readmeDeploy .= "FLUSH PRIVILEGES;\n";
$readmeDeploy .= "```\n\n";
$readmeDeploy .= "### 3. Import schema\n";
$readmeDeploy .= "```bash\n";
$readmeDeploy .= "mysql -u $REAL_DB_USER -p $REAL_DB_NAME < tools/create-database-schema.sql\n";
$readmeDeploy .= "```\n\n";
$readmeDeploy .= "### 4. Cấu hình\n";
$readmeDeploy .= "- File .env đã được cấu hình sẵn\n";
$readmeDeploy .= "- File config/production.php đã sẵn sàng\n\n";
$readmeDeploy .= "### 5. Quyền thư mục\n";
$readmeDeploy .= "```bash\n";
$readmeDeploy .= "chmod 755 uploads/\n";
$readmeDeploy .= "chmod 755 data/\n";
$readmeDeploy .= "chmod 644 .htaccess\n";
$readmeDeploy .= "```\n\n";
$readmeDeploy .= "## 🌐 Truy cập\n\n";
$readmeDeploy .= "- **Website**: https://$REAL_DOMAIN\n";
$readmeDeploy .= "- **Admin**: https://$REAL_DOMAIN/admin/login\n";
$readmeDeploy .= "- **User Login**: https://$REAL_DOMAIN/user/login\n\n";
$readmeDeploy .= "## 🔐 Thông tin đăng nhập mặc định\n\n";
$readmeDeploy .= "- **Username**: admin\n";
$readmeDeploy .= "- **Password**: admin123\n\n";
$readmeDeploy .= "**⚠️ QUAN TRỌNG**: Đổi mật khẩu admin ngay sau khi cài đặt!\n\n";
$readmeDeploy .= "## 📞 Hỗ trợ\n\n";
$readmeDeploy .= "Xem file `DEPLOYMENT-GUIDE.md` để biết thêm chi tiết về deployment và xử lý sự cố.\n\n";
$readmeDeploy .= "---\n\n";
$readmeDeploy .= "**BVOTE System v2.0** - Production Ready cho $REAL_DOMAIN 🚀\n";

file_put_contents($deployDir . '/README.md', $readmeDeploy);
echo "✅ Đã tạo README.md với thông tin thực tế\n";

// Tạo file composer.json
$composerJson = "{\n";
$composerJson .= "    \"name\": \"bvote/system\",\n";
$composerJson .= "    \"description\": \"BVOTE - Hệ thống bình chọn trực tuyến với Auto Login\",\n";
$composerJson .= "    \"type\": \"project\",\n";
$composerJson .= "    \"require\": {\n";
$composerJson .= "        \"php\": \">=7.4\",\n";
$composerJson .= "        \"ext-pdo\": \"*\",\n";
$composerJson .= "        \"ext-json\": \"*\",\n";
$composerJson .= "        \"ext-mbstring\": \"*\"\n";
$composerJson .= "    },\n";
$composerJson .= "    \"autoload\": {\n";
$composerJson .= "        \"psr-4\": {\n";
$composerJson .= "            \"BVote\\\\\": \"core/\"\n";
$composerJson .= "        }\n";
$composerJson .= "    },\n";
$composerJson .= "    \"scripts\": {\n";
$composerJson .= "        \"post-install-cmd\": [\n";
$composerJson .= "            \"chmod 755 uploads/\",\n";
$composerJson .= "            \"chmod 755 data/\",\n";
$composerJson .= "            \"chmod 644 .htaccess\"\n";
$composerJson .= "        ]\n";
$composerJson .= "    },\n";
$composerJson .= "    \"config\": {\n";
$composerJson .= "        \"optimize-autoloader\": true,\n";
$composerJson .= "        \"sort-packages\": true\n";
$composerJson .= "    }\n";
$composerJson .= "}\n";

file_put_contents($deployDir . '/composer.json', $composerJson);
echo "✅ Đã tạo composer.json\n";

// Tạo file zip để dễ upload
echo "\n📦 Đang tạo file zip...\n";
$zipFile = __DIR__ . '/../bvote-deploy-' . $REAL_DOMAIN . '.zip';

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($deployDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($deployDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        echo "✅ Đã tạo file zip: bvote-deploy-$REAL_DOMAIN.zip\n";
    } else {
        echo "❌ Không thể tạo file zip\n";
    }
} else {
    echo "⚠️  ZipArchive không có sẵn, sử dụng thư mục deploy/\n";
}

echo "\n🎉 Hoàn tất chuẩn bị deployment cho $REAL_DOMAIN!\n";
echo "📁 Thư mục deploy/ đã sẵn sàng\n";
echo "📖 Xem DEPLOYMENT-GUIDE.md để biết chi tiết\n";
echo "📦 File zip: $zipFile (nếu có)\n\n";

echo "🚀 Bước tiếp theo:\n";
echo "   1. Upload code lên VPS CyberPanel tại $REAL_IP\n";
echo "   2. Tạo database $REAL_DB_NAME\n";
echo "   3. Import schema\n";
echo "   4. Cấu hình production\n";
echo "   5. Test hệ thống tại https://$REAL_DOMAIN\n\n";

echo "🔐 Thông tin đăng nhập:\n";
echo "   - CyberPanel: https://$REAL_IP:8090 (admin/123123zz#Bong)\n";
echo "   - Admin BVOTE: admin/admin123\n";
echo "   - Database: $REAL_DB_USER/$REAL_DB_PASSWORD\n";

function copyDirectory($source, $dest) {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
        } else {
            copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
        }
    }
}
?>
