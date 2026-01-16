import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { DollarSign, ArrowUp, ArrowDown, History, Plus, Loader } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';
import { useState } from 'react';

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

export default function Wallet({ wallet_balance, transactions }: WalletPageProps) {
    const { auth } = usePage<SharedData>().props;
    const [showAddCredits, setShowAddCredits] = useState(false);
    const [amount, setAmount] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleAddCredits = async () => {
        if (!amount || parseFloat(amount) <= 0) {
            setError('Please enter a valid amount');
            return;
        }

        setLoading(true);
        setError('');

        try {
            const response = await fetch('/wallet-topup/initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    amount: parseFloat(amount),
                }),
            });

            const data = await response.json();

            if (data.success && data.url) {
                // Redirect to Paddle checkout
                window.location.href = data.url;
            } else {
                setError(data.error || 'Failed to initiate payment');
            }
        } catch (err) {
            setError('An error occurred. Please try again.');
            console.error(err);
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

