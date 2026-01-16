import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface PaymentCancelledProps {
    payment: any;
}

export default function PaymentCancelled({ payment }: PaymentCancelledProps) {
    return (
        <AppLayout>
            <Head title="Payment Cancelled" />

            <div className="container mx-auto px-4 py-16 text-center max-w-2xl">
                <AlertCircle className="inline-block w-20 h-20 text-orange-500 mb-6" />

                <h1 className="text-4xl font-bold text-orange-600 mb-4">
                    Payment Cancelled
                </h1>

                <p className="text-lg text-gray-700 mb-8">
                    Your payment has been cancelled. Your order has not been processed.
                </p>

                {payment && (
                    <div className="bg-orange-50 border border-orange-200 rounded-lg p-6 mb-8 text-left">
                        <h2 className="text-lg font-semibold text-orange-900 mb-4">Order Details</h2>
                        <div className="space-y-3">
                            <div className="flex justify-between">
                                <span className="text-gray-600">Order Amount:</span>
                                <span className="font-semibold">
                                    {payment?.amount} {payment?.currency}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-600">Payment Method:</span>
                                <span className="capitalize font-semibold">{payment?.gateway}</span>
                            </div>
                        </div>
                    </div>
                )}

                <div className="space-y-4">
                    <p className="text-gray-600">
                        No charges have been made to your account. You can try again at any time.
                    </p>
                    <div className="flex gap-4 justify-center">
                        <Link href="/checkout">
                            <Button className="bg-blue-600 hover:bg-blue-700">
                                Try Again
                            </Button>
                        </Link>
                        <Link href="/shop">
                            <Button variant="outline">
                                Back to Shop
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
