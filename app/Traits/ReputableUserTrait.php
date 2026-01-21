<?php

namespace App\Traits;

use App\Models\Reputation;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait ReputableUserTrait
{
    public function reputationChanges(): HasMany
    {
        return $this->hasMany(Reputation::class);
    }

    public function getReputation(): int
    {
        return (int) ($this->reputation_score ?? 0);
    }

    public function calculateReputationScore(): int
    {
        $completedLoans = (int) ($this->completed_loans ?? 0);
        $completedOrders = (int) ($this->completed_orders ?? 0);
        $itemsDamaged = (int) ($this->items_damaged ?? 0);

        return ($completedLoans * 10) + ($completedOrders * 5) - ($itemsDamaged * 20);
    }

    public function recalculateReputationScore(string $reason = 'recalculated'): int
    {
        $newScore = $this->calculateReputationScore();
        $currentScore = (int) ($this->reputation_score ?? 0);
        $change = $newScore - $currentScore;

        if ($change === 0 && $this->reputation_score !== null) {
            return $newScore;
        }

        $this->forceFill(['reputation_score' => $newScore])->save();

        if ($change !== 0) {
            $this->recordReputationChange($change, $reason);
        }

        return $newScore;
    }

    public function incrementCompletedLoans(int $by = 1): void
    {
        if ($by <= 0) {
            return;
        }

        $this->increment('completed_loans', $by);
        $this->recalculateReputationScore('loan_returned');
    }

    public function incrementCompletedOrders(int $by = 1): void
    {
        if ($by <= 0) {
            return;
        }

        $this->increment('completed_orders', $by);
        $this->recalculateReputationScore('order_completed');
    }

    public function incrementDamagedItems(int $by = 1): void
    {
        if ($by <= 0) {
            return;
        }

        $this->increment('items_damaged', $by);
        $this->recalculateReputationScore('item_damaged');
    }

    public function incrementReturnsOnTime(int $by = 1): void
    {
        if ($by <= 0) {
            return;
        }

        $this->increment('returns_on_time', $by);
    }

    protected function recordReputationChange(int $change, string $reason): void
    {
        $this->reputationChanges()->create([
            'change' => $change,
            'reason' => $reason,
        ]);
    }
}
