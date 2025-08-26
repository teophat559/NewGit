#!/bin/bash
# BVOTE Automated Build Pipeline
# Integrates comprehensive system cleanup into build process
# Ensures deployment readiness without manual intervention

set -e

echo "🚀 BVOTE Automated Build Pipeline - Deploy Readiness Check"
echo "=========================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')
REPORT_DIR="$PROJECT_DIR/storage/logs"
BUILD_REPORT="$REPORT_DIR/build-pipeline-$TIMESTAMP.log"

print_step() {
    echo -e "${BLUE}📋 Step $1: $2${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Create report directory
mkdir -p "$REPORT_DIR"

exec > >(tee -a "$BUILD_REPORT")
exec 2>&1

print_step 1 "Environment Setup"
echo "Project Directory: $PROJECT_DIR"
echo "Build Report: $BUILD_REPORT"
echo "Timestamp: $TIMESTAMP"

print_step 2 "Dependency Installation"
cd "$PROJECT_DIR"

# Install PHP dependencies
if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
    print_success "Composer dependencies installed"
else
    print_success "Composer dependencies already installed"
fi

# Install Node dependencies
if [ ! -d "node_modules" ]; then
    echo "Installing NPM dependencies..."
    npm install --production
    print_success "NPM dependencies installed"
else
    print_success "NPM dependencies already installed"
fi

print_step 3 "Comprehensive System Cleanup"
echo "Running comprehensive cleanup..."
php tools/comprehensive-system-cleanup.php
CLEANUP_EXIT_CODE=$?

if [ $CLEANUP_EXIT_CODE -eq 0 ]; then
    print_success "System cleanup completed successfully"
else
    print_error "System cleanup found issues that need attention"
fi

print_step 4 "System Health Check"
echo "Running health checks..."
php tools/system-health-check.php > /dev/null 2>&1 || print_warning "Health check completed with warnings"

print_step 5 "Final Validation"
echo "Running final system test..."
php tools/final-test.php > /dev/null 2>&1
FINAL_TEST_EXIT_CODE=$?

if [ $FINAL_TEST_EXIT_CODE -eq 0 ]; then
    print_success "Final validation passed"
    DEPLOYMENT_READY=true
else
    print_warning "Final validation found issues"
    DEPLOYMENT_READY=false
fi

print_step 6 "Build Report Generation"
echo "Generating build report..."

cat > "$REPORT_DIR/build-summary.json" << EOF
{
    "build_timestamp": "$TIMESTAMP",
    "cleanup_status": $([ $CLEANUP_EXIT_CODE -eq 0 ] && echo "\"success\"" || echo "\"warning\""),
    "final_test_status": $([ $FINAL_TEST_EXIT_CODE -eq 0 ] && echo "\"success\"" || echo "\"failed\""),
    "deployment_ready": $DEPLOYMENT_READY,
    "project_directory": "$PROJECT_DIR",
    "build_log": "$BUILD_REPORT"
}
EOF

echo ""
echo "🎯 BUILD PIPELINE RESULTS"
echo "========================="
echo "Cleanup Status: $([ $CLEANUP_EXIT_CODE -eq 0 ] && echo "✅ SUCCESS" || echo "⚠️ WARNING")"
echo "Final Test: $([ $FINAL_TEST_EXIT_CODE -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"

if [ "$DEPLOYMENT_READY" = true ]; then
    echo ""
    echo "🎉 GO-LIVE DECISION: APPROVED ✅"
    echo "================================"
    print_success "System is ready for VPS deployment"
    print_success "All critical checks passed"
    print_success "No manual intervention required"
    echo ""
    echo "Next steps:"
    echo "1. Run: bash tools/deploy-vps-automated.sh"
    echo "2. Or use: php tools/deploy-cyberpanel.php"
    echo ""
    exit 0
else
    echo ""
    echo "⚠️ GO-LIVE DECISION: NEEDS ATTENTION"
    echo "==================================="
    print_error "System requires manual review before deployment"
    print_error "Please check the detailed reports:"
    echo "- Build Log: $BUILD_REPORT"
    echo "- Cleanup Report: $REPORT_DIR/comprehensive-cleanup-report.json"
    echo ""
    exit 1
fi