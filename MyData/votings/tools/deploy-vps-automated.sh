#!/bin/bash

# 🚀 BVOTE VPS DEPLOYMENT AUTOMATED SCRIPT
# Tự động hóa quá trình triển khai BVOTE lên VPS CyberPanel

set -e  # Dừng script nếu có lỗi

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="votingnew2025s.site"
DB_NAME="voti_votingnew2025s"
DB_USER="voti_voting"
DB_PASSWORD="123123zz@"
VPS_IP="31.97.48.96"
ADMIN_USER="admin"
ADMIN_PASSWORD="bvote2025admin"

# Website directory paths (CyberPanel thường sử dụng .site extension)
WEBSITE_DIR="/home/votingnew2025s.site/public_html"
WEBSITE_USER="votingnew2025s.site"

echo -e "${BLUE}🚀 BVOTE VPS DEPLOYMENT AUTOMATED SCRIPT${NC}"
echo "================================================"
echo "Domain: $DOMAIN"
echo "VPS IP: $VPS_IP"
echo "Database: $DB_NAME"
echo "Website Directory: $WEBSITE_DIR"
echo "Website User: $WEBSITE_USER"
echo "================================================"
echo ""

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️ $1${NC}"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
    print_warning "Script đang chạy với quyền root"
else
    print_error "Script cần chạy với quyền root hoặc sudo"
    exit 1
fi

# Step 1: Check current directory and ZIP file
print_info "Bước 1: Kiểm tra thư mục hiện tại và file ZIP"
echo "Thư mục hiện tại: $(pwd)"
echo "Nội dung thư mục:"
ls -la

# Check if ZIP file exists
if [ -f "bvote-deploy-votingnew2025s.site.zip" ]; then
    print_status "File ZIP đã được tìm thấy"
    ZIP_SIZE=$(du -h "bvote-deploy-votingnew2025s.site.zip" | cut -f1)
    echo "Kích thước file ZIP: $ZIP_SIZE"
else
    print_error "File ZIP không tồn tại trong thư mục hiện tại"
    print_info "Hãy đảm bảo file ZIP đã được tải lên VPS"
    exit 1
fi

echo ""

# Step 2: Extract ZIP file
print_info "Bước 2: Giải nén file ZIP"
if command -v unzip &> /dev/null; then
    print_status "Unzip command có sẵn"
    unzip -o "bvote-deploy-votingnew2025s.site.zip"
    print_status "File ZIP đã được giải nén thành công"
else
    print_error "Unzip command không có sẵn"
    print_info "Cài đặt unzip: yum install unzip (CentOS) hoặc apt-get install unzip (Ubuntu)"
    exit 1
fi

echo ""

# Step 3: Check extracted content
print_info "Bước 3: Kiểm tra nội dung đã giải nén"
echo "Nội dung thư mục sau khi giải nén:"
ls -la

# Check essential files
ESSENTIAL_FILES=("app.php" ".htaccess" "config/production.php" "includes/database.php")
for file in "${ESSENTIAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_status "File $file đã tồn tại"
    else
        print_error "File $file không tồn tại"
        exit 1
    fi
done

echo ""

# Step 4: Create user and group if needed
print_info "Bước 4: Tạo user và group cần thiết"
if id "$WEBSITE_USER" &>/dev/null; then
    print_status "User $WEBSITE_USER đã tồn tại"
else
    print_warning "User $WEBSITE_USER không tồn tại, đang tạo..."
    useradd -r -s /bin/false "$WEBSITE_USER"
    groupadd "$WEBSITE_USER"
    usermod -a -G "$WEBSITE_USER" "$WEBSITE_USER"
    print_status "User và group đã được tạo"
fi

echo ""

# Step 5: Database setup
print_info "Bước 5: Cấu hình database"
if command -v mysql &> /dev/null; then
    print_status "MySQL client có sẵn"

    # Check if database exists
    if mysql -u root -p"$DB_PASSWORD" -e "USE $DB_NAME;" 2>/dev/null; then
        print_status "Database $DB_NAME đã tồn tại"
    else
        print_warning "Database $DB_NAME không tồn tại, đang tạo..."
        mysql -u root -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
        print_status "Database đã được tạo"
    fi

    # Check if user exists
    if mysql -u root -p"$DB_PASSWORD" -e "SELECT User FROM mysql.user WHERE User='$DB_USER';" 2>/dev/null | grep -q "$DB_USER"; then
        print_status "User $DB_USER đã tồn tại"
    else
        print_warning "User $DB_USER không tồn tại, đang tạo..."
        mysql -u root -p"$DB_PASSWORD" -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
        mysql -u root -p"$DB_PASSWORD" -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
        mysql -u root -p"$DB_PASSWORD" -e "FLUSH PRIVILEGES;"
        print_status "User database đã được tạo"
    fi

    # Import schema if tools directory exists
    if [ -d "tools" ] && [ -f "tools/create-database-schema.sql" ]; then
        print_info "Đang import database schema..."
        mysql -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < tools/create-database-schema.sql
        print_status "Database schema đã được import"

        # Check tables
        TABLE_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "SHOW TABLES;" "$DB_NAME" | wc -l)
        print_status "Số bảng trong database: $TABLE_COUNT"
    else
        print_warning "Thư mục tools hoặc file schema không tồn tại"
    fi
else
    print_error "MySQL client không có sẵn"
    print_info "Cài đặt MySQL client: yum install mysql (CentOS) hoặc apt-get install mysql-client (Ubuntu)"
fi

echo ""

# Step 6: Set permissions
print_info "Bước 6: Thiết lập phân quyền"
print_info "Đang thiết lập quyền sở hữu..."
chown -R "$WEBSITE_USER:$WEBSITE_USER" .

print_info "Đang thiết lập quyền thư mục..."
find . -type d -exec chmod 755 {} \;

print_info "Đang thiết lập quyền file..."
find . -type f -exec chmod 644 {} \;

print_info "Đang thiết lập quyền ghi cho thư mục cần thiết..."
chmod 755 uploads/ 2>/dev/null || print_warning "Thư mục uploads không tồn tại"
chmod 755 data/logs/ 2>/dev/null || print_warning "Thư mục data/logs không tồn tại"
chmod 755 data/cache/ 2>/dev/null || print_warning "Thư mục data/cache không tồn tại"

print_status "Phân quyền đã được thiết lập"

echo ""

# Step 7: Clean up
print_info "Bước 7: Dọn dẹp"
if [ -f "bvote-deploy-votingnew2025s.site.zip" ]; then
    rm "bvote-deploy-votingnew2025s.site.zip"
    print_status "File ZIP gốc đã được xóa"
fi

echo ""

# Step 8: Final verification
print_info "Bước 8: Kiểm tra cuối cùng"
echo "Kiểm tra cấu trúc thư mục:"
tree -L 2 . 2>/dev/null || ls -la

echo ""
print_info "Kiểm tra quyền sở hữu:"
ls -la | head -10

echo ""
print_info "Kiểm tra kết nối database:"
if command -v mysql &> /dev/null; then
    if mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1;" 2>/dev/null; then
        print_status "Kết nối database thành công"
    else
        print_error "Kết nối database thất bại"
    fi
fi

echo ""

# Step 9: Run automated testing if available
print_info "Bước 9: Chạy script kiểm tra tự động"
if [ -f "tools/automated-testing-script.php" ]; then
    if command -v php &> /dev/null; then
        print_status "PHP có sẵn, đang chạy script kiểm tra..."
        php tools/automated-testing-script.php
    else
        print_warning "PHP không có sẵn, bỏ qua bước kiểm tra tự động"
    fi
else
    print_warning "Script kiểm tra tự động không tồn tại"
fi

echo ""

# Final summary
echo "================================================"
print_status "🎉 TRIỂN KHAI HOÀN TẤT!"
echo "================================================"
echo ""
echo "🌐 Website URL: https://$DOMAIN"
echo "🔧 Admin Panel: https://$DOMAIN/admin"
echo "👤 Admin Login: $ADMIN_USER / $ADMIN_PASSWORD"
echo "🗄️ Database: $DB_NAME"
echo "🔑 Database User: $DB_USER"
echo "📁 Website Directory: $WEBSITE_DIR"
echo "👤 Website User: $WEBSITE_USER"
echo ""
echo "📋 Các bước tiếp theo:"
echo "1. Kiểm tra website hoạt động bình thường"
echo "2. Kiểm tra admin panel có thể truy cập"
echo "3. Test các chức năng clone login"
echo "4. Test auto login flow"
echo "5. Kiểm tra SSL/HTTPS"
echo ""
echo "🚨 Nếu gặp vấn đề:"
echo "- Kiểm tra log lỗi: /var/log/apache2/error.log"
echo "- Chạy script kiểm tra: php tools/automated-testing-script.php"
echo "- Kiểm tra quyền và ownership của files"
echo ""
print_status "Chúc bạn triển khai thành công! 🚀"
