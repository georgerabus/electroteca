import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { DollarSign, ArrowUp, ArrowDown, History, Plus } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';

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

    return (
        <AppLayout breadcrumbs={[{ title: 'Shop', href: '/shop' }, { title: 'Wallet', href: '/wallet' }]}>
            <Head title="Wallet" />
            <div className="mx-auto max-w-4xl p-4 sm:p-8">
                <h1 className="text-3xl font-bold mb-6">My Wallet</h1>

                {/* Balance Card */}
                <div className="rounded-2xl border border-white/10 bg-gradient-to-br from-red-600/20 to-red-500/10 p-8 mb-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <div className="text-sm text-gray-400 mb-2">Current Balance</div>
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
                    <p className="text-gray-400 mb-4">
                        Payment integration coming soon. For now, please contact an administrator to add credits to your wallet.
                    </p>
                    <div className="flex gap-3">
                        <button
                            disabled
                            className="rounded-xl bg-red-600 px-6 py-3 font-semibold text-white hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Add Credits (Coming Soon)
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
            </div>
        </AppLayout>
    );
}

