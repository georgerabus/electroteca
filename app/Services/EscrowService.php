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
        $returnDeadline = now()->addDays($inspectionPeriodDays);
        $order->update([
            'escrow_amount' => $amount,
            'escrow_status' => 'held',
            'return_deadline' => $returnDeadline,
            'inspection_period_days' => $inspectionPeriodDays,
        ]);

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

        // Update order status
        $order->update([
            'escrow_status' => 'released',
        ]);

        return $escrow;
    }

    /**
     * Partially deduct escrow for damages and release remaining balance to seller
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

        $remainingAmount = $escrow->amount - $damageAmount;

        // Create damage deduction transaction in borrower's wallet
        $borrower->walletTransactions()->create([
            'amount' => $damageAmount,
            'type' => 'debit',
            'reason' => 'damage_deduction',
            'meta' => [
                'order_id' => $order->id,
                'escrow_transaction_id' => $escrow->id,
                'description' => $damageDescription,
            ],
        ]);

        // Credit remaining to seller
        $seller->walletTransactions()->create([
            'amount' => $remainingAmount,
            'type' => 'credit',
            'reason' => 'escrow_release_partial',
            'meta' => [
                'order_id' => $order->id,
                'from_user_id' => $borrower->id,
                'damage_deducted' => $damageAmount,
            ],
        ]);

        $seller->increment('wallet_balance', $remainingAmount);

        // Update escrow transaction
        $escrow->update([
            'status' => 'deducted',
            'released_at' => now(),
            'reason_code' => 'damage_fee',
            'notes' => "Escrow partially deducted. Damage: {$damageAmount}, Released: {$remainingAmount}. Description: {$damageDescription}",
            'metadata' => [
                'damage_amount' => $damageAmount,
                'released_amount' => $remainingAmount,
                'damage_description' => $damageDescription,
            ],
        ]);

        // Update order
        $order->update([
            'escrow_status' => 'partially_deducted',
        ]);

        return [
            'escrow' => $escrow,
            'damage_deducted' => $damageAmount,
            'seller_received' => $remainingAmount,
            'borrower_charged' => $damageAmount,
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

        // Update order
        $order->update([
            'escrow_status' => 'cancelled',
        ]);

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
            // Split: deduct damage, rest to seller
            if ($damageAmount > 0 && $damageAmount < $escrow->amount) {
                $toSeller = $escrow->amount - $damageAmount;

                $borrower->walletTransactions()->create([
                    'amount' => $damageAmount,
                    'type' => 'debit',
                    'reason' => 'dispute_compromise_deduction',
                    'meta' => ['order_id' => $order->id],
                ]);
                $borrower->decrement('wallet_balance', $damageAmount);

                $seller->increment('wallet_balance', $toSeller);
                $seller->walletTransactions()->create([
                    'amount' => $toSeller,
                    'type' => 'credit',
                    'reason' => 'dispute_compromise_awarded',
                    'meta' => ['order_id' => $order->id],
                ]);

                $result['borrower_receives'] = 0;
                $result['seller_receives'] = $toSeller;
                $result['borrower_charged'] = $damageAmount;
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

        $order->update(['escrow_status' => 'released']);

        return $result;
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
