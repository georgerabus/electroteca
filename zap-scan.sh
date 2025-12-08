#!/bin/bash

# OWASP ZAP Automated Security Scan Script for Electroteca
# This script performs security scans on the application

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_URL="${APP_URL:-http://localhost:8000}"
ZAP_HOST="${ZAP_HOST:-localhost}"
ZAP_PORT="${ZAP_PORT:-8080}"
ZAP_API_KEY="${ZAP_API_KEY:-}"
REPORT_DIR="${REPORT_DIR:-./zap-reports}"
TIMEOUT="${TIMEOUT:-300}"

# Create reports directory
mkdir -p "$REPORT_DIR"

echo -e "${GREEN}Starting OWASP ZAP Security Scan${NC}"
echo "Application URL: $APP_URL"
echo "ZAP Host: $ZAP_HOST:$ZAP_PORT"
echo "Reports Directory: $REPORT_DIR"
echo ""

# Check if ZAP is running
echo -e "${YELLOW}Checking if ZAP is running...${NC}"
if ! curl -s "http://$ZAP_HOST:$ZAP_PORT" > /dev/null 2>&1; then
    echo -e "${RED}Error: ZAP is not running at http://$ZAP_HOST:$ZAP_PORT${NC}"
    echo "Please start ZAP first:"
    echo "  - Docker: docker run -d -p 8080:8080 owasp/zap2docker-stable"
    echo "  - Desktop: Start OWASP ZAP application"
    exit 1
fi

echo -e "${GREEN}ZAP is running${NC}"
echo ""

# Build API URL
if [ -n "$ZAP_API_KEY" ]; then
    API_URL="http://$ZAP_HOST:$ZAP_PORT/JSON/core/action/?apikey=$ZAP_API_KEY"
    API_URL_JSON="http://$ZAP_HOST:$ZAP_PORT/JSON"
else
    API_URL="http://$ZAP_HOST:$ZAP_PORT/JSON/core/action/"
    API_URL_JSON="http://$ZAP_HOST:$ZAP_PORT/JSON"
fi

# Function to call ZAP API
zap_api() {
    local endpoint="$1"
    local data="$2"
    
    if [ -n "$ZAP_API_KEY" ]; then
        curl -s "http://$ZAP_HOST:$ZAP_PORT$endpoint?apikey=$ZAP_API_KEY" \
            -H "Content-Type: application/json" \
            ${data:+-d "$data"}
    else
        curl -s "http://$ZAP_HOST:$ZAP_PORT$endpoint" \
            -H "Content-Type: application/json" \
            ${data:+-d "$data"}
    fi
}

# Start new session
echo -e "${YELLOW}Starting new ZAP session...${NC}"
zap_api "/JSON/core/action/newSession/" "{\"name\":\"electroteca-scan-$(date +%s)\"}"

# Spider scan
echo -e "${YELLOW}Running spider scan...${NC}"
SPIDER_ID=$(zap_api "/JSON/spider/action/scan/" "{\"url\":\"$APP_URL\"}" | grep -o '"scan":"[^"]*"' | cut -d'"' -f4)
echo "Spider scan ID: $SPIDER_ID"

# Wait for spider to complete
echo "Waiting for spider scan to complete..."
while true; do
    STATUS=$(zap_api "/JSON/spider/view/status/" "{\"scanId\":\"$SPIDER_ID\"}" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
    if [ "$STATUS" = "100" ]; then
        break
    fi
    echo "Spider progress: $STATUS%"
    sleep 2
done
echo -e "${GREEN}Spider scan completed${NC}"
echo ""

# Active scan
echo -e "${YELLOW}Running active scan...${NC}"
ACTIVE_SCAN_ID=$(zap_api "/JSON/ascan/action/scan/" "{\"url\":\"$APP_URL\",\"recurse\":true}" | grep -o '"scan":"[^"]*"' | cut -d'"' -f4)
echo "Active scan ID: $ACTIVE_SCAN_ID"

# Wait for active scan to complete
echo "Waiting for active scan to complete (this may take several minutes)..."
while true; do
    STATUS=$(zap_api "/JSON/ascan/view/status/" "{\"scanId\":\"$ACTIVE_SCAN_ID\"}" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
    if [ "$STATUS" = "100" ]; then
        break
    fi
    echo "Active scan progress: $STATUS%"
    sleep 5
done
echo -e "${GREEN}Active scan completed${NC}"
echo ""

# Generate reports
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo -e "${YELLOW}Generating reports...${NC}"

# HTML Report
echo "Generating HTML report..."
zap_api "/OTHER/core/other/htmlreport/" > "$REPORT_DIR/zap-report-$TIMESTAMP.html"
echo -e "${GREEN}HTML report saved: $REPORT_DIR/zap-report-$TIMESTAMP.html${NC}"

# JSON Report
echo "Generating JSON report..."
zap_api "/JSON/core/view/alerts/" "{\"baseurl\":\"$APP_URL\"}" > "$REPORT_DIR/zap-report-$TIMESTAMP.json"
echo -e "${GREEN}JSON report saved: $REPORT_DIR/zap-report-$TIMESTAMP.json${NC}"

# XML Report
echo "Generating XML report..."
zap_api "/OTHER/core/other/xmlreport/" > "$REPORT_DIR/zap-report-$TIMESTAMP.xml"
echo -e "${GREEN}XML report saved: $REPORT_DIR/zap-report-$TIMESTAMP.xml${NC}"

echo ""
echo -e "${GREEN}Scan completed successfully!${NC}"
echo "Reports are available in: $REPORT_DIR"
echo ""
echo "To view the HTML report, open:"
echo "  file://$(pwd)/$REPORT_DIR/zap-report-$TIMESTAMP.html"

