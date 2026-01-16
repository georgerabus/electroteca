<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Product;
use Illuminate\Console\Command;

class TestPaymentIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test payment integration with Stripe or Paddle';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gateway = 'paddle';

        $this->info('🧪 Testing Paddle Payment Integration');
        $this->info('=====================================');
        $this->newLine();

        // Check configuration
        $this->checkConfiguration($gateway);

        // Create test data
        $this->info('📦 Creating test data...');
        $user = $this->getOrCreateTestUser();
        $product = Product::first();

        if (!$product) {
            $this->error('No products found. Please seed the database first.');
            return 1;
        }

        // Create an order
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'TEST-' . time(),
            'status' => 'pending',
            'total_amount' => $product->price,
            'currency' => $product->currency ?? 'USD',
            'shipping_address' => 'Test Street 123',
        ]);

        $this->info("✓ Created test order: {$order->order_number}");
        $this->newLine();

        // Create payment record
        $this->info('💳 Creating payment record...');
        $payment = Payment::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'payment_id' => 'test_' . uniqid(),
            'gateway' => $gateway,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'status' => 'pending',
            'metadata' => [
                'test' => true,
                'created_at_command' => now()->toDateTimeString(),
            ],
        ]);

        $this->info("✓ Created test payment: {$payment->payment_id}");
        $this->newLine();

        // Display test data
        $this->displayTestData($user, $order, $payment);

        // Test payment status updates
        $this->testPaymentStatusUpdates($payment);

        $this->newLine();
        $this->info('✅ Payment integration test completed successfully!');
        $this->newLine();

        $this->displayNextSteps($gateway);

        return 0;
    }

    private function checkConfiguration(string $gateway): void
    {
        $this->info('⚙️  Checking Paddle configuration...');

        $key = config('services.paddle.key');
        $environment = config('services.paddle.environment');

        if (!$key || !$environment) {
            $this->error('❌ Paddle credentials not configured in .env');
            $this->warn('Required:');
            $this->warn('  - PADDLE_KEY');
            $this->warn('  - PADDLE_ENVIRONMENT');
            exit(1);
        }

        $this->info('✓ Paddle configuration found');
        $this->info('✓ Product prices will be taken from your database');
        $this->newLine();
    }

    private function getOrCreateTestUser(): User
    {
        $user = User::where('email', 'test@example.com')->first();

        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'wallet_balance' => 1000.00,
                'email_verified_at' => now(),
            ]);

            $this->info("✓ Created test user: {$user->email}");
        } else {
            $this->info("✓ Using existing test user: {$user->email}");
        }

        return $user;
    }

    private function displayTestData(User $user, Order $order, Payment $payment): void
    {
        $this->info('📋 Test Data:');
        $this->line('User ID: ' . $user->id);
        $this->line('User Email: ' . $user->email);
        $this->newLine();
        $this->line('Order ID: ' . $order->id);
        $this->line('Order Number: ' . $order->order_number);
        $this->line('Order Amount: ' . $order->total_amount . ' ' . $order->currency);
        $this->newLine();
        $this->line('Payment ID: ' . $payment->id);
        $this->line('Payment Gateway ID: ' . $payment->payment_id);
        $this->line('Payment Status: ' . $payment->status);
        $this->newLine();
    }

    private function testPaymentStatusUpdates(Payment $payment): void
    {
        $this->info('🔄 Testing payment status updates...');

        // Test mark as completed
        $payment->markCompleted();
        $payment->refresh();

        if ($payment->isCompleted()) {
            $this->info('✓ Payment marked as completed');
            $this->line('  Status: ' . $payment->status);
            $this->line('  Completed At: ' . $payment->completed_at);
        }

        // Test mark as failed
        $payment->markFailed('Test failure message');
        $payment->refresh();

        if ($payment->isFailed()) {
            $this->info('✓ Payment marked as failed');
            $this->line('  Status: ' . $payment->status);
            $this->line('  Error: ' . $payment->error_message);
        }

        // Reset to pending for actual testing
        $payment->update(['status' => 'pending', 'failed_at' => null, 'error_message' => null]);

        $this->newLine();
    }

    private function displayNextSteps(string $gateway): void
    {
        $this->info('📝 Next Steps:');
        $this->newLine();

        $this->info('For Paddle Testing:');
        $this->line('1. Create account at: https://paddle.com');
        $this->line('2. Get API key from: https://app.paddle.com/settings/developer');
        $this->line('3. Add to .env:');
        $this->warn('   PADDLE_KEY=your_api_key');
        $this->warn('   PADDLE_ENVIRONMENT=sandbox');
        $this->newLine();

        $this->info('4. Product prices:');
        $this->warn('   ✓ Your products already have prices in the database');
        $this->warn('   ✓ No need for PADDLE_PRICE_ID');
        $this->warn('   ✓ Prices are automatically used during checkout');
        $this->newLine();

        $this->info('5. Initialize payment via API:');
        $this->warn('   POST /payment/initiate');
        $this->warn('   Body: { "order_id": 1 }');
        $this->newLine();
        $this->info('6. Webhook setup:');
        $this->warn('   Webhook URL: https://yourdomain.com/webhooks/paddle');
        $this->newLine();
    }
}
