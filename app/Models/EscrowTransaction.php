<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EscrowTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'wallet_transaction_id',
        'amount',
        'currency',
        'status', // held, released, deducted, refunded, cancelled
        'held_at',
        'released_at',
        'reason_code', // on_time_return, damage_fee, dispute_resolution, other
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'held_at' => 'datetime',
        'released_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The order associated with this escrow transaction
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The wallet transaction that holds the funds
     */
    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Check if escrow is currently held
     */
    public function isHeld(): bool
    {
        return $this->status === 'held';
    }

    /**
     * Check if escrow is released
     */
    public function isReleased(): bool
    {
        return $this->status === 'released';
    }

    /**
     * Check if escrow has been deducted for damages
     */
    public function isDeducted(): bool
    {
        return $this->status === 'deducted';
    }

    /**
     * Get the borrower (order user)
     */
    public function getBorrower(): User
    {
        return $this->order->user;
    }

    /**
     * Get the seller (order seller_id)
     */
    public function getSeller(): User
    {
        return $this->order->seller;
    }
}
