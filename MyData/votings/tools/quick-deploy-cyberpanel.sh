#!/bin/bash

# 🚀 BVOTE Quick Deploy Script for CyberPanel
# Script này giúp deploy nhanh hệ thống BVOTE lên VPS CyberPanel

echo "🚀 BVOTE Quick Deploy Script for CyberPanel"
echo "=============================================="
echo ""

# Kiểm tra tham số
if [ $# -eq 0 ]; then
    echo "❌ Sử dụng: $0 <domain> [database_password]"
    echo "   Ví dụ: $0 example.com mypassword123"
    exit 1
fi

DOMAIN=$1
DB_PASSWORD=${2:-"bvote_secure_pass_$(date +%s)"}

echo "🌐 Domain: $DOMAIN"
echo "🔐 Database Password: $DB_PASSWORD"
echo ""

# Tạo thư mục tạm cho deployment
TEMP_DIR="/tmp/bvote_deploy_$(date +%s)"
echo "📁 Tạo thư mục tạm: $TEMP_DIR"
mkdir -p $TEMP_DIR

# Copy files từ thư mục deploy
echo "📋 Copy files..."
cp -r deploy/* $TEMP_DIR/

# Cập nhật config với domain thực tế
echo "⚙️  Cập nhật config..."
sed -i "s/yourdomain.com/$DOMAIN/g" $TEMP_DIR/config/production.php
sed -i "s/YOUR_SECURE_PASSWORD_HERE/$DB_PASSWORD/g" $TEMP_DIR/config/production.php

# Tạo script SQL để tạo database
echo "🗄️  Tạo script database..."
cat > $TEMP_DIR/create_database.sql << EOF
-- Tạo database cho BVOTE
CREATE DATABASE IF NOT EXISTS \`bvote_system\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tạo user database
CREATE USER IF NOT EXISTS 'bvote_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON bvote_system.* TO 'bvote_user'@'localhost';
FLUSH PRIVILEGES;

-- Sử dụng database
USE bvote_system;
EOF

# Tạo script cài đặt nhanh
echo "🔧 Tạo script cài đặt..."
cat > $TEMP_DIR/install.sh << 'EOF'
#!/bin/bash

echo "🚀 Cài đặt nhanh BVOTE System..."

# Kiểm tra PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP không được cài đặt"
    exit 1
fi

# Kiểm tra MySQL
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL không được cài đặt"
    exit 1
fi

# Tạo database
echo "🗄️  Tạo database..."
mysql -u root -p < create_database.sql

# Import schema
echo "📋 Import database schema..."
mysql -u bvote_user -p bvote_system < tools/create-database-schema.sql

# Cập nhật quyền thư mục
echo "🔒 Cập nhật quyền thư mục..."
chmod 755 uploads/
chmod 755 data/
chmod 644 .htaccess

# Tạo thư mục logs
mkdir -p data/logs
chmod 755 data/logs

echo "✅ Cài đặt hoàn tất!"
echo "🌐 Truy cập: https://$(hostname)"
echo "🔐 Admin: admin/admin123"
echo ""
echo "⚠️  QUAN TRỌNG: Đổi mật khẩu admin ngay!"
EOF

chmod +x $TEMP_DIR/install.sh

# Tạo file hướng dẫn nhanh
echo "📖 Tạo hướng dẫn nhanh..."
cat > $TEMP_DIR/QUICK-START.md << EOF
# 🚀 BVOTE Quick Start Guide

## 📋 Thông tin cài đặt

- **Domain**: $DOMAIN
- **Database**: bvote_system
- **User**: bvote_user
- **Password**: $DB_PASSWORD

## 🔧 Cài đặt nhanh

### 1. Upload code
Upload tất cả files từ thư mục này lên VPS CyberPanel

### 2. Chạy script cài đặt
\`\`\`bash
chmod +x install.sh
./install.sh
\`\`\`

### 3. Truy cập
- Website: https://$DOMAIN
- Admin: https://$DOMAIN/admin/login
- User: https://$DOMAIN/user/login

## 🔐 Đăng nhập mặc định
- **Username**: admin
- **Password**: admin123

## 🚨 Lưu ý bảo mật
1. Đổi mật khẩu admin ngay
2. Cấu hình SSL/HTTPS
3. Backup database định kỳ

---
**BVOTE System v2.0** - Production Ready 🚀
EOF

# Tạo file zip cuối cùng
echo "📦 Tạo file zip deployment..."
cd $TEMP_DIR
zip -r bvote-deploy-$DOMAIN.zip . > /dev/null 2>&1

# Hiển thị thông tin
echo ""
echo "🎉 Deployment package đã sẵn sàng!"
echo "📁 Thư mục: $TEMP_DIR"
echo "📦 File zip: $TEMP_DIR/bvote-deploy-$DOMAIN.zip"
echo ""
echo "🚀 Bước tiếp theo:"
echo "   1. Upload file zip lên VPS CyberPanel"
echo "   2. Giải nén vào thư mục website"
echo "   3. Chạy: chmod +x install.sh && ./install.sh"
echo "   4. Truy cập: https://$DOMAIN"
echo ""
echo "📖 Xem QUICK-START.md để biết chi tiết"
echo "🔧 Xem DEPLOYMENT-GUIDE.md để biết hướng dẫn đầy đủ"
echo ""
echo "⚠️  Lưu ý: Đổi mật khẩu admin sau khi cài đặt!"
