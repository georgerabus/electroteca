<?php

namespace App\Http\Controllers;

use App\Models\LoanRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = LoanRequest::with(['product', 'user'])
            ->orderBy('created_at', 'desc');

        // If the user is not an admin, show only their own loans
        if (! $user || ! $user->admin) {
            $query->where('user_id', $user?->id ?? 0);
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        // Filter by product
        if ($request->filled('product') && $request->product !== 'All products') {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', $request->product);
            });
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('status', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $loanRequests = $query->get()
            ->filter(function ($request) {
                // Filter out loans with deleted products or users
                return $request->product !== null && $request->user !== null;
            })
            ->map(function ($request) {
                return [
                    'id' => $request->request_id,
                    'status' => $request->status,
                    'product' => $request->product->name ?? 'Deleted Product',
                    'requester' => [
                        'name' => $request->user->name ?? 'Unknown User',
                        'email' => $request->user->email ?? 'unknown@example.com',
                    ],
                    'period' => [
                        'from' => $request->period_from->format('Y-m-d'),
                        'to' => $request->period_to->format('Y-m-d'),
                    ],
                    'requestedAt' => $request->created_at->format('Y-m-d, H:i:s'),
                    'details' => $this->getDetailsString($request),
                ];
            })
            ->values();

        $products = Product::pluck('name')->unique()->values();
        $reputation = null;

        if ($user) {
            $reputationScore = $user->getReputation();
            $reputationRating = $user->getReputationRating();
            $reputationChanges = $user->reputationChanges()
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn($change) => [
                    'id' => $change->id,
                    'change' => $change->change,
                    'reason' => $change->reason,
                    'created_at' => $change->created_at?->format('Y-m-d H:i:s'),
                ]);

            $reputation = [
                'score' => $reputationScore,
                'rating' => $reputationRating,
                'stats' => [
                    'completed_loans' => (int) $user->completed_loans,
                    'completed_orders' => (int) $user->completed_orders,
                    'items_damaged' => (int) $user->items_damaged,
                    'returns_on_time' => (int) $user->returns_on_time,
                    'adjustment' => (int) $user->reputation_adjustment,
                ],
                'history' => $reputationChanges,
            ];
        }

        return Inertia::render('dashboard', [
            'loanRequests' => $loanRequests,
            'products' => $products,
            'filters' => $request->only(['status', 'product', 'search']),
            'reputation' => $reputation,
        ]);
    }

    private function getDetailsString(LoanRequest $request): string
    {
        switch ($request->status) {
            case 'Returned':
                return $request->returned_at
                    ? "Returned · {$request->returned_at->format('Y-m-d, H:i:s')}"
                    : '—';
            case 'Defective':
                return $request->returned_at
                    ? "Defective · {$request->returned_at->format('Y-m-d, H:i:s')}"
                    : 'Defective';
            case 'Return Requested':
                return 'Return Requested · Waiting for approval';
            case 'Approved':
                return $request->approved_at
                    ? "Approved · {$request->approved_at->format('Y-m-d, H:i:s')}"
                    : 'Approved';
            case 'Picked up':
                return $request->picked_up_at
                    ? "Picked up · {$request->picked_up_at->format('Y-m-d, H:i:s')}"
                    : 'Picked up';
            default:
                return '—';
        }
    }
}
