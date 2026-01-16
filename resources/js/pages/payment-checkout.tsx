import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { CreditCard, Loader } from 'lucide-react';

interface Order {
    id: number;
    order_number: string;
    total_amount: number;
    currency: string;
    status: string;
}

interface CheckoutPageProps {
    order: Order;
}

export default function CheckoutPage({ order }: CheckoutPageProps) {
    const { props } = usePage();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handlePayment = async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch('/payment/initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content,
                },
                body: JSON.stringify({
                    order_id: order.id,
                }),
            });

            const data = await response.json();

            if (!data.success) {
                setError(data.error || 'Payment initialization failed');
                setLoading(false);
                return;
            }

            // Redirect to payment gateway
            if (data.url) {
                window.location.href = data.url;
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : 'An error occurred');
            setLoading(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Checkout - Pay for Order" />

            <div className="container mx-auto px-4 py-8 max-w-2xl">
                <h1 className="text-3xl font-bold mb-8">Complete Your Payment</h1>

                {/* Order Summary */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>Order Summary</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex justify-between items-center py-2 border-b">
                            <span className="text-gray-600">Order #</span>
                            <span className="font-mono font-semibold">{order.order_number}</span>
                        </div>
                        <div className="flex justify-between items-center py-2 border-b">
                            <span className="text-gray-600">Status</span>
                            <span className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                {order.status}
                            </span>
                        </div>
                        <div className="flex justify-between items-center py-4 bg-blue-50 px-4 rounded-lg">
                            <span className="text-lg font-semibold">Total Amount</span>
                            <span className="text-2xl font-bold text-blue-600">
                                {order.total_amount} {order.currency}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                {/* Payment Info */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>Payment Method</CardTitle>
                        <CardDescription>
                            Secure payment processing with Paddle
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="p-4 border-2 border-blue-500 rounded-lg bg-blue-50">
                            <div className="font-semibold text-lg text-blue-900">🔒 Paddle</div>
                            <div className="text-sm text-blue-700 mt-2">
                                Secure digital product payments with worldwide payment support, invoicing, and local tax handling
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Error Message */}
                {error && (
                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                        {error}
                    </div>
                )}

                {/* Payment Button */}
                <Button
                    onClick={handlePayment}
                    disabled={loading}
                    className="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold text-lg mb-4"
                >
                    {loading ? (
                        <>
                            <Loader className="inline mr-2 animate-spin" size={20} />
                            Processing...
                        </>
                    ) : (
                        <>
                            <CreditCard className="inline mr-2" size={20} />
                            Pay {order.total_amount} {order.currency}
                        </>
                    )}
                </Button>

                {/* Security Info */}
                <div className="text-center text-sm text-gray-600 space-y-2">
                    <p>🔒 Your payment information is secured and encrypted</p>
                    <p>✓ We never store your full card details</p>
                </div>
            </div>
        </AppLayout>
    );
}
