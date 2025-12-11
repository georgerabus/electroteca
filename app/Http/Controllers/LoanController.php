<?php

namespace App\Http\Controllers;

use App\Models\LoanRequest;
use App\Models\Product;
use App\Services\LoanService;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function __construct(
        private LoanService $loanService
    ) {
        // Middleware is applied via routes/web.php
    }

    /**
     * Check if user can borrow a product.
     */
    public function checkBorrowability(Request $request, Product $product)
    {
        $user = $request->user();
        $check = $this->loanService->canBorrow($user, $product);

        return response()->json($check);
    }

    /**
     * Borrow a product.
     */
    public function borrow(Request $request, Product $product)
    {
        $user = $request->user();

        // Prevent unverified users from requesting loans. Send verification email if not verified.
        if (empty($user->email_verified_at)) {
            try {
                Mail::to($user->email)->send(new WelcomeMail($user));
            } catch (\Exception $e) {
                // Log but don't expose internal mail errors to the user
                \Illuminate\Support\Facades\Log::warning('Failed to send verification email to user attempting loan', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }

            return back()->withErrors(['error' => 'You must verify your email before requesting a loan. A verification email has been sent to your address.']);
        }

        $validator = Validator::make($request->all(), [
            'period_from' => 'required|date|after_or_equal:today',
            'period_to' => 'required|date|after:period_from',
            'details' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $periodFrom = Carbon::parse($request->period_from);
            $periodTo = Carbon::parse($request->period_to);

            $loanRequest = $this->loanService->borrowProduct(
                $user,
                $product,
                $periodFrom,
                $periodTo,
                $request->details
            );

            return back()->with('success', 'Loan request created successfully. Deposit of ' . $loanRequest->deposit_amount . ' CR has been deducted from your wallet.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Request to return a product (user action).
     */
    public function requestReturn(Request $request, LoanRequest $loanRequest)
    {
        $user = $request->user();

        // Ensure user owns this loan request
        if ($loanRequest->user_id !== $user->id) {
            abort(403, 'You do not have permission to return this loan');
        }

        try {
            $loanRequest = $this->loanService->requestReturn($loanRequest);
            return back()->with('success', 'Return request submitted. Waiting for admin approval.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Return a product (old method - kept for backward compatibility).
     */
    public function returnProduct(Request $request, LoanRequest $loanRequest)
    {
        $user = $request->user();

        // Ensure user owns this loan request
        if ($loanRequest->user_id !== $user->id) {
            abort(403, 'You do not have permission to return this loan');
        }

        $validator = Validator::make($request->all(), [
            'is_damaged' => 'sometimes|boolean',
            'damage_fee' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $isDamaged = $request->boolean('is_damaged', false);
            $damageFee = $request->has('damage_fee') ? (float) $request->damage_fee : null;

            $loanRequest = $this->loanService->returnProduct($loanRequest, $isDamaged, $damageFee);

            $message = $isDamaged
                ? "Product returned as damaged. Refund of {$loanRequest->refund_amount} CR has been credited to your wallet."
                : "Product returned successfully. Full refund of {$loanRequest->refund_amount} CR has been credited to your wallet.";

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get user's loan requests.
     */
    public function myLoans(Request $request)
    {
        $user = $request->user();

        $loans = LoanRequest::with(['product', 'product.category'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($loan) {
                return $loan->product !== null; // Filter out loans with deleted products
            })
            ->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'request_id' => $loan->request_id,
                    'status' => $loan->status,
                    'product' => [
                        'id' => $loan->product->id,
                        'name' => $loan->product->name,
                        'slug' => $loan->product->slug,
                        'image_url' => $loan->product->image_url,
                    ],
                    'period_from' => $loan->period_from->format('Y-m-d'),
                    'period_to' => $loan->period_to->format('Y-m-d'),
                    'deposit_amount' => number_format($loan->deposit_amount ?? 0, 2),
                    'damage_fee' => $loan->damage_fee ? number_format($loan->damage_fee, 2) : null,
                    'refund_amount' => $loan->refund_amount ? number_format($loan->refund_amount, 2) : null,
                    'details' => $loan->details,
                    'created_at' => $loan->created_at->format('Y-m-d H:i:s'),
                    'returned_at' => $loan->returned_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->values(); // Re-index array after filter

        return Inertia::render('loans/my-loans', [
            'loans' => $loans,
        ]);
    }
}
