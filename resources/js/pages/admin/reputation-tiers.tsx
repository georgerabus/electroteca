import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';

type ReputationTier = {
    id: number;
    name: string;
    min_score: number;
    discount_percent: number;
    description: string | null;
    is_active: boolean;
};

type ReputationTiersPageProps = {
    tiers: ReputationTier[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Reputation', href: '/admin/reputation-tiers' },
];

export default function ReputationTiers({ tiers }: ReputationTiersPageProps) {
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [editingTier, setEditingTier] = useState<ReputationTier | null>(null);

    const createForm = useForm({
        name: '',
        min_score: 0,
        discount_percent: 0,
        description: '',
        is_active: true,
    });

    const editForm = useForm({
        name: '',
        min_score: 0,
        discount_percent: 0,
        description: '',
        is_active: true,
    });

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/admin/reputation-tiers', {
            onSuccess: () => {
                setShowCreateModal(false);
                createForm.reset();
            },
        });
    };

    const handleEditTier = (tier: ReputationTier) => {
        setEditingTier(tier);
        editForm.setData({
            name: tier.name,
            min_score: tier.min_score,
            discount_percent: tier.discount_percent,
            description: tier.description ?? '',
            is_active: tier.is_active,
        });
        setShowEditModal(true);
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingTier) return;

        editForm.put(`/admin/reputation-tiers/${editingTier.id}`, {
            onSuccess: () => {
                setShowEditModal(false);
                setEditingTier(null);
            },
        });
    };

    const handleDelete = (tier: ReputationTier) => {
        if (!confirm(`Delete tier "${tier.name}"?`)) return;

        router.delete(`/admin/reputation-tiers/${tier.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin - Reputation Tiers" />

            <div className="mx-auto max-w-full lg:max-w-[1400px] xl:max-w-[1800px] p-4 md:p-6">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Reputation Discounts
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Control milestone thresholds and discount percentages.
                        </p>
                    </div>
                    <Button onClick={() => setShowCreateModal(true)}>
                        Add Tier
                    </Button>
                </div>

                <div className="rounded-lg border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b">
                                    <th className="px-4 py-3 text-left font-medium">Name</th>
                                    <th className="px-4 py-3 text-left font-medium">Min score</th>
                                    <th className="px-4 py-3 text-left font-medium">Discount</th>
                                    <th className="px-4 py-3 text-left font-medium">Status</th>
                                    <th className="px-4 py-3 text-left font-medium">Description</th>
                                    <th className="px-4 py-3 text-left font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {tiers.length === 0 ? (
                                    <tr>
                                        <td className="px-4 py-6 text-sm text-muted-foreground" colSpan={6}>
                                            No tiers yet. Add the first milestone to enable discounts.
                                        </td>
                                    </tr>
                                ) : (
                                    tiers.map((tier) => (
                                        <tr key={tier.id} className="border-b border-white/10">
                                            <td className="px-4 py-3 font-medium">{tier.name}</td>
                                            <td className="px-4 py-3 text-sm">{tier.min_score}</td>
                                            <td className="px-4 py-3 text-sm">{tier.discount_percent}%</td>
                                            <td className="px-4 py-3">
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${
                                                        tier.is_active
                                                            ? 'bg-green-900/30 text-green-400'
                                                            : 'bg-zinc-800 text-zinc-400'
                                                    }`}
                                                >
                                                    {tier.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-muted-foreground">
                                                {tier.description ?? 'N/A'}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <button
                                                    onClick={() => handleEditTier(tier)}
                                                    className="text-blue-400 hover:text-blue-300"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(tier)}
                                                    className="ml-3 text-red-400 hover:text-red-300"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add Reputation Tier</DialogTitle>
                        <DialogDescription>
                            Create a new milestone to unlock discounts.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreate} className="space-y-4">
                        <div>
                            <Label htmlFor="tier-name">Name</Label>
                            <Input
                                id="tier-name"
                                value={createForm.data.name}
                                onChange={(e) => createForm.setData('name', e.target.value)}
                                placeholder="Bronze"
                            />
                            <InputError message={createForm.errors.name} />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label htmlFor="tier-min-score">Min score</Label>
                                <Input
                                    id="tier-min-score"
                                    type="number"
                                    value={createForm.data.min_score}
                                    onChange={(e) => createForm.setData('min_score', Number(e.target.value))}
                                    placeholder="20"
                                />
                                <InputError message={createForm.errors.min_score} />
                            </div>
                            <div>
                                <Label htmlFor="tier-discount">Discount %</Label>
                                <Input
                                    id="tier-discount"
                                    type="number"
                                    value={createForm.data.discount_percent}
                                    onChange={(e) => createForm.setData('discount_percent', Number(e.target.value))}
                                    placeholder="5"
                                />
                                <InputError message={createForm.errors.discount_percent} />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="tier-description">Description</Label>
                            <Input
                                id="tier-description"
                                value={createForm.data.description}
                                onChange={(e) => createForm.setData('description', e.target.value)}
                                placeholder="Short description"
                            />
                            <InputError message={createForm.errors.description} />
                        </div>
                        <label className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Checkbox
                                checked={createForm.data.is_active}
                                onCheckedChange={(value) => createForm.setData('is_active', Boolean(value))}
                            />
                            Active
                        </label>
                        <DialogFooter>
                            <Button type="submit" disabled={createForm.processing}>
                                Save
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={showEditModal}
                onOpenChange={(open) => {
                    setShowEditModal(open);
                    if (!open) {
                        setEditingTier(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Reputation Tier</DialogTitle>
                        <DialogDescription>
                            Update milestone thresholds or discounts.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleUpdate} className="space-y-4">
                        <div>
                            <Label htmlFor="edit-tier-name">Name</Label>
                            <Input
                                id="edit-tier-name"
                                value={editForm.data.name}
                                onChange={(e) => editForm.setData('name', e.target.value)}
                                placeholder="Silver"
                            />
                            <InputError message={editForm.errors.name} />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label htmlFor="edit-tier-min-score">Min score</Label>
                                <Input
                                    id="edit-tier-min-score"
                                    type="number"
                                    value={editForm.data.min_score}
                                    onChange={(e) => editForm.setData('min_score', Number(e.target.value))}
                                    placeholder="50"
                                />
                                <InputError message={editForm.errors.min_score} />
                            </div>
                            <div>
                                <Label htmlFor="edit-tier-discount">Discount %</Label>
                                <Input
                                    id="edit-tier-discount"
                                    type="number"
                                    value={editForm.data.discount_percent}
                                    onChange={(e) => editForm.setData('discount_percent', Number(e.target.value))}
                                    placeholder="10"
                                />
                                <InputError message={editForm.errors.discount_percent} />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="edit-tier-description">Description</Label>
                            <Input
                                id="edit-tier-description"
                                value={editForm.data.description}
                                onChange={(e) => editForm.setData('description', e.target.value)}
                                placeholder="Short description"
                            />
                            <InputError message={editForm.errors.description} />
                        </div>
                        <label className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Checkbox
                                checked={editForm.data.is_active}
                                onCheckedChange={(value) => editForm.setData('is_active', Boolean(value))}
                            />
                            Active
                        </label>
                        <DialogFooter>
                            <Button type="submit" disabled={editForm.processing}>
                                Update
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
