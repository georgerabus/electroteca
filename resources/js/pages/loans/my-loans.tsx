import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AlertCircle, ArrowLeft, Calendar, CheckCircle, Clock, DollarSign, Package, XCircle } from 'lucide-react';
import { type SharedData } from '@/types';

type Loan = {
    id: number;
    request_id: string;
    status: string;
    product: {
        id: number;
        name: string;
        slug: string;
        image_url?: string;
    };
    period_from: string;
    period_to: string;
    deposit_amount: string;
    damage_fee: string | null;
    refund_amount: string | null;
    details: string | null;
    created_at: string;
    returned_at: string | null;
};

type MyLoansPageProps = {
    loans: Loan[];
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'Returned':
            return 'bg-green-500/20 text-green-400 border-green-500/50';
        case 'Defective':
            return 'bg-red-500/20 text-red-400 border-red-500/50';
        case 'Picked up':
        case 'Approved':
            return 'bg-blue-500/20 text-blue-400 border-blue-500/50';
        case 'Return Requested':
            return 'bg-orange-500/20 text-orange-400 border-orange-500/50';
        case 'Requested':
            return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/50';
        case 'Rejected':
        case 'Cancelled':
            return 'bg-gray-500/20 text-gray-400 border-gray-500/50';
        default:
            return 'bg-gray-500/20 text-gray-400 border-gray-500/50';
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'Returned':
            return <CheckCircle className="h-4 w-4" />;
        case 'Defective':
        case 'Rejected':
        case 'Cancelled':
            return <XCircle className="h-4 w-4" />;
        case 'Return Requested':
            return <Clock className="h-4 w-4" />;
        default:
            return <Package className="h-4 w-4" />;
    }
};

export default function MyLoans({ loans }: MyLoansPageProps) {
    const { auth } = usePage<SharedData>().props;
    const [returningLoanIds, setReturningLoanIds] = useState<Set<number>>(new Set());

    const handleRequestReturn = (loanId: number) => {
        // Don't allow multiple clicks on the same button
        if (returningLoanIds.has(loanId)) {
            return;
        }

        // Add to set of returning loans
        setReturningLoanIds(prev => new Set(prev).add(loanId));

        router.post(`/loans/${loanId}/request-return`, {}, {
            onSuccess: () => {
                // Remove from set when successful
                setReturningLoanIds(prev => {
                    const next = new Set(prev);
                    next.delete(loanId);
                    return next;
                });
            },
            onError: () => {
                // Remove from set on error too
                setReturningLoanIds(prev => {
                    const next = new Set(prev);
                    next.delete(loanId);
                    return next;
                });
            },
        });
    };

    const canRequestReturn = (status: string) => {
        return ['Approved', 'Picked up', 'Late'].includes(status);
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Shop', href: '/shop' }, { title: 'My Loans', href: '/loans/my-loans' }]}>
            <Head title="My Loans" />
            <div className="mx-auto max-w-4xl p-4 sm:p-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold mb-2">My Loans</h1>
                        <p className="text-gray-400">Manage your borrowed products</p>
                    </div>
                    <Link
                        href="/shop"
                        className="inline-flex items-center gap-2 text-sm text-black dark:text-gray-400 hover:text-white transition"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Shop
                    </Link>
                </div>

                {loans.length === 0 ? (
                    <div className="text-center my-24">
                        <div className="mb-4 text-4xl opacity-20">📦</div>
                        <p className="mb-6 text-lg text-gray-300">You don't have any loans yet.</p>
                        <Link href="/shop" className="mt-4 inline-block rounded-xl px-6 py-3 bg-red-600 text-white font-semibold hover:bg-red-700 transition">
                            Browse Products
                        </Link>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {loans.map((loan) => (
                            <div
                                key={loan.id}
                                className="rounded-2xl border border-white/10 bg-white/5 p-6"
                            >
                                <div className="flex flex-col md:flex-row gap-6">
                                    {/* Product Image */}
                                    <div className="flex-shrink-0">
                                        <Link href={`/shop/${loan.product.slug}`}>
                                            <div className="h-32 w-32 bg-zinc-900 rounded-xl flex items-center justify-center overflow-hidden">
                                                {loan.product.image_url ? (
                                                    <img
                                                        src={loan.product.image_url}
                                                        alt={loan.product.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="text-3xl opacity-30">📦</div>
                                                )}
                                            </div>
                                        </Link>
                                    </div>

                                    {/* Loan Details */}
                                    <div className="flex-1">
                                        <div className="flex items-start justify-between mb-4">
                                            <div>
                                                <Link
                                                    href={`/shop/${loan.product.slug}`}
                                                    className="text-xl font-semibold text-white hover:text-red-400 transition"
                                                >
                                                    {loan.product.name}
                                                </Link>
                                                <p className="text-sm text-gray-400 mt-1">Request ID: {loan.request_id}</p>
                                            </div>
                                            <span
                                                className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border text-sm font-medium ${getStatusColor(loan.status)}`}
                                            >
                                                {getStatusIcon(loan.status)}
                                                {loan.status}
                                            </span>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div className="flex items-center gap-2 text-sm text-gray-400">
                                                <Calendar className="h-4 w-4" />
                                                <span>
                                                    {new Date(loan.period_from).toLocaleDateString()} -{' '}
                                                    {new Date(loan.period_to).toLocaleDateString()}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2 text-sm text-gray-400">
                                                <DollarSign className="h-4 w-4" />
                                                <span>Deposit: {loan.deposit_amount} CR</span>
                                            </div>
                                            {loan.refund_amount && (
                                                <div className="flex items-center gap-2 text-sm text-green-400">
                                                    <CheckCircle className="h-4 w-4" />
                                                    <span>Refunded: {loan.refund_amount} CR</span>
                                                </div>
                                            )}
                                            {loan.damage_fee && (
                                                <div className="flex items-center gap-2 text-sm text-red-400">
                                                    <AlertCircle className="h-4 w-4" />
                                                    <span>Damage Fee: {loan.damage_fee} CR</span>
                                                </div>
                                            )}
                                        </div>

                                        {loan.details && (
                                            <p className="text-sm text-gray-300 mb-4">{loan.details}</p>
                                        )}

                                        {loan.returned_at && (
                                            <p className="text-xs text-gray-500 mb-4">
                                                Returned on: {new Date(loan.returned_at).toLocaleString()}
                                            </p>
                                        )}

                                        {/* Return Request Button */}
                                        {canRequestReturn(loan.status) && (
                                            <div className="mt-4">
                                                <button
                                                    onClick={() => handleRequestReturn(loan.id)}
                                                    disabled={returningLoanIds.has(loan.id)}
                                                    className="w-full rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    {returningLoanIds.has(loan.id) ? 'Submitting...' : 'Request Return'}
                                                </button>
                                            </div>
                                        )}

                                        {/* Return Requested Status */}
                                        {loan.status === 'Return Requested' && (
                                            <div className="mt-4 p-4 bg-yellow-500/20 border border-yellow-500/50 rounded-lg">
                                                <p className="text-sm text-yellow-400">
                                                    Return request submitted. Waiting for admin approval. You will receive a full refund of {loan.deposit_amount} CR once approved.
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

