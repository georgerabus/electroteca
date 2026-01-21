import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

type ReputationChange = {
    id: number;
    change: number;
    reason: string;
    created_at: string;
};

type ReputationTierSummary = {
    id: number;
    name: string;
    min_score: number;
    discount_percent: number;
    description: string | null;
};

type ReputationTier = ReputationTierSummary & {
    is_active: boolean;
    is_unlocked: boolean;
    is_current: boolean;
};

type ReputationData = {
    score: number;
    rating: number;
    discount_percent: number;
    current_tier: ReputationTierSummary | null;
    next_tier: ReputationTierSummary | null;
    points_to_next: number | null;
    progress_percent: number;
    stats: {
        completed_loans: number;
        completed_orders: number;
        items_damaged: number;
        returns_on_time: number;
        adjustment: number;
    };
    history: ReputationChange[];
    tiers: ReputationTier[];
};

type ReputationPageProps = {
    reputation: ReputationData;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reputation', href: '/reputation' },
];

const getReputationLabel = (rating: number) => {
    if (rating >= 80) return 'Trusted';
    if (rating >= 60) return 'Reliable';
    if (rating >= 40) return 'Steady';
    if (rating >= 20) return 'New';
    return 'At Risk';
};

export default function ReputationPage({ reputation }: ReputationPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Your Reputation" />

            <div className="mx-auto max-w-[1200px] p-4 md:p-6">
                <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-black dark:text-white">
                            Your Reputation
                        </h1>
                        <p className="text-sm text-black/60 dark:text-gray-400">
                            Track trust signals, unlock discounts, and see your next milestone.
                        </p>
                    </div>
                    <Link
                        href="/dashboard"
                        className="text-sm text-black/70 hover:text-black dark:text-gray-400 dark:hover:text-white transition"
                    >
                        Back to dashboard
                    </Link>
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-6 shadow-[0_10px_30px_-15px_rgba(0,0,0,0.6)] ring-1 ring-white/10 backdrop-blur lg:col-span-2">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div className="text-sm text-black/60 dark:text-gray-400">Reputation score</div>
                                <div className="text-3xl font-bold text-black dark:text-white">{reputation.score}</div>
                                <div className="text-sm text-black/60 dark:text-gray-400">
                                    {getReputationLabel(reputation.rating)} - {reputation.rating}/100
                                </div>
                            </div>
                            <div className="text-right">
                                <div className="text-sm text-black/60 dark:text-gray-400">Current discount</div>
                                <div className="text-2xl font-semibold text-black dark:text-white">
                                    {reputation.discount_percent > 0
                                        ? `${reputation.discount_percent}% off`
                                        : 'No discount yet'}
                                </div>
                                <div className="text-xs text-black/60 dark:text-gray-400">
                                    Tier: {reputation.current_tier?.name ?? 'None'}
                                </div>
                            </div>
                        </div>

                        <div className="mt-4 h-2 rounded-full bg-black/10 dark:bg-white/10">
                            <div
                                className="h-2 rounded-full bg-emerald-400 transition-all"
                                style={{ width: `${reputation.progress_percent}%` }}
                            />
                        </div>
                        <div className="mt-2 text-xs text-black/60 dark:text-gray-400">
                            {reputation.next_tier
                                ? `${reputation.points_to_next} points to reach ${reputation.next_tier.name} (${reputation.next_tier.discount_percent}% off)`
                                : 'Top tier unlocked'}
                        </div>

                        <div className="mt-6 grid grid-cols-2 gap-3 text-xs text-black/60 dark:text-gray-400 md:grid-cols-5">
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
                        <div className="text-lg font-semibold text-black dark:text-white">How it works</div>
                        <div className="mt-3 space-y-2 text-sm text-black/60 dark:text-gray-400">
                            <div>Completed loan: <span className="text-black dark:text-white font-semibold">+10</span></div>
                            <div>Completed order: <span className="text-black dark:text-white font-semibold">+5</span></div>
                            <div>Damaged item: <span className="text-black dark:text-white font-semibold">-20</span></div>
                            <div>On-time returns are tracked for insights.</div>
                            <div>Admins can adjust scores when needed.</div>
                        </div>
                    </div>
                </div>

                <div className="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-6 shadow-[0_10px_30px_-15px_rgba(0,0,0,0.6)] ring-1 ring-white/10 backdrop-blur">
                        <div className="text-lg font-semibold text-black dark:text-white">Milestones</div>
                        <div className="mt-4 space-y-3">
                            {reputation.tiers.length === 0 ? (
                                <div className="text-sm text-black/60 dark:text-gray-400">
                                    No milestones configured yet.
                                </div>
                            ) : (
                                reputation.tiers.map((tier) => (
                                    <div
                                        key={tier.id}
                                        className={`rounded-xl border px-4 py-3 ${
                                            tier.is_current
                                                ? 'border-emerald-400/50 bg-emerald-400/10'
                                                : 'border-white/10 bg-white/5'
                                        }`}
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <div className="text-sm font-semibold text-black dark:text-white">{tier.name}</div>
                                                <div className="text-xs text-black/60 dark:text-gray-400">
                                                    Unlock at {tier.min_score} points
                                                </div>
                                            </div>
                                            <div className="text-sm font-semibold text-emerald-400">
                                                {tier.discount_percent}% off
                                            </div>
                                        </div>
                                        {tier.description && (
                                            <div className="mt-2 text-xs text-black/60 dark:text-gray-400">
                                                {tier.description}
                                            </div>
                                        )}
                                        <div className="mt-2 text-xs text-black/60 dark:text-gray-400">
                                            {tier.is_current
                                                ? 'Current tier'
                                                : tier.is_unlocked
                                                    ? 'Unlocked'
                                                    : 'Locked'}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-6 shadow-[0_10px_30px_-15px_rgba(0,0,0,0.6)] ring-1 ring-white/10 backdrop-blur">
                        <div className="text-lg font-semibold text-black dark:text-white">Recent changes</div>
                        <div className="mt-4 space-y-2 text-sm">
                            {reputation.history.length === 0 ? (
                                <div className="text-sm text-black/60 dark:text-gray-400">
                                    No reputation changes yet.
                                </div>
                            ) : (
                                reputation.history.map((entry) => {
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
                                })
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
