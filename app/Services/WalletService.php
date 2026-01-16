<?php

namespace App\Services;

use App\Models\User;
use App\Models\Payment;
use App\Models\WalletTransaction;
use Exception;

class WalletService
{
    /**
     * Get user's wallet balance
     */
    public function getBalance(User $user): float
    {
        return (float) $user->wallet_balance ?? 0;
    }

    /**
     * Add credits to wallet (top-up)
     */
    public function addCredits(User $user, float $amount, string $reason = 'top_up', array $metadata = []): WalletTransaction
    {
        $transaction = WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => 'credit',
            'reason' => $reason,
            'meta' => $metadata,
        ]);

        // Update user's wallet balance
        $user->wallet_balance = ($user->wallet_balance ?? 0) + $amount;
        $user->save();

        return $transaction;
    }

    /**
     * Deduct credits from wallet (purchase)
     */
    public function deductCredits(User $user, float $amount, string $reason = 'purchase', array $metadata = []): bool
    {
        if ($this->getBalance($user) < $amount) {
            throw new Exception('Insufficient wallet balance');
        }

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => 'debit',
            'reason' => $reason,
            'meta' => $metadata,
        ]);

        // Update user's wallet balance
        $user->wallet_balance = ($user->wallet_balance ?? 0) - $amount;
        $user->save();

        return true;
    }

    /**
     * Check if user has sufficient balance
     */
    public function hasSufficientBalance(User $user, float $amount): bool
    {
        return $this->getBalance($user) >= $amount;
    }

    /**
     * Refund credits to wallet (when payment is refunded)
     */
    public function refundCredits(User $user, float $amount, array $metadata = []): WalletTransaction
    {
        return $this->addCredits($user, $amount, 'refund', $metadata);
    }

    /**
     * Get wallet transaction history
     */
    public function getTransactionHistory(User $user, int $limit = 50)
    {
        return $user->walletTransactions()
            ->latest()
            ->limit($limit)
            ->get();
    }
}
