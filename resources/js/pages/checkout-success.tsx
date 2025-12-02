import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle, Package, ArrowRight } from 'lucide-react';

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
        <AppLayout breadcrumbs={[{ title: 'Shop', href: '/shop' }, { title: 'Order Confirmation', href: '#' }]}>
            <Head title="Order Confirmed" />
            <div className="mx-auto max-w-2xl p-4 sm:p-8">
                <div className="text-center mb-8">
                    <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-500/20 mb-4">
                        <CheckCircle className="h-12 w-12 text-green-500" />
                    </div>
                    <h1 className="text-3xl font-bold mb-2">Order Confirmed!</h1>
                    <p className="text-gray-400">Thank you for your purchase</p>
                </div>

                <div className="rounded-2xl border border-white/10 bg-white/5 p-6 mb-6">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <div className="text-sm text-gray-400 mb-1">Order Number</div>
                            <div className="text-xl font-bold text-white">{order.order_number}</div>
                        </div>
                        <div className="text-right">
                            <div className="text-sm text-gray-400 mb-1">Status</div>
                            <div className="px-3 py-1 rounded-lg bg-blue-500/20 text-blue-400 text-sm font-semibold">
                                {order.status}
                            </div>
                        </div>
                    </div>

                    <div className="mb-6">
                        <h2 className="text-lg font-semibold mb-4 flex items-center gap-2">
                            <Package className="h-5 w-5" />
                            Order Items
                        </h2>
                        <div className="divide-y divide-white/10">
                            {order.items.map((item, index) => (
                                <div key={index} className="flex items-center justify-between py-3">
                                    <div>
                                        <div className="font-semibold text-white">{item.product_name}</div>
                                        <div className="text-sm text-gray-400">
                                            {item.price} {order.currency} x {item.quantity}
                                        </div>
                                    </div>
                                    <div className="font-semibold text-white">
                                        {item.subtotal} {order.currency}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="pt-6 border-t border-white/10 flex items-center justify-between">
                        <span className="text-lg font-semibold text-white">Total:</span>
                        <span className="text-2xl font-bold text-red-500">
                            {order.total_amount} {order.currency}
                        </span>
                    </div>

                    <div className="mt-4 text-sm text-gray-400">
                        Order placed on: {new Date(order.created_at).toLocaleString()}
                    </div>
                </div>

                <div className="flex gap-4">
                    <Link
                        href="/shop"
                        className="flex-1 rounded-xl border-2 border-white/20 bg-transparent text-white px-6 py-3 font-semibold hover:bg-white/5 transition text-center"
                    >
                        Continue Shopping
                    </Link>
                    <Link
                        href="/dashboard"
                        className="flex-1 rounded-xl bg-red-600 text-white px-6 py-3 font-semibold hover:bg-red-700 transition text-center flex items-center justify-center gap-2"
                    >
                        View Orders
                        <ArrowRight className="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}

