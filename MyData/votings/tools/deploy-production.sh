#!/bin/bash

# 🚀 BVOTE Production Deployment Script
# Triển khai hệ thống lên production environment

set -e  # Dừng script nếu có lỗi

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="bvote-voting-system"
DEPLOYMENT_ENV="production"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups"
DEPLOY_DIR="deployments"

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

# Function to check prerequisites
check_prerequisites() {
    print_info "Checking prerequisites..."

    # Check if running as root or with sudo
    if [[ $EUID -eq 0 ]]; then
        print_warning "Script đang chạy với quyền root"
    else
        print_error "Script cần chạy với quyền root hoặc sudo"
        exit 1
    fi

    # Check required commands
    local required_commands=("docker" "docker-compose" "git" "composer" "npm")

    for cmd in "${required_commands[@]}"; do
        if ! command -v "$cmd" &> /dev/null; then
            print_error "Command '$cmd' không có sẵn"
            exit 1
        else
            print_status "$cmd: OK"
        fi
    done

    # Check Docker daemon
    if ! docker info &> /dev/null; then
        print_error "Docker daemon không chạy"
        exit 1
    fi

    print_status "Prerequisites check completed"
}

# Function to create backup
create_backup() {
    print_info "Creating backup..."

    if [ ! -d "$BACKUP_DIR" ]; then
        mkdir -p "$BACKUP_DIR"
    fi

    # Create database backup
    if docker ps | grep -q "bvote_mysql"; then
        print_info "Creating database backup..."
        docker exec bvote_mysql mysqldump -u root -p"${MYSQL_ROOT_PASSWORD}" "${DB_DATABASE}" > "$BACKUP_DIR/db_backup_$TIMESTAMP.sql"
        print_status "Database backup created: db_backup_$TIMESTAMP.sql"
    fi

    # Create application backup
    print_info "Creating application backup..."
    tar -czf "$BACKUP_DIR/app_backup_$TIMESTAMP.tar.gz" \
        --exclude=node_modules \
        --exclude=vendor \
        --exclude=.git \
        --exclude=storage/logs \
        --exclude=storage/cache \
        .
    print_status "Application backup created: app_backup_$TIMESTAMP.tar.gz"

    # Clean old backups (keep last 5)
    print_info "Cleaning old backups..."
    cd "$BACKUP_DIR"
    ls -t db_backup_*.sql | tail -n +6 | xargs -r rm
    ls -t app_backup_*.tar.gz | tail -n +6 | xargs -r rm
    cd ..
}

# Function to stop services
stop_services() {
    print_info "Stopping existing services..."

    if [ -f "docker-compose.yml" ]; then
        docker-compose down --remove-orphans
        print_status "Services stopped"
    else
        print_warning "docker-compose.yml not found"
    fi
}

# Function to pull latest code
pull_latest_code() {
    print_info "Pulling latest code..."

    if [ -d ".git" ]; then
        git fetch origin
        git reset --hard origin/master
        print_status "Code updated to latest version"
    else
        print_warning "Git repository not found"
    fi
}

# Function to install dependencies
install_dependencies() {
    print_info "Installing dependencies..."

    # Install PHP dependencies
    if [ -f "composer.json" ]; then
        print_info "Installing PHP dependencies..."
        composer install --no-dev --optimize-autoloader --no-interaction
        print_status "PHP dependencies installed"
    fi

    # Install Node.js dependencies
    if [ -f "package.json" ]; then
        print_info "Installing Node.js dependencies..."
        npm ci --only=production
        print_status "Node.js dependencies installed"
    fi

    # Build frontend assets
    if [ -f "package.json" ] && grep -q "build" package.json; then
        print_info "Building frontend assets..."
        npm run build
        print_status "Frontend assets built"
    fi
}

# Function to run health check
run_health_check() {
    print_info "Running system health check..."

    if [ -f "tools/system-health-check.php" ]; then
        php tools/system-health-check.php
        if [ $? -eq 0 ]; then
            print_status "Health check passed"
        else
            print_error "Health check failed"
            exit 1
        fi
    else
        print_warning "Health check script not found"
    fi
}

# Function to start services
start_services() {
    print_info "Starting services..."

    if [ -f "docker-compose.yml" ]; then
        # Start services in background
        docker-compose up -d

        # Wait for services to be ready
        print_info "Waiting for services to be ready..."
        sleep 30

        # Check service status
        if docker-compose ps | grep -q "Up"; then
            print_status "Services started successfully"
        else
            print_error "Some services failed to start"
            docker-compose logs
            exit 1
        fi
    else
        print_error "docker-compose.yml not found"
        exit 1
    fi
}

# Function to run database migrations
run_migrations() {
    print_info "Running database migrations..."

    if [ -f "tools/setup-database.php" ]; then
        php tools/setup-database.php
        print_status "Database migrations completed"
    else
        print_warning "Database migration script not found"
    fi
}

# Function to verify deployment
verify_deployment() {
    print_info "Verifying deployment..."

    # Check if services are responding
    local max_attempts=10
    local attempt=1

    while [ $attempt -le $max_attempts ]; do
        print_info "Health check attempt $attempt/$max_attempts..."

        if curl -f http://localhost/health &> /dev/null; then
            print_status "Application is responding"
            break
        fi

        if [ $attempt -eq $max_attempts ]; then
            print_error "Application failed to respond after $max_attempts attempts"
            exit 1
        fi

        attempt=$((attempt + 1))
        sleep 10
    done

    # Check database connection
    if docker exec bvote_mysql mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "USE ${DB_DATABASE};" &> /dev/null; then
        print_status "Database connection verified"
    else
        print_error "Database connection failed"
        exit 1
    fi

    # Check Redis connection
    if docker exec bvote_redis redis-cli -a "${REDIS_PASSWORD}" ping | grep -q "PONG"; then
        print_status "Redis connection verified"
    else
        print_error "Redis connection failed"
        exit 1
    fi
}

# Function to update deployment log
update_deployment_log() {
    print_info "Updating deployment log..."

    if [ ! -d "$DEPLOY_DIR" ]; then
        mkdir -p "$DEPLOY_DIR"
    fi

    echo "Deployment completed at: $(date)" >> "$DEPLOY_DIR/deployment.log"
    echo "Environment: $DEPLOYMENT_ENV" >> "$DEPLOY_DIR/deployment.log"
    echo "Timestamp: $TIMESTAMP" >> "$DEPLOY_DIR/deployment.log"
    echo "Status: SUCCESS" >> "$DEPLOY_DIR/deployment.log"
    echo "---" >> "$DEPLOY_DIR/deployment.log"

    print_status "Deployment log updated"
}

# Function to cleanup
cleanup() {
    print_info "Cleaning up..."

    # Remove old Docker images
    docker image prune -f

    # Remove old containers
    docker container prune -f

    # Remove old volumes
    docker volume prune -f

    print_status "Cleanup completed"
}

# Main deployment function
main() {
    echo -e "${BLUE}🚀 BVOTE Production Deployment Starting...${NC}"
    echo "================================================"
    echo "Project: $PROJECT_NAME"
    echo "Environment: $DEPLOYMENT_ENV"
    echo "Timestamp: $TIMESTAMP"
    echo "================================================"
    echo ""

    # Load environment variables
    if [ -f ".env" ]; then
        source .env
        print_status "Environment variables loaded"
    else
        print_error ".env file not found"
        exit 1
    fi

    # Check prerequisites
    check_prerequisites

    # Create backup
    create_backup

    # Stop existing services
    stop_services

    # Pull latest code
    pull_latest_code

    # Install dependencies
    install_dependencies

    # Run health check
    run_health_check

    # Start services
    start_services

    # Run migrations
    run_migrations

    # Verify deployment
    verify_deployment

    # Update deployment log
    update_deployment_log

    # Cleanup
    cleanup

    echo ""
    echo -e "${GREEN}🎉 Deployment completed successfully!${NC}"
    echo "================================================"
    echo "Application URL: http://localhost"
    echo "Admin Panel: http://localhost/admin"
    echo "Database: localhost:3306"
    echo "Redis: localhost:6379"
    echo "================================================"
}

# Error handling
trap 'print_error "Deployment failed. Check logs for details."; exit 1' ERR

# Run main function
main "$@"
