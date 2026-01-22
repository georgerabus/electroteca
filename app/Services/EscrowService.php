<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\EscrowTransaction;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Collection;
use Exception;
use RuntimeException;

class EscrowService
{
    private WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Initialize escrow for an order when payment is completed
     * Holds funds in escrow from the borrower's wallet
     */
    public function holdFunds(Order $order, float $amount, int $inspectionPeriodDays = 7): EscrowTransaction
    {
        if ($amount <= 0) {
            throw new Exception('Escrow amount must be positive.');
        }

        $borrower = $order->user;

        // Check sufficient balance
        if (!$this->walletService->hasSufficientBalance($borrower, $amount)) {
            throw new RuntimeException('Insufficient wallet balance for escrow hold.');
        }

        // Create a debit transaction (funds are held, not removed immediately)
        $walletTransaction = $borrower->walletTransactions()->create([
            'amount' => $amount,
            'type' => 'debit',
            'reason' => 'escrow_hold',
            'meta' => [
                'order_id' => $order->id,
                'type' => 'escrow_hold',
            ],
        ]);

        // Reduce wallet balance
        $borrower->decrement('wallet_balance', $amount);

        // Create escrow transaction
        $escrowTransaction = $order->escrowTransactions()->create([
            'wallet_transaction_id' => $walletTransaction->id,
            'amount' => $amount,
            'currency' => $order->currency,
            'status' => 'held',
            'held_at' => now(),
            'reason_code' => 'order_purchase',
            'notes' => "Escrow hold for order {$order->order_number}",
            'metadata' => [
                'borrower_id' => $borrower->id,
                'seller_id' => $order->seller_id,
                'inspection_period_days' => $inspectionPeriodDays,
            ],
        ]);

        // Set return deadline
        if (!$order->return_deadline) {
            $order->return_deadline = now()->addDays($inspectionPeriodDays);
        }

        $order->escrow_amount = (float) ($order->escrow_amount ?? 0) + $amount;
        $order->escrow_status = 'held';
        $order->inspection_period_days = $inspectionPeriodDays;
        $order->save();

        return $escrowTransaction;
    }

    /**
     * Release held escrow funds when item is returned on time and in good condition
     */
    public function releaseFunds(EscrowTransaction $escrow, string $reason = 'on_time_return'): EscrowTransaction
    {
        if (!$escrow->isHeld()) {
            throw new RuntimeException('Escrow is not currently held.');
        }

        $order = $escrow->order;
        $seller = $order->seller;
        $borrower = $order->user;

        // Credit funds to seller
        $seller->walletTransactions()->create([
            'amount' => $escrow->amount,
            'type' => 'credit',
            'reason' => 'escrow_release',
            'meta' => [
                'order_id' => $order->id,
                'from_user_id' => $borrower->id,
                'type' => 'escrow_release',
            ],
        ]);

        $seller->increment('wallet_balance', $escrow->amount);

        // Update escrow transaction
        $escrow->update([
            'status' => 'released',
            'released_at' => now(),
            'reason_code' => $reason,
            'notes' => "Escrow released for order {$order->order_number}. Reason: {$reason}",
        ]);

        $this->refreshOrderEscrowStatus($order);

        return $escrow;
    }

    /**
     * Refund held escrow funds back to borrower (normal return)
     */
    public function refundToBorrower(EscrowTransaction $escrow, string $reason = 'on_time_return'): EscrowTransaction
    {
        if (!$escrow->isHeld() && $escrow->status !== 'awaiting_resolution') {
            throw new RuntimeException('Escrow must be held or awaiting resolution to refund.');
        }

        $order = $escrow->order;
        $borrower = $order->user;

        $borrower->walletTransactions()->create([
            'amount' => $escrow->amount,
            'type' => 'credit',
            'reason' => 'escrow_refund',
            'meta' => [
                'order_id' => $order->id,
                'type' => 'escrow_refund',
            ],
        ]);

        $borrower->increment('wallet_balance', $escrow->amount);

        $escrow->update([
            'status' => 'released',
            'released_at' => now(),
            'reason_code' => $reason,
            'notes' => "Escrow refunded to borrower for order {$order->order_number}. Reason: {$reason}",
        ]);

        $this->refreshOrderEscrowStatus($order);

        return $escrow;
    }

    /**
     * Partially deduct escrow for damages and refund the remainder to borrower
     */
    public function deductForDamage(
        EscrowTransaction $escrow,
        float $damageAmount,
        string $damageDescription = ''
    ): array {
        if (!$escrow->isHeld()) {
            throw new RuntimeException('Escrow is not currently held.');
        }

        if ($damageAmount <= 0 || $damageAmount > $escrow->amount) {
            throw new Exception('Damage amount must be between 0 and escrow amount.');
        }

        $order = $escrow->order;
        $seller = $order->seller;
        $borrower = $order->user;

        $refundAmount = $escrow->amount - $damageAmount;

        if ($refundAmount > 0) {
            $borrower->walletTransactions()->create([
                'amount' => $refundAmount,
                'type' => 'credit',
                'reason' => 'escrow_refund_partial',
                'meta' => [
                    'order_id' => $order->id,
                    'escrow_transaction_id' => $escrow->id,
                    'damage_deducted' => $damageAmount,
                ],
            ]);

            $borrower->increment('wallet_balance', $refundAmount);
        }

        if ($seller) {
            $seller->walletTransactions()->create([
                'amount' => $damageAmount,
                'type' => 'credit',
                'reason' => 'damage_fee_awarded',
                'meta' => [
                    'order_id' => $order->id,
                    'from_user_id' => $borrower->id,
                    'damage_deducted' => $damageAmount,
                ],
            ]);

            $seller->increment('wallet_balance', $damageAmount);
        }

        // Update escrow transaction
        $escrow->update([
            'status' => 'deducted',
            'released_at' => now(),
            'reason_code' => 'damage_fee',
            'notes' => "Escrow damage deduction. Damage: {$damageAmount}, Refunded: {$refundAmount}. Description: {$damageDescription}",
            'metadata' => [
                'damage_amount' => $damageAmount,
                'refund_amount' => $refundAmount,
                'damage_description' => $damageDescription,
            ],
        ]);

        $this->refreshOrderEscrowStatus($order);

        return [
            'escrow' => $escrow,
            'damage_deducted' => $damageAmount,
            'seller_received' => $seller ? $damageAmount : 0,
            'borrower_refund' => $refundAmount,
        ];
    }

    /**
     * Refund entire escrow to borrower (for cancelled orders)
     */
    public function refundEscrow(EscrowTransaction $escrow, string $reason = 'order_cancelled'): EscrowTransaction
    {
        if (!$escrow->isHeld()) {
            throw new RuntimeException('Escrow is not currently held.');
        }

        $order = $escrow->order;
        $borrower = $order->user;

        // Refund to borrower
        $borrower->walletTransactions()->create([
            'amount' => $escrow->amount,
            'type' => 'credit',
            'reason' => 'escrow_refund',
            'meta' => [
                'order_id' => $order->id,
                'reason' => $reason,
            ],
        ]);

        $borrower->increment('wallet_balance', $escrow->amount);

        // Update escrow
        $escrow->update([
            'status' => 'refunded',
            'released_at' => now(),
            'reason_code' => $reason,
            'notes' => "Escrow refunded to borrower. Reason: {$reason}",
        ]);

        $this->refreshOrderEscrowStatus($order);

        return $escrow;
    }

    /**
     * Handle escrow when dispute is resolved with a decision
     * Can distribute based on dispute resolution
     */
    public function handleDisputeResolution(
        EscrowTransaction $escrow,
        string $resolution, // 'initiator_wins', 'respondent_wins', 'compromise'
        float $damageAmount = 0,
        string $reason = 'dispute_resolution'
    ): array {
        if (!$escrow->isHeld() && $escrow->status !== 'awaiting_resolution') {
            throw new RuntimeException('Escrow must be held or awaiting resolution for dispute handling.');
        }

        $order = $escrow->order;
        $seller = $order->seller;
        $borrower = $order->user;

        $result = [
            'status' => $resolution,
            'borrower_receives' => 0,
            'seller_receives' => 0,
        ];

        if ($resolution === 'initiator_wins') {
            // Initiator (borrower) gets full refund
            $borrower->increment('wallet_balance', $escrow->amount);
            $borrower->walletTransactions()->create([
                'amount' => $escrow->amount,
                'type' => 'credit',
                'reason' => 'dispute_resolution_refund',
                'meta' => ['order_id' => $order->id],
            ]);
            $result['borrower_receives'] = $escrow->amount;
        } elseif ($resolution === 'respondent_wins') {
            if (!$seller) {
                throw new RuntimeException('Order has no seller to receive funds.');
            }

            // Respondent (seller) gets full amount
            $seller->increment('wallet_balance', $escrow->amount);
            $seller->walletTransactions()->create([
                'amount' => $escrow->amount,
                'type' => 'credit',
                'reason' => 'dispute_resolution_awarded',
                'meta' => ['order_id' => $order->id],
            ]);
            $result['seller_receives'] = $escrow->amount;
        } elseif ($resolution === 'compromise') {
            // Split: borrower refunded, damage portion awarded to seller
            if ($damageAmount > 0 && $damageAmount < $escrow->amount) {
                $refundAmount = $escrow->amount - $damageAmount;

                if ($refundAmount > 0) {
                    $borrower->increment('wallet_balance', $refundAmount);
                    $borrower->walletTransactions()->create([
                        'amount' => $refundAmount,
                        'type' => 'credit',
                        'reason' => 'dispute_compromise_refund',
                        'meta' => ['order_id' => $order->id],
                    ]);
                    $result['borrower_receives'] = $refundAmount;
                }

                if ($seller) {
                    $seller->increment('wallet_balance', $damageAmount);
                    $seller->walletTransactions()->create([
                        'amount' => $damageAmount,
                        'type' => 'credit',
                        'reason' => 'dispute_compromise_awarded',
                        'meta' => ['order_id' => $order->id],
                    ]);
                    $result['seller_receives'] = $damageAmount;
                }
            }
        }

        // Update escrow
        $escrow->update([
            'status' => 'released',
            'released_at' => now(),
            'reason_code' => $reason,
            'notes' => "Dispute resolution: {$resolution}",
            'metadata' => array_merge($escrow->metadata ?? [], [
                'resolution' => $resolution,
                'damage_amount' => $damageAmount,
            ]),
        ]);

        $this->refreshOrderEscrowStatus($order);

        return $result;
    }

    private function refreshOrderEscrowStatus(Order $order): void
    {
        $statuses = $order->escrowTransactions()->pluck('status')->all();

        if (empty($statuses)) {
            $order->update(['escrow_status' => 'none']);
            return;
        }

        $hasHeld = in_array('held', $statuses, true) || in_array('awaiting_resolution', $statuses, true);
        $hasDeducted = in_array('deducted', $statuses, true);
        $hasReleased = in_array('released', $statuses, true);
        $hasRefunded = in_array('refunded', $statuses, true) || in_array('cancelled', $statuses, true);

        if ($hasHeld) {
            $status = 'held';
        } elseif ($hasDeducted) {
            $status = 'partially_deducted';
        } elseif ($hasReleased) {
            $status = 'released';
        } elseif ($hasRefunded) {
            $status = 'cancelled';
        } else {
            $status = 'none';
        }

        $order->update(['escrow_status' => $status]);
    }

    /**
     * Get active escrow for an order
     */
    public function getActiveEscrow(Order $order): ?EscrowTransaction
    {
        return $order->escrowTransactions()
            ->whereIn('status', ['held', 'awaiting_resolution'])
            ->latest()
            ->first();
    }

    /**
     * Check if escrow return deadline has passed
     */
    public function isReturnDeadlinePassed(Order $order): bool
    {
        return $order->return_deadline && $order->return_deadline->isPast();
    }

    /**
     * Get all completed escrows for a user as seller
     */
    public function getSellerEscrowHistory(User $seller): Collection
    {
        return EscrowTransaction::whereHas('order', function ($query) use ($seller) {
            $query->where('seller_id', $seller->id);
        })
            ->where('status', 'released')
            ->latest()
            ->get();
    }

    /**
     * Get all completed escrows for a user as borrower
     */
    public function getBorrowerEscrowHistory(User $borrower): Collection
    {
        return EscrowTransaction::whereHas('order', function ($query) use ($borrower) {
            $query->where('user_id', $borrower->id);
        })
            ->where('status', 'released')
            ->latest()
            ->get();
    }

    /**
     * Get escrow statistics for a user
     */
    public function getUserEscrowStats(User $user): array
    {
        $asSellerReleased = EscrowTransaction::whereHas('order', function ($query) use ($user) {
            $query->where('seller_id', $user->id);
        })
            ->where('status', 'released')
            ->sum('amount');

        $asBorrowerReleased = EscrowTransaction::whereHas('order', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('status', 'released')
            ->sum('amount');

        $asBorrowerDeducted = EscrowTransaction::whereHas('order', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('status', 'deducted')
            ->sum('amount');

        return [
            'total_received_as_seller' => (float) $asSellerReleased,
            'total_returned_as_borrower' => (float) $asBorrowerReleased,
            'total_damage_charged' => (float) $asBorrowerDeducted,
        ];
    }
}
