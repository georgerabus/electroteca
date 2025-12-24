import AppLayout from '@/layouts/app-layout';
import { products } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ChangeEvent, useMemo, useState } from 'react';
import { ShoppingCart, Star } from 'lucide-react';
import { useCart } from '@/hooks/use-cart';

type Product = {
    id: number;
    name: string;
    slug?: string;
    description: string;
    price: string;
    currency: string;
    stock_quantity: number;
    is_available: boolean;
    category: string;
    image_url?: string;
    avg_rating?: number;
};

type ProductsPageProps = {
    products: Product[];
    categories: string[];
    filters: {
        category?: string;
        availability?: string;
        search?: string;
        sort?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Products', href: products().url },
];

const SORT_OPTIONS = [
    { value: 'name', label: 'Name A-Z' },
    { value: 'price_asc', label: 'Price: Low to High' },
    { value: 'price_desc', label: 'Price: High to Low' },
] as const;

const AVAILABILITY_OPTIONS = ['All', 'Available', 'Out of stock'] as const;

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

function ProductCard({ product }: { product: Product }) {
    const [addedToCart, setAddedToCart] = useState(false);
    const { addToCart } = useCart();

    const handleAddToCart = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();

        addToCart(
            {
                id: product.id,
                name: product.name,
                price: product.price,
                currency: product.currency,
                image_url: product.image_url,
            },
            1,
        );
        setAddedToCart(true);

        // Reset message after 2 seconds
        setTimeout(() => setAddedToCart(false), 2000);
    };

    return (
        <Link
            href={`/shop/${product.slug || product.name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')}`}
            className="group overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] shadow-[0_10px_30px_-15px_rgba(0,0,0,0.6)] ring-1 ring-white/10 backdrop-blur transition hover:bg-white/[0.05]"
        >
            <div className="flex aspect-video items-center justify-center bg-gradient-to-br from-zinc-800 to-zinc-900">
                {product.image_url ? (
                    <img
                        src={product.image_url}
                        alt={product.name}
                        className="h-full w-full object-cover transition group-hover:scale-105"
                    />
                ) : (
                    <div className="text-4xl opacity-30">📦</div>
                )}
            </div>
            <div className="p-5">
                {/* Title */}
                    <div className="mb-2">
                    <h3 className="text-base leading-tight font-semibold text-black dark:text-white group-hover:text-red-400 transition">
                        {product.name}
                    </h3>

                    {/* Stars / avg rating (if available) */}
                    <div className="mt-1 flex items-center gap-2">
                        <div className="flex items-center gap-0.5">
                            {Array.from({ length: 5 }).map((_, si) => (
                                <Star key={si} className={`h-3 w-3 ${product.avg_rating && si < Math.round(product.avg_rating) ? 'text-yellow-400' : 'text-gray-400/60'}`} />
                            ))}
                        </div>
                        <div className="text-xs text-black dark:text-gray-300">
                            {product.avg_rating ? `${(Math.round((product.avg_rating || 0) * 10) / 10).toFixed(1)}` : '—'}
                        </div>
                    </div>
                </div>
                {/* Price + stock aligned left */}
                    <div className="mb-2 flex items-center gap-3 text-sm">
                    <div className="font-medium text-black dark:text-white">
                        {product.price} {product.currency}
                    </div>
                    <div className="text-gray-700 dark:text-gray-300">Stock: {product.stock_quantity}</div>
                </div>
                <p className="mb-3 line-clamp-2 text-xs text-black dark:text-gray-300">
                    {product.description}
                </p>
                <div className="mb-4 text-xs text-black dark:text-gray-300">
                    Category: {product.category}
                </div>
                <div className="flex gap-2">
                    <button
                        onClick={handleAddToCart}
                        disabled={!product.is_available}
                        className={`flex-1 flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition ${
                            product.is_available
                                ? 'bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400'
                                : 'cursor-not-allowed bg-zinc-800 text-zinc-500'
                        }`}
                    >
                        <ShoppingCart className="h-4 w-4" />
                        {addedToCart ? 'Added!' : 'Add to Cart'}
                    </button>
                    <button
                        disabled={!product.is_available}
                        className={`rounded-xl px-4 py-2.5 text-sm font-medium transition ${
                            product.is_available
                                ? 'bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400'
                                : 'cursor-not-allowed bg-zinc-800 text-zinc-500'
                        }`}
                    >
                        {product.is_available ? 'Borrow' : 'Unavailable'}
                    </button>
                </div>
            </div>
        </Link>
    );
}

function ProductCardWithDiscount({ product, discountPercent }: { product: Product; discountPercent?: number }) {
    const [addedToCart, setAddedToCart] = useState(false);
    const { addToCart } = useCart();

    const handleAddToCart = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        const priceToUse = hasDiscount && discountedPrice ? String(discountedPrice) : product.price;
        addToCart(
            {
                id: product.id,
                name: product.name,
                // store the discounted price as the cart unit `price`, but also keep metadata
                price: priceToUse,
                currency: product.currency,
                image_url: product.image_url,
                discountPercent: discountPercent,
                original_price: product.price,
            },
            1,
        );
        setAddedToCart(true);
        setTimeout(() => setAddedToCart(false), 2000);
    };

    const priceNum = parseFloat(product.price);
    const hasDiscount = !!discountPercent && discountPercent > 0;
    const discountedPrice = hasDiscount ? (priceNum * (1 - discountPercent! / 100)).toFixed(2) : null;

    return (
        <Link
            href={`/shop/${product.slug || product.name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')}`}
            className="group overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] shadow-[0_10px_30px_-15px_rgba(0,0,0,0.6)] ring-1 ring-white/10 backdrop-blur transition hover:bg-white/[0.05]"
        >
            <div className="flex aspect-video items-center justify-center bg-gradient-to-br from-zinc-800 to-zinc-900">
                {product.image_url ? (
                    <img
                        src={product.image_url}
                        alt={product.name}
                        className="h-full w-full object-cover transition group-hover:scale-105"
                    />
                ) : (
                    <div className="text-4xl opacity-30">📦</div>
                )}
            </div>
            <div className="p-5">
                <div className="mb-2">
                    <h3 className="text-base leading-tight font-semibold text-black dark:text-white group-hover:text-red-400 transition">
                        {product.name}
                    </h3>
                    <div className="mt-1 flex items-center gap-2">
                        <div className="flex items-center gap-0.5">
                            {Array.from({ length: 5 }).map((_, si) => (
                                <Star key={si} className={`h-3 w-3 ${product.avg_rating && si < Math.round(product.avg_rating) ? 'text-yellow-400' : 'text-gray-400/60'}`} />
                            ))}
                        </div>
                        <div className="text-xs text-black dark:text-gray-300">
                            {product.avg_rating ? `${(Math.round((product.avg_rating || 0) * 10) / 10).toFixed(1)}` : '—'}
                        </div>
                    </div>
                </div>
                <div className="mb-2 flex items-center gap-3 text-sm">
                    <div className="font-medium text-black dark:text-white">
                        {hasDiscount ? (
                            <div className="flex items-baseline gap-3">
                                <div className="text-sm text-gray-500 line-through">{product.price} {product.currency}</div>
                                <div className="text-lg font-bold text-red-600">{discountedPrice} {product.currency}</div>
                            </div>
                        ) : (
                            <>{product.price} {product.currency}</>
                        )}
                    </div>
                    <div className="text-gray-700 dark:text-gray-300">Stock: {product.stock_quantity}</div>
                </div>
                <p className="mb-3 line-clamp-2 text-xs text-black dark:text-gray-300">
                    {product.description}
                </p>
                <div className="mb-4 text-xs text-black dark:text-gray-300">
                    Category: {product.category}
                </div>
                <div className="flex gap-2">
                    <button
                        onClick={handleAddToCart}
                        disabled={!product.is_available}
                        className={`flex-1 flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition ${
                            product.is_available
                                ? 'bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400'
                                : 'cursor-not-allowed bg-zinc-800 text-zinc-500'
                        }`}
                    >
                        <ShoppingCart className="h-4 w-4" />
                        {addedToCart ? 'Added!' : 'Add to Cart'}
                    </button>
                    <button
                        disabled={!product.is_available}
                        className={`rounded-xl px-4 py-2.5 text-sm font-medium transition ${
                            product.is_available
                                ? 'bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400'
                                : 'cursor-not-allowed bg-zinc-800 text-zinc-500'
                        }`}
                    >
                        {product.is_available ? 'Borrow' : 'Unavailable'}
                    </button>
                </div>
                {hasDiscount && (
                    <div className="mt-3 inline-block rounded-full bg-red-600/10 text-red-600 px-2 py-0.5 text-xs font-semibold">-{discountPercent}%</div>
                )}
            </div>
        </Link>
    );
}

export default function Products({
    products: initialProducts = [],
    categories = ['All categories'],
    filters = {},
}: ProductsPageProps) {
    const [category, setCategory] = useState(
        filters.category || 'All categories',
    );
    const [availability, setAvailability] = useState(
        filters.availability || 'All',
    );
    const [sort, setSort] = useState(filters.sort || 'name');
    const [query, setQuery] = useState(filters.search || '');
    const promotions = [
        { id: 'none', label: 'No promotion', percent: 0 },
        { id: 'bf10', label: 'Black Friday -10%', percent: 10 },
        { id: 'holiday15', label: 'Holiday -15%', percent: 15 },
    ];
    const [selectedPromotion, setSelectedPromotion] = useState(promotions[0].id);

    const products = useMemo(() => {
        let filtered = [...initialProducts];

        if (!Array.isArray(initialProducts)) {
            return [];
        }

        // Filter by category
        if (category !== 'All categories') {
            filtered = filtered.filter((p) => p.category === category);
        }

        // Filter by availability
        if (availability === 'Available') {
            filtered = filtered.filter((p) => p.is_available);
        } else if (availability === 'Out of stock') {
            filtered = filtered.filter((p) => !p.is_available);
        }

        // Search filter
        if (query.trim()) {
            const q = query.trim().toLowerCase();
            filtered = filtered.filter(
                (p) =>
                    p.name.toLowerCase().includes(q) ||
                    p.description.toLowerCase().includes(q) ||
                    p.category.toLowerCase().includes(q),
            );
        }

        // Sort
        if (sort === 'price_asc') {
            filtered.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
        } else if (sort === 'price_desc') {
            filtered.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
        } else {
            filtered.sort((a, b) => a.name.localeCompare(b.name));
        }

        return filtered;
    }, [initialProducts, category, availability, sort, query]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Products" />

            <div className="mx-auto max-w-[1200px] p-4 md:p-6">
                {/* header */}
                <div className="mb-4 flex items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Products
                    </h1>
                    <span className="hidden text-sm opacity-60 md:block">
                        {products.length} product
                        {products.length === 1 ? '' : 's'}
                    </span>
                </div>

                {/* toolbar */}
                <div className="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                    <ToolbarSelect
                        value={category}
                        onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                            setCategory(e.target.value)
                        }
                    >
                        {categories.map((cat) => (
                            <option key={cat} value={cat}>{cat}</option>
                        ))}
                    </ToolbarSelect>

                    <ToolbarSelect
                        value={availability}
                        onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                            setAvailability(e.target.value)
                        }
                    >
                        {AVAILABILITY_OPTIONS.map((avail) => (
                            <option key={avail} value={avail}>{avail}</option>
                        ))}
                    </ToolbarSelect>

                    <ToolbarSelect
                        value={sort}
                        onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                            setSort(e.target.value)
                        }
                    >
                        {SORT_OPTIONS.map((sortOpt) => (
                            <option key={sortOpt.value} value={sortOpt.value}>{sortOpt.label}</option>
                        ))}
                    </ToolbarSelect>

                    <div className="relative w-full md:w-72">
                        <select
                            value={selectedPromotion}
                            onChange={(e) => setSelectedPromotion(e.target.value)}
                            className="peer w-full appearance-none rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-black dark:text-white backdrop-blur transition outline-none hover:bg-white/7 focus:border-white/20"
                        >
                            {promotions.map((p) => (
                                <option key={p.id} value={p.id}>{p.label}</option>
                            ))}
                        </select>
                        <div className="pointer-events-none absolute inset-y-0 right-3 flex items-center opacity-60">▾</div>
                    </div>

                    <input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Search products..."
                        className="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm backdrop-blur transition outline-none placeholder:text-zinc-400 hover:bg-white/7 focus:border-white/20"
                    />
                </div>

                {/* products grid */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {products.map((product, idx) => {
                        // Apply discount only to first two visible products
                        const promo = promotions.find((p) => p.id === selectedPromotion) || promotions[0];
                        const applyDiscount = promo.percent > 0 && idx < 2;
                        return (
                            <ProductCardWithDiscount key={product.id} product={product} discountPercent={applyDiscount ? promo.percent : undefined} />
                        );
                    })}
                </div>

                {products.length === 0 && (
                    <div className="flex h-64 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.03]">
                        <div className="text-center">
                            <div className="mb-3 text-4xl opacity-20">🔍</div>
                            <p className="text-sm opacity-70">
                                No products found. Try adjusting your filters.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
