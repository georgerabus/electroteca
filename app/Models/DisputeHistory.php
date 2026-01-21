<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputeHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispute_id',
        'action',
        'description',
        'user_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
