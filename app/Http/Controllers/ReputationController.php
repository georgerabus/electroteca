<?php

namespace App\Http\Controllers;

use App\Models\ReputationTier;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReputationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        $score = $user->getReputation();
        $rating = $user->getReputationRating();

        $tiers = ReputationTier::active()
            ->orderBy('min_score')
            ->get();

        $currentTier = ReputationTier::tierForScore($score);
        $nextTier = $tiers->first(fn($tier) => $tier->min_score > $score);

        $progressPercent = 0;
        $pointsToNext = null;

        if ($nextTier) {
            $currentMin = $currentTier?->min_score ?? 0;
            $range = max(1, $nextTier->min_score - $currentMin);
            $progressPercent = (int) min(100, max(0, (($score - $currentMin) / $range) * 100));
            $pointsToNext = max(0, $nextTier->min_score - $score);
        } elseif ($currentTier) {
            $progressPercent = 100;
        }

        $history = $user->reputationChanges()
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn($change) => [
                'id' => $change->id,
                'change' => $change->change,
                'reason' => $change->reason,
                'created_at' => $change->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('reputation', [
            'reputation' => [
                'score' => $score,
                'rating' => $rating,
                'discount_percent' => $currentTier?->discount_percent ?? 0,
                'current_tier' => $currentTier ? [
                    'id' => $currentTier->id,
                    'name' => $currentTier->name,
                    'min_score' => $currentTier->min_score,
                    'discount_percent' => $currentTier->discount_percent,
                    'description' => $currentTier->description,
                ] : null,
                'next_tier' => $nextTier ? [
                    'id' => $nextTier->id,
                    'name' => $nextTier->name,
                    'min_score' => $nextTier->min_score,
                    'discount_percent' => $nextTier->discount_percent,
                    'description' => $nextTier->description,
                ] : null,
                'points_to_next' => $pointsToNext,
                'progress_percent' => $progressPercent,
                'stats' => [
                    'completed_loans' => (int) $user->completed_loans,
                    'completed_orders' => (int) $user->completed_orders,
                    'items_damaged' => (int) $user->items_damaged,
                    'returns_on_time' => (int) $user->returns_on_time,
                    'adjustment' => (int) $user->reputation_adjustment,
                ],
                'history' => $history,
                'tiers' => $tiers->map(fn($tier) => [
                    'id' => $tier->id,
                    'name' => $tier->name,
                    'min_score' => $tier->min_score,
                    'discount_percent' => $tier->discount_percent,
                    'description' => $tier->description,
                    'is_active' => $tier->is_active,
                    'is_unlocked' => $score >= $tier->min_score,
                    'is_current' => $currentTier?->id === $tier->id,
                ]),
            ],
        ]);
    }

    public function show(Request $request, int $userId)
    {
        $user = User::with(['reputationChanges' => function ($query) {
            $query->latest()->limit(50);
        }])->findOrFail($userId);

        $this->authorizeViewer($request, $user);

        $score = $user->getReputation();
        $rating = $user->getReputationRating();
        $currentTier = ReputationTier::tierForScore($score);

        $history = $user->reputationChanges->map(function ($change) {
            return [
                'id' => $change->id,
                'change' => $change->change,
                'reason' => $change->reason,
                'created_at' => $change->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'score' => $score,
            'rating' => $rating,
            'discount_percent' => $currentTier?->discount_percent ?? 0,
            'tier' => $currentTier ? [
                'id' => $currentTier->id,
                'name' => $currentTier->name,
                'min_score' => $currentTier->min_score,
                'discount_percent' => $currentTier->discount_percent,
            ] : null,
            'stats' => [
                'completed_loans' => (int) $user->completed_loans,
                'completed_orders' => (int) $user->completed_orders,
                'items_damaged' => (int) $user->items_damaged,
                'returns_on_time' => (int) $user->returns_on_time,
                'adjustment' => (int) $user->reputation_adjustment,
            ],
            'history' => $history,
        ]);
    }

    public function getRating(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);

        $this->authorizeViewer($request, $user);

        return response()->json([
            'rating' => $user->getReputationRating(),
        ]);
    }

    private function authorizeViewer(Request $request, User $user): void
    {
        $viewer = $request->user();

        if (! $viewer || (! $viewer->admin && $viewer->id !== $user->id)) {
            abort(403);
        }
    }

}
