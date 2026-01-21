<?php

namespace App\Events;

use App\Models\EscrowTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EscrowReleased
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public EscrowTransaction $escrow
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('escrows'),
        ];
    }
}
