<?php

namespace App\Services;

use App\Models\LoanRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReputationTier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LoanService
{
    public function __construct(
        private EscrowService $escrowService
    ) {
    }

    /**
     * Calculate the required deposit for a product loan.
     * Now uses 100% of product price (full amount).
     */
    public function calculateDeposit(Product $product, float $multiplier = 1.0): float
    {
        return round((float) $product->price * $multiplier, 2);
    }

    public function calculateDepositForUser(Product $product, User $user): float
    {
        $discountPercent = $this->getUserDiscountPercent($user);
        $multiplier = 1 - ($discountPercent / 100);

        return $this->calculateDeposit($product, $multiplier);
    }

    /**
     * Check if user can borrow a product (has enough balance and product is available).
     */
    public function canBorrow(User $user, Product $product): array
    {
        $deposit = $this->calculateDepositForUser($product, $user);
        $canBorrow = true;
        $reasons = [];

        if (!$product->is_available || $product->stock_quantity <= 0) {
            $canBorrow = false;
            $reasons[] = 'Product is not available';
        }

        if ($user->wallet_balance < $deposit) {
            $canBorrow = false;
            $reasons[] = "Insufficient balance. Required: {$deposit} CR, Available: {$user->wallet_balance} CR";
        }

        // Check if user already has an active loan for this product
        $activeLoan = LoanRequest::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->whereIn('status', ['Requested', 'Approved', 'Picked up', 'Late'])
            ->exists();

        if ($activeLoan) {
            $canBorrow = false;
            $reasons[] = 'You already have an active loan for this product';
        }

        return [
            'can_borrow' => $canBorrow,
            'deposit_required' => $deposit,
            'reasons' => $reasons,
        ];
    }

    /**
     * Create a loan request and hold deposit in escrow.
     */
    public function borrowProduct(
        User $user,
        Product $product,
        Carbon $periodFrom,
        Carbon $periodTo,
        ?string $details = null,
        array $orderData = []
    ): LoanRequest
    {
        $check = $this->canBorrow($user, $product);

        if (!$check['can_borrow']) {
            throw new \RuntimeException('Cannot borrow product: ' . implode(', ', $check['reasons']));
        }

        $deposit = $check['deposit_required'];

        return DB::transaction(function () use ($user, $product, $periodFrom, $periodTo, $details, $deposit, $orderData) {
            $order = $this->createOrderForLoans(
                user: $user,
                totalAmount: $deposit,
                currency: $product->currency ?? 'MDL',
                shippingAddress: $orderData['shipping_address'] ?? null,
                notes: $orderData['notes'] ?? $details
            );

            return $this->createLoanForOrder(
                order: $order,
                user: $user,
                product: $product,
                periodFrom: $periodFrom,
                periodTo: $periodTo,
                details: $details,
                deposit: $deposit
            );
        });
    }

    public function createOrderForLoans(
        User $user,
        float $totalAmount,
        string $currency,
        ?string $shippingAddress = null,
        ?string $notes = null
    ): Order {
        $sellerId = $this->resolveSellerId();

        return Order::create([
            'user_id' => $user->id,
            'seller_id' => $sellerId,
            'status' => 'processing',
            'total_amount' => $totalAmount,
            'currency' => $currency,
            'shipping_address' => $shippingAddress,
            'notes' => $notes,
        ]);
    }

    public function createLoanForOrder(
        Order $order,
        User $user,
        Product $product,
        Carbon $periodFrom,
        Carbon $periodTo,
        ?string $details,
        float $deposit
    ): LoanRequest {
        $escrow = $this->escrowService->holdFunds($order, $deposit);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $deposit,
            'subtotal' => $deposit,
        ]);

        $loanRequest = LoanRequest::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'escrow_transaction_id' => $escrow->id,
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'details' => $details,
            'status' => 'Approved',
            'deposit_amount' => $deposit,
            'approved_at' => now(),
        ]);

        $product->decrement('stock_quantity');

        Log::info('Loan request created', [
            'loan_request_id' => $loanRequest->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'deposit' => $deposit,
        ]);

        return $loanRequest->fresh();
    }

    private function getUserDiscountPercent(User $user): int
    {
        if (! Schema::hasTable('reputation_tiers')) {
            return 0;
        }

        return ReputationTier::discountForScore($user->getReputation());
    }

    private function resolveSellerId(): int
    {
        $seller = User::where('admin', true)->orderBy('id')->first();

        if (!$seller) {
            throw new \RuntimeException('No admin seller user found to associate with the order.');
        }

        return $seller->id;
    }

    /**
     * Return a product and refund the deposit (minus damage fee if applicable).
     */
    public function returnProduct(LoanRequest $loanRequest, bool $isDamaged = false, ?float $damageFee = null): LoanRequest
    {
        if (!in_array($loanRequest->status, ['Picked up', 'Late'])) {
            throw new \RuntimeException('Product must be picked up before it can be returned');
        }

        return DB::transaction(function () use ($loanRequest, $isDamaged, $damageFee) {
            $user = $loanRequest->user;
            $product = $loanRequest->product;
            $deposit = $loanRequest->deposit_amount;
            $order = $loanRequest->order;
            $escrow = $loanRequest->escrowTransaction
                ?? ($order ? $this->escrowService->getActiveEscrow($order) : null);

            // Calculate refund (deposit - damage fee)
            if ($isDamaged) {
                $damageFee = $damageFee ?? ($deposit * 0.5); // Default 50% of deposit if not specified
                if ($escrow) {
                    $escrowResult = $this->escrowService->deductForDamage(
                        $escrow,
                        (float) $damageFee,
                        'Loan return damage'
                    );
                    $refundAmount = (float) ($escrowResult['borrower_refund'] ?? 0);
                } else {
                    $refundAmount = max(0, $deposit - $damageFee);
                    if ($refundAmount > 0) {
                        $user->creditWallet($refundAmount, 'loan_refund', [
                            'loan_request_id' => $loanRequest->id,
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                        ]);
                    }
                }
            } else {
                $damageFee = 0;
                $refundAmount = $deposit;
                if ($escrow) {
                    $this->escrowService->refundToBorrower($escrow, 'on_time_return');
                } else {
                    $user->creditWallet($refundAmount, 'loan_refund', [
                        'loan_request_id' => $loanRequest->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                    ]);
                }
            }

            // Update loan request
            $loanRequest->update([
                'status' => $isDamaged ? 'Defective' : 'Returned',
                'returned_at' => now(),
                'damage_fee' => $damageFee,
                'refund_amount' => $refundAmount,
            ]);

            // Restore product stock
            $product->increment('stock_quantity');

            Log::info('Product returned', [
                'loan_request_id' => $loanRequest->id,
                'user_id' => $user->id,
                'product_id' => $product->id,
                'refund_amount' => $refundAmount,
                'damage_fee' => $damageFee,
            ]);

            if ($order) {
                $this->refreshOrderStatusForLoans($order);
            }

            return $loanRequest->fresh();
        });
    }

    /**
     * Approve a loan request (admin action).
     */
    public function approveLoan(LoanRequest $loanRequest): LoanRequest
    {
        if ($loanRequest->status !== 'Requested') {
            throw new \RuntimeException('Only requested loans can be approved');
        }

        $loanRequest->update([
            'status' => 'Approved',
            'approved_at' => now(),
        ]);

        return $loanRequest->fresh();
    }

    /**
     * Mark loan as picked up (admin action).
     */
    public function markAsPickedUp(LoanRequest $loanRequest): LoanRequest
    {
        if ($loanRequest->status !== 'Approved') {
            throw new \RuntimeException('Only approved loans can be marked as picked up');
        }

        $loanRequest->update([
            'status' => 'Picked up',
            'picked_up_at' => now(),
        ]);

        return $loanRequest->fresh();
    }

    /**
     * Reject a loan request and refund the deposit.
     */
    public function rejectLoan(LoanRequest $loanRequest, ?string $reason = null): LoanRequest
    {
        if ($loanRequest->status !== 'Requested') {
            throw new \RuntimeException('Only requested loans can be rejected');
        }

        return DB::transaction(function () use ($loanRequest, $reason) {
            $user = $loanRequest->user;
            $deposit = $loanRequest->deposit_amount;
            $order = $loanRequest->order;
            $escrow = $loanRequest->escrowTransaction
                ?? ($order ? $this->escrowService->getActiveEscrow($order) : null);

            if ($escrow) {
                $this->escrowService->refundToBorrower($escrow, 'loan_rejected');
            } else {
                // Fallback for legacy loans without orders.
                $user->creditWallet($deposit, 'loan_rejection_refund', [
                    'loan_request_id' => $loanRequest->id,
                    'reason' => $reason,
                ]);
            }

            // Restore product stock
            $loanRequest->product->increment('stock_quantity');

            // Update status
            $loanRequest->update([
                'status' => 'Rejected',
                'details' => ($loanRequest->details ?? '') . ($reason ? "\nRejection reason: {$reason}" : ''),
            ]);

            if ($order) {
                $this->refreshOrderStatusForLoans($order);
            }

            return $loanRequest->fresh();
        });
    }

    /**
     * Request to return a product (user action).
     */
    public function requestReturn(LoanRequest $loanRequest): LoanRequest
    {
        if (!in_array($loanRequest->status, ['Approved', 'Picked up', 'Late'])) {
            throw new \RuntimeException('Only approved or picked up loans can be requested for return');
        }

        $loanRequest->update([
            'status' => 'Return Requested',
        ]);

        Log::info('Return requested', [
            'loan_request_id' => $loanRequest->id,
            'user_id' => $loanRequest->user_id,
            'product_id' => $loanRequest->product_id,
        ]);

        return $loanRequest->fresh();
    }

    /**
     * Approve return and refund full amount (admin action).
     */
    public function approveReturn(LoanRequest $loanRequest): LoanRequest
    {
        if ($loanRequest->status !== 'Return Requested') {
            throw new \RuntimeException('Only return requested loans can be approved for return');
        }

        return DB::transaction(function () use ($loanRequest) {
            $user = $loanRequest->user;
            $product = $loanRequest->product;
            $deposit = $loanRequest->deposit_amount;
            $order = $loanRequest->order;
            $escrow = $loanRequest->escrowTransaction
                ?? ($order ? $this->escrowService->getActiveEscrow($order) : null);

            if ($escrow) {
                $this->escrowService->refundToBorrower($escrow, 'on_time_return');
            } else {
                // Fallback for legacy loans without orders/escrow.
                $user->creditWallet($deposit, 'loan_return_refund', [
                    'loan_request_id' => $loanRequest->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                ]);
            }

            // Update loan request
            $loanRequest->update([
                'status' => 'Returned',
                'returned_at' => now(),
                'refund_amount' => $deposit,
                'damage_fee' => 0,
            ]);

            // Restore product stock
            $product->increment('stock_quantity');

            Log::info('Return approved', [
                'loan_request_id' => $loanRequest->id,
                'user_id' => $user->id,
                'product_id' => $product->id,
                'refund_amount' => $deposit,
            ]);

            if ($order) {
                $this->refreshOrderStatusForLoans($order);
            }

            return $loanRequest->fresh();
        });
    }

    private function refreshOrderStatusForLoans(Order $order): void
    {
        $statuses = $order->loanRequests()->pluck('status')->all();

        if (empty($statuses)) {
            return;
        }

        $activeStatuses = ['Requested', 'Approved', 'Picked up', 'Late', 'Return Requested'];
        $hasActive = !empty(array_intersect($statuses, $activeStatuses));

        if ($hasActive) {
            $order->update(['status' => 'processing']);
            return;
        }

        $terminalStatuses = ['Rejected', 'Cancelled'];
        $allRejected = empty(array_diff($statuses, $terminalStatuses));

        $order->update(['status' => $allRejected ? 'cancelled' : 'completed']);
    }
}
