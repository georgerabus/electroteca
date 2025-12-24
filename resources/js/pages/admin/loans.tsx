import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Package, User, Calendar, DollarSign, Filter, Check, X, PackageCheck } from 'lucide-react';
import { useState } from 'react';

type Loan = {
    id: number;
    request_id: string;
    status: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
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
};

type AdminLoansPageProps = {
    loans: Loan[];
    users: User[];
    filters: {
        status: string;
        user_id?: string;
    };
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

export default function AdminLoans({ loans, users, filters }: AdminLoansPageProps) {
    const [statusFilter, setStatusFilter] = useState(filters.status);
    const [userFilter, setUserFilter] = useState(filters.user_id || '');

    const handleFilter = () => {
        const params: Record<string, string> = {};
        if (statusFilter !== 'All') {
            params.status = statusFilter;
        }
        if (userFilter) {
            params.user_id = userFilter;
        }
        router.get('/admin/loans', params, { preserveState: true });
    };

    const filteredLoans = loans.filter(loan => {
        if (statusFilter !== 'All' && loan.status !== statusFilter) {
            return false;
        }
        if (userFilter && loan.user.id.toString() !== userFilter) {
            return false;
        }
        return true;
    });

    return (
        <AppLayout breadcrumbs={[{ title: 'Admin', href: '/admin' }, { title: 'Loans', href: '/admin/loans' }]}>
            <Head title="Admin - Loans" />
            <div className="mx-auto max-w-full lg:max-w-[1400px] xl:max-w-[1800px] p-4 sm:p-8">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-3xl font-bold flex items-center gap-2">
                        <Package className="h-8 w-8" />
                        All Loans
                    </h1>
                </div>

                {/* Filters */}
                <div className="mb-6 rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div className="flex items-center gap-4 flex-wrap">
                        <div className="flex items-center gap-2">
                            <Filter className="h-5 w-5 text-gray-400" />
                            <span className="text-sm text-gray-400">Filters:</span>
                        </div>
                        <select
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                            className="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none"
                        >
                            <option value="All">All Statuses</option>
                            <option value="Requested">Requested</option>
                            <option value="Approved">Approved</option>
                            <option value="Picked up">Picked up</option>
                            <option value="Late">Late</option>
                            <option value="Return Requested">Return Requested</option>
                            <option value="Returned">Returned</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Defective">Defective</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                        <select
                            value={userFilter}
                            onChange={(e) => setUserFilter(e.target.value)}
                            className="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none"
                        >
                            <option value="">All Users</option>
                            {users.map((user) => (
                                <option key={user.id} value={user.id.toString()}>
                                    {user.name} ({user.email})
                                </option>
                            ))}
                        </select>
                        <button
                            onClick={handleFilter}
                            className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition"
                        >
                            Apply Filters
                        </button>
                    </div>
                </div>

                {/* Loans List */}
                {filteredLoans.length === 0 ? (
                    <div className="text-center my-24">
                        <div className="mb-4 text-4xl opacity-20">📦</div>
                        <p className="mb-4 text-lg text-gray-300">No loans found.</p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {filteredLoans.map((loan) => (
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
                                                    <User className="h-4 w-4" />
                                                    <Link
                                                        href={`/admin/users/${loan.user.id}/dashboard`}
                                                        className="hover:text-white hover:underline"
                                                    >
                                                        {loan.user.name} ({loan.user.email})
                                                    </Link>
                                                </div>
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

                <div className="mt-6 text-sm text-gray-400">
                    Showing {filteredLoans.length} of {loans.length} loans
                </div>
            </div>
        </AppLayout>
    );
}

