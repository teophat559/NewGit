#!/bin/bash

# 🔧 BVOTE 404 ERROR TROUBLESHOOTING SCRIPT
# Khắc phục lỗi 404 Not Found sau khi triển khai

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
DOMAIN="votingnew2025s.site"
WEBSITE_DIR="/home/votingnew2025s.site/public_html"
WEBSITE_USER="votingnew2025s.site"

echo -e "${BLUE}🔧 BVOTE 404 ERROR TROUBLESHOOTING SCRIPT${NC}"
echo "================================================"
echo "Domain: $DOMAIN"
echo "Website Directory: $WEBSITE_DIR"
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

echo "🔍 BẮT ĐẦU KIỂM TRA LỖI 404..."

echo ""
print_info "Bước 1: Kiểm tra thư mục website"
echo "======================================"

# Check if website directory exists
if [ -d "$WEBSITE_DIR" ]; then
    print_status "Thư mục website tồn tại: $WEBSITE_DIR"
else
    print_error "Thư mục website không tồn tại: $WEBSITE_DIR"
    print_info "Tạo thư mục website..."
    mkdir -p "$WEBSITE_DIR"
    print_status "Đã tạo thư mục website"
fi

# Check current working directory
echo "Thư mục hiện tại: $(pwd)"
echo "Nội dung thư mục hiện tại:"
ls -la

echo ""
print_info "Bước 2: Kiểm tra file cần thiết"
echo "====================================="

# Check essential files
ESSENTIAL_FILES=("app.php" ".htaccess" "index.php" "config/production.php")
for file in "${ESSENTIAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_status "File $file tồn tại"
    else
        print_error "File $file không tồn tại"
    fi
done

echo ""
print_info "Bước 3: Kiểm tra cấu hình web server"
echo "=========================================="

# Check if running Apache or Nginx
if command -v apache2 &> /dev/null || command -v httpd &> /dev/null; then
    print_status "Apache web server được phát hiện"
    WEB_SERVER="apache"
elif command -v nginx &> /dev/null; then
    print_status "Nginx web server được phát hiện"
    WEB_SERVER="nginx"
else
    print_error "Không tìm thấy web server (Apache/Nginx)"
    exit 1
fi

echo "Web server: $WEB_SERVER"

echo ""
print_info "Bước 4: Kiểm tra cấu hình Nginx (nếu có)"
echo "==============================================="

if [ "$WEB_SERVER" = "nginx" ]; then
    # Check Nginx configuration
    NGINX_CONF="/etc/nginx/sites-available/$DOMAIN"
    if [ -f "$NGINX_CONF" ]; then
        print_status "File cấu hình Nginx tồn tại: $NGINX_CONF"
        echo "Nội dung cấu hình Nginx:"
        cat "$NGINX_CONF"
    else
        print_warning "File cấu hình Nginx không tồn tại: $NGINX_CONF"
        
        # Check for other possible locations
        NGINX_CONF_ALT="/etc/nginx/conf.d/$DOMAIN.conf"
        if [ -f "$NGINX_CONF_ALT" ]; then
            print_status "File cấu hình Nginx thay thế: $NGINX_CONF_ALT"
            echo "Nội dung cấu hình Nginx:"
            cat "$NGINX_CONF_ALT"
        else
            print_error "Không tìm thấy file cấu hình Nginx cho domain $DOMAIN"
        fi
    fi
    
    # Check if Nginx is running
    if systemctl is-active --quiet nginx; then
        print_status "Nginx service đang chạy"
    else
        print_error "Nginx service không chạy"
        print_info "Khởi động Nginx..."
        systemctl start nginx
    fi
fi

echo ""
print_info "Bước 5: Kiểm tra cấu hình Apache (nếu có)"
echo "================================================"

if [ "$WEB_SERVER" = "apache" ]; then
    # Check Apache configuration
    APACHE_CONF="/etc/apache2/sites-available/$DOMAIN.conf"
    if [ -f "$APACHE_CONF" ]; then
        print_status "File cấu hình Apache tồn tại: $APACHE_CONF"
        echo "Nội dung cấu hình Apache:"
        cat "$APACHE_CONF"
    else
        print_warning "File cấu hình Apache không tồn tại: $APACHE_CONF"
    fi
    
    # Check if Apache is running
    if systemctl is-active --quiet apache2 || systemctl is-active --quiet httpd; then
        print_status "Apache service đang chạy"
    else
        print_error "Apache service không chạy"
        print_info "Khởi động Apache..."
        systemctl start apache2 2>/dev/null || systemctl start httpd
    fi
fi

echo ""
print_info "Bước 6: Kiểm tra file index và routing"
echo "==========================================="

# Check if index.php exists
if [ -f "index.php" ]; then
    print_status "File index.php tồn tại"
    echo "Nội dung index.php:"
    head -5 index.php
else
    print_warning "File index.php không tồn tại"
    
    # Check if app.php exists
    if [ -f "app.php" ]; then
        print_status "File app.php tồn tại, tạo index.php để redirect"
        cat > index.php << 'EOF'
<?php
// Redirect to app.php for routing
require_once 'app.php';
EOF
        print_status "Đã tạo index.php"
    else
        print_error "Không tìm thấy file app.php hoặc index.php"
    fi
fi

# Check .htaccess file
if [ -f ".htaccess" ]; then
    print_status "File .htaccess tồn tại"
    echo "Nội dung .htaccess:"
    cat .htaccess
else
    print_warning "File .htaccess không tồn tại"
    
    # Create .htaccess for Apache
    if [ "$WEB_SERVER" = "apache" ]; then
        print_info "Tạo file .htaccess cho Apache"
        cat > .htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ app.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
EOF
        print_status "Đã tạo .htaccess cho Apache"
    fi
fi

echo ""
print_info "Bước 7: Tạo cấu hình Nginx (nếu cần)"
echo "==========================================="

if [ "$WEB_SERVER" = "nginx" ]; then
    NGINX_CONF="/etc/nginx/sites-available/$DOMAIN"
    
    if [ ! -f "$NGINX_CONF" ]; then
        print_info "Tạo cấu hình Nginx cho domain $DOMAIN"
        
        # Create Nginx configuration
        cat > "$NGINX_CONF" << EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root $WEBSITE_DIR;
    index index.php index.html index.htm;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # Handle PHP files
    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
    }
    
    # Handle static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Main location block
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ \.(htaccess|htpasswd|ini|log|sh|sql|conf)$ {
        deny all;
    }
}
EOF
        
        print_status "Đã tạo cấu hình Nginx"
        
        # Enable site
        if [ -f "/etc/nginx/sites-enabled/$DOMAIN" ]; then
            rm "/etc/nginx/sites-enabled/$DOMAIN"
        fi
        ln -s "$NGINX_CONF" "/etc/nginx/sites-enabled/$DOMAIN"
        print_status "Đã enable site"
        
        # Test Nginx configuration
        if nginx -t; then
            print_status "Cấu hình Nginx hợp lệ"
            systemctl reload nginx
            print_status "Đã reload Nginx"
        else
            print_error "Cấu hình Nginx không hợp lệ"
        fi
    else
        print_status "File cấu hình Nginx đã tồn tại"
    fi
fi

echo ""
print_info "Bước 8: Kiểm tra phân quyền"
echo "================================="

# Check ownership
CURRENT_OWNER=$(stat -c '%U:%G' .)
echo "Quyền sở hữu hiện tại: $CURRENT_OWNER"

if [ "$CURRENT_OWNER" = "$WEBSITE_USER:$WEBSITE_USER" ]; then
    print_status "Quyền sở hữu đúng"
else
    print_warning "Quyền sở hữu không đúng, đang sửa..."
    chown -R "$WEBSITE_USER:$WEBSITE_USER" .
    print_status "Đã sửa quyền sở hữu"
fi

# Check permissions
print_info "Kiểm tra quyền thư mục..."
find . -type d -exec chmod 755 {} \;
print_info "Kiểm tra quyền file..."
find . -type f -exec chmod 644 {} \;

# Special permissions for writable directories
chmod 755 uploads/ 2>/dev/null || print_warning "Thư mục uploads không tồn tại"
chmod 755 data/logs/ 2>/dev/null || print_warning "Thư mục data/logs không tồn tại"
chmod 755 data/cache/ 2>/dev/null || print_warning "Thư mục data/cache không tồn tại"

print_status "Phân quyền đã được thiết lập"

echo ""
print_info "Bước 9: Kiểm tra kết nối database"
echo "======================================="

# Check database connection
if [ -f "config/production.php" ]; then
    print_status "File cấu hình database tồn tại"
    
    # Test database connection
    if command -v mysql &> /dev/null; then
        DB_USER=$(grep "DB_USER" config/production.php | cut -d"'" -f4)
        DB_PASS=$(grep "DB_PASSWORD" config/production.php | cut -d"'" -f4)
        DB_NAME=$(grep "DB_NAME" config/production.php | cut -d"'" -f4)
        
        if [ -n "$DB_USER" ] && [ -n "$DB_PASS" ] && [ -n "$DB_NAME" ]; then
            print_status "Thông tin database: $DB_USER@$DB_NAME"
            
            if mysql -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1;" 2>/dev/null; then
                print_status "Kết nối database thành công"
            else
                print_error "Kết nối database thất bại"
            fi
        else
            print_warning "Không thể đọc thông tin database từ config"
        fi
    else
        print_warning "MySQL client không có sẵn"
    fi
else
    print_error "File cấu hình database không tồn tại"
fi

echo ""
print_info "Bước 10: Kiểm tra cuối cùng"
echo "================================="

# Check if website is accessible
echo "Kiểm tra website có thể truy cập..."
if curl -s -o /dev/null -w "%{http_code}" "http://$DOMAIN" | grep -q "200\|301\|302"; then
    print_status "Website có thể truy cập (HTTP 200/301/302)"
else
    print_error "Website không thể truy cập"
    echo "Kiểm tra log lỗi..."
    
    if [ "$WEB_SERVER" = "nginx" ]; then
        echo "Nginx error log:"
        tail -10 /var/log/nginx/error.log 2>/dev/null || echo "Không thể đọc log Nginx"
    elif [ "$WEB_SERVER" = "apache" ]; then
        echo "Apache error log:"
        tail -10 /var/log/apache2/error.log 2>/dev/null || tail -10 /var/log/httpd/error_log 2>/dev/null || echo "Không thể đọc log Apache"
    fi
fi

echo ""
echo "================================================"
print_status "🎯 KHẮC PHỤC LỖI 404 HOÀN TẤT!"
echo "================================================"
echo ""
echo "📋 Các bước đã thực hiện:"
echo "1. ✅ Kiểm tra thư mục website"
echo "2. ✅ Kiểm tra file cần thiết"
echo "3. ✅ Kiểm tra cấu hình web server"
echo "4. ✅ Cấu hình Nginx (nếu cần)"
echo "5. ✅ Cấu hình Apache (nếu cần)"
echo "6. ✅ Tạo file index.php và .htaccess"
echo "7. ✅ Thiết lập phân quyền"
echo "8. ✅ Kiểm tra kết nối database"
echo "9. ✅ Kiểm tra website accessibility"
echo ""
echo "🌐 Website URL: http://$DOMAIN"
echo "🔧 Admin Panel: http://$DOMAIN/admin"
echo ""
echo "🚨 Nếu vẫn gặp lỗi:"
echo "- Kiểm tra DNS resolution: nslookup $DOMAIN"
echo "- Kiểm tra firewall: ufw status hoặc iptables -L"
echo "- Kiểm tra SELinux: getenforce (nếu có)"
echo "- Restart web server: systemctl restart nginx/apache2"
echo ""
print_status "Chúc bạn khắc phục thành công! 🚀"
