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
        $query = LoanRequest::with(['product', 'user'])
            ->orderBy('created_at', 'desc');

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

        $loanRequests = $query->get()->map(function ($request) {
            return [
                'id' => $request->request_id,
                'status' => $request->status,
                'product' => $request->product->name,
                'requester' => [
                    'name' => $request->user->name,
                    'email' => $request->user->email,
                ],
                'period' => [
                    'from' => $request->period_from->format('Y-m-d'),
                    'to' => $request->period_to->format('Y-m-d'),
                ],
                'requestedAt' => $request->created_at->format('Y-m-d, H:i:s'),
                'details' => $this->getDetailsString($request),
            ];
        });

        $products = Product::pluck('name')->unique()->values();

        return Inertia::render('dashboard', [
            'loanRequests' => $loanRequests,
            'products' => $products,
            'filters' => $request->only(['status', 'product', 'search']),
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
