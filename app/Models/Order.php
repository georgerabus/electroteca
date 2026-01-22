<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'seller_id',
        'status',
        'total_amount',
        'escrow_amount',
        'currency',
        'shipping_address',
        'notes',
        'escrow_status',
        'return_deadline',
        'inspection_period_days',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'escrow_amount' => 'decimal:2',
        'return_deadline' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function escrowTransactions(): HasMany
    {
        return $this->hasMany(EscrowTransaction::class);
    }

    public function loanRequests(): HasMany
    {
        return $this->hasMany(LoanRequest::class);
    }

    /**
     * Get the active escrow transaction for this order
     */
    public function activeEscrow(): ?EscrowTransaction
    {
        return $this->escrowTransactions()->whereIn('status', ['held', 'awaiting_resolution'])->first();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'ORD-' . str_pad(
                    Order::max('id') + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });

        static::updated(function (Order $order) {
            if (! $order->wasChanged('status')) {
                return;
            }

            if ($order->status !== 'completed') {
                return;
            }

            $user = $order->user;

            if (! $user) {
                return;
            }

            $user->incrementCompletedOrders();
        });
    }
}
