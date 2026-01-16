import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ArrowLeft, ShoppingCart, DollarSign, AlertCircle, Calendar } from 'lucide-react';
import { useCart } from '@/hooks/use-cart';
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';

type CheckoutPageProps = {
    items?: Array<{
        id: number;
        name: string;
        price: string;
        currency: string;
        quantity: number;
        subtotal: string;
        image_url?: string;
    }>;
    total?: string;
    currency?: string;
    wallet_balance?: string;
};

export default function Checkout({ items: serverItems, total: serverTotal, currency: serverCurrency, wallet_balance: serverWalletBalance }: CheckoutPageProps) {
    const { auth } = usePage<SharedData>().props;
    const { cart, totalPrice, clearCart } = useCart();
    const [cartLoaded, setCartLoaded] = useState(false);
    
    // Wait for cart to load from localStorage
    useEffect(() => {
        // Give it a moment for localStorage to load
        const timer = setTimeout(() => {
            setCartLoaded(true);
        }, 100);
        return () => clearTimeout(timer);
    }, []);

    // Calculate items from cart
    const items = cart.map(item => ({
        id: item.id,
        name: item.name,
        price: item.price,
        currency: item.currency,
        quantity: item.quantity,
        subtotal: (parseFloat(item.price) * item.quantity).toFixed(2),
        image_url: item.image_url,
    }));
    
    const total = totalPrice;
    const currency = cart.length > 0 ? cart[0].currency : 'MDL';
    const walletBalance = serverWalletBalance ? parseFloat(serverWalletBalance) : (auth.user?.wallet_balance || 0);

    const { data, setData, post, processing, errors } = useForm({
        items: cart.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
        })),
        shipping_address: '',
        period_from: '',
        period_to: '',
        notes: '',
    });

    // Update cart in form data whenever cart changes
    useEffect(() => {
        setData('items', cart.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
        })));
    }, [cart, setData]);

    // Redirect if cart is empty (only after cart has loaded)
    useEffect(() => {
        if (cartLoaded && cart.length === 0) {
            router.visit('/cart');
        }
    }, [cart, cartLoaded]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.shipping_address.trim()) {
            alert('Please provide a shipping address');
            return;
        }

        // Ensure cart is up to date
        setData('items', cart.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
        })));

        // Check if wallet has enough for full amount
        if (walletBalance < total) {
            return;
        }

        const fromDate = new Date(data.period_from);
        const toDate = new Date(data.period_to);

        if (toDate <= fromDate) {
            return;
        }

        post('/checkout', {
            onSuccess: () => {
                // Clear cart immediately on success
                clearCart();
                // Also clear from localStorage directly to ensure it's cleared
                if (typeof window !== 'undefined') {
                    window.localStorage.removeItem('cart');
                    window.localStorage.removeItem('cart_user_id');
                    window.dispatchEvent(new CustomEvent('cart-updated', { detail: [] }));
                }
            },
            onError: (errors) => {
                // If server returned the unverified-email message, show a popup to the user
                if (errors && (errors as any).error) {
                    alert((errors as any).error);
                }
            },
        });
    };

    // Check if wallet has enough for full amount
    const insufficientBalance = walletBalance < total;

    // Redirect if not logged in
    useEffect(() => {
        if (!auth.user) {
            router.visit('/login');
        }
    }, [auth.user]);

    // Show loading or redirect if cart is empty
    if (!auth.user) {
        return null; // Will redirect via useEffect
    }

    if (!cartLoaded || cart.length === 0) {
        return (
            <AppLayout breadcrumbs={[{ title: 'Shop', href: '/shop' }, { title: 'Request Loans', href: '/checkout' }]}>
                <Head title="Request Loans" />
                <div className="mx-auto max-w-4xl p-4 sm:p-8">
                    <div className="text-center my-24">
                        <div className="mb-4 text-4xl opacity-20">⏳</div>
                        <p className="text-lg text-gray-300">Loading...</p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={[{ title: 'Shop', href: '/shop' }, { title: 'Cart', href: '/cart' }, { title: 'Request Loans', href: '/checkout' }]}>
            <Head title="Request Loans" />
            <div className="mx-auto max-w-4xl p-4 sm:p-8">
                <Link
                    href="/cart"
                    className="mb-6 inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Cart
                </Link>

                <h1 className="text-3xl font-bold mb-6">Request Loans</h1>
                <p className="text-gray-400 mb-6">Select the borrowing period for your items</p>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Order Summary */}
                    <div className="lg:col-span-2">
                        <div className="rounded-2xl border border-white/10 bg-white/5 p-6 mb-6">
                            <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
                                <ShoppingCart className="h-5 w-5" />
                                Order Summary
                            </h2>
                            <div className="divide-y divide-white/10">
                                {items.map((item) => (
                                    <div key={`${item.id}-${item.quantity}`} className="flex items-center gap-4 py-4">
                                        <div className="h-16 w-16 bg-zinc-900 rounded-xl flex-shrink-0 flex items-center justify-center overflow-hidden">
                                            {item.image_url ? (
                                                <img src={item.image_url} alt={item.name} className="h-full w-full object-cover" />
                                            ) : (
                                                <div className="text-xl opacity-30">📦</div>
                                            )}
                                        </div>
                                        <div className="flex-1">
                                            <div className="font-semibold text-white">{item.name}</div>
                                            <div className="text-sm text-gray-400">
                                                {item.price} {item.currency} x {item.quantity}
                                            </div>
                                        </div>
                                        <div className="font-semibold text-white">
                                            {item.subtotal} {item.currency}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div className="mt-6 pt-6 border-t border-white/10 flex items-center justify-between">
                                <span className="text-lg font-semibold text-white">Total:</span>
                                <span className="text-2xl font-bold text-red-500">
                                    {total.toFixed(2)} {currency}
                                </span>
                            </div>
                        </div>

                        {/* Wallet Balance Info */}
                        <div className="rounded-2xl border border-white/10 bg-white/5 p-6 mb-6">
                            <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
                                <DollarSign className="h-5 w-5" />
                                Payment Method
                            </h2>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-gray-400">Wallet Balance:</span>
                                    <span className="font-semibold text-white">{walletBalance.toFixed(2)} CR</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-gray-400">Order Total:</span>
                                    <span className="font-semibold text-white">{total.toFixed(2)} CR</span>
                                </div>
                                <div className="flex items-center justify-between pt-3 border-t border-white/10">
                                    <span className="text-gray-400">Remaining Balance:</span>
                                    <span className={`font-semibold ${walletBalance >= total ? 'text-green-400' : 'text-red-400'}`}>
                                        {(walletBalance - total).toFixed(2)} CR
                                    </span>
                                </div>
                                {insufficientBalance && (
                                    <div className="mt-4 p-3 rounded-lg bg-red-500/20 border border-red-500/50 flex items-start gap-2">
                                        <AlertCircle className="h-5 w-5 text-red-400 flex-shrink-0 mt-0.5" />
                                        <div className="text-sm text-red-400">
                                            Insufficient wallet balance. You need {(total - walletBalance).toFixed(2)} more CR.
                                            <Link href="/wallet" className="underline ml-1">Add credits</Link>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Checkout Form */}
                    <div className="lg:col-span-1">
                        <form onSubmit={handleSubmit} className="rounded-2xl border border-white/10 bg-white/5 p-6">
                            <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
                                <Calendar className="h-5 w-5" />
                                Borrowing Period
                            </h2>
                            
                            <div className="space-y-4 mb-6">
                                <div>
                                    <label className="block text-sm text-gray-300 mb-1">
                                        Shipping Address <span className="text-red-400">*</span>
                                    </label>
                                    <textarea
                                        value={data.shipping_address}
                                        onChange={(e) => setData('shipping_address', e.target.value)}
                                        placeholder="Enter your shipping address..."
                                        rows={3}
                                        required
                                        className="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-white/20 focus:outline-none"
                                    />
                                    {errors.shipping_address && (
                                        <p className="mt-1 text-xs text-red-400">{errors.shipping_address}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm text-gray-300 mb-1">
                                        From Date <span className="text-red-400">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        value={data.period_from}
                                        onChange={(e) => setData('period_from', e.target.value)}
                                        min={new Date().toISOString().split('T')[0]}
                                        required
                                        className="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none"
                                    />
                                    {errors.period_from && (
                                        <p className="mt-1 text-xs text-red-400">{errors.period_from}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm text-gray-300 mb-1">
                                        To Date <span className="text-red-400">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        value={data.period_to}
                                        onChange={(e) => setData('period_to', e.target.value)}
                                        min={data.period_from || new Date().toISOString().split('T')[0]}
                                        required
                                        className="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none"
                                    />
                                    {errors.period_to && (
                                        <p className="mt-1 text-xs text-red-400">{errors.period_to}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm text-gray-300 mb-1">
                                        Notes (optional)
                                    </label>
                                    <textarea
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        placeholder="Any additional information..."
                                        rows={2}
                                        className="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-white/20 focus:outline-none"
                                    />
                                    {errors.notes && (
                                        <p className="mt-1 text-xs text-red-400">{errors.notes}</p>
                                    )}
                                </div>

                                <div className="p-3 rounded-lg bg-blue-500/20 border border-blue-500/50 text-sm text-blue-400">
                                    <strong>Note:</strong> The full amount will be deducted from your wallet when you request the loan. The amount will be refunded when you return the items.
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={processing || insufficientBalance || items.length === 0 || !data.period_from || !data.period_to || !data.shipping_address.trim()}
                                className={`w-full rounded-xl px-6 py-4 font-semibold text-white text-lg transition ${
                                    processing || insufficientBalance || items.length === 0 || !data.period_from || !data.period_to || !data.shipping_address.trim()
                                        ? 'bg-gray-600 cursor-not-allowed opacity-60'
                                        : 'bg-red-600 hover:bg-red-700'
                                }`}
                            >
                                {processing ? 'Processing...' : insufficientBalance ? 'Insufficient Balance' : 'Request Loans'}
                            </button>

                            {errors.cart && (
                                <div className="mt-4 p-3 rounded-lg bg-red-500/20 border border-red-500/50 text-sm text-red-400">
                                    {Array.isArray(errors.cart) ? errors.cart.join(', ') : errors.cart}
                                </div>
                            )}
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

