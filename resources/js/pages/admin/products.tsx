import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

type Product = {
    id: number;
    name: string;
    description: string;
    price: string;
    currency: string;
    stock_quantity: number;
    is_available: boolean;
    category: string;
    image_url?: string;
};

type AdminProductsPageProps = {
    products: Product[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Products', href: '/admin/products' },
];

function ProductRow({ product }: { product: Product }) {
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
                <button className="text-sm text-blue-400 hover:text-blue-300">
                    Edit
                </button>
            </td>
        </tr>
    );
}

export default function AdminProducts({
    products = [],
}: AdminProductsPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin - Products" />

            <div className="mx-auto max-w-7xl p-4 md:p-6">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Products Management
                    </h1>
                    <button className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium hover:bg-blue-500">
                        Add Product
                    </button>
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
        </AppLayout>
    );
}
