<?php

namespace App\Events;

use App\Models\Dispute;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DisputeResolved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Dispute $dispute
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('disputes'),
        ];
    }
}
