import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ChangeEvent, useMemo, useState, useEffect } from 'react';

type Status =
    | 'Requested'
    | 'Approved'
    | 'Picked up'
    | 'Late'
    | 'Returned'
    | 'Rejected'
    | 'Defective'
    | 'Cancelled';

type Row = {
    id: string;
    status: Status;
    product: string;
    requester: { name: string; email: string };
    period: { from: string; to: string };
    requestedAt: string;
    details?: string;
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
        adjustment: number;
    };
    history: ReputationChange[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

type DashboardPageProps = {
    loanRequests: Row[];
    products: string[];
    reputation?: Reputation | null;
    filters?: {
        status?: string;
        product?: string;
        search?: string;
    };
};

// Make options `as const`, then derive their union types
const STATUS_OPTIONS = [
    'All',
    'Requested',
    'Approved',
    'Picked up',
    'Late',
    'Returned',
    'Rejected',
    'Defective',
    'Cancelled',
] as const;
type StatusOption = (typeof STATUS_OPTIONS)[number];

type ProductOption = string;

// ---------- small UI helpers ----------
const statusTone: Record<Status, string> = {
    Returned: 'text-emerald-400 bg-emerald-400/10 ring-1 ring-emerald-400/20',
    Defective: 'text-rose-400 bg-rose-400/10 ring-1 ring-rose-400/20',
    Rejected: 'text-rose-300 bg-rose-300/10 ring-1 ring-rose-300/20',
    'Picked up': 'text-blue-300 bg-blue-300/10 ring-1 ring-blue-300/20',
    Approved: 'text-sky-300 bg-sky-300/10 ring-1 ring-sky-300/20',
    'Return Requested': 'text-orange-300 bg-orange-300/10 ring-1 ring-orange-300/20',
    Late: 'text-amber-300 bg-amber-300/10 ring-1 ring-amber-300/20',
    Requested: 'text-zinc-300 bg-zinc-300/10 ring-1 ring-zinc-300/20',
    Cancelled: 'text-zinc-400 bg-zinc-400/10 ring-1 ring-zinc-400/20',
};

const getReputationLabel = (rating: number) => {
    if (rating >= 80) return 'Trusted';
    if (rating >= 60) return 'Reliable';
    if (rating >= 40) return 'Steady';
    if (rating >= 20) return 'New';
    return 'At Risk';
};

function Badge({
    children,
    tone,
}: {
    children: React.ReactNode;
    tone: string;
}) {
    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs ${tone}`}
        >
            {children}
        </span>
    );
}

function ToolbarSelect(
    props: React.DetailedHTMLProps<
        React.SelectHTMLAttributes<HTMLSelectElement>,
        HTMLSelectElement
    >,
) {
    return (
        <div className="relative w-full md:w-72">
            <select
                {...props}
                className="peer w-full appearance-none rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-black dark:text-white backdrop-blur transition outline-none hover:bg-white/7 focus:border-white/20"
            />
            <div className="pointer-events-none absolute inset-y-0 right-3 flex items-center opacity-60">
                ▾
            </div>
        </div>
    );
}

// ---------- page ----------
export default function Dashboard({ loanRequests, products, filters, reputation }: DashboardPageProps) {
    const [status, setStatus] = useState<StatusOption>((filters?.status as StatusOption) || 'All');
    const [product, setProduct] = useState<ProductOption>(filters?.product || 'All products');
    const [query, setQuery] = useState(filters?.search || '');

    // Typed handlers (no `any`)
    const onStatusChange = (e: ChangeEvent<HTMLSelectElement>) => {
        const val = STATUS_OPTIONS.find((s) => s === e.target.value) ?? 'All';
        setStatus(val);
    };
    const onProductChange = (e: ChangeEvent<HTMLSelectElement>) => {
        setProduct(e.target.value);
    };

    // Update URL when filters change
    useEffect(() => {
        const params: Record<string, string> = {};
        if (status !== 'All') params.status = status;
        if (product !== 'All products') params.product = product;
        if (query) params.search = query;

        router.get(dashboard().url, params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, [status, product, query]);

    // Use loanRequests directly from backend (already filtered)
    const rows = loanRequests;
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-[1200px] p-4 md:p-6">
                {reputation && (
                    <div className="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-6 shadow-[0_10px_30px_-15px_rgba(0,0,0,0.6)] ring-1 ring-white/10 backdrop-blur">
                            <div className="text-sm text-black/60 dark:text-gray-400">Your Reputation</div>
                            <div className="mt-2 flex items-end justify-between gap-3">
                                <div>
                                    <div className="text-3xl font-bold text-black dark:text-white">{reputation.score}</div>
                                    <div className="text-sm text-black/60 dark:text-gray-400">
                                        {getReputationLabel(reputation.rating)} • {reputation.rating}/100
                                    </div>
                                </div>
                                <div className="text-xs text-black/60 dark:text-gray-400 text-right">
                                    +10 loan • +5 order • -20 damage
                                </div>
                            </div>
                            <div className="mt-4 h-2 rounded-full bg-black/10 dark:bg-white/10">
                                <div
                                    className="h-2 rounded-full bg-emerald-400 transition-all"
                                    style={{ width: `${reputation.rating}%` }}
                                />
                            </div>
                            <div className="mt-4 grid grid-cols-2 gap-3 text-xs text-black/60 dark:text-gray-400">
                                <div>
                                    <div>Completed loans</div>
                                    <div className="text-black dark:text-white font-semibold">{reputation.stats.completed_loans}</div>
                                </div>
                                <div>
                                    <div>Completed orders</div>
                                    <div className="text-black dark:text-white font-semibold">{reputation.stats.completed_orders}</div>
                                </div>
                                <div>
                                    <div>Items damaged</div>
                                    <div className="text-black dark:text-white font-semibold">{reputation.stats.items_damaged}</div>
                                </div>
                                <div>
                                    <div>On-time returns</div>
                                    <div className="text-black dark:text-white font-semibold">{reputation.stats.returns_on_time}</div>
                                </div>
                                <div>
                                    <div>Admin adjustments</div>
                                    <div className="text-black dark:text-white font-semibold">{reputation.stats.adjustment}</div>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-6 shadow-[0_10px_30px_-15px_rgba(0,0,0,0.6)] ring-1 ring-white/10 backdrop-blur">
                            <div className="text-lg font-semibold text-black dark:text-white mb-3">How it works</div>
                            <div className="space-y-2 text-sm text-black/60 dark:text-gray-400">
                                <div>Each completed loan adds <span className="text-black dark:text-white font-semibold">+10</span>.</div>
                                <div>Each completed order adds <span className="text-black dark:text-white font-semibold">+5</span>.</div>
                                <div>Each damaged item subtracts <span className="text-black dark:text-white font-semibold">-20</span>.</div>
                                <div>On-time returns are tracked for insights.</div>
                                <div>Admin adjustments can add or remove points when needed.</div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-6 shadow-[0_10px_30px_-15px_rgba(0,0,0,0.6)] ring-1 ring-white/10 backdrop-blur">
                            <div className="text-lg font-semibold text-black dark:text-white mb-3">Recent Changes</div>
                            {reputation.history.length === 0 ? (
                                <div className="text-sm text-black/60 dark:text-gray-400">No reputation changes yet.</div>
                            ) : (
                                <div className="space-y-2 text-sm">
                                    {reputation.history.map((entry) => {
                                        const isPositive = entry.change >= 0;
                                        const reason = entry.reason.replace(/_/g, ' ');

                                        return (
                                            <div
                                                key={entry.id}
                                                className="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-3 py-2"
                                            >
                                                <div>
                                                    <div className="text-black dark:text-white capitalize">{reason}</div>
                                                    <div className="text-xs text-black/60 dark:text-gray-400">{entry.created_at}</div>
                                                </div>
                                                <div className={isPositive ? 'text-emerald-400 font-semibold' : 'text-rose-400 font-semibold'}>
                                                    {isPositive ? '+' : ''}{entry.change}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* header */}
                <div className="mb-4 flex items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold tracking-tight text-black dark:text-white">
                        Loans
                    </h1>
                    <span className="hidden text-sm text-black dark:text-white md:block">
                        {rows.length} result{rows.length === 1 ? '' : 's'}
                    </span>
                </div>

                {/* toolbar */}
                <div className="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                    <ToolbarSelect value={status} onChange={onStatusChange}>
                        {STATUS_OPTIONS.map((s) => (
                            <option key={s} value={s}>{s === 'All' ? 'All statuses' : s}</option>
                        ))}
                    </ToolbarSelect>

                    <ToolbarSelect value={product} onChange={onProductChange}>
                        <option value="All products">All products</option>
                        {products.map((p) => (
                            <option key={p} value={p}>{p}</option>
                        ))}
                    </ToolbarSelect>

                    <input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Search requester, product, status…"
                        className="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm backdrop-blur transition outline-none placeholder:text-zinc-400 hover:bg-white/7 focus:border-white/20 md:col-span-2"
                    />
                </div>

                {/* table card */}
                <div className="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] shadow-[0_10px_30px_-15px_rgba(0,0,0,0.6)] ring-1 ring-white/10 backdrop-blur">
                    <div className="max-h-[65vh] overflow-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="sticky top-0 z-10 bg-black/50 backdrop-blur">
                                <tr className="text-black dark:text-white">
                                    <th className="px-5 py-4 font-medium">
                                        Status
                                    </th>
                                    <th className="px-5 py-4 font-medium">
                                        Product
                                    </th>
                                    <th className="px-5 py-4 font-medium">
                                        Requester
                                    </th>
                                    <th className="px-5 py-4 font-medium">
                                        Period
                                    </th>
                                    <th className="px-5 py-4 font-medium">
                                        Requested
                                    </th>
                                    <th className="px-5 py-4 font-medium">
                                        Details
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((r, i) => (
                                    <tr
                                        key={r.id}
                                        className={
                                            i % 2 ? 'bg-white/[0.025]' : ''
                                        }
                                    >
                                        <td className="px-5 py-4">
                                            <Badge tone={statusTone[r.status]}>
                                                {r.status}
                                            </Badge>
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="text-black dark:text-white font-medium">{r.product}</div>
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="text-black dark:text-white">{r.requester.name}</div>
                                            <div className="text-xs text-black dark:text-white">
                                                {r.requester.email}
                                            </div>
                                        </td>
                                        <td className="px-5 py-4">
                                            {r.period.from} —{' '}
                                            <span className="whitespace-nowrap">
                                                {r.period.to}
                                            </span>
                                        </td>
                                        <td className="px-5 py-4">
                                            {r.requestedAt}
                                        </td>
                                        <td className="px-5 py-4">
                                            {r.details ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                                {rows.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-5 py-12 text-center text-sm text-black dark:text-white"
                                        >
                                            No matches. Adjust filters or
                                            search.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* footer */}
                    <div className="flex items-center justify-between border-t border-white/10 px-5 py-3 text-xs text-black dark:text-white">
                        <span>
                            {rows.length} result{rows.length === 1 ? '' : 's'}
                        </span>
                        <button
                            type="button"
                            onClick={() => {
                                setStatus('All');
                                setProduct('All products');
                                setQuery('');
                            }}
                            className="rounded-full border border-white/10 px-3 py-1.5 hover:bg-white/5 text-black dark:text-white"
                        >
                            Clear filters
                        </button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
