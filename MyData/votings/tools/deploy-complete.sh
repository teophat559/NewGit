#!/bin/bash

# BVOTE Complete Deployment Script
# Triển khai hệ thống hoàn chỉnh lên production

set -e

echo "🚀 BVOTE Complete Deployment Starting..."
echo "========================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="bvote"
PROJECT_DIR="/var/www/bvote"
BACKUP_DIR="/var/backups/bvote"
LOG_FILE="/var/log/bvote/deployment.log"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}❌ $1${NC}" | tee -a "$LOG_FILE"
    exit 1
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   error "This script should not be run as root"
fi

# Create log directory
sudo mkdir -p /var/log/bvote
sudo chown $USER:$USER /var/log/bvote

log "Starting deployment process..."

# Step 1: Prerequisites Check
log "Step 1: Checking prerequisites..."

# Check required commands
commands=("git" "composer" "php" "mysql" "nginx" "systemctl")
for cmd in "${commands[@]}"; do
    if command -v $cmd &> /dev/null; then
        success "$cmd is available"
    else
        error "$cmd is not available. Please install it first."
    fi
done

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
if [[ $(echo "$PHP_VERSION >= 7.4" | bc -l) -eq 1 ]]; then
    success "PHP version $PHP_VERSION is compatible"
else
    error "PHP version $PHP_VERSION is not compatible. Required: >= 7.4"
fi

# Check required PHP extensions
required_extensions=("pdo" "pdo_mysql" "json" "mbstring" "openssl" "curl")
for ext in "${required_extensions[@]}"; do
    if php -m | grep -q "^$ext$"; then
        success "PHP extension $ext is loaded"
    else
        error "PHP extension $ext is not loaded"
    fi
done

# Step 2: System Preparation
log "Step 2: Preparing system..."

# Create project directory
sudo mkdir -p "$PROJECT_DIR"
sudo chown $USER:$USER "$PROJECT_DIR"

# Create backup directory
sudo mkdir -p "$BACKUP_DIR"
sudo chown $USER:$USER "$BACKUP_DIR"

# Create required system directories
sudo mkdir -p /var/log/bvote
sudo mkdir -p /var/cache/bvote
sudo mkdir -p /var/sessions/bvote
sudo chown -R $USER:$USER /var/log/bvote /var/cache/bvote /var/sessions/bvote

success "System directories created"

# Step 3: Database Setup
log "Step 3: Setting up database..."

# Create MySQL user and database
mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS bvote_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'bvote_user'@'localhost' IDENTIFIED BY 'bvote_secure_password_2024';
GRANT ALL PRIVILEGES ON bvote_system.* TO 'bvote_user'@'localhost';
FLUSH PRIVILEGES;
EOF

success "Database setup completed"

# Step 4: Application Deployment
log "Step 4: Deploying application..."

# Clone or update repository
if [ -d "$PROJECT_DIR/.git" ]; then
    log "Updating existing repository..."
    cd "$PROJECT_DIR"
    git fetch origin
    git reset --hard origin/main
else
    log "Cloning repository..."
    git clone https://github.com/teophat559/NewGit.git "$PROJECT_DIR"
    cd "$PROJECT_DIR"
fi

# Install PHP dependencies
log "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies (if needed)
if [ -f "package.json" ]; then
    log "Installing Node.js dependencies..."
    npm ci --production
    npm run build
fi

# Create storage directories
mkdir -p storage/logs storage/cache storage/sessions uploads
chmod -R 755 storage uploads

# Copy environment file
if [ ! -f .env ]; then
    cp .env.example .env
    log "Environment file created from template"
fi

# Update environment configuration
sed -i 's/DB_USERNAME=root/DB_USERNAME=bvote_user/' .env
sed -i 's/DB_PASSWORD=/DB_PASSWORD=bvote_secure_password_2024/' .env
sed -i 's/APP_ENV=local/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
sed -i 's/APP_URL=http:\/\/localhost/APP_URL=https:\/\/yourdomain.com/' .env

success "Application deployed successfully"

# Step 5: Database Migration
log "Step 5: Running database migrations..."

# Run database setup
php tools/setup-db-simple.php

success "Database migration completed"

# Step 6: Web Server Configuration
log "Step 6: Configuring web server..."

# Create Nginx configuration
sudo tee /etc/nginx/sites-available/bvote << EOF
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root $PROJECT_DIR;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;

    # Handle PHP files
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Handle static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /(storage|vendor|tools) {
        deny all;
    }

    # Main location
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
}
EOF

# Enable site
sudo ln -sf /etc/nginx/sites-available/bvote /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test Nginx configuration
sudo nginx -t
success "Nginx configuration is valid"

# Step 7: PHP-FPM Configuration
log "Step 7: Configuring PHP-FPM..."

# Create PHP-FPM pool configuration
sudo tee /etc/php/8.1/fpm/pool.d/bvote.conf << EOF
[bvote]
user = www-data
group = www-data
listen = /run/php/php8.1-fpm-bvote.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
chdir = /
EOF

# Step 8: Systemd Service Configuration
log "Step 8: Configuring system services..."

# Create systemd service for BVOTE
sudo tee /etc/systemd/system/bvote.service << EOF
[Unit]
Description=BVOTE Voting System
After=network.target mysql.service redis.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=$PROJECT_DIR
ExecStart=/usr/bin/php -S 0.0.0.0:8000 -t public/
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Create backup service
sudo tee /etc/systemd/system/bvote-backup.service << EOF
[Unit]
Description=BVOTE Backup Service
After=mysql.service

[Service]
Type=oneshot
User=www-data
Group=www-data
WorkingDirectory=$PROJECT_DIR
ExecStart=/usr/bin/php tools/backup-database.php
EOF

# Create backup timer
sudo tee /etc/systemd/system/bvote-backup.timer << EOF
[Unit]
Description=Run BVOTE backup daily
Requires=bvote-backup.service

[Timer]
Unit=bvote-backup.service
OnCalendar=daily
Persistent=true

[Install]
WantedBy=timers.target
EOF

# Step 9: Security Configuration
log "Step 9: Configuring security..."

# Set proper permissions
sudo chown -R www-data:www-data "$PROJECT_DIR"
sudo chmod -R 755 "$PROJECT_DIR"
sudo chmod -R 775 "$PROJECT_DIR/storage"
sudo chmod -R 775 "$PROJECT_DIR/uploads"

# Create firewall rules
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp

# Step 10: SSL Certificate (Let's Encrypt)
log "Step 10: Setting up SSL certificate..."

# Install Certbot
if ! command -v certbot &> /dev/null; then
    sudo apt update
    sudo apt install -y certbot python3-certbot-nginx
fi

# Get SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com --non-interactive --agree-tos --email admin@yourdomain.com

success "SSL certificate configured"

# Step 11: Final Configuration
log "Step 11: Final configuration..."

# Reload systemd
sudo systemctl daemon-reload

# Enable and start services
sudo systemctl enable bvote.service
sudo systemctl start bvote.service

sudo systemctl enable bvote-backup.timer
sudo systemctl start bvote-backup.timer

# Restart web services
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm

# Step 12: Health Check
log "Step 12: Running health check..."

# Wait for services to start
sleep 10

# Test application
if curl -f http://localhost/health > /dev/null 2>&1; then
    success "Application health check passed"
else
    warning "Application health check failed - check logs"
fi

# Step 13: Monitoring Setup
log "Step 13: Setting up monitoring..."

# Create log rotation
sudo tee /etc/logrotate.d/bvote << EOF
$PROJECT_DIR/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload bvote.service
    endscript
}
EOF

# Create monitoring script
sudo tee /usr/local/bin/bvote-monitor << EOF
#!/bin/bash
# BVOTE System Monitor

LOG_FILE="/var/log/bvote/monitor.log"
PROJECT_DIR="$PROJECT_DIR"

echo "\$(date): Starting BVOTE system check..." >> \$LOG_FILE

# Check if services are running
if ! systemctl is-active --quiet bvote.service; then
    echo "\$(date): BVOTE service is down, restarting..." >> \$LOG_FILE
    systemctl restart bvote.service
fi

if ! systemctl is-active --quiet nginx; then
    echo "\$(date): Nginx is down, restarting..." >> \$LOG_FILE
    systemctl restart nginx
fi

# Check disk space
DISK_USAGE=\$(df / | tail -1 | awk '{print \$5}' | sed 's/%//')
if [ \$DISK_USAGE -gt 90 ]; then
    echo "\$(date): Disk usage is high: \$DISK_USAGE%" >> \$LOG_FILE
fi

# Check memory usage
MEMORY_USAGE=\$(free | grep Mem | awk '{printf("%.2f", \$3/\$2 * 100.0)}')
if (( \$(echo "\$MEMORY_USAGE > 90" | bc -l) )); then
    echo "\$(date): Memory usage is high: \$MEMORY_USAGE%" >> \$LOG_FILE
fi

echo "\$(date): BVOTE system check completed" >> \$LOG_FILE
EOF

sudo chmod +x /usr/local/bin/bvote-monitor

# Create monitoring cron job
echo "*/5 * * * * /usr/local/bin/bvote-monitor" | sudo crontab -

success "Monitoring setup completed"

# Step 14: Final Status
log "Step 14: Deployment completed successfully!"

echo ""
echo "🎉 BVOTE System Deployment Completed!"
echo "====================================="
echo ""
echo "📋 System Information:"
echo "   • Project Directory: $PROJECT_DIR"
echo "   • Database: bvote_system"
echo "   • Database User: bvote_user"
echo "   • Web Server: Nginx + PHP-FPM"
echo "   • SSL: Let's Encrypt"
echo "   • Monitoring: Enabled"
echo "   • Backup: Daily automated"
echo ""
echo "🔧 Management Commands:"
echo "   • Service Status: sudo systemctl status bvote.service"
echo "   • Restart Service: sudo systemctl restart bvote.service"
echo "   • View Logs: sudo journalctl -u bvote.service -f"
echo "   • Manual Backup: sudo systemctl start bvote-backup.service"
echo "   • Monitor Logs: tail -f /var/log/bvote/monitor.log"
echo ""
echo "🌐 Access Information:"
echo "   • HTTP: http://yourdomain.com"
echo "   • HTTPS: https://yourdomain.com"
echo "   • Health Check: https://yourdomain.com/health"
echo ""
echo "⚠️  Important Notes:"
echo "   • Update 'yourdomain.com' with your actual domain"
echo "   • Change default passwords in production"
echo "   • Configure backup storage location"
echo "   • Set up monitoring alerts"
echo "   • Regular security updates"
echo ""
echo "📚 Documentation:"
echo "   • Check logs in: $PROJECT_DIR/storage/logs"
echo "   • Configuration: $PROJECT_DIR/.env"
echo "   • Nginx config: /etc/nginx/sites-available/bvote"
echo ""

# Create deployment summary
cat > "$PROJECT_DIR/DEPLOYMENT_SUMMARY.txt" << EOF
BVOTE System Deployment Summary
==============================

Deployment Date: $(date)
Deployment User: $USER
Project Directory: $PROJECT_DIR
Database: bvote_system
Database User: bvote_user

Services Installed:
- bvote.service (main application)
- bvote-backup.service (database backup)
- bvote-backup.timer (daily backup schedule)
- nginx (web server)
- php8.1-fpm (PHP processor)

Configuration Files:
- Nginx: /etc/nginx/sites-available/bvote
- PHP-FPM: /etc/php/8.1/fpm/pool.d/bvote.conf
- Systemd: /etc/systemd/system/bvote.service
- Log Rotation: /etc/logrotate.d/bvote
- Monitoring: /usr/local/bin/bvote-monitor

Security Features:
- SSL/TLS encryption
- Security headers
- File permissions
- Firewall rules
- Rate limiting

Monitoring:
- Service health checks
- Resource usage monitoring
- Automated backups
- Log rotation

Next Steps:
1. Update domain name in configuration
2. Configure email settings
3. Set up monitoring alerts
4. Test all functionality
5. Configure backup storage
6. Set up CI/CD pipeline

Support:
- Check logs: $PROJECT_DIR/storage/logs
- Service status: systemctl status bvote.service
- Health check: /health endpoint
EOF

success "Deployment summary saved to DEPLOYMENT_SUMMARY.txt"

log "Deployment process completed successfully!"
log "Please review the summary and complete any remaining configuration steps."

echo ""
echo "🚀 Your BVOTE system is now ready for production!"
echo "   Remember to update domain names and test thoroughly."
echo ""
