import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
// import { useRef } from 'react'; // Not strictly needed unless you're implementing form logic

// Helper component for styled form fields
const Input = ({ label, id, placeholder, type = 'text', className = '' }) => (
    <div>
        <label
            htmlFor={id}
            className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
            {label}
        </label>
        <input
            type={type}
            id={id}
            placeholder={placeholder}
            className={`w-full rounded-lg border border-neutral-300 bg-white p-3 text-black placeholder-gray-500 shadow-sm transition duration-150 ease-in-out focus:border-red-600 focus:ring-red-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:placeholder-gray-400 ${className}`}
        />
    </div>
);

export default function Contact() {
    return (
        <AppLayout breadcrumbs={[{ title: 'Contact', href: '/contact' }]}>
            <Head title="Contact" />

            <div className="w-full bg-white py-12 text-black dark:bg-neutral-900 dark:text-white">
                <div className="mx-auto w-full max-w-[1100px] p-4 sm:p-8">
                    {/* --- Header Section (Only H1 remains) --- */}
                    <header className="mb-12 text-center">
                        <h1 className="mb-3 text-4xl font-extrabold text-black sm:text-5xl dark:text-white">
                            Contact our team
                        </h1>
                    </header>
                    {/* --- End Header Section --- */}

                    {/* --- Main Content Grid --- */}
                    <div className="grid grid-cols-1 border border-neutral-200 bg-white shadow-xl lg:grid-cols-3 dark:border-neutral-800 dark:bg-neutral-900">
                        {/* Left Column: Contact Form (lg:col-span-2) */}
                        <div className="p-6 sm:p-10 lg:col-span-2">
                            <form className="space-y-6">
                                {/* First Name & Last Name */}
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <Input
                                        label="First name"
                                        id="first_name"
                                        placeholder="First name"
                                    />
                                    <Input
                                        label="Last name"
                                        id="last_name"
                                        placeholder="Last name"
                                    />
                                </div>

                                {/* Email */}
                                <Input
                                    label="Email"
                                    id="email"
                                    type="email"
                                    placeholder="you@company.com"
                                />

                                {/* Phone Number */}
                                <Input
                                    label="Phone number"
                                    id="phone_number"
                                    type="tel"
                                    placeholder="+1 (555) 000-0000"
                                />

                                {/* Message Area */}
                                <div>
                                    <label
                                        htmlFor="message"
                                        className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Message
                                    </label>
                                    <textarea
                                        id="message"
                                        rows={4}
                                        placeholder="Leave us a message..."
                                        className="w-full rounded-lg border border-neutral-300 bg-white p-3 text-black placeholder-gray-500 shadow-sm transition duration-150 ease-in-out focus:border-red-600 focus:ring-red-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:placeholder-gray-400"
                                    ></textarea>
                                </div>

                                {/* Send Message Button */}
                                <button
                                    type="submit"
                                    className="mt-6 w-full rounded-lg bg-black px-6 py-3 font-semibold text-white transition duration-150 ease-in-out hover:opacity-95 dark:bg-white dark:text-black"
                                >
                                    Send message
                                </button>
                            </form>
                        </div>
                    </div>
                    {/* Right Column: Contact Methods Sidebar (bg-neutral-800) */}
                    <div className="space-y-10 border-neutral-200 bg-neutral-50 p-6 sm:p-10 lg:border-l dark:border-neutral-700 dark:bg-neutral-800">
                        {/* Call us */}
                        <div>
                            <h2 className="mb-3 text-xl font-bold text-black dark:text-white">
                                Call us
                            </h2>
                            <p className="mb-4 text-gray-800/90 dark:text-gray-300">
                                Call our team Mon-Fri from 8am to 5pm.
                            </p>
                            <div className="flex items-center text-lg font-bold text-black dark:text-white">
                                <span className="mr-2 text-red-600">📞</span>
                                <a
                                    href="tel:+15550000000"
                                    className="transition duration-150 ease-in-out hover:text-red-600"
                                >
                                    +373 (79) 993255
                                </a>
                            </div>
                        </div>

                        {/* Visit us */}
                        <div>
                            <h2 className="mb-3 text-xl font-bold text-black dark:text-white">
                                Visit us
                            </h2>
                            <p className="mb-4 text-gray-800/90 dark:text-gray-300">
                                Chat to us in person at Microlab, Tekwill.
                            </p>
                            <div className="flex items-center text-lg font-bold text-black dark:text-white">
                                <span className="mr-2 text-red-600">📍</span>
                                <a
                                    href="https://maps.app.goo.gl/2TGmBGTnj8Mz1Dt39"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="transition duration-150 ease-in-out hover:text-red-600"
                                >
                                    Studentilor Street 9/11, Chișinău, Moldova
                                </a>
                            </div>
                        </div>
                    </div>
                    {/* --- End Main Content Grid --- */}
                </div>
            </div>
        </AppLayout>
    );
}
