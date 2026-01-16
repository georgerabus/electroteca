<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;

class PaymentService
{
    private WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Initialize payment - can be wallet only, paddle only, or hybrid
     * $paymentMethod can be: 'paddle', 'wallet', or 'hybrid'
     * For hybrid: pass array with 'use_wallet' => true/amount
     */
    public function initializePayment(
        User $user,
        Order $order,
        string $paymentMethod = 'paddle',
        array $options = []
    ): array {
        // Determine how to handle the payment
        if ($paymentMethod === 'wallet') {
            return $this->initializeWalletPayment($user, $order);
        } elseif ($paymentMethod === 'hybrid') {
            return $this->initializeHybridPayment($user, $order, $options);
        }

        // Default to paddle
        return $this->initializePaddlePayment($user, $order);
    }

    /**
     * Initialize wallet-only payment
     */
    private function initializeWalletPayment(User $user, Order $order): array
    {
        try {
            if (!$this->walletService->hasSufficientBalance($user, $order->total_amount)) {
                return [
                    'success' => false,
                    'error' => 'Insufficient wallet balance',
                    'required' => (float) $order->total_amount,
                    'balance' => $this->walletService->getBalance($user),
                ];
            }

            // Deduct from wallet
            $this->walletService->deductCredits($user, $order->total_amount, 'purchase', [
                'order_id' => $order->id,
            ]);

            // Create payment record with wallet as method
            $payment = Payment::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'payment_id' => 'wallet_' . uniqid(),
                'gateway' => 'wallet',
                'payment_method' => 'wallet',
                'amount' => $order->total_amount,
                'wallet_amount' => $order->total_amount,
                'paddle_amount' => 0,
                'currency' => $order->currency ?? 'USD',
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => [
                    'payment_type' => 'wallet_only',
                    'order_id' => $order->id,
                ],
            ]);

            // Mark order as completed
            $order->update(['status' => 'completed']);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'payment_method' => 'wallet',
                'message' => 'Payment completed using wallet credits',
                'new_balance' => $this->walletService->getBalance($user),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initialize hybrid payment (wallet + paddle)
     * $options should contain wallet_amount to use, rest paid via paddle
     */
    private function initializeHybridPayment(User $user, Order $order, array $options = []): array
    {
        try {
            $walletAmount = min(
                $options['wallet_amount'] ?? $this->walletService->getBalance($user),
                $order->total_amount
            );

            if ($walletAmount < 0) {
                $walletAmount = 0;
            }

            $paddleAmount = $order->total_amount - $walletAmount;

            // If wallet covers everything, process as wallet payment
            if ($paddleAmount <= 0) {
                return $this->initializeWalletPayment($user, $order);
            }

            // Deduct wallet amount if specified
            if ($walletAmount > 0) {
                $this->walletService->deductCredits($user, $walletAmount, 'purchase_partial', [
                    'order_id' => $order->id,
                    'paddle_amount_remaining' => $paddleAmount,
                ]);
            }

            // Initialize paddle for remaining amount
            $paddleResult = $this->initializePaddlePaymentForAmount($user, $order, $paddleAmount, [
                'payment_method' => 'hybrid',
                'wallet_amount' => $walletAmount,
            ]);

            if (!$paddleResult['success']) {
                // Refund wallet deduction if paddle fails
                if ($walletAmount > 0) {
                    $this->walletService->refundCredits($user, $walletAmount, [
                        'order_id' => $order->id,
                        'reason' => 'paddle_initialization_failed',
                    ]);
                }
                return $paddleResult;
            }

            // Update payment record with hybrid details
            $payment = Payment::find($paddleResult['payment_id']);
            if ($payment) {
                $payment->update([
                    'payment_method' => 'hybrid',
                    'wallet_amount' => $walletAmount,
                    'paddle_amount' => $paddleAmount,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'payment_method' => 'hybrid',
                        'wallet_amount' => $walletAmount,
                        'paddle_amount' => $paddleAmount,
                    ]),
                ]);
            }

            return array_merge($paddleResult, [
                'payment_method' => 'hybrid',
                'wallet_amount_used' => $walletAmount,
                'paddle_amount_due' => $paddleAmount,
                'new_wallet_balance' => $this->walletService->getBalance($user),
            ]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initialize Paddle payment with dynamic product prices from database
     */
    private function initializePaddlePayment(User $user, Order $order): array
    {
        return $this->initializePaddlePaymentForAmount($user, $order, $order->total_amount, [
            'payment_method' => 'paddle',
        ]);
    }

    /**
     * Initialize Paddle payment for a specific amount (for hybrid payments)
     */
    private function initializePaddlePaymentForAmount(User $user, Order $order, float $amount, array $metadata = []): array
    {
        try {
            $paddleApiKey = config('services.paddle.key');
            $paddleEnvironment = config('services.paddle.environment', 'sandbox');

            if (!$paddleApiKey) {
                throw new Exception('Paddle API key not configured');
            }

            // Build line items from actual product prices in database
            $lineItems = [];
            foreach ($order->items as $item) {
                $lineItems[] = [
                    'price_id' => $this->getOrCreatePaddlePrice($item->product, $paddleApiKey, $paddleEnvironment),
                    'quantity' => $item->quantity,
                ];
            }

            if (empty($lineItems)) {
                throw new Exception('No items in order');
            }

            // Prepare Paddle checkout with actual order data
            $paddleData = [
                'items' => $lineItems,
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                'custom_data' => array_merge([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                ], $metadata),
                'return_url' => config('app.url') . '/payment/success',
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://' . ($paddleEnvironment === 'sandbox' ? 'sandbox-api' : 'api') . '.paddle.com/checkouts',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $paddleApiKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode($paddleData),
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception('Paddle API error: ' . $response);
            }

            $paddleResponse = json_decode($response, true);

            if (!isset($paddleResponse['data']['checkout_id'])) {
                throw new Exception('Invalid Paddle response');
            }

            // Create payment record
            $payment = Payment::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'payment_id' => $paddleResponse['data']['checkout_id'],
                'gateway' => 'paddle',
                'payment_method' => $metadata['payment_method'] ?? 'paddle',
                'amount' => $order->total_amount,
                'paddle_amount' => $amount,
                'wallet_amount' => $order->total_amount - $amount,
                'currency' => $order->currency ?? 'USD',
                'status' => 'pending',
                'metadata' => array_merge([
                    'checkout_id' => $paddleResponse['data']['checkout_id'],
                    'items_count' => count($lineItems),
                ], $metadata),
            ]);

            return [
                'success' => true,
                'url' => $paddleResponse['data']['checkout_url'],
                'payment_id' => $payment->id,
                'checkout_id' => $paddleResponse['data']['checkout_id'],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get or create a Paddle price for a product
     * Uses the product's database price, not a pre-configured price ID
     */
    private function getOrCreatePaddlePrice($product, $paddleApiKey, $paddleEnvironment)
    {
        // For sandbox mode, we can use the amount directly
        // Store the price ID mapped to product in metadata for later reference
        $cacheKey = 'paddle_price_' . $product->id . '_' . $product->currency;
        
        // Try to get from cache/metadata first
        $priceId = Cache::get($cacheKey);
        
        if ($priceId) {
            return $priceId;
        }

        // For now, return a generic price structure
        // In production, you would create Paddle prices programmatically
        // For sandbox testing, Paddle allows dynamic amounts
        $priceData = [
            'description' => $product->name,
            'amount' => (int)($product->price * 100), // Convert to cents
            'currency_code' => strtoupper($product->currency ?? 'USD'),
        ];

        // Cache for 24 hours
        Cache::put($cacheKey, $priceData, 86400);

        return $priceData;
    }

    /**
     * Initialize wallet top-up via Paddle
     */
    public function initializeWalletTopup(User $user, float $amount): array
    {
        try {
            $paddleApiKey = config('services.paddle.key');
            $paddleEnvironment = config('services.paddle.environment', 'sandbox');

            if (!$paddleApiKey) {
                throw new Exception('Paddle API key not configured');
            }

            // Create a virtual "wallet top-up" item for Paddle
            $paddleData = [
                'items' => [
                    [
                        'price_id' => [
                            'description' => 'Wallet Top-up Credits',
                            'amount' => (int)($amount * 100), // Convert to cents
                            'currency_code' => 'USD',
                        ],
                        'quantity' => 1,
                    ],
                ],
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                'custom_data' => [
                    'user_id' => $user->id,
                    'transaction_type' => 'wallet_topup',
                    'topup_amount' => $amount,
                ],
                'return_url' => config('app.url') . '/wallet-topup/success',
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://' . ($paddleEnvironment === 'sandbox' ? 'sandbox-api' : 'api') . '.paddle.com/checkouts',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $paddleApiKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode($paddleData),
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception('Paddle API error: ' . $response);
            }

            $paddleResponse = json_decode($response, true);

            if (!isset($paddleResponse['data']['checkout_id'])) {
                throw new Exception('Invalid Paddle response');
            }

            // Create a payment record for wallet top-up
            $payment = Payment::create([
                'user_id' => $user->id,
                'order_id' => null,
                'payment_id' => $paddleResponse['data']['checkout_id'],
                'gateway' => 'paddle',
                'payment_method' => 'wallet_topup',
                'amount' => $amount,
                'paddle_amount' => $amount,
                'currency' => 'USD',
                'status' => 'pending',
                'metadata' => [
                    'checkout_id' => $paddleResponse['data']['checkout_id'],
                    'transaction_type' => 'wallet_topup',
                    'topup_amount' => $amount,
                ],
            ]);

            return [
                'success' => true,
                'url' => $paddleResponse['data']['checkout_url'],
                'payment_id' => $payment->id,
                'checkout_id' => $paddleResponse['data']['checkout_id'],
                'topup_amount' => $amount,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Complete wallet top-up after successful Paddle payment
     */
    public function completeWalletTopup(Payment $payment): array
    {
        try {
            if (!$payment->metadata['topup_amount'] ?? null) {
                throw new Exception('Invalid wallet top-up payment');
            }

            $topupAmount = $payment->metadata['topup_amount'];
            $this->walletService->addCredits($payment->user, $topupAmount, 'paddle_topup', [
                'payment_id' => $payment->id,
            ]);

            $payment->markCompleted();

            return [
                'success' => true,
                'topup_amount' => $topupAmount,
                'new_balance' => $this->walletService->getBalance($payment->user),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Complete order after successful payment
     */
    private function completeOrder(Order $order = null): void
    {
        if ($order) {
            $order->update(['status' => 'completed']);
        }
    }

    /**
     * Handle Paddle webhook
     */
    public function handlePaddleWebhook(array $data): array
    {
        $eventType = $data['event_type'] ?? null;

        if ($eventType === 'checkout.completed') {
            $customData = $data['data']['custom_data'] ?? [];
            $checkoutId = $data['data']['checkout_id'] ?? null;

            $payment = Payment::where('payment_id', $checkoutId)
                ->where('gateway', 'paddle')
                ->first();

            if (!$payment) {
                return ['success' => false, 'error' => 'Payment not found'];
            }

            // Handle wallet top-up
            if (($customData['transaction_type'] ?? null) === 'wallet_topup') {
                return $this->completeWalletTopup($payment);
            }

            // Handle regular order payment
            $payment->markCompleted();
            $this->completeOrder($payment->order);
            return ['success' => true, 'message' => 'Payment completed'];
        }

        return ['success' => true];
    }

    /**
     * Process refund
     */
    public function refundPayment(Payment $payment, string $reason = null): array
    {
        try {
            return $this->refundPaddlePayment($payment, $reason);
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Refund Paddle payment
     */
    private function refundPaddlePayment(Payment $payment, string $reason = null): array
    {
        $paddleApiKey = config('services.paddle.key');
        $paddleEnvironment = config('services.paddle.environment', 'sandbox');

        if (!$paddleApiKey) {
            throw new Exception('Paddle API key not configured');
        }

        $refundData = [
            'reason' => $reason ?? 'customer_request',
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://' . ($paddleEnvironment === 'sandbox' ? 'sandbox-api' : 'api') . '.paddle.com/transactions/' . $payment->payment_id . '/refund',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $paddleApiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($refundData),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Refund failed'];
    }
}
