import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface PaymentPendingProps {
    payment: any;
}

export default function PaymentPending({ payment }: PaymentPendingProps) {
    return (
        <AppLayout>
            <Head title="Payment Pending" />

            <div className="container mx-auto px-4 py-16 text-center max-w-2xl">
                <Clock className="inline-block w-20 h-20 text-blue-500 mb-6 animate-spin" />

                <h1 className="text-4xl font-bold text-blue-600 mb-4">
                    Payment Processing...
                </h1>

                <p className="text-lg text-gray-700 mb-8">
                    Your payment is being processed. This may take a few moments.
                </p>

                {payment && (
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8 text-left">
                        <h2 className="text-lg font-semibold text-blue-900 mb-4">Order Details</h2>
                        <div className="space-y-3">
                            <div className="flex justify-between">
                                <span className="text-gray-600">Amount:</span>
                                <span className="font-semibold">
                                    {payment?.amount} {payment?.currency}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-600">Status:</span>
                                <span className="text-blue-600 font-semibold capitalize">
                                    {payment?.status}
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
                        Please do not close this page. We'll redirect you automatically once payment is confirmed.
                    </p>
                    <div className="flex gap-4 justify-center">
                        <Link href="/dashboard">
                            <Button className="bg-blue-600 hover:bg-blue-700">
                                Go to Dashboard
                            </Button>
                        </Link>
                        <Link href="/">
                            <Button variant="outline">
                                Home
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
