import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { DollarSign, ArrowUp, ArrowDown, History, Plus, Loader } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';
import { useState } from 'react';
import axios from 'axios';

declare global {
  interface Window {
    Paddle?: any;
  }
}

type Transaction = {
    id: number;
    amount: string;
    type: 'credit' | 'debit';
    reason: string | null;
    created_at: string;
};

type WalletPageProps = {
    wallet_balance: string;
    transactions: Transaction[];
};

const loadPaddle = () =>
  new Promise<void>((resolve, reject) => {
    // If Paddle is already loaded, resolve immediately
    if (window.Paddle) {
      return resolve();
    }

    // Check if script is already in DOM
    const existing = document.querySelector('script[src="https://cdn.paddle.com/paddle/v2/paddle.js"]');
    if (existing) {
      // If script exists and Paddle is available, resolve
      if (window.Paddle) {
        return resolve();
      }
      
      // Otherwise wait for the load event
      const onLoad = () => {
        existing.removeEventListener('load', onLoad);
        if (window.Paddle) {
          resolve();
        } else {
          reject(new Error('Paddle.js script loaded but window.Paddle not available'));
        }
      };
      existing.addEventListener('load', onLoad);
      return;
    }

    // Create and inject the script
    const s = document.createElement('script');
    s.src = 'https://cdn.paddle.com/paddle/v2/paddle.js';
    s.async = true;
    s.charset = 'utf-8';
    
    let timeoutId: NodeJS.Timeout;
    
    const cleanup = () => {
      clearTimeout(timeoutId);
      s.removeEventListener('load', onLoad);
      s.removeEventListener('error', onError);
    };
    
    const onLoad = () => {
      cleanup();
      if (window.Paddle) {
        resolve();
      } else {
        reject(new Error('Paddle.js script loaded but window.Paddle is not defined'));
      }
    };
    
    const onError = (error: Event | string) => {
      cleanup();
      const message = typeof error === 'string' ? error : 'Failed to load Paddle.js from CDN';
      console.error('[Paddle Loading Error]', message);
      reject(new Error(message));
    };
    
    s.onload = onLoad;
    s.onerror = () => onError('Failed to fetch Paddle.js script');
    
    // Set a timeout as fallback (10 seconds)
    timeoutId = setTimeout(() => {
      if (!window.Paddle) {
        cleanup();
        reject(new Error('Paddle.js loading timeout - took longer than 10 seconds'));
      }
    }, 10000);
    
    document.body.appendChild(s);
  });

const initPaddleOnce = (() => {
  let inited = false;

  return async (clientToken: string) => {
    await loadPaddle();

    if (!window.Paddle) {
      throw new Error('Paddle.js not available after loading script');
    }

    if (!inited) {
      // IMPORTANT: default e production dacă nu setezi sandbox
      window.Paddle.Environment.set('sandbox'); // :contentReference[oaicite:2]{index=2}

      window.Paddle.Initialize({
        token: clientToken, // test_...
        eventCallback: (event: any) => {
          console.log('[Paddle event]', event);
        },
        checkout: {
          settings: {
            displayMode: 'overlay',
          },
        },
      }); // :contentReference[oaicite:3]{index=3}

      inited = true;
    }
  };
})();



export default function Wallet({ wallet_balance, transactions }: WalletPageProps) {
    const { auth } = usePage<SharedData>().props;
    const [showAddCredits, setShowAddCredits] = useState(false);
    const [amount, setAmount] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const { props } = usePage<any>();
    const paddleClientToken = props?.paddle?.clientToken;

    const handleAddCredits = async () => {
    setLoading(true);
    setError('');

    try {
        if (!paddleClientToken) {
        console.error('[Paddle Wallet] Missing Paddle client token in Inertia props');
        throw new Error('Missing Paddle client token - please check server configuration');
        }

        console.log('[Paddle Wallet] Initializing Paddle.js...');
        
        // 1) init Paddle.js
        await initPaddleOnce(paddleClientToken);
        console.log('[Paddle Wallet] Paddle.js initialized successfully');

        // 2) create transaction on backend
        console.log('[Paddle Wallet] Initiating wallet topup with amount:', amount);
        const res = await axios.post('/wallet-topup/initiate', {
        amount: Number(amount),
        });

const txnId = res.data?.transaction_id ?? res.data?.paddle_transaction_id ?? res.data?.id;
if (!txnId) throw new Error('Backend did not return transaction id');


        console.log('[Paddle Wallet] Transaction created, ID:', txnId);

        // 3) open checkout overlay
        console.log('[Paddle Wallet] Opening Paddle checkout for transaction:', txnId);
window.Paddle.Checkout.open({ transactionId: txnId }); // :contentReference[oaicite:4]{index=4}

    } catch (e: any) {
        const errorMessage = e?.response?.data?.error ?? e?.message ?? 'Payment failed';
        console.error('[Paddle Wallet] Error:', errorMessage, e);
        setError(errorMessage);
    } finally {
        setLoading(false);
    }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Shop', href: '/shop' }, { title: 'Wallet', href: '/wallet' }]}>
            <Head title="Wallet" />
            <div className="mx-auto max-w-4xl p-4 sm:p-8">
                <h1 className="text-3xl font-bold mb-6">My Wallet</h1>

                {/* Balance Card */}
                <div className="rounded-2xl border border-white/10 bg-gradient-to-br from-red-600/20 to-red-500/10 p-8 mb-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <div className="text-sm text-black dark:text-gray-300 font-medium mb-2">Current Balance</div>
                            <div className="text-4xl font-bold text-white flex items-center gap-2">
                                <DollarSign className="h-8 w-8" />
                                {wallet_balance} CR
                            </div>
                        </div>
                        <div className="text-right">
                            <div className="text-sm text-gray-400 mb-2">Status</div>
                            <div className="px-4 py-2 rounded-lg bg-green-500/20 text-green-400 text-sm font-semibold">
                                Active
                            </div>
                        </div>
                    </div>
                </div>

                {/* Add Credits Section */}
                <div className="rounded-2xl border border-white/10 bg-white/5 p-6 mb-6">
                    <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
                        <Plus className="h-5 w-5" />
                        Add Credits
                    </h2>
                    <p className="text-gray-800/90 dark:text-gray-300/90 mb-4">
                        Add credits to your wallet using Paddle secure payment.
                    </p>
                    <div className="flex gap-3">
                        <button
                            onClick={() => setShowAddCredits(true)}
                            className="rounded-xl bg-red-600 px-6 py-3 font-semibold text-white hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Add Credits
                        </button>
                        <Link
                            href="/contact"
                            className="rounded-xl border-2 border-white/20 bg-transparent text-white px-6 py-3 font-semibold hover:bg-white/5 transition"
                        >
                            Contact Admin
                        </Link>
                    </div>
                </div>

                {/* Transaction History */}
                <div className="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
                        <History className="h-5 w-5" />
                        Transaction History
                    </h2>
                    {transactions.length === 0 ? (
                        <div className="text-center py-12">
                            <div className="mb-4 text-4xl opacity-20">📊</div>
                            <p className="text-gray-400">No transactions yet</p>
                        </div>
                    ) : (
                        <div className="divide-y divide-white/10">
                            {transactions.map((transaction) => (
                                <div key={transaction.id} className="flex items-center justify-between py-4">
                                    <div className="flex items-center gap-4">
                                        <div className={`h-10 w-10 rounded-full flex items-center justify-center ${
                                            transaction.type === 'credit' 
                                                ? 'bg-green-500/20 text-green-400' 
                                                : 'bg-red-500/20 text-red-400'
                                        }`}>
                                            {transaction.type === 'credit' ? (
                                                <ArrowUp className="h-5 w-5" />
                                            ) : (
                                                <ArrowDown className="h-5 w-5" />
                                            )}
                                        </div>
                                        <div>
                                            <div className="font-semibold text-white">
                                                {transaction.reason || (transaction.type === 'credit' ? 'Credit' : 'Debit')}
                                            </div>
                                            <div className="text-sm text-gray-400">
                                                {transaction.created_at}
                                            </div>
                                        </div>
                                    </div>
                                    <div className={`font-semibold ${
                                        transaction.type === 'credit' ? 'text-green-400' : 'text-red-400'
                                    }`}>
                                        {transaction.type === 'credit' ? '+' : '-'}{transaction.amount} CR
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Quick Actions */}
                <div className="mt-6 flex gap-4">
                    <Link
                        href="/shop"
                        className="flex-1 rounded-xl border-2 border-white/20 bg-transparent text-white px-6 py-3 font-semibold hover:bg-white/5 transition text-center"
                    >
                        Continue Shopping
                    </Link>
                    <Link
                        href="/cart"
                        className="flex-1 rounded-xl bg-red-600 text-white px-6 py-3 font-semibold hover:bg-red-700 transition text-center"
                    >
                        View Cart
                    </Link>
                </div>

                {/* Add Credits Modal */}
                {showAddCredits && (
                    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                        <div className="bg-gray-900 rounded-2xl border border-white/10 p-8 max-w-md w-full">
                            <h3 className="text-2xl font-bold mb-4">Add Credits to Your Wallet</h3>
                            
                            {error && (
                                <div className="mb-4 p-4 rounded-lg bg-red-500/20 border border-red-500/50 text-red-400 text-sm">
                                    {error}
                                </div>
                            )}

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Amount (CR)
                                </label>
                                <div className="flex items-center">
                                    <span className="text-gray-400 mr-2">$</span>
                                    <input
                                        type="number"
                                        value={amount}
                                        onChange={(e) => setAmount(e.target.value)}
                                        placeholder="0.00"
                                        min="0.01"
                                        step="0.01"
                                        disabled={loading}
                                        className="flex-1 px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:outline-none focus:border-red-500 disabled:opacity-50"
                                    />
                                    <span className="text-gray-400 ml-2">CR</span>
                                </div>
                                <p className="text-xs text-gray-400 mt-2">Minimum: $0.01</p>
                            </div>

                            {/* Quick amounts */}
                            <div className="mb-6">
                                <p className="text-xs text-gray-400 mb-2">Quick amounts:</p>
                                <div className="grid grid-cols-4 gap-2">
                                    {['10', '25', '50', '100'].map((preset) => (
                                        <button
                                            key={preset}
                                            onClick={() => setAmount(preset)}
                                            disabled={loading}
                                            className="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-white text-sm hover:bg-white/10 transition disabled:opacity-50"
                                        >
                                            ${preset}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="flex gap-3">
                                <button
                                    onClick={() => {
                                        setShowAddCredits(false);
                                        setAmount('');
                                        setError('');
                                    }}
                                    disabled={loading}
                                    className="flex-1 px-4 py-2 rounded-lg border border-white/20 text-white hover:bg-white/5 transition disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    onClick={handleAddCredits}
                                    disabled={loading || !amount}
                                    className="flex-1 px-4 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                >
                                    {loading && <Loader className="h-4 w-4 animate-spin" />}
                                    {loading ? 'Processing...' : 'Continue to Payment'}
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

