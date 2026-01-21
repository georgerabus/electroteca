import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { User, Package, DollarSign, Calendar, ArrowLeft, Check, X, PackageCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';

type Loan = {
    id: number;
    request_id: string;
    status: string;
    product: {
        id: number | null;
        name: string;
        slug: string | null;
        image_url: string | null;
    };
    period_from: string;
    period_to: string;
    deposit_amount: string;
    damage_fee: string | null;
    refund_amount: string | null;
    created_at: string;
    approved_at: string | null;
    picked_up_at: string | null;
    returned_at: string | null;
};

type User = {
    id: number;
    name: string;
    email: string;
    wallet_balance: string;
    created_at: string;
};

type ReputationChange = {
    id: number;
    change: number;
    reason: string;
    created_at: string;
};

type Reputation = {
    score: number;
    rating: number;
    stats: {
        completed_loans: number;
        completed_orders: number;
        items_damaged: number;
        returns_on_time: number;
    };
    history: ReputationChange[];
};

type UserDashboardPageProps = {
    user: User;
    loans: Loan[];
    reputation: Reputation;
};

const STATUS_COLORS: Record<string, string> = {
    'Requested': 'bg-yellow-500/20 text-yellow-400',
    'Approved': 'bg-blue-500/20 text-blue-400',
    'Picked up': 'bg-purple-500/20 text-purple-400',
    'Late': 'bg-red-500/20 text-red-400',
    'Return Requested': 'bg-orange-500/20 text-orange-400',
    'Returned': 'bg-green-500/20 text-green-400',
    'Rejected': 'bg-gray-500/20 text-gray-400',
    'Defective': 'bg-orange-500/20 text-orange-400',
    'Cancelled': 'bg-gray-500/20 text-gray-400',
};

export default function UserDashboard({ user, loans, reputation }: UserDashboardPageProps) {
    return (
        <AppLayout breadcrumbs={[
            { title: 'Admin', href: '/admin' },
            { title: 'Users', href: '/admin/users' },
            { title: user.name, href: `/admin/users/${user.id}/dashboard` }
        ]}>
            <Head title={`Admin - ${user.name}`} />
            <div className="mx-auto max-w-full lg:max-w-[1400px] xl:max-w-[1800px] p-4 sm:p-8">
                <Link
                    href="/admin/users"
                    className="mb-6 inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Users
                </Link>

                {/* User Info */}
                <div className="mb-6 rounded-2xl border border-white/10 bg-white/5 p-6">
                    <div className="flex items-center gap-4 mb-4">
                        <div className="h-16 w-16 rounded-full bg-zinc-800 flex items-center justify-center">
                            <User className="h-8 w-8 text-gray-400" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-white">{user.name}</h1>
                            <p className="text-gray-400">{user.email}</p>
                        </div>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-white/10">
                        <div>
                            <div className="text-sm text-gray-400 mb-1">Wallet Balance</div>
                            <div className="text-xl font-semibold text-white flex items-center gap-2">
                                <DollarSign className="h-5 w-5" />
                                {user.wallet_balance} CR
                            </div>
                        </div>
                        <div>
                            <div className="text-sm text-gray-400 mb-1">Total Loans</div>
                            <div className="text-xl font-semibold text-white flex items-center gap-2">
                                <Package className="h-5 w-5" />
                                {loans.length}
                            </div>
                        </div>
                        <div>
                            <div className="text-sm text-gray-400 mb-1">Member Since</div>
                            <div className="text-lg font-semibold text-white">
                                {new Date(user.created_at).toLocaleDateString()}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Reputation */}
                <div className="mb-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-6 lg:col-span-1">
                        <div className="text-sm text-gray-400 mb-1">Reputation Score</div>
                        <div className="text-3xl font-bold text-white">{reputation.score}</div>
                        <div className="text-sm text-gray-400">Rating: {reputation.rating}/100</div>
                        <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <div className="text-gray-400">Completed Loans</div>
                                <div className="text-white font-semibold">{reputation.stats.completed_loans}</div>
                            </div>
                            <div>
                                <div className="text-gray-400">Completed Orders</div>
                                <div className="text-white font-semibold">{reputation.stats.completed_orders}</div>
                            </div>
                            <div>
                                <div className="text-gray-400">Items Damaged</div>
                                <div className="text-white font-semibold">{reputation.stats.items_damaged}</div>
                            </div>
                            <div>
                                <div className="text-gray-400">Returns On Time</div>
                                <div className="text-white font-semibold">{reputation.stats.returns_on_time}</div>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-6 lg:col-span-2">
                        <div className="text-lg font-semibold text-white mb-3">Reputation Changes</div>
                        {reputation.history.length === 0 ? (
                            <div className="text-sm text-gray-400">No reputation changes recorded yet.</div>
                        ) : (
                            <div className="space-y-2">
                                {reputation.history.map((entry) => {
                                    const isPositive = entry.change >= 0;
                                    const reason = entry.reason.replace(/_/g, ' ');

                                    return (
                                        <div
                                            key={entry.id}
                                            className="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm"
                                        >
                                            <div>
                                                <div className="text-white capitalize">{reason}</div>
                                                <div className="text-gray-400">{entry.created_at}</div>
                                            </div>
                                            <div className={isPositive ? 'text-green-400 font-semibold' : 'text-red-400 font-semibold'}>
                                                {isPositive ? '+' : ''}{entry.change}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>

                {/* Loans Section */}
                <div className="mb-6">
                    <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
                        <Package className="h-5 w-5" />
                        Loan History
                    </h2>
                    {loans.length === 0 ? (
                        <div className="text-center py-12 rounded-2xl border border-white/10 bg-white/5">
                            <div className="mb-4 text-4xl opacity-20">📦</div>
                            <p className="text-gray-400">This user has no loans yet.</p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {loans.map((loan) => (
                                <div
                                    key={loan.id}
                                    className="rounded-2xl border border-white/10 bg-white/5 p-6"
                                >
                                    <div className="flex flex-col lg:flex-row gap-6">
                                        {/* Product Info */}
                                        <div className="flex items-start gap-4 flex-1">
                                            <div className="h-20 w-20 bg-zinc-900 rounded-xl flex-shrink-0 flex items-center justify-center overflow-hidden">
                                                {loan.product.image_url ? (
                                                    <img
                                                        src={loan.product.image_url}
                                                        alt={loan.product.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="text-2xl opacity-30">📦</div>
                                                )}
                                            </div>
                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <h3 className="text-lg font-semibold text-white">
                                                        {loan.product.name}
                                                    </h3>
                                                    <span className={`px-3 py-1 rounded-lg text-xs font-semibold ${STATUS_COLORS[loan.status] || STATUS_COLORS['Requested']}`}>
                                                        {loan.status}
                                                    </span>
                                                </div>
                                                <div className="text-sm text-gray-400 space-y-1">
                                                    <div className="flex items-center gap-2">
                                                        <Calendar className="h-4 w-4" />
                                                        <span>
                                                            {loan.period_from} to {loan.period_to}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <DollarSign className="h-4 w-4" />
                                                        <span>Deposit: {loan.deposit_amount} CR</span>
                                                        {loan.damage_fee && (
                                                            <span className="text-red-400">
                                                                • Damage Fee: {loan.damage_fee} CR
                                                            </span>
                                                        )}
                                                        {loan.refund_amount !== null && (
                                                            <span className="text-green-400">
                                                                • Refunded: {loan.refund_amount} CR
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Loan Details & Actions */}
                                        <div className="lg:w-64 text-sm text-gray-400 space-y-2">
                                            <div>
                                                <span className="text-gray-500">Request ID:</span>
                                                <div className="font-mono text-white">{loan.request_id}</div>
                                            </div>
                                            <div>
                                                <span className="text-gray-500">Created:</span>
                                                <div>{loan.created_at}</div>
                                            </div>
                                            {loan.approved_at && (
                                                <div>
                                                    <span className="text-gray-500">Approved:</span>
                                                    <div>{loan.approved_at}</div>
                                                </div>
                                            )}
                                            {loan.picked_up_at && (
                                                <div>
                                                    <span className="text-gray-500">Picked Up:</span>
                                                    <div>{loan.picked_up_at}</div>
                                                </div>
                                            )}
                                            {loan.returned_at && (
                                                <div>
                                                    <span className="text-gray-500">Returned:</span>
                                                    <div>{loan.returned_at}</div>
                                                </div>
                                            )}

                                            {/* Admin Actions */}
                                            <div className="pt-4 mt-4 border-t border-white/10 space-y-2">
                                                {loan.status === 'Requested' && (
                                                    <>
                                                        <button
                                                            onClick={() => {
                                                                router.post(`/admin/loans/${loan.id}/approve`, {}, {
                                                                    preserveScroll: true,
                                                                });
                                                            }}
                                                            className="w-full rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700 transition flex items-center justify-center gap-2"
                                                        >
                                                            <Check className="h-4 w-4" />
                                                            Approve
                                                        </button>
                                                        <button
                                                            onClick={() => {
                                                                if (confirm('Are you sure you want to reject this loan? The deposit will be refunded.')) {
                                                                    router.post(`/admin/loans/${loan.id}/reject`, {}, {
                                                                        preserveScroll: true,
                                                                    });
                                                                }
                                                            }}
                                                            className="w-full rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700 transition flex items-center justify-center gap-2"
                                                        >
                                                            <X className="h-4 w-4" />
                                                            Reject
                                                        </button>
                                                    </>
                                                )}
                                            {loan.status === 'Approved' && (
                                                <button
                                                    onClick={() => {
                                                        router.post(`/admin/loans/${loan.id}/picked-up`, {}, {
                                                            preserveScroll: true,
                                                        });
                                                    }}
                                                    className="w-full rounded-lg bg-purple-600 px-3 py-2 text-sm font-semibold text-white hover:bg-purple-700 transition flex items-center justify-center gap-2"
                                                >
                                                    <PackageCheck className="h-4 w-4" />
                                                    Mark as Picked Up
                                                </button>
                                            )}
                                            {loan.status === 'Return Requested' && (
                                                <button
                                                    onClick={() => {
                                                        if (confirm('Approve return and refund full amount to user?')) {
                                                            router.post(`/admin/loans/${loan.id}/approve-return`, {}, {
                                                                preserveScroll: true,
                                                            });
                                                        }
                                                    }}
                                                    className="w-full rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700 transition flex items-center justify-center gap-2"
                                                >
                                                    <Check className="h-4 w-4" />
                                                    Approve Return
                                                </button>
                                            )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
