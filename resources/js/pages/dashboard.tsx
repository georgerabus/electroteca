import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ChangeEvent, useMemo, useState } from 'react';

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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

// ---------- dummy rows (frontend-only) ----------
const ALL_ROWS: Row[] = [
    {
        id: 'R-1001',
        status: 'Picked up',
        product: 'Jumper Wires',
        requester: { name: 'Mihai T.', email: 'mihai.traian@utm.md' },
        period: { from: '2025-09-03', to: '2025-09-10' },
        requestedAt: '2025-09-03, 14:10:00',
        details: '—',
    },
    {
        id: 'R-1002',
        status: 'Returned',
        product: 'Electronic Cable',
        requester: { name: 'Ana Popescu', email: 'ana.popescu@utm.md' },
        period: { from: '2025-09-02', to: '2025-09-09' },
        requestedAt: '2025-09-02, 15:30:00',
        details: 'Returned · 2025-09-09, 18:05:00',
    },
    {
        id: 'R-1003',
        status: 'Defective',
        product: '4-in-1 Breadboard',
        requester: { name: 'Ion Marin', email: 'ion.marin@utm.md' },
        period: { from: '2025-09-01', to: '2025-09-07' },
        requestedAt: '2025-09-01, 13:00:00',
        details: 'Defective · 2025-09-05, 16:00:00',
    },
];

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

const PRODUCT_OPTIONS = [
    'All products',
    '4-in-1 Breadboard',
    'Electronic Cable',
    'Connecting Cables',
    'Solder Paste',
    'Unloaded circuit board',
    'ESP32 Expansion Board',
    'PCB Test Board',
    'Double Sided PCB Protoboard Boards',
    'Precision Screwdriver Set',
    'Nylon Soldering Station clamp holder',
    'Heat-Shrink Tubes',
] as const;
type ProductOption = (typeof PRODUCT_OPTIONS)[number];

// ---------- small UI helpers ----------
const statusTone: Record<Status, string> = {
    Returned: 'text-emerald-400 bg-emerald-400/10 ring-1 ring-emerald-400/20',
    Defective: 'text-rose-400 bg-rose-400/10 ring-1 ring-rose-400/20',
    Rejected: 'text-rose-300 bg-rose-300/10 ring-1 ring-rose-300/20',
    'Picked up': 'text-blue-300 bg-blue-300/10 ring-1 ring-blue-300/20',
    Approved: 'text-sky-300 bg-sky-300/10 ring-1 ring-sky-300/20',
    Late: 'text-amber-300 bg-amber-300/10 ring-1 ring-amber-300/20',
    Requested: 'text-zinc-300 bg-zinc-300/10 ring-1 ring-zinc-300/20',
    Cancelled: 'text-zinc-400 bg-zinc-400/10 ring-1 ring-zinc-400/20',
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
                className="peer w-full appearance-none rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm backdrop-blur transition outline-none hover:bg-white/7 focus:border-white/20"
            />
            <div className="pointer-events-none absolute inset-y-0 right-3 flex items-center opacity-60">
                ▾
            </div>
        </div>
    );
}

// ---------- page ----------
export default function Dashboard() {
    const [status, setStatus] = useState<StatusOption>('All');
    const [product, setProduct] = useState<ProductOption>('All products');
    const [query, setQuery] = useState('');

    // Typed handlers (no `any`)
    const onStatusChange = (e: ChangeEvent<HTMLSelectElement>) => {
        const val = STATUS_OPTIONS.find((s) => s === e.target.value) ?? 'All';
        setStatus(val);
    };
    const onProductChange = (e: ChangeEvent<HTMLSelectElement>) => {
        const val =
            PRODUCT_OPTIONS.find((p) => p === e.target.value) ?? 'All products';
        setProduct(val);
    };

    const rows = useMemo(() => {
        const q = query.trim().toLowerCase();
        return ALL_ROWS.filter((r) => {
            const okStatus = status === 'All' || r.status === status;
            const okProduct =
                product === 'All products' || r.product === product;
            const okQuery =
                q.length === 0 ||
                r.product.toLowerCase().includes(q) ||
                r.requester.name.toLowerCase().includes(q) ||
                r.requester.email.toLowerCase().includes(q) ||
                r.status.toLowerCase().includes(q);
            return okStatus && okProduct && okQuery;
        });
    }, [status, product, query]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-[1200px] p-4 md:p-6">
                {/* header */}
                <div className="mb-4 flex items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Loans
                    </h1>
                    <span className="hidden text-sm opacity-60 md:block">
                        {rows.length} result{rows.length === 1 ? '' : 's'}
                    </span>
                </div>

                {/* toolbar */}
                <div className="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                    <ToolbarSelect value={status} onChange={onStatusChange}>
                        {STATUS_OPTIONS.map((s) => (
                            <option key={s} value={s} className="bg-zinc-900">
                                {s === 'All' ? 'All statuses' : s}
                            </option>
                        ))}
                    </ToolbarSelect>

                    <ToolbarSelect value={product} onChange={onProductChange}>
                        {PRODUCT_OPTIONS.map((p) => (
                            <option key={p} value={p} className="bg-zinc-900">
                                {p}
                            </option>
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
                                <tr className="text-zinc-300">
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
                                            {r.product}
                                        </td>
                                        <td className="px-5 py-4">
                                            <div>{r.requester.name}</div>
                                            <div className="text-xs opacity-60">
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
                                            className="px-5 py-12 text-center text-sm opacity-70"
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
                    <div className="flex items-center justify-between border-t border-white/10 px-5 py-3 text-xs opacity-70">
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
                            className="rounded-full border border-white/10 px-3 py-1.5 hover:bg-white/5"
                        >
                            Clear filters
                        </button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
