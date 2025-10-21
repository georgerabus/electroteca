<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'status',
        'product_id',
        'user_id',
        'period_from',
        'period_to',
        'details',
        'approved_at',
        'picked_up_at',
        'returned_at',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'approved_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($loanRequest) {
            if (!$loanRequest->request_id) {
                $loanRequest->request_id = 'R-' . str_pad(
                    LoanRequest::max('id') + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
