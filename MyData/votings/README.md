# BVOTE 2025 - Blockchain Voting System

## Overview
BVOTE is a secure, modern voting system built with PHP, featuring real-time monitoring, advanced authentication, and comprehensive security measures.

## Features
- 🔐 Secure voting system with OTP authentication
- 📊 Real-time vote monitoring and analytics
- 🤖 Automated login bot with Telegram integration
- 🔒 Multi-layer security with rate limiting
- 📱 Responsive web interface
- 🐳 Docker containerization support

## System Requirements
- PHP 8.1+
- MySQL/MariaDB
- Redis (optional)
- Node.js (for Puppeteer automation)
- Composer

## Installation

### 1. Install Dependencies
```bash
composer install
npm install
```

### 2. Configure Environment
```bash
cp .env.example .env
# Edit .env with your database and configuration settings
```

### 3. Run System Integrity Check
```bash
php tools/system-integrity-checker.php
```

### 4. Start the Application
```bash
# Development server
php -S localhost:8000

# Or use the provided batch file
start-server.bat
```

## Directory Structure
```
├── admin/          # Admin panel and management
├── api/            # RESTful API endpoints
├── config/         # Configuration files
├── core/           # Core system classes
├── data/           # Data storage
├── includes/       # Include files and utilities
├── logs/           # System logs
├── modules/        # Application modules
├── public/         # Public assets
├── tools/          # System maintenance tools
├── uploads/        # File uploads
└── user/           # User interface
```

## System Health Checks

### Available Tools
- `tools/system-integrity-checker.php` - Check system file integrity
- `tools/system-health-check.php` - Comprehensive system health monitoring
- `tools/final-test.php` - Complete system validation
- `tools/automated-testing-script.php` - Automated test suite

### Run All Checks
```bash
php tools/system-integrity-checker.php
php tools/system-health-check.php
php tools/final-test.php
```

## Security Features
- Input validation and sanitization
- CSRF protection
- Rate limiting
- Secure session management
- SQL injection prevention
- XSS protection

## API Endpoints
- `GET /api/v1/health` - System health status
- `POST /api/v1/auth/login` - User authentication
- `POST /api/v1/votes` - Submit vote
- `GET /api/v1/results` - Get voting results

## License
Licensed under the MIT License. See LICENSE file for details.

## Support
For support and issues, please check the system logs or run the diagnostic tools.