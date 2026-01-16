# Hybrid Payment System with Wallet & Paddle Integration

## Overview

Your Electroteca payment system now supports a **flexible hybrid payment model** that combines wallet credits with Paddle payments. Users can:

1. **Pay with Wallet Credits Only** - Instantly deduct from their account balance
2. **Pay with Paddle (Card)** - Use credit card through Paddle gateway
3. **Hybrid Payments** - Split payment between wallet credits and Paddle
4. **Top-up Wallet** - Add credits to their account via Paddle

## Architecture

### Payment Methods

The system supports three payment methods:

#### 1. Wallet-Only Payments ✅ FULLY FUNCTIONAL
- **Instant completion** - No external processing
- **Requires balance** - Must have sufficient credits
- **Use case** - Quick checkout for users with credits
- **Flow**: User clicks "Pay with Wallet" → Credits deducted immediately → Order marked completed

#### 2. Paddle-Only Payments 🔄 WORKING (PUBLIC URL NEEDED)
- **Uses Paddle checkout** - Standard credit card payment
- **No wallet required** - Direct payment processing
- **Use case** - Users without credits or large purchases
- **Flow**: User clicks "Pay with Paddle" → Redirected to Paddle → Payment webhook updates status

#### 3. Hybrid Payments (Wallet + Paddle) 🔄 WORKING (PUBLIC URL NEEDED)
- **Split payment** - Wallet covers part, Paddle covers remainder
- **Smart allocation** - Automatically uses available wallet balance
- **Use case** - User has partial credits for their purchase
- **Flow**: Deduct wallet credits → Initialize Paddle for remaining → Complete both → Order marked completed

#### 4. Wallet Top-up 🔄 WORKING (PUBLIC URL NEEDED)
- **Add credits via Paddle** - Purchase credits to add to wallet
- **Webhook updates** - Credits added automatically when payment confirmed
- **Use case** - Users need to buy more credits
- **Flow**: Choose amount → Paddle checkout → Payment confirmed → Credits added to wallet

## Technical Implementation

### Database

New fields added to `payments` table:

```sql
- payment_method (string): 'paddle', 'wallet', 'hybrid', or 'wallet_topup'
- paddle_amount (decimal): Amount charged via Paddle
- wallet_amount (decimal): Amount deducted from wallet
```

### Services

#### WalletService (`app/Services/WalletService.php`)
Manages user wallet balance and transactions:

```php
// Get current balance
$balance = $walletService->getBalance($user);

// Add credits (top-up, refunds)
$walletService->addCredits($user, 50.00, 'paddle_topup');

// Deduct credits (purchases)
$walletService->deductCredits($user, 25.99, 'purchase', ['order_id' => 1]);

// Check if user has enough
if ($walletService->hasSufficientBalance($user, 100.00)) { ... }

// Get transaction history
$transactions = $walletService->getTransactionHistory($user, 50);
```

#### PaymentService (`app/Services/PaymentService.php`)
Orchestrates payment processing:

```php
// Initialize payment (auto-detects method)
$result = $paymentService->initializePayment($user, $order, 'wallet');
// or 'paddle' or 'hybrid'

// For hybrid: specify wallet amount to use
$result = $paymentService->initializePayment($user, $order, 'hybrid', [
    'wallet_amount' => 25.00  // Rest paid via Paddle
]);

// Initialize wallet top-up
$result = $paymentService->initializeWalletTopup($user, 100.00);

// Handle Paddle webhook
$result = $paymentService->handlePaddleWebhook($webhookData);

// Complete wallet top-up
$result = $paymentService->completeWalletTopup($payment);
```

### Controllers

#### PaymentController Updates

New endpoints:

```php
// Existing: Initiate payment (enhanced)
POST /payment/initiate
{
    "payment_method": "wallet|paddle|hybrid",  // default: paddle
    "wallet_amount": 25.00  // optional, for hybrid
}

// NEW: Wallet top-up
POST /wallet-topup/initiate
{
    "amount": 100.00  // Amount to add
}

// NEW: Wallet top-up success callback
GET /wallet-topup/success?checkout_id=...
```

#### WalletController Enhancements

```php
// Show wallet dashboard
GET /wallet

// Get balance (API)
GET /wallet/balance

// Get transaction history (API)
GET /wallet/history?limit=50
```

### Frontend Integration Points

Update your checkout flow to support payment method selection:

```javascript
// 1. Check user's wallet balance
const response = await fetch('/wallet/balance');
const { balance } = await response.json();

// 2. Show payment options
if (balance > 0) {
    // Show wallet + hybrid options
} else {
    // Show Paddle option only
}

// 3. Initiate payment with method
const paymentResult = await fetch('/payment/initiate', {
    method: 'POST',
    body: JSON.stringify({
        order_id: orderId,
        payment_method: selectedMethod,  // 'wallet', 'paddle', or 'hybrid'
        wallet_amount: walletAmountToUse  // if hybrid
    })
});

if (paymentResult.payment_method === 'wallet') {
    // Payment complete, redirect to success
} else if (paymentResult.url) {
    // Redirect to Paddle checkout
    window.location.href = paymentResult.url;
}
```

## Testing

### Test Wallet-Only Payments

```bash
php artisan payment:test-hybrid
```

This command tests all payment methods:
1. ✅ Wallet-only (instant completion)
2. 🔄 Paddle-only (requires public URL)
3. 🔄 Hybrid payment (requires public URL)
4. 🔄 Wallet top-up (requires public URL)

### Manual Testing with Public URL

When deployed with a public URL:

1. **Update APP_URL in .env:**
   ```
   APP_URL=https://yourdomain.com
   ```

2. **Configure Paddle webhook in dashboard:**
   - Endpoint: `https://yourdomain.com/webhooks/paddle`
   - Events: `checkout.completed`

3. **Test wallet-only (works locally):**
   ```php
   // Add credits
   $walletService->addCredits($user, 100.00, 'test');

   // Purchase with wallet
   $result = $paymentService->initializePayment($user, $order, 'wallet');
   ```

4. **Test Paddle payments (needs public URL):**
   - Create an order
   - Call `/payment/initiate` with `payment_method: 'paddle'`
   - Receive Paddle checkout URL
   - Complete payment in Paddle sandbox
   - Webhook will update payment and order status

## User Wallet Management

### Adding Credits to User

Admin can add credits programmatically:

```php
// In admin panel or command
$walletService->addCredits($user, 50.00, 'admin_refund', [
    'reason' => 'Customer refund',
    'admin_id' => $admin->id,
]);
```

### User Transactions

Each wallet action creates a transaction record:

- `type`: 'credit' or 'debit'
- `reason`: 'paddle_topup', 'purchase', 'purchase_partial', 'refund', 'admin_refund', etc.
- `meta`: Additional data (order_id, payment_id, etc.)

## Payment Flow Diagrams

### Wallet-Only Flow
```
User clicks "Pay with Wallet"
        ↓
Check wallet balance
        ↓
Deduct from wallet ✓
        ↓
Create payment (completed)
        ↓
Update order status → completed
        ↓
Instant success page
```

### Hybrid Flow
```
User selects "Use Wallet + Card"
        ↓
Deduct wallet amount ✓
        ↓
Initialize Paddle for remainder
        ↓
User completes Paddle checkout
        ↓
Webhook received → Payment marked completed
        ↓
Order marked completed
```

### Wallet Top-Up Flow
```
User clicks "Add Credits"
        ↓
Enter amount
        ↓
Initialize Paddle checkout
        ↓
User completes Paddle payment
        ↓
Webhook received
        ↓
Add credits to wallet
        ↓
Transaction recorded
        ↓
Success notification
```

## Configuration

### Payment Method Selection Priority

The system automatically chooses the best payment method:

```php
// If wallet has exact amount → wallet-only
if ($walletService->hasSufficientBalance($user, $order->total)) {
    // User can choose wallet or paddle
}

// If wallet has partial amount → show hybrid option
if ($balance > 0 && $balance < $order->total) {
    // Show: "Use wallet ($X) + Card for remaining ($Y)"
}

// If no wallet balance → paddle only
if ($balance == 0) {
    // Show: "Pay with Card"
}
```

## Error Handling

### Wallet Payment Failures

```php
$result = $paymentService->initializePayment($user, $order, 'wallet');

if (!$result['success']) {
    // Error: 'Insufficient wallet balance'
    $required = $result['required'];  // Amount needed
    $balance = $result['balance'];     // Current balance
    $needed = $required - $balance;    // Amount to top-up
}
```

### Hybrid Payment Rollback

If Paddle initialization fails in hybrid mode:
- Wallet deduction is automatically refunded
- No partial state is left in database
- User can retry with different method

## Security Considerations

1. **Wallet Deduction Happens First** - Reduces risk if Paddle fails
2. **Refund on Failure** - Wallet amounts are refunded if Paddle fails
3. **Webhook Verification** - Paddle webhooks verified by signature (when live)
4. **User Isolation** - Users can only access their own payments/wallet

## Next Steps

1. **Deploy to Public Domain**
   - Update `APP_URL` in .env
   - Set up HTTPS certificate
   - Configure Paddle webhook endpoint

2. **Configure Paddle Webhook**
   - Login to Paddle dashboard
   - Set webhook URL to: `https://yourdomain.com/webhooks/paddle`
   - Select `checkout.completed` event

3. **Frontend Implementation**
   - Add payment method selector to checkout
   - Show wallet balance on product pages
   - Add wallet top-up button to wallet page
   - Display transaction history

4. **Testing in Production**
   - Test with Paddle sandbox credentials
   - Complete full payment flow
   - Verify webhook updates payments
   - Monitor wallet transactions

5. **Go Live**
   - Switch Paddle environment to production
   - Update `PADDLE_ENVIRONMENT=production` in .env
   - Test with real test card numbers
   - Monitor transactions and refunds

## API Reference

### Payment Initialization

**Endpoint:** `POST /payment/initiate`

**Request:**
```json
{
    "payment_method": "wallet|paddle|hybrid",
    "wallet_amount": 25.00  // optional, for hybrid
}
```

**Response (Wallet):**
```json
{
    "success": true,
    "payment_id": 123,
    "payment_method": "wallet",
    "message": "Payment completed using wallet credits",
    "new_balance": 10.50
}
```

**Response (Paddle):**
```json
{
    "success": true,
    "payment_id": 456,
    "checkout_id": "chk_...",
    "url": "https://checkout.paddle.com/..."
}
```

**Response (Hybrid):**
```json
{
    "success": true,
    "payment_id": 789,
    "payment_method": "hybrid",
    "wallet_amount_used": 25.00,
    "paddle_amount_due": 25.99,
    "new_wallet_balance": 10.50,
    "url": "https://checkout.paddle.com/..."
}
```

### Wallet Top-Up

**Endpoint:** `POST /wallet-topup/initiate`

**Request:**
```json
{
    "amount": 100.00
}
```

**Response:**
```json
{
    "success": true,
    "payment_id": 890,
    "topup_amount": 100.00,
    "url": "https://checkout.paddle.com/..."
}
```

### Get Wallet Balance

**Endpoint:** `GET /wallet/balance`

**Response:**
```json
{
    "balance": 45.50,
    "formatted_balance": "45.50",
    "currency": "credits"
}
```

### Get Transaction History

**Endpoint:** `GET /wallet/history?limit=50`

**Response:**
```json
{
    "transactions": [
        {
            "id": 1,
            "user_id": 1,
            "amount": "100.00",
            "type": "credit",
            "reason": "paddle_topup",
            "meta": {"payment_id": 890},
            "created_at": "2026-01-16T..."
        }
    ],
    "total": 1
}
```

## Troubleshooting

### Paddle "Invalid URL" Error
- **Cause:** APP_URL is localhost or invalid
- **Solution:** Update APP_URL to public domain in .env

### Wallet payment not completing
- **Check:** `php artisan payment:test-hybrid`
- **Verify:** User has sufficient balance
- **Check DB:** Payment record created with correct status

### Webhook not updating payment
- **Verify:** Webhook URL configured in Paddle dashboard
- **Check:** `storage/logs/laravel.log` for webhook errors
- **Test:** Use `php artisan payment:test-paddle`

## Support

For issues or questions:
1. Check the test command output
2. Review logs in `storage/logs/`
3. Verify Paddle API key and environment
4. Ensure public URL is configured for webhooks
