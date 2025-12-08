<?php

namespace App\Http\Controllers;

use App\Models\LoanRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function __construct(
        private LoanService $loanService
    ) {
    }
    public function products()
    {
        $products = Product::with('category')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => number_format($product->price, 2),
                'currency' => $product->currency ?? 'MDL',
                'stock_quantity' => $product->stock_quantity,
                'is_available' => $product->is_available,
                'category' => $product->category->name,
                'category_id' => $product->category_id,
                'image_url' => $product->image_url,
            ];
        });

        $categories = \App\Models\Category::all()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ];
        });

        return Inertia::render('admin/products', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_available' => ['boolean'],
            'image_url' => ['nullable', 'url'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $product = Product::create($validated);

        return back()->with('success', 'Product created successfully!');
    }

    public function updateProduct(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:products,slug,' . $product->id],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
            'image_url' => ['nullable', 'url'],
            'category_id' => ['sometimes', 'exists:categories,id'],
        ]);

        $product->update($validated);

        return back()->with('success', 'Product updated successfully!');
    }

    public function updateStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $product->update(['stock_quantity' => $validated['stock_quantity']]);

        return back()->with('success', 'Stock quantity updated successfully!');
    }

    public function users()
    {
        $users = User::all();

        return Inertia::render('admin/users', [
            'users' => $users,
        ]);
    }

    public function userDashboard(User $user)
    {
        $loans = LoanRequest::with(['product', 'product.category'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'request_id' => $loan->request_id,
                    'status' => $loan->status,
                    'product' => [
                        'id' => $loan->product->id ?? null,
                        'name' => $loan->product->name ?? 'Deleted Product',
                        'slug' => $loan->product->slug ?? null,
                        'image_url' => $loan->product->image_url ?? null,
                    ],
                    'period_from' => $loan->period_from->format('Y-m-d'),
                    'period_to' => $loan->period_to->format('Y-m-d'),
                    'deposit_amount' => number_format($loan->deposit_amount ?? 0, 2),
                    'damage_fee' => $loan->damage_fee ? number_format($loan->damage_fee, 2) : null,
                    'refund_amount' => $loan->refund_amount ? number_format($loan->refund_amount, 2) : null,
                    'created_at' => $loan->created_at->format('Y-m-d H:i:s'),
                    'approved_at' => $loan->approved_at?->format('Y-m-d H:i:s'),
                    'picked_up_at' => $loan->picked_up_at?->format('Y-m-d H:i:s'),
                    'returned_at' => $loan->returned_at?->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('admin/user-dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'wallet_balance' => number_format($user->wallet_balance, 2),
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ],
            'loans' => $loans,
        ]);
    }

    public function loans(Request $request)
    {
        $query = LoanRequest::with(['product', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $loans = $query->get()->map(function ($loan) {
            return [
                'id' => $loan->id,
                'request_id' => $loan->request_id,
                'status' => $loan->status,
                'user' => [
                    'id' => $loan->user->id,
                    'name' => $loan->user->name,
                    'email' => $loan->user->email,
                ],
                'product' => [
                    'id' => $loan->product->id ?? null,
                    'name' => $loan->product->name ?? 'Deleted Product',
                    'slug' => $loan->product->slug ?? null,
                    'image_url' => $loan->product->image_url ?? null,
                ],
                'period_from' => $loan->period_from->format('Y-m-d'),
                'period_to' => $loan->period_to->format('Y-m-d'),
                'deposit_amount' => number_format($loan->deposit_amount ?? 0, 2),
                'damage_fee' => $loan->damage_fee ? number_format($loan->damage_fee, 2) : null,
                'refund_amount' => $loan->refund_amount ? number_format($loan->refund_amount, 2) : null,
                'created_at' => $loan->created_at->format('Y-m-d H:i:s'),
                'approved_at' => $loan->approved_at?->format('Y-m-d H:i:s'),
                'picked_up_at' => $loan->picked_up_at?->format('Y-m-d H:i:s'),
                'returned_at' => $loan->returned_at?->format('Y-m-d H:i:s'),
            ];
        });

        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('admin/loans', [
            'loans' => $loans,
            'users' => $users,
            'filters' => [
                'status' => $request->get('status', 'All'),
                'user_id' => $request->get('user_id'),
            ],
        ]);
    }

    public function approveLoan(LoanRequest $loanRequest)
    {
        try {
            $this->loanService->approveLoan($loanRequest);
            return back()->with('success', 'Loan approved successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function rejectLoan(Request $request, LoanRequest $loanRequest)
    {
        try {
            $this->loanService->rejectLoan($loanRequest, $request->get('reason'));
            return back()->with('success', 'Loan rejected and deposit refunded!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function markAsPickedUp(LoanRequest $loanRequest)
    {
        try {
            $this->loanService->markAsPickedUp($loanRequest);
            return back()->with('success', 'Loan marked as picked up!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function approveReturn(LoanRequest $loanRequest)
    {
        try {
            $loanRequest = $this->loanService->approveReturn($loanRequest);
            return back()->with('success', 'Return approved! Full refund of ' . number_format($loanRequest->refund_amount, 2) . ' CR has been credited to user\'s wallet.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
