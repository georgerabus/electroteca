import AppLayout from '@/layouts/app-layout';
import { products } from '@/routes';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { AlertCircle, ArrowLeft, Calendar, Package, ShoppingCart, Star } from 'lucide-react';
import { useCart } from '@/hooks/use-cart';

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
    const { auth } = usePage<SharedData>().props;
    const reputationDiscount = Math.max(0, Math.round(Number(auth.user?.reputation_discount_percent ?? 0)));
    const [addedToCart, setAddedToCart] = useState(false);
    const [borrowInfo, setBorrowInfo] = useState<{
        can_borrow: boolean;
        deposit_required: number;
        reasons: string[];
    } | null>(null);
    const [isCheckingBorrow, setIsCheckingBorrow] = useState(false);
    const [isBorrowing, setIsBorrowing] = useState(false);
    const [showBorrowForm, setShowBorrowForm] = useState(false);
    const [borrowPeriodFrom, setBorrowPeriodFrom] = useState('');
    const [borrowPeriodTo, setBorrowPeriodTo] = useState('');
    const [borrowDetails, setBorrowDetails] = useState('');
    const { addToCart } = useCart();
    const priceNum = Number.parseFloat(product.price);
    const hasDiscount = reputationDiscount > 0 && Number.isFinite(priceNum);
    const discountedPrice = hasDiscount
        ? (priceNum * (1 - reputationDiscount / 100)).toFixed(2)
        : null;

    // --- Reviews (client-side sample implementation) ---
    type Review = {
        id: string;
        name: string;
        rating: number; // 1-5
        comment: string;
        created_at: string;
    };

    const sampleReviews: Review[] = [
        {
            id: 'r1',
            name: 'Alex I.',
            rating: 5,
            comment: 'Excellent kit — clear layout and reliable contacts. Great for rapid prototyping.',
            created_at: new Date().toISOString(),
        },
        {
            id: 'r2',
            name: 'Maria P.',
            rating: 4,
            comment: 'Very versatile. Wish documentation included a few more example circuits.',
            created_at: new Date(Date.now() - 1000 * 60 * 60 * 24 * 7).toISOString(),
        },
    ];

    const [reviews, setReviews] = useState<Review[]>(sampleReviews);
    const [reviewName, setReviewName] = useState('');
    const [reviewRating, setReviewRating] = useState(5);
    const [reviewComment, setReviewComment] = useState('');

    const averageRating = reviews.length
        ? Math.round((reviews.reduce((s, r) => s + r.rating, 0) / reviews.length) * 10) / 10
        : 0;

    const submitReview = (e?: React.FormEvent) => {
        e?.preventDefault();
        if (!reviewName.trim() || !reviewComment.trim()) {
            alert('Please provide your name and a comment.');
            return;
        }

        const newReview: Review = {
            id: `r_${Date.now()}`,
            name: reviewName.trim(),
            rating: reviewRating,
            comment: reviewComment.trim(),
            created_at: new Date().toISOString(),
        };

        setReviews((prev) => [newReview, ...prev]);
        setReviewName('');
        setReviewRating(5);
        setReviewComment('');
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Shop', href: products().url },
        { title: product.category, href: products().url + `?category=${encodeURIComponent(product.category)}` },
        { title: product.name, href: `/shop/${product.slug}` },
    ];

    // Check borrowability when component mounts or product changes
    useEffect(() => {
        if (auth.user && product.is_available) {
            checkBorrowability();
        }
    }, [product.id, auth.user?.wallet_balance]);

    const checkBorrowability = async () => {
        if (!auth.user) return;

        setIsCheckingBorrow(true);
        try {
            const response = await fetch(`/products/${product.id}/check-borrow`);
            const data = await response.json();
            setBorrowInfo(data);
        } catch (error) {
            console.error('Error checking borrowability:', error);
        } finally {
            setIsCheckingBorrow(false);
        }
    };

    const handleAddToCart = () => {
        const priceToUse = hasDiscount && discountedPrice ? discountedPrice : product.price;
        addToCart(
            {
                id: product.id,
                name: product.name,
                price: priceToUse,
                currency: product.currency,
                image_url: product.image_url,
                discountPercent: hasDiscount ? reputationDiscount : undefined,
                original_price: hasDiscount ? product.price : undefined,
            },
            1,
        );
        setAddedToCart(true);

        // Reset message after 2 seconds
        setTimeout(() => setAddedToCart(false), 2000);
    };

    const handleBorrow = () => {
        if (!auth.user) {
            router.visit('/login');
            return;
        }

        if (!borrowInfo?.can_borrow) {
            alert(borrowInfo?.reasons.join('\n') || 'Cannot borrow this product');
            return;
        }

        setShowBorrowForm(true);
    };

    const submitBorrow = () => {
        if (!borrowPeriodFrom || !borrowPeriodTo) {
            alert('Please select both start and end dates');
            return;
        }

        const fromDate = new Date(borrowPeriodFrom);
        const toDate = new Date(borrowPeriodTo);

        if (toDate <= fromDate) {
            alert('End date must be after start date');
            return;
        }

        setIsBorrowing(true);
        router.post(`/products/${product.id}/borrow`, {
            period_from: borrowPeriodFrom,
            period_to: borrowPeriodTo,
            details: borrowDetails,
        }, {
            onSuccess: () => {
                setShowBorrowForm(false);
                setBorrowPeriodFrom('');
                setBorrowPeriodTo('');
                setBorrowDetails('');
                checkBorrowability(); // Refresh borrow info
            },
            onError: (errors) => {
                console.error('Borrow error:', errors);
                // Show popup if server returned an unverified-email message
                if (errors && (errors as any).error) {
                    // Keep behavior unchanged server-side; just surface a popup to the user
                    alert((errors as any).error);
                }
            },
            onFinish: () => {
                setIsBorrowing(false);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={product.name} />

            <div className="mx-auto max-w-full lg:max-w-10xl p-4 md:p-8">
                {/* Back button */}
                <Link
                    href={products().url}
                    className="mb-6 inline-flex items-center gap-2 text-sm text-black dark:text-gray-400 hover:text-white transition"
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
                            <div className="mb-2 text-sm text-black dark:text-gray-400">{product.category}</div>

                            {/* Title */}
                            <h1 className="mb-4 text-3xl font-bold text-white">{product.name}</h1>

                            {/* Price */}
                            <div className="mb-6">
                                {hasDiscount ? (
                                    <div className="flex flex-wrap items-baseline gap-3">
                                        <div className="text-sm text-gray-500 line-through">
                                            {product.price} {product.currency}
                                        </div>
                                        <div className="text-3xl font-bold text-red-500">
                                            {discountedPrice} {product.currency}
                                        </div>
                                        <span className="rounded-full bg-red-500/10 px-2 py-1 text-xs font-semibold text-red-400">
                                            -{reputationDiscount}%
                                        </span>
                                    </div>
                                ) : (
                                    <div className="text-3xl font-bold text-blue-500">
                                        {product.price} {product.currency}
                                    </div>
                                )}
                            </div>

                            {/* Description */}
                            <div className="mb-6">
                                <h2 className="mb-2 text-lg font-semibold text-white">Description</h2>
                                <p className="text-black dark:text-gray-300 leading-relaxed">{product.description}</p>
                            </div>

                            {/* Reviews */}
                            <div className="mb-6">
                                <h2 className="mb-3 text-lg font-semibold text-white">Customer Reviews</h2>

                                <div className="mb-4 flex items-center gap-4">
                                    <div className="flex items-center gap-2">
                                        <div className="inline-flex items-center justify-center rounded-full bg-yellow-500 px-3 py-1 text-sm font-semibold text-black">{averageRating || '—'}</div>
                                        <div className="text-sm text-black dark:text-gray-300">avg rating</div>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        {Array.from({ length: 5 }).map((_, idx) => (
                                            <Star key={idx} className={`h-4 w-4 ${idx < Math.round(averageRating) ? 'text-yellow-400' : 'text-gray-600/40'}`} />
                                        ))}
                                    </div>
                                    <div className="text-sm text-gray-400">{reviews.length} review{reviews.length === 1 ? '' : 's'}</div>
                                </div>

                                {/* Reviews list */}
                                <div className="space-y-3 mb-4">
                                    {reviews.map((rv) => (
                                        <div key={rv.id} className="rounded-lg border border-white/6 bg-white/3 p-3">
                                            <div className="flex items-center justify-between">
                                                <div className="font-semibold text-black dark:text-white">{rv.name}</div>
                                                <div className="flex items-center gap-1 text-yellow-400">
                                                    {Array.from({ length: rv.rating }).map((_, i) => (
                                                        <Star key={i} className="h-4 w-4" />
                                                    ))}
                                                </div>
                                            </div>
                                            <div className="text-sm text-gray-700 dark:text-gray-300 mt-1">{rv.comment}</div>
                                            <div className="text-xs text-gray-500 mt-2">{new Date(rv.created_at).toLocaleDateString()}</div>
                                        </div>
                                    ))}
                                </div>

                                {/* Review form */}
                                <form onSubmit={submitReview} className="rounded-lg border border-white/6 bg-white/5 p-4">
                                    <h3 className="text-sm font-semibold text-white mb-2">Write a review</h3>
                                    <div className="grid grid-cols-1 gap-3">
                                        <input value={reviewName} onChange={(e) => setReviewName(e.target.value)} placeholder="Your name" className="w-full rounded-md border border-white/10 bg-white dark:bg-neutral-800 text-black dark:text-white px-3 py-2" />
                                        <div className="flex items-center gap-2">
                                            <label className="text-sm text-gray-300">Rating:</label>
                                            <select value={reviewRating} onChange={(e) => setReviewRating(Number(e.target.value))} className="rounded-md border border-white/10 bg-white dark:bg-neutral-800 text-black dark:text-white px-2 py-1">
                                                {[5,4,3,2,1].map((r) => <option key={r} value={r}>{r} star{r>1?'s':''}</option>)}
                                            </select>
                                        </div>
                                        <textarea value={reviewComment} onChange={(e) => setReviewComment(e.target.value)} rows={4} placeholder="Your review" className="w-full rounded-md border border-white/10 bg-white dark:bg-neutral-800 text-black dark:text-white px-3 py-2" />
                                        <div className="flex gap-2">
                                            <button type="submit" className="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-700">Submit Review</button>
                                            <button type="button" onClick={() => { setReviewName(''); setReviewRating(5); setReviewComment(''); }} className="rounded-xl border border-white/10 text-white px-4 py-2">Reset</button>
                                        </div>
                                    </div>
                                </form>
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

                            {auth.user ? (
                                <>
                                    {borrowInfo && (
                                        <div className="mb-3 rounded-lg border border-white/10 bg-white/5 p-3">
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="text-sm text-black dark:text-gray-400">Deposit Required:</span>
                                                <span className="text-sm font-semibold text-white">
                                                    {borrowInfo.deposit_required.toFixed(2)} CR
                                                </span>
                                            </div>
                                            {!borrowInfo.can_borrow && (
                                                <div className="flex items-start gap-2 text-xs text-red-400 mt-2">
                                                    <AlertCircle className="h-4 w-4 mt-0.5 flex-shrink-0" />
                                                    <div>
                                                        {borrowInfo.reasons.map((reason, idx) => (
                                                            <div key={idx}>{reason}</div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                    <button
                                        onClick={handleBorrow}
                                        disabled={!product.is_available || isCheckingBorrow || (borrowInfo && !borrowInfo.can_borrow)}
                                        className={`flex items-center justify-center gap-2 rounded-xl border-2 px-6 py-4 text-base font-semibold transition ${
                                            product.is_available && borrowInfo?.can_borrow
                                                ? 'border-blue-500/50 bg-transparent text-blue-400 hover:bg-blue-500/10'
                                                : 'cursor-not-allowed border-zinc-700 bg-transparent text-zinc-500'
                                        }`}
                                    >
                                        <Package className="h-5 w-5" />
                                        {isCheckingBorrow ? 'Checking...' : 'Borrow'}
                                    </button>
                                </>
                            ) : (
                                <Link
                                    href="/login"
                                    className="flex items-center justify-center gap-2 rounded-xl border-2 border-white/20 bg-transparent text-white hover:bg-white/5 px-6 py-4 text-base font-semibold transition"
                                >
                                    <Package className="h-5 w-5" />
                                    Login to Borrow
                                </Link>
                            )}
                        </div>

                        {addedToCart && (
                            <div className="mt-4 rounded-lg bg-green-500/20 border border-green-500/50 p-3 text-sm text-green-400">
                                Product added to cart!
                            </div>
                        )}

                        {/* Borrow Form Modal */}
                        {showBorrowForm && (
                            <div className="mt-4 rounded-lg border border-white/10 bg-zinc-900/50 p-4">
                                <h3 className="text-lg font-semibold text-white mb-4">Borrow Product</h3>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm text-gray-300 mb-1">
                                            <Calendar className="inline h-4 w-4 mr-1" />
                                            Start Date
                                        </label>
                                        <input
                                            type="date"
                                            value={borrowPeriodFrom}
                                            onChange={(e) => setBorrowPeriodFrom(e.target.value)}
                                            min={new Date().toISOString().split('T')[0]}
                                            className="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm text-gray-300 mb-1">
                                            <Calendar className="inline h-4 w-4 mr-1" />
                                            End Date
                                        </label>
                                        <input
                                            type="date"
                                            value={borrowPeriodTo}
                                            onChange={(e) => setBorrowPeriodTo(e.target.value)}
                                            min={borrowPeriodFrom || new Date().toISOString().split('T')[0]}
                                            className="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm text-gray-300 mb-1">
                                            Details (optional)
                                        </label>
                                        <textarea
                                            value={borrowDetails}
                                            onChange={(e) => setBorrowDetails(e.target.value)}
                                            placeholder="Any additional information..."
                                            rows={3}
                                            className="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-white/20 focus:outline-none"
                                        />
                                    </div>
                                    <div className="flex gap-2">
                                        <button
                                            onClick={submitBorrow}
                                            disabled={isBorrowing}
                                            className="flex-1 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition disabled:opacity-50"
                                        >
                                            {isBorrowing ? 'Processing...' : 'Submit Request'}
                                        </button>
                                        <button
                                            onClick={() => {
                                                setShowBorrowForm(false);
                                                setBorrowPeriodFrom('');
                                                setBorrowPeriodTo('');
                                                setBorrowDetails('');
                                            }}
                                            className="rounded-xl border border-white/20 bg-transparent px-4 py-2 text-sm font-semibold text-white hover:bg-white/5 transition"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

