import AppLayout from '@/layouts/app-layout';
import { products } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { ShoppingCart, ArrowLeft } from 'lucide-react';

type Product = {
    id: number;
    name: string;
    slug: string;
    description: string;
    price: string;
    currency: string;
    stock_quantity: number;
    is_available: boolean;
    category: string;
    image_url?: string;
};

type ProductShowPageProps = {
    product: Product;
};

export default function ProductShow({ product }: ProductShowPageProps) {
    const [addedToCart, setAddedToCart] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Shop', href: products().url },
        { title: product.category, href: products().url + `?category=${encodeURIComponent(product.category)}` },
        { title: product.name, href: `/shop/${product.slug}` },
    ];

    const handleAddToCart = () => {
        // Get existing cart from localStorage
        const existingCart = JSON.parse(localStorage.getItem('cart') || '[]');
        
        // Check if product already in cart
        const existingItem = existingCart.find((item: { id: number }) => item.id === product.id);
        
        if (existingItem) {
            // Increment quantity
            existingItem.quantity = (existingItem.quantity || 1) + 1;
        } else {
            // Add new item
            existingCart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                currency: product.currency,
                image_url: product.image_url,
                quantity: 1,
            });
        }
        
        // Save to localStorage
        localStorage.setItem('cart', JSON.stringify(existingCart));
        setAddedToCart(true);
        
        // Reset message after 2 seconds
        setTimeout(() => setAddedToCart(false), 2000);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={product.name} />

            <div className="mx-auto max-w-7xl p-4 md:p-8">
                {/* Back button */}
                <Link
                    href={products().url}
                    className="mb-6 inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Shop
                </Link>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    {/* Product Image */}
                    <div className="flex items-center justify-center rounded-2xl border border-white/10 bg-white/[0.03] p-8">
                        {product.image_url ? (
                            <img
                                src={product.image_url}
                                alt={product.name}
                                className="max-h-[600px] w-full object-contain"
                            />
                        ) : (
                            <div className="text-6xl opacity-30">📦</div>
                        )}
                    </div>

                    {/* Product Details */}
                    <div className="flex flex-col justify-between">
                        <div>
                            {/* Category */}
                            <div className="mb-2 text-sm text-gray-400">{product.category}</div>

                            {/* Title */}
                            <h1 className="mb-4 text-3xl font-bold text-white">{product.name}</h1>

                            {/* Price */}
                            <div className="mb-6 text-3xl font-bold text-blue-500">
                                {product.price} {product.currency}
                            </div>

                            {/* Description */}
                            <div className="mb-6">
                                <h2 className="mb-2 text-lg font-semibold text-white">Description</h2>
                                <p className="text-gray-300 leading-relaxed">{product.description}</p>
                            </div>

                            {/* Stock Info */}
                            <div className="mb-6 text-sm">
                                <span className="text-gray-400">Stock: </span>
                                <span className={product.is_available ? 'text-green-400' : 'text-red-400'}>
                                    {product.stock_quantity} available
                                </span>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex flex-col gap-3">
                            <button
                                onClick={handleAddToCart}
                                disabled={!product.is_available}
                                className={`flex items-center justify-center gap-2 rounded-xl px-6 py-4 text-base font-semibold transition ${
                                    product.is_available
                                        ? 'bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400'
                                        : 'cursor-not-allowed bg-zinc-800 text-zinc-500'
                                }`}
                            >
                                <ShoppingCart className="h-5 w-5" />
                                {addedToCart ? 'Added to Cart!' : 'Add to Cart'}
                            </button>

                            <button
                                disabled={!product.is_available}
                                className={`rounded-xl border-2 px-6 py-4 text-base font-semibold transition ${
                                    product.is_available
                                        ? 'border-white/20 bg-transparent text-white hover:bg-white/5'
                                        : 'cursor-not-allowed border-zinc-700 bg-transparent text-zinc-500'
                                }`}
                            >
                                Compare
                            </button>
                        </div>

                        {addedToCart && (
                            <div className="mt-4 rounded-lg bg-green-500/20 border border-green-500/50 p-3 text-sm text-green-400">
                                Product added! Compare
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

