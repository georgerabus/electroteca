<?php

namespace App\Services;

use App\Models\Dispute;
use App\Models\DisputeEvidence;
use App\Models\DisputeHistory;
use App\Models\Order;
use App\Models\User;
use App\Models\EscrowTransaction;
use Illuminate\Database\Eloquent\Collection;
use Exception;
use RuntimeException;

class DisputeService
{
    private EscrowService $escrowService;

    public function __construct(EscrowService $escrowService)
    {
        $this->escrowService = $escrowService;
    }

    /**
     * Create a new dispute for an order
     * Can be initiated by either buyer or seller
     */
    public function createDispute(
        Order $order,
        User $initiator,
        string $title,
        string $description,
        string $reason, // 'item_damaged', 'not_as_described', 'not_received', 'other'
        ?float $damageClaimAmount = null
    ): Dispute {
        // Verify initiator is involved in the order
        if ($initiator->id !== $order->user_id && $initiator->id !== $order->seller_id) {
            throw new RuntimeException('Initiator must be involved in this order.');
        }

        // Determine respondent
        $respondent = $initiator->id === $order->user_id ? $order->seller : $order->user;

        if (!$respondent) {
            throw new RuntimeException('Respondent cannot be determined.');
        }

        // Create dispute
        $dispute = $order->disputes()->create([
            'initiator_id' => $initiator->id,
            'respondent_id' => $respondent->id,
            'title' => $title,
            'description' => $description,
            'status' => 'open',
            'reason' => $reason,
            'damage_claim_amount' => $damageClaimAmount,
        ]);

        // Update escrow to awaiting_resolution
        $escrow = $this->escrowService->getActiveEscrow($order);
        if ($escrow) {
            $escrow->update(['status' => 'awaiting_resolution']);
        }

        // Record history
        $dispute->addHistory(
            'created',
            "Dispute created by {$initiator->name}",
            $initiator->id,
            [
                'reason' => $reason,
                'claim_amount' => $damageClaimAmount,
            ]
        );

        return $dispute;
    }

    /**
     * Add evidence to a dispute
     */
    public function submitEvidence(
        Dispute $dispute,
        User $user,
        string $fileUrl,
        string $evidenceType = 'photo',
        ?string $description = null
    ): DisputeEvidence {
        // Verify user is involved in dispute
        if (!$dispute->isUserInvolved($user)) {
            throw new RuntimeException('User is not involved in this dispute.');
        }

        // Only allow evidence submission if dispute is open
        if (!$dispute->isOpen() && !$dispute->isInArbitration()) {
            throw new RuntimeException('Cannot submit evidence to a resolved dispute.');
        }

        $evidence = $dispute->evidenceSubmissions()->create([
            'user_id' => $user->id,
            'file_url' => $fileUrl,
            'description' => $description,
            'evidence_type' => $evidenceType,
        ]);

        // Update history
        $dispute->addHistory(
            'evidence_submitted',
            "{$user->name} submitted evidence",
            $user->id,
            [
                'evidence_type' => $evidenceType,
                'file_url' => $fileUrl,
            ]
        );

        return $evidence;
    }

    /**
     * Resolve a dispute with an admin decision
     */
    public function resolveDispute(
        Dispute $dispute,
        User $resolver,
        string $resolution, // 'initiator_wins', 'respondent_wins', 'compromise'
        string $resolutionNotes,
        ?float $approvedDeduction = null
    ): Dispute {
        if (!$resolver->admin) {
            throw new RuntimeException('Only admins can resolve disputes.');
        }

        // Get the order and escrow
        $order = $dispute->order;
        $escrow = $this->escrowService->getActiveEscrow($order);

        if (!$escrow) {
            throw new RuntimeException('No active escrow found for this order.');
        }

        // Handle the escrow based on resolution
        $escrowResult = $this->escrowService->handleDisputeResolution(
            $escrow,
            $resolution,
            $approvedDeduction ?? 0,
            'dispute_resolution'
        );

        // Update dispute
        $dispute->update([
            'status' => 'resolved',
            'resolver_id' => $resolver->id,
            'resolved_at' => now(),
            'resolution_notes' => $resolutionNotes,
            'final_resolution' => $resolution,
            'approved_deduction_amount' => $approvedDeduction,
        ]);

        // Record history
        $dispute->addHistory(
            'resolved',
            "Dispute resolved by {$resolver->name}. Resolution: {$resolution}",
            $resolver->id,
            [
                'resolution' => $resolution,
                'approved_deduction' => $approvedDeduction,
                'escrow_result' => $escrowResult,
            ]
        );

        return $dispute;
    }

    /**
     * Allow respondent to submit counter-evidence
     */
    public function submitCounterEvidence(
        Dispute $dispute,
        User $respondent,
        string $fileUrl,
        string $evidenceType = 'photo',
        ?string $description = null
    ): DisputeEvidence {
        if ($respondent->id !== $dispute->respondent_id) {
            throw new RuntimeException('Only the respondent can submit counter-evidence.');
        }

        return $this->submitEvidence($dispute, $respondent, $fileUrl, $evidenceType, $description);
    }

    /**
     * Appeal a resolved dispute
     */
    public function appealDispute(
        Dispute $dispute,
        User $appellant,
        string $appealNotes,
        array $appealEvidenceUrls = []
    ): Dispute {
        if (!$dispute->isResolved()) {
            throw new RuntimeException('Only resolved disputes can be appealed.');
        }

        if ($appellant->id !== $dispute->initiator_id && $appellant->id !== $dispute->respondent_id) {
            throw new RuntimeException('Only involved parties can appeal.');
        }

        $dispute->update([
            'is_appealed' => true,
            'appeal_notes' => $appealNotes,
            'appeal_evidence_urls' => $appealEvidenceUrls,
            'status' => 'awaiting_resolution', // Back to arbitration
        ]);

        $dispute->addHistory(
            'appealed',
            "{$appellant->name} appealed the dispute",
            $appellant->id,
            [
                'appeal_notes' => $appealNotes,
                'evidence_count' => count($appealEvidenceUrls),
            ]
        );

        return $dispute;
    }

    /**
     * Get open disputes for a user (either as initiator or respondent)
     */
    public function getUserOpenDisputes(User $user): Collection
    {
        return Dispute::where(function ($query) use ($user) {
            $query->where('initiator_id', $user->id)
                ->orWhere('respondent_id', $user->id);
        })
            ->where('status', 'open')
            ->latest()
            ->get();
    }

    /**
     * Get all disputes for a user
     */
    public function getUserDisputes(User $user): Collection
    {
        return Dispute::where(function ($query) use ($user) {
            $query->where('initiator_id', $user->id)
                ->orWhere('respondent_id', $user->id);
        })
            ->latest()
            ->get();
    }

    /**
     * Get disputes awaiting admin resolution
     */
    public function getDisputesAwaitingResolution(): Collection
    {
        return Dispute::where('status', 'awaiting_resolution')
            ->latest()
            ->get();
    }

    /**
     * Get dispute statistics for an order
     */
    public function getOrderDisputeStats(Order $order): array
    {
        $disputes = $order->disputes;

        return [
            'total_disputes' => $disputes->count(),
            'open_disputes' => $disputes->where('status', 'open')->count(),
            'resolved_disputes' => $disputes->where('status', 'resolved')->count(),
            'appealed_disputes' => $disputes->where('is_appealed', true)->count(),
        ];
    }

    /**
     * Get dispute statistics for a user
     */
    public function getUserDisputeStats(User $user): array
    {
        $asInitiator = Dispute::where('initiator_id', $user->id)->get();
        $asRespondent = Dispute::where('respondent_id', $user->id)->get();

        $allDisputes = $asInitiator->merge($asRespondent)->unique('id');

        return [
            'total_disputes' => $allDisputes->count(),
            'as_initiator' => $asInitiator->count(),
            'as_respondent' => $asRespondent->count(),
            'won_as_initiator' => $asInitiator->where('final_resolution', 'initiator_wins')->count(),
            'won_as_respondent' => $asRespondent->where('final_resolution', 'respondent_wins')->count(),
            'awaiting_resolution' => $allDisputes->where('status', 'awaiting_resolution')->count(),
        ];
    }

    /**
     * Get all disputes by status for admin dashboard
     */
    public function getDisputesByStatus(string $status = null): Collection
    {
        $query = Dispute::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->with(['order', 'initiator', 'respondent'])
            ->latest()
            ->get();
    }

    /**
     * Close a resolved dispute permanently
     */
    public function closeDispute(Dispute $dispute, string $closureReason = ''): Dispute
    {
        if (!$dispute->isResolved()) {
            throw new RuntimeException('Only resolved disputes can be closed.');
        }

        $dispute->update([
            'status' => 'closed',
        ]);

        $dispute->addHistory(
            'closed',
            'Dispute closed',
            null,
            ['reason' => $closureReason]
        );

        return $dispute;
    }

    /**
     * Get dispute timeline/history
     */
    public function getDisputeTimeline(Dispute $dispute): Collection
    {
        return $dispute->history()->latest()->get();
    }

    /**
     * Check if there's a recent dispute for an order (fraud prevention)
     */
    public function hasRecentDispute(Order $order, int $daysBack = 30): bool
    {
        return $order->disputes()
            ->where('created_at', '>=', now()->subDays($daysBack))
            ->exists();
    }
}
