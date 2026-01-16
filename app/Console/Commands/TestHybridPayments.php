<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Console\Command;

class TestHybridPayments extends Command
{
    protected $signature = 'payment:test-hybrid';
    protected $description = 'Test hybrid payment system (wallet + paddle)';

    public function handle(PaymentService $paymentService, WalletService $walletService)
    {
        $this->line("\n🧪 Testing Hybrid Payment System");
        $this->line("=".str_repeat("=", 50));

        // Get or create test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );
        $user->wallet_balance = 0;
        $user->save();

        $this->info("\n✓ Using test user: {$user->email}");

        // Get product for order
        $category = \App\Models\Category::first() ?? \App\Models\Category::create([
            'name' => 'Test Category',
            'description' => 'Test category for testing',
        ]);

        $product = Product::firstOrCreate(
            ['slug' => 'test-product'],
            [
                'name' => 'Test Product',
                'description' => 'Test product for payment testing',
                'price' => 25.99,
                'currency' => 'USD',
                'category_id' => $category->id,
            ]
        );

        // --- TEST 1: Wallet-only payment ---
        $this->line("\n📋 Test 1: Wallet-Only Payment");
        $this->line("-".str_repeat("-", 50));

        // Create order
        $order1 = Order::create([
            'order_number' => 'TEST-' . time(),
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 10.00,
            'currency' => 'USD',
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 10.00,
            'subtotal' => 10.00,
        ]);

        // Add wallet credits
        $walletService->addCredits($user, 20.00, 'test_topup', ['test' => true]);
        $this->info("✓ Added \$20.00 to wallet");

        $balance = $walletService->getBalance($user);
        $this->info("✓ Wallet balance: \${$balance}");

        // Try wallet payment
        $result1 = $paymentService->initializePayment($user, $order1, 'wallet');
        if ($result1['success']) {
            $this->info("✓ Wallet payment completed");
            $this->info("  Payment ID: {$result1['payment_id']}");
            $this->info("  New balance: \${$result1['new_balance']}");
        } else {
            $this->error("✗ Wallet payment failed: {$result1['error']}");
        }

        // --- TEST 2: Paddle-only payment ---
        $this->line("\n📋 Test 2: Paddle-Only Payment");
        $this->line("-".str_repeat("-", 50));

        $order2 = Order::create([
            'order_number' => 'TEST-' . (time() + 1),
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 25.99,
            'currency' => 'USD',
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 25.99,
            'subtotal' => 25.99,
        ]);

        $result2 = $paymentService->initializePayment($user, $order2, 'paddle');
        if ($result2['success']) {
            $this->info("✓ Paddle payment initialized");
            $this->info("  Payment ID: {$result2['payment_id']}");
            $this->info("  Checkout URL: {$result2['url']}");
        } else {
            $this->error("✗ Paddle payment failed: {$result2['error']}");
        }

        // --- TEST 3: Hybrid payment (wallet + paddle) ---
        $this->line("\n📋 Test 3: Hybrid Payment (Wallet + Paddle)");
        $this->line("-".str_repeat("-", 50));

        $order3 = Order::create([
            'order_number' => 'TEST-' . (time() + 2),
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 50.00,
            'currency' => 'USD',
        ]);

        OrderItem::create([
            'order_id' => $order3->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 25.00,
            'subtotal' => 50.00,
        ]);

        $walletBalance = $walletService->getBalance($user);
        $this->info("Current wallet balance: \${$walletBalance}");

        $result3 = $paymentService->initializePayment($user, $order3, 'hybrid', [
            'wallet_amount' => min($walletBalance, 25.00) // Use up to $25 from wallet
        ]);

        if ($result3['success']) {
            $this->info("✓ Hybrid payment initialized");
            $this->info("  Wallet amount used: \${$result3['wallet_amount_used']}");
            $this->info("  Paddle amount due: \${$result3['paddle_amount_due']}");
            $this->info("  New wallet balance: \${$result3['new_wallet_balance']}");
            $this->info("  Checkout URL: {$result3['url']}");
        } else {
            $this->error("✗ Hybrid payment failed: {$result3['error']}");
        }

        // --- TEST 4: Wallet top-up ---
        $this->line("\n📋 Test 4: Wallet Top-Up Via Paddle");
        $this->line("-".str_repeat("-", 50));

        $result4 = $paymentService->initializeWalletTopup($user, 100.00);
        if ($result4['success']) {
            $this->info("✓ Wallet top-up initialized");
            $this->info("  Top-up amount: \${$result4['topup_amount']}");
            $this->info("  Payment ID: {$result4['payment_id']}");
            $this->info("  Checkout URL: {$result4['url']}");
        } else {
            $this->error("✗ Wallet top-up failed: {$result4['error']}");
        }

        // --- Summary ---
        $this->line("\n✅ Hybrid Payment System Test Completed!");
        $this->line("=".str_repeat("=", 50));
        $this->info("\n📊 Summary:");
        $this->info("  • Wallet-only payment: " . ($result1['success'] ? "✓" : "✗"));
        $this->info("  • Paddle-only payment: " . ($result2['success'] ? "✓" : "✗"));
        $this->info("  • Hybrid payment: " . ($result3['success'] ? "✓" : "✗"));
        $this->info("  • Wallet top-up: " . ($result4['success'] ? "✓" : "✗"));

        $this->line("\n🔗 Next Steps:");
        $this->info("  1. Wallet-only payments complete instantly");
        $this->info("  2. Paddle payments require sandbox checkout completion");
        $this->info("  3. Hybrid payments split between wallet and Paddle");
        $this->info("  4. Wallet top-ups add credits via Paddle");
        $this->info("  5. Webhooks automatically update wallet/order status");
    }
}
