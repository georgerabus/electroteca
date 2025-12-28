<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'model_type',
        'model_id',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get audit logs for admin view.
     */
    public static function getAdminLogs(int $limit = 50)
    {
        return self::with('user')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'user' => $log->user?->name,
                'action' => $log->action,
                'description' => $log->description,
                'model' => class_basename($log->model_type),
                'changes' => $log->changes,
                'ip_address' => $log->ip_address,
                'timestamp' => $log->created_at->diffForHumans(),
            ]);
    }
}
