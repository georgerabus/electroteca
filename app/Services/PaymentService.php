<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function __construct(
        private WalletService $walletService
    ) {}

    /**
     * Entry point: wallet only, paddle only, or hybrid.
     */
    public function initializePayment(
        User $user,
        Order $order,
        string $paymentMethod = 'paddle',
        array $options = []
    ): array {
        if ($paymentMethod === 'wallet') {
            return $this->initializeWalletPayment($user, $order);
        }

        if ($paymentMethod === 'hybrid') {
            return $this->initializeHybridPayment($user, $order, $options);
        }

        return $this->initializePaddlePayment($user, $order);
    }

    /**
     * Wallet-only payment.
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

            $this->walletService->deductCredits($user, $order->total_amount, 'purchase', [
                'order_id' => $order->id,
            ]);

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

            $order->update(['status' => 'completed']);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'payment_method' => 'wallet',
                'message' => 'Payment completed using wallet credits',
                'new_balance' => $this->walletService->getBalance($user),
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Hybrid payment (wallet + paddle).
     *
     * IMPORTANT: Paddle charges based on items you send.
     * For hybrid, this implementation charges a SINGLE line item for the remaining balance.
     */
    private function initializeHybridPayment(User $user, Order $order, array $options = []): array
    {
        try {
            $walletAmount = min(
                (float) ($options['wallet_amount'] ?? $this->walletService->getBalance($user)),
                (float) $order->total_amount
            );

            if ($walletAmount < 0) $walletAmount = 0;

            $paddleAmount = (float) $order->total_amount - (float) $walletAmount;

            // Wallet covers everything
            if ($paddleAmount <= 0) {
                return $this->initializeWalletPayment($user, $order);
            }

            // Deduct wallet first
            if ($walletAmount > 0) {
                $this->walletService->deductCredits($user, $walletAmount, 'purchase_partial', [
                    'order_id' => $order->id,
                    'paddle_amount_remaining' => $paddleAmount,
                ]);
            }

            // Charge remaining via Paddle as one line
            $paddleResult = $this->initializePaddlePaymentForAmount(
                user: $user,
                order: $order,
                amount: $paddleAmount,
                metadata: [
                    'payment_method' => 'hybrid',
                    'wallet_amount' => $walletAmount,
                ],
                singleBalanceLineItem: true
            );

            if (!$paddleResult['success']) {
                // Refund wallet deduction if paddle init fails
                if ($walletAmount > 0) {
                    $this->walletService->refundCredits($user, $walletAmount, [
                        'order_id' => $order->id,
                        'reason' => 'paddle_initialization_failed',
                    ]);
                }
                return $paddleResult;
            }

            // Update payment record
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
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function initializePaddlePayment(User $user, Order $order): array
    {
        return $this->initializePaddlePaymentForAmount(
            user: $user,
            order: $order,
            amount: (float) $order->total_amount,
            metadata: ['payment_method' => 'paddle'],
            singleBalanceLineItem: false
        );
    }

    /**
     * Create a Paddle Billing transaction (POST /transactions).
     * Uses NON-catalog items (price + product) so you don't need to create products/prices in Paddle. :contentReference[oaicite:5]{index=5}
     *
     * If singleBalanceLineItem=true, charges one item for $amount (used for hybrid).
     */
    private function initializePaddlePaymentForAmount(
        User $user,
        Order $order,
        float $amount,
        array $metadata = [],
        bool $singleBalanceLineItem = false
    ): array {
        try {
            $apiKey = (string) config('services.paddle.key');
            if ($apiKey === '') {
                throw new Exception('Paddle API key not configured');
            }

            $currency = strtoupper($order->currency ?? 'USD');

            $items = $singleBalanceLineItem
                ? [$this->makeNonCatalogItem(
                    name: 'Order balance #' . $order->id,
                    description: 'Remaining balance for order ' . $order->id,
                    unitAmountMinor: $this->toMinorUnits($amount),
                    currency: $currency,
                    quantity: 1
                )]
                : $this->buildItemsFromOrder($order, $currency);

            if (empty($items)) {
                throw new Exception('No items in order');
            }

            // Important: set checkout.url so you don't need a "Default Payment Link" in Paddle dashboard. :contentReference[oaicite:6]{index=6}
            $checkoutBaseUrl = rtrim((string) config('services.paddle.checkout_url', config('app.url') . '/pay'), '/');

            $payload = [
                'items' => $items,
                'collection_mode' => 'automatic',
                'currency_code' => $currency,
                'custom_data' => array_merge([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                ], $metadata),
                'checkout' => [
                    'url' => $checkoutBaseUrl,
                ],
            ];

            $data = $this->paddlePost('/transactions', $payload);

            $txnId = $data['id'] ?? null; // txn_...
            if (!$txnId) {
                throw new Exception('Invalid Paddle response: missing data.id');
            }

            $checkoutUrl = $data['checkout']['url'] ?? null;

            $payment = Payment::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'payment_id' => $txnId, // store txn_... here
                'gateway' => 'paddle',
                'payment_method' => $metadata['payment_method'] ?? 'paddle',
                'amount' => (float) $order->total_amount,
                'paddle_amount' => (float) $amount,
                'wallet_amount' => (float) $order->total_amount - (float) $amount,
                'currency' => $currency,
                'status' => 'pending',
                'metadata' => array_merge([
                    'paddle_transaction_id' => $txnId,
                    'checkout_url' => $checkoutUrl,
                    'items_count' => count($items),
                ], $metadata),
            ]);

            return [
                'success' => true,
                'url' => $checkoutUrl,
                'payment_id' => $payment->id, // your DB row id
                'paddle_transaction_id' => $txnId,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Wallet top-up via Paddle transaction (non-catalog).
     */
    public function initializeWalletTopup(User $user, float $amount): array
    {
        try {
            $currency = 'USD';

            $payload = [
                'items' => [
                    $this->makeNonCatalogItem(
                        name: 'Wallet Top-up Credits',
                        description: 'Wallet top-up',
                        unitAmountMinor: $this->toMinorUnits($amount),
                        currency: $currency,
                        quantity: 1
                    ),
                ],
                'collection_mode' => 'automatic',
                'currency_code' => $currency,
                'custom_data' => [
                    'user_id' => $user->id,
                    'transaction_type' => 'wallet_topup',
                    'topup_amount' => $amount,
                ],
            ];

            $data = $this->paddlePost('/transactions', $payload);

            logger()->info('PADDLE tx create response', [
                'id' => $data['id'] ?? null,
                'status' => $data['status'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'address_id' => $data['address_id'] ?? null,
            ]);

            $txnId = $data['id'] ?? null;
            if (!$txnId) {
                throw new Exception('Invalid Paddle response: missing data.id');
            }

            $checkoutUrl = $data['checkout']['url'] ?? null; 

            $payment = Payment::create([
                'user_id' => $user->id,
                'order_id' => null,
                'payment_id' => $txnId,
                'gateway' => 'paddle',
                'payment_method' => 'wallet_topup',
                'amount' => $amount,
                'paddle_amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
                'metadata' => [
                    'paddle_transaction_id' => $txnId,
                    'checkout_url' => $checkoutUrl,
                    'transaction_type' => 'wallet_topup',
                    'topup_amount' => $amount,
                ],
            ]);

            return [
                'success' => true,
                'transaction_id' => $txnId,
                'payment_id' => $payment->id,
            ];
        } catch (Exception $e) {
            logger()->error('Wallet topup init failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function completeWalletTopup(Payment $payment): array
    {
        try {
            $topupAmount = $payment->metadata['topup_amount'] ?? null;
            if (!$topupAmount) {
                throw new Exception('Invalid wallet top-up payment');
            }

            $this->walletService->addCredits($payment->user, (float) $topupAmount, 'paddle_topup', [
                'payment_id' => $payment->id,
            ]);

            $payment->markCompleted();

            return [
                'success' => true,
                'topup_amount' => (float) $topupAmount,
                'new_balance' => $this->walletService->getBalance($payment->user),
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function completeOrder(?Order $order): void
    {
        if ($order) {
            $order->update(['status' => 'completed']);
        }
    }

    /**
     * Paddle webhook handler (Billing): transaction.completed. :contentReference[oaicite:7]{index=7}
     */
    public function handlePaddleWebhook(array $payload): array
    {
        $eventType = $payload['event_type'] ?? null;

        if ($eventType !== 'transaction.completed') {
            return ['success' => true];
        }

        $txnId = $payload['data']['id'] ?? null; // txn_...
        if (!$txnId) {
            return ['success' => false, 'error' => 'Missing transaction id'];
        }

        $payment = Payment::where('payment_id', $txnId)
            ->where('gateway', 'paddle')
            ->first();

        if (!$payment) {
            return ['success' => false, 'error' => 'Payment not found'];
        }

        if ($payment->isCompleted()) {
            return ['success' => true, 'message' => 'Payment already completed'];
        }

        $customData = $payload['data']['custom_data'] ?? [];

        $isWalletTopup = ($customData['transaction_type'] ?? null) === 'wallet_topup'
            || ($payment->payment_method ?? null) === 'wallet_topup';

        if ($isWalletTopup) {
            return $this->completeWalletTopup($payment);
        }

        $payment->markCompleted();
        $this->completeOrder($payment->order);

        return ['success' => true, 'message' => 'Payment completed'];
    }

    /**
     * Refund (Billing) is done via Adjustments, not /transactions/{id}/refund. :contentReference[oaicite:8]{index=8}
     * This creates a FULL refund adjustment.
     */
    public function refundPayment(Payment $payment, ?string $reason = null): array
    {
        try {
            if (($payment->gateway ?? null) !== 'paddle') {
                throw new Exception('Not a Paddle payment');
            }

            $payload = [
                'action' => 'refund',
                'reason' => $reason ?: 'customer_request',
                'transaction_id' => $payment->payment_id, // txn_...
                'type' => 'full',
            ];

            $data = $this->paddlePost('/adjustments', $payload);

            $payment->update([
                'status' => 'refund_requested',
                'refunded_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'adjustment_id' => $data['id'] ?? null, // adj_...
                ]),
            ]);

            return ['success' => true, 'message' => 'Refund requested'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // -----------------------------
    // Helpers
    // -----------------------------

private function paddleBaseUrl(): string
{
    $env = strtolower(trim((string) config('services.paddle.environment', 'sandbox')));

    return $env === 'sandbox'
        ? 'https://sandbox-api.paddle.com'
        : 'https://api.paddle.com';
}

private function paddlePost(string $path, array $payload): array
{
    $apiKey = (string) config('services.paddle.key');
    if ($apiKey === '') {
        throw new Exception('Paddle API key not configured');
    }

    $url = $this->paddleBaseUrl() . $path;

    $res = Http::withToken($apiKey)
        ->acceptJson()
        ->withHeaders([
            'Paddle-Version' => '1',
        ])
        ->post($url, $payload);

    // IMPORTANT: vezi exact ce întoarce Paddle
    logger()->info('PADDLE raw response', [
        'url' => $url,
        'status' => $res->status(),
        'body' => $res->body(),
    ]);

    if (!$res->successful()) {
        throw new Exception('Paddle API error: ' . $res->body());
    }

    $json = $res->json();
    if (!is_array($json) || !array_key_exists('data', $json) || !is_array($json['data'])) {
        throw new Exception('Paddle API unexpected response: ' . $res->body());
    }

    return $json['data'];
}

    private function buildItemsFromOrder(Order $order, string $currency): array
    {
        $items = [];

        foreach ($order->items as $item) {
            $qty = max(1, (int) $item->quantity);

            $items[] = $this->makeNonCatalogItem(
                name: (string) $item->product->name,
                description: (string) $item->product->name,
                unitAmountMinor: $this->toMinorUnits((float) $item->product->price),
                currency: strtoupper($item->product->currency ?? $currency),
                quantity: $qty
            );
        }

        return $items;
    }

    /**
     * Non-catalog price + non-catalog product (no pri_/pro_ needed). :contentReference[oaicite:11]{index=11}
     */
    private function makeNonCatalogItem(
        string $name,
        string $description,
        int $unitAmountMinor,
        string $currency,
        int $quantity
    ): array {
        return [
            'quantity' => $quantity,
            'price' => [
                'name' => $name,
                'description' => $description,
                'unit_price' => [
                    'amount' => (string) $unitAmountMinor,
                    'currency_code' => $currency,
                ],
                'product' => [
                    'name' => $name,
                    // Pick the right tax category for what you sell
                    'tax_category' => 'standard',
                ],
            ],
        ];
    }

    private function toMinorUnits(float $amount): int
    {
        // Works for USD/EUR/etc (2 decimals). If you use 0/3-decimal currencies, adjust this.
        return (int) round($amount * 100);
    }
    
}
