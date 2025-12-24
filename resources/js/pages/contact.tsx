import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
// import { useRef } from 'react'; // Not strictly needed unless you're implementing form logic

// Helper component for styled form fields
const Input = ({ label, id, placeholder, type = 'text', className = '' }) => (
    <div>
        <label htmlFor={id} className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {label}
        </label>
        <input
            type={type}
            id={id}
            placeholder={placeholder}
            className={`w-full rounded-lg border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 p-3 shadow-sm focus:border-red-600 focus:ring-red-600 transition duration-150 ease-in-out ${className}`}
        />
    </div>
);

export default function Contact() {
    return (
        <AppLayout breadcrumbs={[{ title: 'Contact', href: '/contact' }]}>
            <Head title="Contact" />
            
            <div className="w-full bg-white dark:bg-neutral-900 py-12 text-black dark:text-white">
                <div className="mx-auto w-full max-w-[1100px] p-4 sm:p-8">

                {/* --- Header Section (Only H1 remains) --- */}
                <header className="text-center mb-12">
                    <h1 className="text-4xl sm:text-5xl font-extrabold mb-3 text-black dark:text-white">
                        Contact our team
                    </h1>
                </header>
                {/* --- End Header Section --- */}

                {/* --- Main Content Grid --- */}
                <div className="grid grid-cols-1 lg:grid-cols-3 bg-white dark:bg-neutral-900 shadow-xl border border-neutral-200 dark:border-neutral-800">
                    
                    {/* Left Column: Contact Form (lg:col-span-2) */}
                    <div className="lg:col-span-2 p-6 sm:p-10">
                        <form className="space-y-6">
                            
                            {/* First Name & Last Name */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <Input label="First name" id="first_name" placeholder="First name" />
                                <Input label="Last name" id="last_name" placeholder="Last name" />
                            </div>

                            {/* Email */}
                            <Input label="Email" id="email" type="email" placeholder="you@company.com" />

                            {/* Phone Number */}
                            <Input label="Phone number" id="phone_number" type="tel" placeholder="+1 (555) 000-0000" />

                            {/* Message Area */}
                            <div>
                                <label htmlFor="message" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Message
                                </label>
                                <textarea
                                    id="message"
                                        rows={4}
                                        placeholder="Leave us a message..."
                                    className="w-full rounded-lg border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 p-3 shadow-sm focus:border-red-600 focus:ring-red-600 transition duration-150 ease-in-out"
                                ></textarea>
                            </div>

                            {/* Send Message Button */}
                            <button
                                type="submit"
                                className="w-full bg-black text-white dark:bg-white dark:text-black font-semibold py-3 px-6 rounded-lg hover:opacity-95 transition duration-150 ease-in-out mt-6"
                            >
                                Send message
                            </button>
                        </form>
                    </div>
                    </div>
                    {/* Right Column: Contact Methods Sidebar (bg-neutral-800) */}
                    <div className="bg-neutral-50 dark:bg-neutral-800 p-6 sm:p-10 space-y-10 lg:border-l border-neutral-200 dark:border-neutral-700">
                        {/* Call us */}
                        <div>
                            <h2 className="text-xl font-bold text-black dark:text-white mb-3">Call us</h2>
                            <p className="text-gray-800/90 dark:text-gray-300 mb-4">Call our team Mon-Fri from 8am to 5pm.</p>
                            <div className="flex items-center text-black dark:text-white text-lg font-bold">
                                <span className="mr-2 text-red-600">📞</span> 
                                <a href="tel:+15550000000" className="hover:text-red-600 transition duration-150 ease-in-out">+373 (79) 993255</a>
                            </div>
                        </div>

                        {/* Visit us */}
                        <div>
                            <h2 className="text-xl font-bold text-black dark:text-white mb-3">Visit us</h2>
                            <p className="text-gray-800/90 dark:text-gray-300 mb-4">Chat to us in person at Microlab, Tekwill.</p>
                            <div className="flex items-center text-black dark:text-white text-lg font-bold">
                                <span className="mr-2 text-red-600">📍</span>
                                <a href="https://maps.app.goo.gl/2TGmBGTnj8Mz1Dt39" target="_blank" rel="noopener noreferrer" className="hover:text-red-600 transition duration-150 ease-in-out">
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