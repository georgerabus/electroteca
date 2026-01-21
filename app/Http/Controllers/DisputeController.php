<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use App\Models\Order;
use App\Services\DisputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class DisputeController extends Controller
{
    private DisputeService $disputeService;

    public function __construct(DisputeService $disputeService)
    {
        $this->disputeService = $disputeService;
    }

    /**
     * Create a new dispute
     */
    public function store(Request $request, Order $order): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'reason' => 'required|in:item_damaged,not_as_described,not_received,other',
                'damage_claim_amount' => 'numeric|nullable|min:0.01',
            ]);

            $user = auth()->user();

            $dispute = $this->disputeService->createDispute(
                $order,
                $user,
                $validated['title'],
                $validated['description'],
                $validated['reason'],
                $validated['damage_claim_amount'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Dispute created successfully',
                'dispute' => $dispute,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get dispute details
     */
    public function show(Dispute $dispute): JsonResponse
    {
        $user = auth()->user();

        if (!$dispute->isUserInvolved($user)) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        $dispute->load(['order', 'initiator', 'respondent', 'evidenceSubmissions', 'history']);

        return response()->json([
            'success' => true,
            'dispute' => $dispute,
        ]);
    }

    /**
     * Submit evidence to a dispute
     */
    public function submitEvidence(Request $request, Dispute $dispute): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file_url' => 'required|url',
                'evidence_type' => 'in:photo,video,receipt,message,document,other',
                'description' => 'string|nullable',
            ]);

            $user = auth()->user();

            $evidence = $this->disputeService->submitEvidence(
                $dispute,
                $user,
                $validated['file_url'],
                $validated['evidence_type'] ?? 'photo',
                $validated['description'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Evidence submitted successfully',
                'evidence' => $evidence,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Resolve a dispute (admin only)
     */
    public function resolve(Request $request, Dispute $dispute): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user->admin) {
                return response()->json([
                    'success' => false,
                    'error' => 'Only admins can resolve disputes',
                ], 403);
            }

            $validated = $request->validate([
                'resolution' => 'required|in:initiator_wins,respondent_wins,compromise',
                'resolution_notes' => 'required|string',
                'approved_deduction_amount' => 'numeric|nullable|min:0',
            ]);

            $resolved = $this->disputeService->resolveDispute(
                $dispute,
                $user,
                $validated['resolution'],
                $validated['resolution_notes'],
                $validated['approved_deduction_amount'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Dispute resolved successfully',
                'dispute' => $resolved,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Appeal a resolved dispute
     */
    public function appeal(Request $request, Dispute $dispute): JsonResponse
    {
        try {
            $validated = $request->validate([
                'appeal_notes' => 'required|string',
                'appeal_evidence_urls' => 'array|nullable',
                'appeal_evidence_urls.*' => 'url',
            ]);

            $user = auth()->user();

            $appealed = $this->disputeService->appealDispute(
                $dispute,
                $user,
                $validated['appeal_notes'],
                $validated['appeal_evidence_urls'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Dispute appealed successfully',
                'dispute' => $appealed,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's disputes
     */
    public function userDisputes(): JsonResponse
    {
        $user = auth()->user();
        $disputes = $this->disputeService->getUserDisputes($user);

        return response()->json([
            'success' => true,
            'count' => $disputes->count(),
            'disputes' => $disputes->load(['order', 'initiator', 'respondent']),
        ]);
    }

    /**
     * Get user's open disputes
     */
    public function userOpenDisputes(): JsonResponse
    {
        $user = auth()->user();
        $disputes = $this->disputeService->getUserOpenDisputes($user);

        return response()->json([
            'success' => true,
            'count' => $disputes->count(),
            'disputes' => $disputes->load(['order', 'initiator', 'respondent']),
        ]);
    }

    /**
     * Get disputes awaiting resolution (admin only)
     */
    public function awaitingResolution(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->admin) {
            return response()->json([
                'success' => false,
                'error' => 'Only admins can view this',
            ], 403);
        }

        $disputes = $this->disputeService->getDisputesAwaitingResolution();

        return response()->json([
            'success' => true,
            'count' => $disputes->count(),
            'disputes' => $disputes->load(['order', 'initiator', 'respondent', 'evidenceSubmissions']),
        ]);
    }

    /**
     * Get dispute timeline
     */
    public function timeline(Dispute $dispute): JsonResponse
    {
        $user = auth()->user();

        if (!$dispute->isUserInvolved($user)) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        $timeline = $this->disputeService->getDisputeTimeline($dispute);

        return response()->json([
            'success' => true,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Get user dispute statistics
     */
    public function userStats(): JsonResponse
    {
        $user = auth()->user();
        $stats = $this->disputeService->getUserDisputeStats($user);

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Get order dispute statistics
     */
    public function orderStats(Order $order): JsonResponse
    {
        $stats = $this->disputeService->getOrderDisputeStats($order);

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Close a dispute (admin only)
     */
    public function close(Request $request, Dispute $dispute): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user->admin) {
                return response()->json([
                    'success' => false,
                    'error' => 'Only admins can close disputes',
                ], 403);
            }

            $validated = $request->validate([
                'closure_reason' => 'string|nullable',
            ]);

            $closed = $this->disputeService->closeDispute(
                $dispute,
                $validated['closure_reason'] ?? ''
            );

            return response()->json([
                'success' => true,
                'message' => 'Dispute closed successfully',
                'dispute' => $closed,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
