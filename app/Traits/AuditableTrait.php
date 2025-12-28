<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait AuditableTrait
{
    /**
     * Log an audit event.
     *
     * @param string $action The action performed (create, update, delete, approve, etc.)
     * @param string $description Human-readable description of the action
     * @param array|null $changes The specific changes made (only for updates)
     * @return void
     */
    public static function logAudit(string $action, string $description, ?array $changes = null): void
    {
        if (!auth()->check()) {
            return;
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'model_type' => static::class,
            'model_id' => null,
            'changes' => $changes ? json_encode($changes) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log an action on a specific model instance.
     *
     * @param Model $model
     * @param string $action
     * @param string $description
     * @param array|null $changes
     * @return void
     */
    public static function logModelAudit(Model $model, string $action, string $description, ?array $changes = null): void
    {
        if (!auth()->check()) {
            return;
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'model_type' => $model::class,
            'model_id' => $model->id,
            'changes' => $changes ? json_encode($changes) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
