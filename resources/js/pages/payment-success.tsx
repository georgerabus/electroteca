import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface PaymentSuccessProps {
    payment: any;
    order: any;
}

export default function PaymentSuccess({ payment, order }: PaymentSuccessProps) {
    return (
        <AppLayout>
            <Head title="Payment Successful" />

            <div className="container mx-auto px-4 py-16 text-center max-w-2xl">
                <CheckCircle className="inline-block w-20 h-20 text-green-500 mb-6" />

                <h1 className="text-4xl font-bold text-green-600 mb-4">
                    Payment Successful! ✓
                </h1>

                <p className="text-lg text-gray-700 mb-8">
                    Thank you for your payment. Your order has been confirmed.
                </p>

                <div className="bg-green-50 border border-green-200 rounded-lg p-6 mb-8 text-left">
                    <h2 className="text-lg font-semibold text-green-900 mb-4">Order Details</h2>
                    <div className="space-y-3">
                        <div className="flex justify-between">
                            <span className="text-gray-600">Order Number:</span>
                            <span className="font-mono font-semibold">{order?.order_number}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-600">Payment Status:</span>
                            <span className="text-green-600 font-semibold">Completed</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-600">Amount Paid:</span>
                            <span className="font-semibold">
                                {payment?.amount} {payment?.currency}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-600">Payment Method:</span>
                            <span className="capitalize font-semibold">{payment?.gateway}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-600">Date:</span>
                            <span className="font-semibold">
                                {new Date(payment?.completed_at).toLocaleString()}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="space-y-4">
                    <p className="text-gray-600">
                        A confirmation email has been sent to your registered email address.
                    </p>
                    <div className="flex gap-4 justify-center">
                        <Link href="/dashboard">
                            <Button className="bg-blue-600 hover:bg-blue-700">
                                Go to Dashboard
                            </Button>
                        </Link>
                        <Link href="/shop">
                            <Button variant="outline">
                                Continue Shopping
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
