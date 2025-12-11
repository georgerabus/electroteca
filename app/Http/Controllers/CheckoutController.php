<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\LoanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;
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

        // Prevent unverified users from checking out / requesting loans
        if (empty($user->email_verified_at)) {
            try {
                Mail::to($user->email)->send(new WelcomeMail($user));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to send verification email to user attempting checkout', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }

            return back()->withErrors(['error' => 'You must verify your email before requesting loans. A verification email has been sent.'])->withInput();
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

            // First pass: Validate all items and calculate total deposit
            foreach ($cart as $item) {
                $product = Product::findOrFail($item['id']);
                $quantity = (int) $item['quantity'];

                // Check stock availability
                if ($product->stock_quantity < $quantity) {
                    $errors[] = "{$product->name}: Insufficient stock. Available: {$product->stock_quantity}, Requested: {$quantity}";
                    continue;
                }

                // Check if user can borrow (for each item)
                $check = $this->loanService->canBorrow($user, $product);
                
                if (!$check['can_borrow']) {
                    $errors[] = "{$product->name}: " . implode(', ', $check['reasons']);
                    continue;
                }

                // Calculate deposit for this product (multiply by quantity)
                $deposit = $this->loanService->calculateDeposit($product);
                $totalDeposit += ($deposit * $quantity);
            }

            // Validate total wallet balance before processing
            if ($user->wallet_balance < $totalDeposit) {
                return back()->withErrors([
                    'wallet' => "Insufficient wallet balance. Required: " . number_format($totalDeposit, 2) . " CR, Available: " . number_format($user->wallet_balance, 2) . " CR"
                ])->withInput();
            }

            // If there are validation errors, return early
            if (!empty($errors)) {
                return back()->withErrors(['cart' => $errors])->withInput();
            }

            // Second pass: Create loan requests (one per quantity)
            foreach ($cart as $item) {
                $product = Product::findOrFail($item['id']);
                $quantity = (int) $item['quantity'];

                // Create multiple loan requests based on quantity
                for ($i = 0; $i < $quantity; $i++) {
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
                        $errors[] = "{$product->name} (item " . ($i + 1) . "): {$e->getMessage()}";
                    }
                }
            }

            if (!empty($errors)) {
                return back()->withErrors(['cart' => $errors])->withInput();
            }

            if (empty($loanRequests)) {
                return back()->withErrors(['cart' => 'No items could be borrowed'])->withInput();
            }

            return redirect()->route('loans.my-loans')->with('success', 
                'Loans approved successfully! Total amount: ' . number_format($totalDeposit, 2) . ' CR'
            );
        });
    }

}
