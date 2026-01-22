import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { createPortal } from 'react-dom';

type Dispute = {
    id: number;
    order_id: number;
    loan_request_id?: number | null;
    title: string;
    reason: string;
    status: string;
    damage_claim_amount?: string | null;
    created_at?: string | null;
    initiator?: {
        id?: number | null;
        name?: string | null;
        email?: string | null;
    };
    respondent?: {
        id?: number | null;
        name?: string | null;
        email?: string | null;
    };
};

type DisputesPageProps = {
    disputes: Dispute[];
};

export default function AdminDisputes({ disputes }: DisputesPageProps) {
    const [activeDispute, setActiveDispute] = useState<Dispute | null>(null);
    const [resolution, setResolution] = useState('initiator_wins');
    const [resolutionNotes, setResolutionNotes] = useState('');
    const [approvedDeduction, setApprovedDeduction] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const portalTarget = typeof document === 'undefined' ? null : document.body;

    const openResolve = (dispute: Dispute) => {
        setActiveDispute(dispute);
        setResolution('initiator_wins');
        setResolutionNotes('');
        setApprovedDeduction('');
        setError(null);
    };

    const closeResolve = () => {
        setActiveDispute(null);
        setError(null);
    };

    const submitResolution = async () => {
        if (!activeDispute) {
            return;
        }

        if (!resolutionNotes.trim()) {
            setError('Resolution notes are required.');
            return;
        }

        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;

        setSubmitting(true);
        setError(null);

        try {
            const response = await fetch(`/admin/disputes/${activeDispute.id}/resolve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify({
                    resolution,
                    resolution_notes: resolutionNotes.trim(),
                    approved_deduction_amount: approvedDeduction ? Number(approvedDeduction) : null,
                }),
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                setError(data.error || 'Failed to resolve dispute.');
                return;
            }

            closeResolve();
            router.reload();
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to resolve dispute.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Admin', href: '/admin' }, { title: 'Disputes', href: '/admin/disputes' }]}>
            <Head title="Admin - Disputes" />

            <div className="mx-auto max-w-5xl p-4 sm:p-8">
                <h1 className="text-3xl font-bold mb-6">Disputes</h1>

                {disputes.length === 0 ? (
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-6 text-gray-300">
                        No disputes found.
                    </div>
                ) : (
                    <div className="space-y-4">
                        {disputes.map((dispute) => (
                            <div key={dispute.id} className="rounded-2xl border border-white/10 bg-white/5 p-6">
                                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <div className="text-lg font-semibold text-white">{dispute.title}</div>
                                        <div className="text-sm text-gray-400">
                                            Order #{dispute.order_id} · Reason: {dispute.reason}
                                        </div>
                                        {dispute.loan_request_id && (
                                            <div className="text-xs text-gray-500">
                                                Loan ID: {dispute.loan_request_id}
                                            </div>
                                        )}
                                        {dispute.created_at && (
                                            <div className="text-xs text-gray-500">Created: {dispute.created_at}</div>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className="rounded-lg border border-white/10 px-3 py-1 text-xs uppercase text-gray-300">
                                            {dispute.status}
                                        </span>
                                        {dispute.status !== 'resolved' && dispute.status !== 'closed' && (
                                            <button
                                                type="button"
                                                onClick={() => openResolve(dispute)}
                                                className="rounded-lg bg-amber-500 px-3 py-1 text-sm font-semibold text-black hover:bg-amber-400"
                                            >
                                                Resolve
                                            </button>
                                        )}
                                    </div>
                                </div>
                                <div className="mt-4 grid grid-cols-1 gap-3 text-sm text-gray-300 md:grid-cols-2">
                                    <div>
                                        <div className="text-xs text-gray-500">Initiator</div>
                                        <div>{dispute.initiator?.name ?? 'Unknown'}</div>
                                        <div className="text-xs text-gray-500">{dispute.initiator?.email}</div>
                                    </div>
                                    <div>
                                        <div className="text-xs text-gray-500">Respondent</div>
                                        <div>{dispute.respondent?.name ?? 'Unknown'}</div>
                                        <div className="text-xs text-gray-500">{dispute.respondent?.email}</div>
                                    </div>
                                    {dispute.damage_claim_amount && (
                                        <div>
                                            <div className="text-xs text-gray-500">Claim Amount</div>
                                            <div>{dispute.damage_claim_amount}</div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {activeDispute && portalTarget
                ? createPortal(
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
                        <div className="w-full max-w-lg rounded-2xl border border-white/10 bg-zinc-900 p-6 text-white">
                            <h2 className="text-xl font-semibold mb-2">Resolve Dispute</h2>
                            <p className="text-sm text-gray-400 mb-4">
                                Dispute #{activeDispute.id} · {activeDispute.title}
                            </p>

                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm text-gray-300 mb-1">Resolution</label>
                                    <select
                                        value={resolution}
                                        onChange={(e) => setResolution(e.target.value)}
                                        className="w-full rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm"
                                    >
                                        <option value="initiator_wins">Initiator wins</option>
                                        <option value="respondent_wins">Respondent wins</option>
                                        <option value="compromise">Compromise</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm text-gray-300 mb-1">Resolution notes</label>
                                    <textarea
                                        value={resolutionNotes}
                                        onChange={(e) => setResolutionNotes(e.target.value)}
                                        className="w-full rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm min-h-[120px]"
                                        placeholder="Explain the decision..."
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm text-gray-300 mb-1">Approved deduction (optional)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={approvedDeduction}
                                        onChange={(e) => setApprovedDeduction(e.target.value)}
                                        className="w-full rounded-lg bg-black/40 border border-white/10 px-3 py-2 text-sm"
                                        placeholder="0.00"
                                    />
                                </div>

                                {error && (
                                    <div className="rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-300">
                                        {error}
                                    </div>
                                )}
                            </div>

                            <div className="mt-6 flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={closeResolve}
                                    className="rounded-lg border border-white/10 px-4 py-2 text-sm text-gray-300 hover:text-white"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    onClick={submitResolution}
                                    disabled={submitting}
                                    className="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-black hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {submitting ? 'Saving...' : 'Resolve Dispute'}
                                </button>
                            </div>
                        </div>
                    </div>,
                    portalTarget,
                )
                : null}
        </AppLayout>
    );
}
