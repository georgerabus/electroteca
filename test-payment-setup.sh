#!/bin/bash

echo "🧪 Testing Payment Integration Setup"
echo "======================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${RED}✗ .env file not found${NC}"
    echo "Please run: cp .env.example .env"
    exit 1
fi

echo -e "${GREEN}✓ .env file exists${NC}"
echo ""

# Check if migration exists
if [ -f "database/migrations/2025_01_15_000000_create_payments_table.php" ]; then
    echo -e "${GREEN}✓ Payment migration exists${NC}"
else
    echo -e "${RED}✗ Payment migration not found${NC}"
    exit 1
fi

# Check if Payment model exists
if [ -f "app/Models/Payment.php" ]; then
    echo -e "${GREEN}✓ Payment model exists${NC}"
else
    echo -e "${RED}✗ Payment model not found${NC}"
    exit 1
fi

# Check if PaymentService exists
if [ -f "app/Services/PaymentService.php" ]; then
    echo -e "${GREEN}✓ PaymentService exists${NC}"
else
    echo -e "${RED}✗ PaymentService not found${NC}"
    exit 1
fi

# Check if PaymentController exists
if [ -f "app/Http/Controllers/PaymentController.php" ]; then
    echo -e "${GREEN}✓ PaymentController exists${NC}"
else
    echo -e "${RED}✗ PaymentController not found${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}📋 Next Steps:${NC}"
echo "1. Run migrations: php artisan migrate"
echo "2. Get Paddle API key from: https://app.paddle.com/settings/developer"
echo "3. Update .env with payment gateway credentials"
echo "4. Test payment flow by creating an order"
echo ""

echo -e "${YELLOW}⚙️  Required .env Variables:${NC}"
echo "For Paddle:"
echo "  - PADDLE_KEY=your_paddle_api_key"
echo "  - PADDLE_ENVIRONMENT=sandbox or production"
echo ""
echo "Product prices are automatically taken from your database!"
echo ""

echo -e "${GREEN}✓ All payment integration files are in place!${NC}"
