import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';

type Category = {
    id: number;
    name: string;
    slug: string;
};

type Product = {
    id: number;
    name: string;
    description: string;
    price: string;
    currency: string;
    stock_quantity: number;
    is_available: boolean;
    category: string;
    category_id: number;
    image_url?: string;
};

type AdminProductsPageProps = {
    products: Product[];
    categories: Category[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
];

function ProductRow({
    product,
    categories,
    onEdit
}: {
    product: Product;
    categories: Category[];
    onEdit: (product: Product) => void;
}) {
    return (
        <tr className="border-b border-white/10">
            <td className="px-4 py-3">
                <div className="flex items-center space-x-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded bg-zinc-800">
                        {product.image_url ? (
                            <img
                                src={product.image_url}
                                alt={product.name}
                                className="h-full w-full rounded object-cover"
                            />
                        ) : (
                            <span className="text-xs">📦</span>
                        )}
                    </div>
                    <div>
                        <div className="font-medium">{product.name}</div>
                        <div className="text-xs text-zinc-400">
                            {product.category}
                        </div>
                    </div>
                </div>
            </td>
            <td className="px-4 py-3 text-sm">{product.description}</td>
            <td className="px-4 py-3 text-sm">
                {product.price} {product.currency}
            </td>
            <td className="px-4 py-3 text-sm">{product.stock_quantity}</td>
            <td className="px-4 py-3">
                <span
                    className={`inline-block rounded-full px-2 py-1 text-xs ${
                        product.is_available
                            ? 'bg-green-900/30 text-green-400'
                            : 'bg-red-900/30 text-red-400'
                    }`}
                >
                    {product.is_available ? 'Available' : 'Unavailable'}
                </span>
            </td>
            <td className="px-4 py-3">
                <button
                    onClick={() => onEdit(product)}
                    className="text-sm text-blue-400 hover:text-blue-300"
                >
                    Edit
                </button>
            </td>
        </tr>
    );
}

export default function AdminProducts({
    products = [],
    categories = [],
}: AdminProductsPageProps) {
    const [showAddModal, setShowAddModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [editingProduct, setEditingProduct] = useState<Product | null>(null);

    const addForm = useForm({
        name: '',
        slug: '',
        description: '',
        price: '',
        currency: 'MDL',
        stock_quantity: 0,
        is_available: true,
        image_url: '',
        category_id: categories[0]?.id || '',
    });

    const editForm = useForm({
        name: '',
        slug: '',
        description: '',
        price: '',
        currency: 'MDL',
        stock_quantity: 0,
        is_available: true,
        image_url: '',
        category_id: '',
    });

    const stockForm = useForm({
        stock_quantity: 0,
    });

    const handleAddProduct = (e: React.FormEvent) => {
        e.preventDefault();
        addForm.post('/admin/products', {
            onSuccess: () => {
                setShowAddModal(false);
                addForm.reset();
            },
        });
    };

    const handleEditProduct = (product: Product) => {
        setEditingProduct(product);
        editForm.setData({
            name: product.name,
            slug: product.name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, ''),
            description: product.description,
            price: product.price.replace(/,/g, ''),
            currency: product.currency,
            stock_quantity: product.stock_quantity,
            is_available: product.is_available,
            image_url: product.image_url || '',
            category_id: product.category_id,
        });
        stockForm.setData({
            stock_quantity: product.stock_quantity,
        });
        setShowEditModal(true);
    };

    const handleUpdateProduct = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingProduct) return;

        editForm.put(`/admin/products/${editingProduct.id}`, {
            onSuccess: () => {
                setShowEditModal(false);
                setEditingProduct(null);
            },
        });
    };

    const handleUpdateStock = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingProduct) return;

        stockForm.patch(`/admin/products/${editingProduct.id}/stock`, {
            onSuccess: () => {
                // Keep modal open but show success
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin - Products" />

            <div className="mx-auto max-w-full lg:max-w-[1400px] xl:max-w-[1800px] p-4 md:p-6">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Products Management
                    </h1>
                    <div className="flex gap-2">
                        <Button
                            onClick={() => setShowAddModal(true)}
                            className="bg-blue-600 hover:bg-blue-500"
                        >
                            Add Product
                        </Button>
                        <Link
                            href="/admin/users"
                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium hover:bg-blue-500"
                        >
                            Users
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-white/10 bg-white/[0.03]">
                    <table className="w-full">
                        <thead className="bg-white/5">
                            <tr>
                                <th className="px-4 py-3 text-left text-sm font-medium">
                                    Product
                                </th>
                                <th className="px-4 py-3 text-left text-sm font-medium">
                                    Description
                                </th>
                                <th className="px-4 py-3 text-left text-sm font-medium">
                                    Price
                                </th>
                                <th className="px-4 py-3 text-left text-sm font-medium">
                                    Stock
                                </th>
                                <th className="px-4 py-3 text-left text-sm font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-left text-sm font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.map((product) => (
                                <ProductRow
                                    key={product.id}
                                    product={product}
                                    categories={categories}
                                    onEdit={handleEditProduct}
                                />
                            ))}
                        </tbody>
                    </table>

                    {products.length === 0 && (
                        <div className="flex h-32 items-center justify-center">
                            <p className="text-zinc-400">No products found</p>
                        </div>
                    )}
                </div>
            </div>

            {/* Add Product Modal */}
            <Dialog open={showAddModal} onOpenChange={setShowAddModal}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Add New Product</DialogTitle>
                        <DialogDescription>
                            Fill in the details to create a new product.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleAddProduct} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Product Name *</Label>
                            <Input
                                id="name"
                                value={addForm.data.name}
                                onChange={(e) => {
                                    addForm.setData('name', e.target.value);
                                    // Auto-generate slug
                                    const slug = e.target.value.toLowerCase()
                                        .replace(/\s+/g, '-')
                                        .replace(/[^a-z0-9-]/g, '');
                                    addForm.setData('slug', slug);
                                }}
                                required
                            />
                            <InputError message={addForm.errors.name} />
                        </div>

                        <div>
                            <Label htmlFor="slug">Slug *</Label>
                            <Input
                                id="slug"
                                value={addForm.data.slug}
                                onChange={(e) => addForm.setData('slug', e.target.value)}
                                required
                            />
                            <InputError message={addForm.errors.slug} />
                        </div>

                        <div>
                            <Label htmlFor="description">Description</Label>
                            <textarea
                                id="description"
                                value={addForm.data.description}
                                onChange={(e) => addForm.setData('description', e.target.value)}
                                className="w-full rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm"
                                rows={3}
                            />
                            <InputError message={addForm.errors.description} />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="price">Price *</Label>
                                <Input
                                    id="price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={addForm.data.price}
                                    onChange={(e) => addForm.setData('price', e.target.value)}
                                    required
                                />
                                <InputError message={addForm.errors.price} />
                            </div>

                            <div>
                                <Label htmlFor="currency">Currency *</Label>
                                <Input
                                    id="currency"
                                    value={addForm.data.currency}
                                    onChange={(e) => addForm.setData('currency', e.target.value)}
                                    maxLength={3}
                                    required
                                />
                                <InputError message={addForm.errors.currency} />
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="stock_quantity">Stock Quantity *</Label>
                            <Input
                                id="stock_quantity"
                                type="number"
                                min="0"
                                value={addForm.data.stock_quantity}
                                onChange={(e) => addForm.setData('stock_quantity', parseInt(e.target.value) || 0)}
                                required
                            />
                            <InputError message={addForm.errors.stock_quantity} />
                        </div>

                        <div>
                            <Label htmlFor="category_id">Category *</Label>
                            <select
                                id="category_id"
                                value={addForm.data.category_id}
                                onChange={(e) => addForm.setData('category_id', parseInt(e.target.value))}
                                className="w-full rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm"
                                required
                            >
                                <option value="">Select a category</option>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={addForm.errors.category_id} />
                        </div>

                        <div>
                            <Label htmlFor="image_url">Image URL</Label>
                            <Input
                                id="image_url"
                                type="url"
                                value={addForm.data.image_url}
                                onChange={(e) => addForm.setData('image_url', e.target.value)}
                            />
                            <InputError message={addForm.errors.image_url} />
                        </div>

                        <div className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="is_available"
                                checked={addForm.data.is_available}
                                onChange={(e) => addForm.setData('is_available', e.target.checked)}
                                className="rounded"
                            />
                            <Label htmlFor="is_available">Product is available</Label>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowAddModal(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={addForm.processing}>
                                {addForm.processing ? 'Creating...' : 'Create Product'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit Product Modal */}
            <Dialog open={showEditModal} onOpenChange={setShowEditModal}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Edit Product</DialogTitle>
                        <DialogDescription>
                            Update product details or stock quantity.
                        </DialogDescription>
                    </DialogHeader>

                    {/* Quick Stock Update */}
                    <div className="mb-6 rounded-lg border border-white/10 bg-white/5 p-4">
                        <h3 className="mb-3 text-sm font-medium">Quick Stock Update</h3>
                        <form onSubmit={handleUpdateStock} className="flex gap-2">
                            <div className="flex-1">
                                <Input
                                    type="number"
                                    min="0"
                                    value={stockForm.data.stock_quantity}
                                    onChange={(e) => stockForm.setData('stock_quantity', parseInt(e.target.value) || 0)}
                                    required
                                />
                                <InputError message={stockForm.errors.stock_quantity} />
                            </div>
                            <Button type="submit" size="sm" disabled={stockForm.processing}>
                                {stockForm.processing ? 'Updating...' : 'Update Stock'}
                            </Button>
                        </form>
                    </div>

                    {/* Full Product Edit */}
                    <form onSubmit={handleUpdateProduct} className="space-y-4">
                        <div>
                            <Label htmlFor="edit_name">Product Name *</Label>
                            <Input
                                id="edit_name"
                                value={editForm.data.name}
                                onChange={(e) => {
                                    editForm.setData('name', e.target.value);
                                    const slug = e.target.value.toLowerCase()
                                        .replace(/\s+/g, '-')
                                        .replace(/[^a-z0-9-]/g, '');
                                    editForm.setData('slug', slug);
                                }}
                                required
                            />
                            <InputError message={editForm.errors.name} />
                        </div>

                        <div>
                            <Label htmlFor="edit_slug">Slug *</Label>
                            <Input
                                id="edit_slug"
                                value={editForm.data.slug}
                                onChange={(e) => editForm.setData('slug', e.target.value)}
                                required
                            />
                            <InputError message={editForm.errors.slug} />
                        </div>

                        <div>
                            <Label htmlFor="edit_description">Description</Label>
                            <textarea
                                id="edit_description"
                                value={editForm.data.description}
                                onChange={(e) => editForm.setData('description', e.target.value)}
                                className="w-full rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm"
                                rows={3}
                            />
                            <InputError message={editForm.errors.description} />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="edit_price">Price *</Label>
                                <Input
                                    id="edit_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={editForm.data.price}
                                    onChange={(e) => editForm.setData('price', e.target.value)}
                                    required
                                />
                                <InputError message={editForm.errors.price} />
                            </div>

                            <div>
                                <Label htmlFor="edit_currency">Currency *</Label>
                                <Input
                                    id="edit_currency"
                                    value={editForm.data.currency}
                                    onChange={(e) => editForm.setData('currency', e.target.value)}
                                    maxLength={3}
                                    required
                                />
                                <InputError message={editForm.errors.currency} />
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="edit_stock_quantity">Stock Quantity *</Label>
                            <Input
                                id="edit_stock_quantity"
                                type="number"
                                min="0"
                                value={editForm.data.stock_quantity}
                                onChange={(e) => editForm.setData('stock_quantity', parseInt(e.target.value) || 0)}
                                required
                            />
                            <InputError message={editForm.errors.stock_quantity} />
                        </div>

                        <div>
                            <Label htmlFor="edit_category_id">Category *</Label>
                            <select
                                id="edit_category_id"
                                value={editForm.data.category_id}
                                onChange={(e) => editForm.setData('category_id', parseInt(e.target.value))}
                                className="w-full rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm"
                                required
                            >
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={editForm.errors.category_id} />
                        </div>

                        <div>
                            <Label htmlFor="edit_image_url">Image URL</Label>
                            <Input
                                id="edit_image_url"
                                type="url"
                                value={editForm.data.image_url}
                                onChange={(e) => editForm.setData('image_url', e.target.value)}
                            />
                            <InputError message={editForm.errors.image_url} />
                        </div>

                        <div className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="edit_is_available"
                                checked={editForm.data.is_available}
                                onChange={(e) => editForm.setData('is_available', e.target.checked)}
                                className="rounded"
                            />
                            <Label htmlFor="edit_is_available">Product is available</Label>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setShowEditModal(false);
                                    setEditingProduct(null);
                                }}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                {editForm.processing ? 'Updating...' : 'Update Product'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
