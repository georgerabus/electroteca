<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {
    }

    /**
     * Show wallet page with balance and transactions.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect('/login');
        }

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'amount' => number_format($transaction->amount, 2),
                    'type' => $transaction->type,
                    'reason' => $transaction->reason,
                    'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('wallet', [
            'wallet_balance' => number_format($user->wallet_balance ?? 0, 2),
            'transactions' => $transactions,
        ]);
    }

    /**
     * Get wallet balance (API)
     */
    public function balance(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'balance' => $this->walletService->getBalance($user),
            'formatted_balance' => number_format($this->walletService->getBalance($user), 2),
            'currency' => 'credits',
        ]);
    }

    /**
     * Get transaction history (API)
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $limit = $request->input('limit', 50);

        $transactions = $this->walletService->getTransactionHistory($user, $limit);

        return response()->json([
            'transactions' => $transactions,
            'total' => count($transactions),
        ]);
    }
}

