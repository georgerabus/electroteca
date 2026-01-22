<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\AuditLog;
use App\Models\Product;
use App\Services\LoanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;
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
    public function store(CheckoutRequest $request)
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

        $cart = $request->validated()['items'] ?? [];
        $shippingAddress = $request->validated()['shipping_address'];
        $notes = $request->validated()['notes'] ?? null;
        $periodFrom = Carbon::createFromFormat('Y-m-d', $request->validated()['period_from']);
        $periodTo = Carbon::createFromFormat('Y-m-d', $request->validated()['period_to']);

        return DB::transaction(function () use ($user, $cart, $shippingAddress, $notes, $periodFrom, $periodTo, $request) {
            $loanRequests = [];
            $totalDeposit = 0;
            $errors = [];
            $orderCurrency = null;

            // First pass: Validate all items and calculate total deposit
            foreach ($cart as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = (int)$item['quantity'];

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
                $deposit = $this->loanService->calculateDepositForUser($product, $user);
                $totalDeposit += ($deposit * $quantity);

                if ($orderCurrency === null) {
                    $orderCurrency = $product->currency ?? 'MDL';
                }
            }

            // Validate total wallet balance before processing
            if ($user->wallet_balance < $totalDeposit) {
                return back()->withErrors([
                    'wallet' => "Insufficient wallet balance. Required: " . number_format($totalDeposit, 2) . " CR, Available: " . number_format($user->wallet_balance, 2) . " CR",
                ])->withInput();
            }

            // If there are validation errors, return early
            if (!empty($errors)) {
                return back()->withErrors(['cart' => $errors])->withInput();
            }

            // Create a single order for the cart
            $order = $this->loanService->createOrderForLoans(
                user: $user,
                totalAmount: $totalDeposit,
                currency: $orderCurrency ?? 'MDL',
                shippingAddress: $shippingAddress,
                notes: $notes
            );

            // Second pass: Create loan requests (one per quantity)
            foreach ($cart as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = (int)$item['quantity'];
                $deposit = $this->loanService->calculateDepositForUser($product, $user);

                // Create multiple loan requests based on quantity
                for ($i = 0; $i < $quantity; $i++) {
                    try {
                        $loanRequest = $this->loanService->createLoanForOrder(
                            order: $order,
                            user: $user,
                            product: $product,
                            periodFrom: $periodFrom,
                            periodTo: $periodTo,
                            details: $notes,
                            deposit: $deposit
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

            // Audit log the checkout
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'checkout',
                'description' => "User completed checkout with " . count($loanRequests) . " items",
                'model_type' => 'Checkout',
                'model_id' => null,
                'changes' => json_encode([
                    'total_deposit' => $totalDeposit,
                    'items_count' => count($loanRequests),
                    'shipping_address' => substr($shippingAddress, 0, 50) . '...',
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->route('loans.my-loans')->with('success',
                'Loans approved successfully! Total amount: ' . number_format($totalDeposit, 2) . ' CR'
            );
        });
    }
}
