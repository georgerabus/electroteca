<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ReputationController extends Controller
{
    public function show(Request $request, int $userId)
    {
        $user = User::with(['reputationChanges' => function ($query) {
            $query->latest()->limit(50);
        }])->findOrFail($userId);

        $this->authorizeViewer($request, $user);

        $score = $user->getReputation();
        $rating = $user->getReputationRating();

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
