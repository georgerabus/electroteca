import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle, Package } from 'lucide-react';

type Order = {
    id: number;
    order_number: string;
    status: string;
    total_amount: string;
    currency: string;
    created_at: string;
    items: Array<{
        product_name: string;
        quantity: number;
        price: string;
        subtotal: string;
    }>;
};

type CheckoutSuccessPageProps = {
    order: Order;
};

export default function CheckoutSuccess({ order }: CheckoutSuccessPageProps) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Shop', href: '/shop' },
                { title: 'Order Confirmation', href: '#' },
            ]}
        >
            <Head title="Order Confirmed" />
            <div className="mx-auto max-w-2xl p-4 sm:p-8">
                <div className="mb-8 text-center">
                    <div className="mb-4 inline-flex h-20 w-20 items-center justify-center rounded-full bg-green-500/20">
                        <CheckCircle className="h-12 w-12 text-green-500" />
                    </div>
                    <h1 className="mb-2 text-3xl font-bold">
                        Order Confirmed!
                    </h1>
                    <p className="text-gray-400">Thank you for your purchase</p>
                </div>

                <div className="mb-6 rounded-2xl border border-white/10 bg-white/5 p-6">
                    <div className="mb-6 flex items-center justify-between">
                        <div>
                            <div className="mb-1 text-sm text-gray-400">
                                Order Number
                            </div>
                            <div className="text-xl font-bold text-white">
                                {order.order_number}
                            </div>
                        </div>
                        <div className="text-right">
                            <div className="mb-1 text-sm text-gray-400">
                                Status
                            </div>
                            <div className="rounded-lg bg-blue-500/20 px-3 py-1 text-sm font-semibold text-blue-400">
                                {order.status}
                            </div>
                        </div>
                    </div>

                    <div className="mb-6">
                        <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                            <Package className="h-5 w-5" />
                            Order Items
                        </h2>
                        <div className="divide-y divide-white/10">
                            {order.items.map((item, index) => (
                                <div
                                    key={index}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <div className="font-semibold text-white">
                                            {item.product_name}
                                        </div>
                                        <div className="text-sm text-gray-400">
                                            {item.price} {order.currency} x{' '}
                                            {item.quantity}
                                        </div>
                                    </div>
                                    <div className="font-semibold text-white">
                                        {item.subtotal} {order.currency}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="flex items-center justify-between border-t border-white/10 pt-6">
                        <span className="text-lg font-semibold text-white">
                            Total:
                        </span>
                        <span className="text-2xl font-bold text-red-500">
                            {order.total_amount} {order.currency}
                        </span>
                    </div>

                    <div className="mt-4 text-sm text-gray-400">
                        Order placed on:{' '}
                        {new Date(order.created_at).toLocaleString()}
                    </div>
                </div>

                <div className="flex gap-4">
                    <Link
                        href="/shop"
                        className="flex-1 rounded-xl border-2 border-white/20 bg-transparent px-6 py-3 text-center font-semibold text-white transition hover:bg-white/5"
                    >
                        Continue Shopping
                    </Link>
                    <Link
                        href="/dashboard"
                        className="flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 px-6 py-3 text-center font-semibold text-white transition hover:bg-red-700"
                    >
                        View Orders
                        <ArrowRight className="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
