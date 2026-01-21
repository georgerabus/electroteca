<?php

namespace App\Contracts;

interface ReputableUser
{
    public function getReputation(): int;

    public function getReputationRating(): int;

    public function calculateReputationScore(): int;

    public function recalculateReputationScore(string $reason = 'recalculated'): int;

    public function adjustReputation(int $change, string $reason = 'manual_adjustment'): int;

    public function incrementCompletedLoans(int $by = 1): void;

    public function incrementCompletedOrders(int $by = 1): void;

    public function incrementDamagedItems(int $by = 1): void;

    public function incrementReturnsOnTime(int $by = 1): void;
}
