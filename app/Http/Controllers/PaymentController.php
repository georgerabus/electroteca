<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {
    }

    /**
     * Initiate payment - supports wallet, paddle, or hybrid
     */
    public function initiate(Request $request, Order $order)
    {
        $user = $request->user();

        if (!$user || $order->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $paymentMethod = $request->input('payment_method', 'paddle'); // 'paddle', 'wallet', or 'hybrid'
        $options = [];

        // For hybrid payments, allow specifying wallet amount
        if ($paymentMethod === 'hybrid' && $request->has('wallet_amount')) {
            $options['wallet_amount'] = (float) $request->input('wallet_amount');
        }

        $result = $this->paymentService->initializePayment($user, $order, $paymentMethod, $options);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Payment success callback
     */
    public function success(Request $request)
    {
        $checkoutId = $request->input('checkout_id');

        $payment = Payment::where('payment_id', $checkoutId)->first();

        if ($payment && $payment->isCompleted()) {
            return Inertia::render('payment-success', [
                'payment' => $payment,
                'order' => $payment->order,
            ]);
        }

        return Inertia::render('payment-pending', [
            'payment' => $payment,
        ]);
    }

    /**
     * Payment cancel callback
     */
    public function cancel(Request $request)
    {
        $checkoutId = $request->input('checkout_id');

        $payment = Payment::where('payment_id', $checkoutId)->first();

        return Inertia::render('payment-cancelled', [
            'payment' => $payment,
        ]);
    }

    /**
     * Handle Paddle webhook
     */
    public function paddleWebhook(Request $request)
    {
        if (!$this->verifyPaddleSignature($request)) {
            return response()->json(['error' => 'Invalid Paddle signature'], 401);
        }

        $result = $this->paymentService->handlePaddleWebhook($request->all());
        return response()->json($result);
    }

    /**
     * Get payment status
     */
    public function status(Request $request, Payment $payment)
    {
        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => $payment->status,
            'completed_at' => $payment->completed_at,
            'error_message' => $payment->error_message,
        ]);
    }

    /**
     * Refund payment (admin)
     */
    public function refund(Request $request, Payment $payment)
    {
        if (!$request->user()->admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reason = $request->input('reason');
        $result = $this->paymentService->refundPayment($payment, $reason);

        return response()->json($result);
    }

    /**
     * Initiate wallet top-up via Paddle
     */
    public function initiateWalletTopup(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:10000',
        ]);

        $result = $this->paymentService->initializeWalletTopup($user, (float) $validated['amount']);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Wallet top-up success callback
     */
    public function walletTopupSuccess(Request $request)
    {
        $checkoutId = $request->input('checkout_id');

        $payment = Payment::where('payment_id', $checkoutId)
            ->where('payment_method', 'wallet_topup')
            ->first();

        if ($payment && $payment->isCompleted()) {
            return Inertia::render('wallet-topup-success', [
                'payment' => $payment,
                'topup_amount' => $payment->metadata['topup_amount'] ?? 0,
            ]);
        }

        return Inertia::render('wallet-topup-pending', [
            'payment' => $payment,
        ]);
    }

    private function verifyPaddleSignature(Request $request): bool
    {
        $secret = (string) config('services.paddle.webhook_secret');
        if ($secret === '') {
            return !app()->environment('production');
        }

        $signatureHeader = $request->header('Paddle-Signature');
        if (!$signatureHeader) {
            return false;
        }

        $parts = [];
        foreach (explode(';', $signatureHeader) as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $segment, 2), 2, null);
            if ($key && $value) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['ts'] ?? null;
        $signature = $parts['h1'] ?? ($parts['v1'] ?? null);
        if (!$timestamp || !$signature) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $signedPayload = $timestamp . ':' . $request->getContent();
        $computed = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($computed, $signature);
    }
}
