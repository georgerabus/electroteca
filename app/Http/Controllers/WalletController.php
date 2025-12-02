<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WalletController extends Controller
{
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
            'wallet_balance' => number_format($user->wallet_balance, 2),
            'transactions' => $transactions,
        ]);
    }
}

