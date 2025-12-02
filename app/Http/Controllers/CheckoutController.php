<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\LoanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(
        private LoanService $loanService
    ) {
    }

    /**
     * Show checkout page.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect('/login');
        }

        return Inertia::render('checkout', [
            'wallet_balance' => number_format($user->wallet_balance, 2),
        ]);
    }

    /**
     * Process checkout (create loan requests for all cart items).
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect('/login');
        }

        $validator = Validator::make($request->all(), [
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|integer|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'period_from' => 'required|date|after_or_equal:today',
            'period_to' => 'required|date|after:period_from',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $cart = $request->cart;
        $periodFrom = Carbon::parse($request->period_from);
        $periodTo = Carbon::parse($request->period_to);

        return DB::transaction(function () use ($user, $cart, $periodFrom, $periodTo) {
            $loanRequests = [];
            $totalDeposit = 0;
            $errors = [];

            // Process each cart item as a loan request
            foreach ($cart as $item) {
                $product = Product::findOrFail($item['id']);

                // Check if user can borrow
                $check = $this->loanService->canBorrow($user, $product);
                
                if (!$check['can_borrow']) {
                    $errors[] = "{$product->name}: " . implode(', ', $check['reasons']);
                    continue;
                }

                // Calculate deposit for this product
                $deposit = $this->loanService->calculateDeposit($product);
                $totalDeposit += $deposit;

                // Create loan request (automatically approved)
                try {
                    $loanRequest = $this->loanService->borrowProduct(
                        $user,
                        $product,
                        $periodFrom,
                        $periodTo,
                        null
                    );
                    
                    $loanRequests[] = $loanRequest;
                } catch (\Exception $e) {
                    $errors[] = "{$product->name}: {$e->getMessage()}";
                }
            }

            if (!empty($errors)) {
                return back()->withErrors(['cart' => $errors]);
            }

            if (empty($loanRequests)) {
                return back()->withErrors(['cart' => 'No items could be borrowed']);
            }

            return redirect()->route('loans.my-loans')->with('success', 
                'Loans approved successfully! Total amount: ' . number_format($totalDeposit, 2) . ' CR'
            );
        });
    }

}
