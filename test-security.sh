#!/bin/bash

# Quick Security Testing Script for Electroteca
# This script tests basic security headers and configurations

set -e

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

APP_URL="${APP_URL:-http://127.0.0.1:8000}"

echo -e "${GREEN}=== Electroteca Security Testing ===${NC}"
echo ""
echo "Testing application at: $APP_URL"
echo ""

# Test 1: Check if application is running
echo -e "${YELLOW}[1/8] Checking if application is running...${NC}"
if curl.exe -s "$APP_URL" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Application is running${NC}"
else
    echo -e "${RED}✗ Application is not running. Please start it with: php artisan serve${NC}"
    exit 1
fi
echo ""

# Test 2: Security Headers
echo -e "${YELLOW}[2/8] Testing Security Headers...${NC}"
HEADERS=$(curl.exe -sI "$APP_URL" 2>&1)

check_header() {
    local header_name=$1
    if echo "$HEADERS" | grep -qi "$header_name"; then
        echo -e "${GREEN}✓ $header_name is present${NC}"
        echo "$HEADERS" | grep -i "$header_name" | head -1
    else
        echo -e "${RED}✗ $header_name is missing${NC}"
    fi
}

check_header "Content-Security-Policy"
check_header "X-Frame-Options"
check_header "X-Content-Type-Options"
check_header "X-XSS-Protection"
check_header "Referrer-Policy"
echo ""

# Test 3: HTTPS Enforcement (if enabled)
echo -e "${YELLOW}[3/8] Testing HTTPS Enforcement...${NC}"
if grep -q "FORCE_HTTPS=true" .env 2>/dev/null; then
    echo -e "${GREEN}✓ FORCE_HTTPS is enabled${NC}"
    HTTP_RESPONSE=$(curl.exe -sI "http://127.0.0.1:8000" 2>&1 | head -1)
    if echo "$HTTP_RESPONSE" | grep -qi "301\|302"; then
        echo -e "${GREEN}✓ HTTP to HTTPS redirect is working${NC}"
    else
        echo -e "${YELLOW}⚠ HTTP to HTTPS redirect may not be working${NC}"
    fi
else
    echo -e "${YELLOW}⚠ FORCE_HTTPS is not enabled (normal for development)${NC}"
fi
echo ""

# Test 4: CSRF Protection
echo -e "${YELLOW}[4/8] Testing CSRF Protection...${NC}"
CSRF_TEST=$(curl.exe -s -X POST "$APP_URL/checkout" \
    -H "Content-Type: application/json" \
    -d '{"test": "data"}' 2>&1)

if echo "$CSRF_TEST" | grep -qi "419\|csrf\|token"; then
    echo -e "${GREEN}✓ CSRF protection is active${NC}"
else
    echo -e "${YELLOW}⚠ CSRF protection may not be working as expected${NC}"
fi
echo ""

# Test 5: Session Cookie Security
echo -e "${YELLOW}[5/8] Testing Session Cookie Security...${NC}"
COOKIES=$(curl.exe -sI "$APP_URL" 2>&1 | grep -i "set-cookie")

if echo "$COOKIES" | grep -qi "httponly"; then
    echo -e "${GREEN}✓ Session cookies have HttpOnly flag${NC}"
else
    echo -e "${RED}✗ Session cookies may not have HttpOnly flag${NC}"
fi

if echo "$COOKIES" | grep -qi "samesite"; then
    echo -e "${GREEN}✓ Session cookies have SameSite attribute${NC}"
else
    echo -e "${YELLOW}⚠ Session cookies may not have SameSite attribute${NC}"
fi
echo ""

# Test 6: JWT Cookie Security
echo -e "${YELLOW}[6/8] Testing JWT Cookie Configuration...${NC}"
echo -e "${YELLOW}Note: JWT cookies are only set after login${NC}"
echo -e "${YELLOW}To test: Log in and check cookies in browser DevTools${NC}"
echo ""

# Test 7: Database Security
echo -e "${YELLOW}[7/8] Testing Database Configuration...${NC}"
if [ -f ".env" ]; then
    if grep -q "DB_CONNECTION=sqlite" .env; then
        echo -e "${GREEN}✓ Using SQLite (development)${NC}"
    elif grep -q "DB_CONNECTION=mysql\|DB_CONNECTION=pgsql" .env; then
        echo -e "${GREEN}✓ Using production database${NC}"
    fi
    
    if grep -q "APP_DEBUG=false" .env; then
        echo -e "${GREEN}✓ Debug mode is disabled (production)${NC}"
    else
        echo -e "${YELLOW}⚠ Debug mode is enabled (normal for development)${NC}"
    fi
else
    echo -e "${RED}✗ .env file not found${NC}"
fi
echo ""

# Test 8: File Permissions
echo -e "${YELLOW}[8/8] Testing File Permissions...${NC}"
if [ -d "storage" ]; then
    STORAGE_PERMS=$(stat -f "%OLp" storage 2>/dev/null || stat -c "%a" storage 2>/dev/null)
    echo -e "${GREEN}✓ Storage directory permissions: $STORAGE_PERMS${NC}"
else
    echo -e "${RED}✗ Storage directory not found${NC}"
fi

if [ -d "bootstrap/cache" ]; then
    CACHE_PERMS=$(stat -f "%OLp" bootstrap/cache 2>/dev/null || stat -c "%a" bootstrap/cache 2>/dev/null)
    echo -e "${GREEN}✓ Cache directory permissions: $CACHE_PERMS${NC}"
else
    echo -e "${RED}✗ Cache directory not found${NC}"
fi
echo ""

# Summary
echo -e "${GREEN}=== Security Testing Complete ===${NC}"
echo ""
echo "Next steps:"
echo "1. Review the results above"
echo "2. Test 2FA functionality manually"
echo "3. Run OWASP ZAP scan: ./zap-scan.sh"
echo "4. Check browser DevTools for cookie security"
echo "5. Review SECURITY_TESTING_GUIDE.md for detailed tests"
echo ""

