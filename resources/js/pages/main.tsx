import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Cpu,
    GraduationCap,
    Handshake,
    Leaf,
    Rocket,
    ShieldCheck,
} from 'lucide-react';

const accent = 'text-red-600';
const accentBg = 'bg-red-600';
// Updated heroText to include text clipping utilities for gradients
const heroText =
    'text-5xl sm:text-6xl lg:text-7xl tracking-tight font-extrabold';

const coreValues = [
    {
        icon: (
            <Cpu className="mx-auto mb-4 h-12 w-12 text-red-500 transition duration-300 group-hover:scale-110" />
        ),
        title: 'Focus on Electronics',
        desc: 'Providing high-quality components and reliable technology for all projects.',
    },
    {
        icon: (
            <GraduationCap className="mx-auto mb-4 h-12 w-12 text-red-500 transition duration-300 group-hover:scale-110" />
        ),
        title: 'Education & Growth',
        desc: 'Tutorials, mentorship, hands-on learning via our programs.',
    },
    {
        icon: (
            <Handshake className="mx-auto mb-4 h-12 w-12 text-red-500 transition duration-300 group-hover:scale-110" />
        ),
        title: 'Community & Collaboration',
        desc: 'Strong network where ideas are shared and projects thrive.',
    },
    {
        icon: (
            <ShieldCheck className="mx-auto mb-4 h-12 w-12 text-red-500 transition duration-300 group-hover:scale-110" />
        ),
        title: 'Quality & Reliability',
        desc: 'Performance and longevity guaranteed for all electronic parts offered.',
    },
    {
        icon: (
            <Leaf className="mx-auto mb-4 h-12 w-12 text-red-500 transition duration-300 group-hover:scale-110" />
        ),
        title: 'Sustainable Future',
        desc: 'Responsible sourcing and eco-friendly practices in tech.',
    },
    {
        icon: (
            <Rocket className="mx-auto mb-4 h-12 w-12 text-red-500 transition duration-300 group-hover:scale-110" />
        ),
        title: 'Pioneering Research',
        desc: 'Supporting the next generation of technological breakthroughs.',
    },
];

const metrics = [
    { value: '10K+', label: 'Components in Stock' },
    { value: '500+', label: 'Active Innovators' },
    { value: '4.9/5', label: 'Project Rating' },
    { value: '12', label: 'Internship Programs' },
];

export default function Main() {
    return (
        <AppLayout breadcrumbs={[]}>
            <Head title="Electroteca" />
            <div className="flex min-h-[100vh] w-full flex-col justify-between bg-black text-white">
                {/* Hero Section */}
                <div className="relative container mx-auto flex flex-col items-center justify-between gap-12 px-2 py-16 pt-8 sm:py-24 lg:flex-row lg:items-start lg:gap-0">
                    {/* Logo and Title */}
                    <div className="flex min-w-[320px] flex-1 flex-col items-start justify-center">
                        <div className="mb-8 flex items-center gap-4">
                            <span className="block rounded-xl bg-red-600 p-2">
                                {/* Replace with your SVG/logo as needed */}
                                <svg
                                    width="44"
                                    height="44"
                                    fill="currentColor"
                                    className="text-white"
                                    viewBox="0 0 20 20"
                                >
                                    <rect
                                        x="2"
                                        y="2"
                                        width="4"
                                        height="4"
                                        rx="1"
                                    />
                                    <rect
                                        x="8"
                                        y="2"
                                        width="4"
                                        height="4"
                                        rx="1"
                                    />
                                    <rect
                                        x="14"
                                        y="2"
                                        width="4"
                                        height="4"
                                        rx="1"
                                    />
                                    <rect
                                        x="2"
                                        y="8"
                                        width="4"
                                        height="4"
                                        rx="1"
                                    />
                                    <rect
                                        x="8"
                                        y="8"
                                        width="4"
                                        height="4"
                                        rx="1"
                                    />
                                    <rect
                                        x="14"
                                        y="8"
                                        width="4"
                                        height="4"
                                        rx="1"
                                    />
                                    <rect
                                        x="2"
                                        y="14"
                                        width="4"
                                        height="4"
                                        rx="1"
                                    />
                                    <rect
                                        x="8"
                                        y="14"
                                        width="4"
                                        height="4"
                                        rx="1"
                                    />
                                    <rect
                                        x="14"
                                        y="14"
                                        width="4"
                                        height="4"
                                        rx="1"
                                    />
                                </svg>
                            </span>
                            <span className="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                                Electroteca
                            </span>
                        </div>

                        {/* Heading with Gradient Text */}
                        <div className={heroText + ' mb-6'}>
                            {/* Apply gradient to the white text */}
                            <span className="bg-gradient-to-r from-white to-gray-200 bg-clip-text text-transparent text-white">
                                Powering the Future of{' '}
                            </span>
                            <span className="text-red-600">
                                Tech Innovation
                            </span>
                        </div>

                        {/* Sub-text revised for impact */}
                        <div className="mb-8 max-w-2xl text-lg leading-relaxed text-gray-800/95 dark:text-gray-300/95">
                            <p className="mb-2">
                                Electroteca is a collaborative hub for advanced
                                electronics, combining practical labs with
                                expert-led instruction.
                            </p>
                            <p className="mb-3 text-lg font-semibold text-red-600">
                                Stop guessing. Start building.
                            </p>
                            <p>
                                Gain hands-on training, project-based education,
                                and connect with a community of committed
                                innovators to accelerate your skills.
                            </p>
                        </div>

                        <Link
                            href="/shop"
                            className="group inline-flex items-center rounded-xl bg-red-600 px-8 py-4 text-lg font-semibold text-white shadow-lg transition hover:bg-red-700 focus:ring-2 focus:ring-red-400 focus:outline-none"
                        >
                            START YOUR PROJECT TODAY
                            <ArrowRight className="ml-3 transition-transform group-hover:translate-x-1" />
                        </Link>
                    </div>

                    {/* Features/CTAs */}
                    <div className="mx-auto mt-12 flex max-w-md min-w-[330px] flex-grow flex-col gap-8 lg:mx-0 lg:mt-0">
                        <div className="mb-2 rounded-3xl bg-gradient-to-br from-red-600 to-red-500 p-8 shadow-lg">
                            <div className="mb-2 text-2xl font-bold text-white">
                                Explore Our Electronics
                            </div>
                            <div className="mb-7 text-white/90">
                                Find the components, kits, and tools you need to
                                bring your ideas to life.
                            </div>
                            <Link
                                href="/shop"
                                className="rounded-xl bg-white px-6 py-3 font-semibold text-red-600 shadow transition hover:bg-red-50"
                            >
                                SHOP NOW
                            </Link>
                        </div>
                        <div className="rounded-3xl bg-neutral-900/80 p-8 shadow-lg">
                            <div className="mb-2 text-2xl font-bold text-red-400">
                                Internship Programs
                            </div>
                            <div className="mb-7 text-gray-100/90">
                                Apply for our programs to gain hands-on
                                experience and launch your career in tech.
                            </div>
                            <Link
                                href="/internship-programs"
                                className="rounded-xl bg-red-600 px-6 py-3 font-semibold text-white shadow transition hover:bg-red-700"
                            >
                                VIEW INTERNSHIPS
                            </Link>
                        </div>
                    </div>
                </div>

                {/* --- Social Proof / Metrics Bar (NEW SECTION) --- */}
                <section className="border-y border-red-600/30 bg-neutral-900 py-6">
                    <div className="container mx-auto px-3">
                        <div className="grid grid-cols-2 gap-4 text-center md:grid-cols-4">
                            {metrics.map((m) => (
                                <div key={m.label} className="py-2">
                                    <div className="text-3xl font-extrabold text-red-500">
                                        {m.value}
                                    </div>
                                    <div className="mt-1 text-sm tracking-wider text-gray-400 uppercase">
                                        {m.label}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
                {/* -------------------------------------------------- */}

                <hr className="border-t border-neutral-900" />

                {/* Core Values Section */}
                <section className="bg-black py-16 text-center">
                    <div className="container mx-auto px-3">
                        <h2 className="mb-12 text-3xl font-bold text-white sm:text-4xl">
                            Our Core Values:{' '}
                            <span className="text-red-600">
                                Driving Innovation
                            </span>
                        </h2>
                        <div className="mx-auto grid max-w-5xl grid-cols-1 gap-10 md:grid-cols-3 lg:gap-12">
                            {coreValues.map((v) => (
                                <div
                                    key={v.title}
                                    // Added hover effects: slight scale and subtle shadow
                                    className="group flex transform flex-col items-center rounded-2xl bg-neutral-900 p-8 shadow transition duration-300 ease-in-out hover:scale-[1.03] hover:shadow-xl hover:shadow-red-900/40"
                                >
                                    {/* Icon now receives the group-hover transition */}
                                    {v.icon}
                                    <h3 className="always-white mt-6 mb-2 text-xl font-semibold">
                                        {v.title}
                                    </h3>
                                    <p className="text-base text-gray-300">
                                        {v.desc}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <hr className="border-t border-neutral-900" />

                {/* Latest Innovations / Gallery CTA */}
                <section className="bg-black py-24">
                    <div className="container mx-auto px-3 text-center">
                        <h2 className="mb-8 text-4xl font-bold text-white">
                            Our Latest Innovations
                        </h2>
                        <div className="mb-8 text-2xl font-bold text-red-500">
                            Ready to jump in?
                        </div>
                        <Link
                            href="#projects"
                            className="inline-block rounded-xl bg-red-600 px-8 py-4 text-lg font-semibold text-white shadow-lg transition hover:bg-red-700"
                        >
                            VIEW FULL PROJECT GALLERY{' '}
                            <ArrowRight className="ml-2 inline-block" />
                        </Link>
                    </div>
                </section>

                <hr className="border-t border-neutral-800" />
            </div>
        </AppLayout>
    );
}
