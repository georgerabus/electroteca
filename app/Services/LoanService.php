<?php

namespace App\Services;

use App\Models\LoanRequest;
use App\Models\Product;
use App\Models\ReputationTier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LoanService
{
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
     * Create a loan request and deduct deposit from user's wallet.
     */
    public function borrowProduct(User $user, Product $product, Carbon $periodFrom, Carbon $periodTo, ?string $details = null): LoanRequest
    {
        $check = $this->canBorrow($user, $product);
        
        if (!$check['can_borrow']) {
            throw new \RuntimeException('Cannot borrow product: ' . implode(', ', $check['reasons']));
        }

        $deposit = $check['deposit_required'];

        return DB::transaction(function () use ($user, $product, $periodFrom, $periodTo, $details, $deposit) {
            // Deduct deposit from wallet
            $transaction = $user->debitWallet($deposit, 'loan_deposit', [
                'product_id' => $product->id,
                'product_name' => $product->name,
            ]);

            // Create loan request (auto-approved)
            $loanRequest = LoanRequest::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'details' => $details,
                'status' => 'Approved',
                'deposit_amount' => $deposit,
                'approved_at' => now(),
            ]);

            // Decrease product stock
            $product->decrement('stock_quantity');

            Log::info('Loan request created', [
                'loan_request_id' => $loanRequest->id,
                'user_id' => $user->id,
                'product_id' => $product->id,
                'deposit' => $deposit,
            ]);

            return $loanRequest->fresh();
        });
    }

    private function getUserDiscountPercent(User $user): int
    {
        if (! Schema::hasTable('reputation_tiers')) {
            return 0;
        }

        return ReputationTier::discountForScore($user->getReputation());
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

            // Calculate refund (deposit - damage fee)
            if ($isDamaged) {
                $damageFee = $damageFee ?? ($deposit * 0.5); // Default 50% of deposit if not specified
                $refundAmount = max(0, $deposit - $damageFee);
            } else {
                $damageFee = 0;
                $refundAmount = $deposit;
            }

            // Refund to wallet
            if ($refundAmount > 0) {
                $user->creditWallet($refundAmount, 'loan_refund', [
                    'loan_request_id' => $loanRequest->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                ]);
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

            // Refund deposit
            $user->creditWallet($deposit, 'loan_rejection_refund', [
                'loan_request_id' => $loanRequest->id,
                'reason' => $reason,
            ]);

            // Restore product stock
            $loanRequest->product->increment('stock_quantity');

            // Update status
            $loanRequest->update([
                'status' => 'Rejected',
                'details' => $loanRequest->details . ($reason ? "\nRejection reason: {$reason}" : ''),
            ]);

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

            // Refund full amount
            $user->creditWallet($deposit, 'loan_return_refund', [
                'loan_request_id' => $loanRequest->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
            ]);

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

            return $loanRequest->fresh();
        });
    }
}
