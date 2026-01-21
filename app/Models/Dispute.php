<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'initiator_id',
        'respondent_id',
        'title',
        'description',
        'status',
        'reason',
        'evidence_urls',
        'resolution_notes',
        'resolver_id',
        'resolved_at',
        'is_appealed',
        'appeal_notes',
        'appeal_evidence_urls',
        'appeal_resolved_at',
        'final_resolution',
        'damage_claim_amount',
        'approved_deduction_amount',
    ];

    protected $casts = [
        'evidence_urls' => 'array',
        'appeal_evidence_urls' => 'array',
        'resolved_at' => 'datetime',
        'appeal_resolved_at' => 'datetime',
        'damage_claim_amount' => 'decimal:2',
        'approved_deduction_amount' => 'decimal:2',
    ];

    /**
     * The order associated with the dispute
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The user who initiated the dispute
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    /**
     * The user who is responding to the dispute
     */
    public function respondent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondent_id');
    }

    /**
     * The admin who resolved the dispute
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolver_id');
    }

    /**
     * Evidence submissions for the dispute
     */
    public function evidenceSubmissions(): HasMany
    {
        return $this->hasMany(DisputeEvidence::class);
    }

    /**
     * Timeline/history of the dispute
     */
    public function history(): HasMany
    {
        return $this->hasMany(DisputeHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if dispute is open
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if dispute is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if dispute is appealed
     */
    public function isAppealed(): bool
    {
        return $this->is_appealed === true;
    }

    /**
     * Check if dispute is in arbitration (waiting for admin decision)
     */
    public function isInArbitration(): bool
    {
        return $this->status === 'awaiting_resolution';
    }

    /**
     * Get the other party in the dispute
     */
    public function getOtherParty(User $user): User
    {
        return $user->id === $this->initiator_id ? $this->respondent : $this->initiator;
    }

    /**
     * Check if a user is involved in this dispute
     */
    public function isUserInvolved(User $user): bool
    {
        return $user->id === $this->initiator_id || $user->id === $this->respondent_id;
    }

    /**
     * Add to dispute history
     */
    public function addHistory(string $action, string $description, ?int $userId = null, array $data = []): DisputeHistory
    {
        return $this->history()->create([
            'action' => $action,
            'description' => $description,
            'user_id' => $userId,
            'data' => $data,
        ]);
    }
}
