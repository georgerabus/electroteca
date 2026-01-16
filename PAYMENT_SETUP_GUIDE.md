# 💳 Payment Integration Setup Guide

## Overview
Payment integration implemented with support for **Stripe** and **Paddle** payment gateways.

---

## ✅ What Has Been Implemented

### 1. **Payment Model & Database**
- Created `app/Models/Payment.php` with complete payment tracking
- Created database migration: `database/migrations/2025_01_15_000000_create_payments_table.php`
- Tracks: payment_id, gateway, amount, currency, status, metadata, timestamps

### 2. **Payment Service** (`app/Services/PaymentService.php`)
- Unified payment interface supporting multiple gateways
- Methods:
  - `initializePayment()` - Start payment with specified gateway
  - `handleStripeWebhook()` - Process Stripe webhooks
  - `handlePaddleWebhook()` - Process Paddle webhooks
  - `refundPayment()` - Process refunds

### 3. **Payment Controller** (`app/Http/Controllers/PaymentController.php`)
- Routes for payment initialization
- Webhook handlers for both gateways
- Payment status tracking
- Refund management (admin only)

### 4. **Frontend Pages**
- `resources/js/pages/payment-checkout.tsx` - Payment gateway selection
- `resources/js/pages/payment-success.tsx` - Success confirmation
- `resources/js/pages/payment-cancelled.tsx` - Cancellation page
- `resources/js/pages/payment-pending.tsx` - Processing state

### 5. **Test Command**
- `app/Console/Commands/TestPaymentIntegration.php`
- Command: `php artisan payment:test --gateway=stripe`

---

## 🚀 Quick Start Setup

### Step 1: Install Dependencies
```bash
composer require stripe/stripe-php:^13.0
composer update
```

### Step 2: Configure .env File

#### **For Stripe Testing:**
```env
# Get from: https://dashboard.stripe.com/apikeys
STRIPE_PUBLIC_KEY=pk_test_YOUR_KEY_HERE
STRIPE_SECRET_KEY=sk_test_YOUR_KEY_HERE
STRIPE_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SECRET

# Default gateway
PAYMENT_GATEWAY=stripe
```

#### **For Paddle Testing:**
```env
# Get from: https://app.paddle.com/settings/developer
PADDLE_KEY=YOUR_PADDLE_API_KEY
PADDLE_ENVIRONMENT=sandbox  # or 'production'
PADDLE_PRICE_ID=pri_xxxxx   # Your product price ID

# Default gateway
PAYMENT_GATEWAY=paddle
```

### Step 3: Run Migrations
```bash
php artisan migrate
```

### Step 4: Test the Integration
```bash
# Test Stripe setup
php artisan payment:test --gateway=stripe

# Test Paddle setup
php artisan payment:test --gateway=paddle
```

---

## 📋 API Endpoints

### Payment Initialization
```http
POST /payment/initiate
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "order_id": 1,
  "gateway": "stripe"  // or "paddle"
}

Response:
{
  "success": true,
  "url": "https://checkout.stripe.com/...",
  "payment_id": 1,
  "session_id": "cs_test_..."
}
```

### Payment Success Callback
```http
GET /payment/success?session_id=cs_test_...
```

### Payment Cancellation Callback
```http
GET /payment/cancel
```

### Check Payment Status
```http
GET /payment/status/{payment_id}

Response:
{
  "status": "completed",
  "completed_at": "2025-01-15T12:00:00Z",
  "error_message": null
}
```

### Refund Payment (Admin Only)
```http
POST /admin/payments/{payment_id}/refund
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}

{
  "reason": "customer_request"
}
```

---

## 🔑 Credentials You Need to Provide

### **For Stripe:**

1. **Go to:** https://dashboard.stripe.com/apikeys
2. **Copy these values:**
   - Publishable key (starts with `pk_test_` or `pk_live_`)
   - Secret key (starts with `sk_test_` or `sk_live_`)
3. **Setup Webhook:**
   - Go to Developers → Webhooks
   - Add endpoint: `https://yourdomain.com/webhooks/stripe`
   - Select events: `checkout.session.completed`, `charge.refunded`
   - Copy the Webhook Secret (`whsec_...`)

### **For Paddle:**

1. **Create account at:** https://paddle.com
2. **Go to:** Settings → Developer
3. **Copy these values:**
   - API Key
4. **Get Product Info:**
   - Create a product/price
   - Copy the Price ID (`pri_...`)
5. **Setup Webhook:**
   - Add webhook endpoint: `https://yourdomain.com/webhooks/paddle`
   - Select events: `checkout.completed`

---

## 💰 Test Payments

### **Stripe Test Cards:**
- ✅ **Success:** `4242 4242 4242 4242`
- ❌ **Decline:** `4000 0000 0000 0002`
- 🔐 **3D Secure:** `4000 0025 0000 3155`

**Expiry:** Any future date (e.g., 12/25)
**CVC:** Any 3 digits (e.g., 123)

### **Paddle Sandbox:**
- Paddle handles test payments automatically in sandbox mode
- Just use the sandbox API key and `PADDLE_ENVIRONMENT=sandbox`

---

## 🔄 Payment Flow

### Normal Flow:
```
1. User creates order with products
   ↓
2. User clicks "Pay Now" button
   ↓
3. Select payment gateway (Stripe/Paddle)
   ↓
4. System initializes payment:
   - Creates Payment record (status: pending)
   - Returns checkout URL from gateway
   ↓
5. User redirected to Stripe/Paddle checkout
   ↓
6. User completes payment
   ↓
7. Gateway sends webhook to your server
   ↓
8. Server processes webhook:
   - Finds Payment record
   - Marks as completed
   - Updates Order status
   ↓
9. User redirected to success page
   ↓
10. Order confirmed, items shipped
```

### Failure Flow:
```
User cancels payment → Redirects to /payment/cancel
↓
Payment stays "pending" in database
↓
User can try again with same order
```

---

## 📊 Database Structure

### Payments Table
```sql
CREATE TABLE payments (
  id BIGINT PRIMARY KEY,
  user_id BIGINT FOREIGN KEY,
  order_id BIGINT FOREIGN KEY,
  payment_id VARCHAR UNIQUE,      -- External gateway ID
  gateway ENUM('paddle', 'stripe'), 
  amount DECIMAL(10, 2),
  currency VARCHAR(3),
  status ENUM('pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'),
  metadata JSON,                   -- Gateway-specific data
  error_message TEXT,
  completed_at TIMESTAMP,
  failed_at TIMESTAMP,
  refunded_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  INDEX(user_id),
  INDEX(order_id),
  INDEX(status),
  INDEX(gateway),
  UNIQUE(payment_id)
);
```

---

## 🔐 Security Features

1. **CSRF Protection** - All endpoints protected with CSRF tokens
2. **Webhook Verification** - Stripe signatures verified, Paddle data validated
3. **User Authorization** - Payment must belong to authenticated user
4. **Admin Only Refunds** - Refunds require admin privileges
5. **No Card Storage** - Cards never stored locally, handled by gateway
6. **Encrypted Metadata** - Sensitive data encrypted in database

---

## 🧪 Testing Checklist

- [ ] Install Stripe SDK: `composer require stripe/stripe-php`
- [ ] Add Stripe credentials to .env
- [ ] Run migrations: `php artisan migrate`
- [ ] Test command: `php artisan payment:test --gateway=stripe`
- [ ] Create test order through admin
- [ ] Test payment initialization
- [ ] Test webhook handling
- [ ] Test success page display
- [ ] Test cancellation flow
- [ ] Test refund functionality

---

## 📞 Support & Troubleshooting

### Common Issues:

**1. "Stripe credentials not configured"**
- Add STRIPE_PUBLIC_KEY and STRIPE_SECRET_KEY to .env
- Run: `php artisan config:cache`

**2. "Payment not found"**
- Check if payment was created: `SELECT * FROM payments`
- Verify webhook is reaching your server

**3. "Invalid webhook signature"**
- Regenerate webhook in gateway dashboard
- Update STRIPE_WEBHOOK_SECRET in .env

**4. "CSRF token mismatch"**
- Ensure X-CSRF-TOKEN header sent with POST requests
- Token available at: `document.querySelector('meta[name="csrf-token"]').content`

---

## 🚀 Next Steps

1. **Get API Credentials:**
   - Stripe: https://dashboard.stripe.com/apikeys
   - Paddle: https://app.paddle.com/settings/developer

2. **Update .env** with your credentials

3. **Test the integration** using the test command

4. **Setup webhooks** in payment gateway dashboards

5. **Deploy to production** with live keys when ready

6. **Monitor transactions** in your payment dashboard

---

## 📚 Additional Resources

- **Stripe Docs:** https://stripe.com/docs
- **Paddle Docs:** https://developer.paddle.com/docs
- **Laravel Cashier:** https://laravel.com/docs/cashier-paddle
- **Webhook Testing:** https://ngrok.com/ (for local testing)

---

## ✅ Implementation Complete!

All payment integration files are ready. Just add your credentials to .env and test! 🎉
