<?php

namespace App\Events;

use App\Models\EscrowTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EscrowDamageDeducted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public EscrowTransaction $escrow,
        public float $damageAmount
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('escrows'),
        ];
    }
}
