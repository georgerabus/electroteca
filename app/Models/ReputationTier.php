<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReputationTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_score',
        'discount_percent',
        'description',
        'is_active',
    ];

    protected $casts = [
        'min_score' => 'integer',
        'discount_percent' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function discountForScore(int $score): int
    {
        return (int) (self::active()
            ->where('min_score', '<=', $score)
            ->orderByDesc('min_score')
            ->value('discount_percent') ?? 0);
    }

    public static function tierForScore(int $score): ?self
    {
        return self::active()
            ->where('min_score', '<=', $score)
            ->orderByDesc('min_score')
            ->first();
    }
}
