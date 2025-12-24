import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import {
    Cpu,
    GraduationCap,
    Handshake,
    ShieldCheck,
    Leaf,
    Rocket,
    ArrowRight,
} from 'lucide-react';

const accent = 'text-red-600';
const accentBg = 'bg-red-600';
// Updated heroText to include text clipping utilities for gradients
const heroText = 'text-5xl sm:text-6xl lg:text-7xl tracking-tight font-extrabold';

const coreValues = [
    {
        icon: <Cpu className="h-12 w-12 mx-auto text-red-500 mb-4 transition duration-300 group-hover:scale-110" />,
        title: 'Focus on Electronics',
        desc: 'Providing high-quality components and reliable technology for all projects.'
    },
    {
        icon: <GraduationCap className="h-12 w-12 mx-auto text-red-500 mb-4 transition duration-300 group-hover:scale-110" />,
        title: 'Education & Growth',
        desc: 'Tutorials, mentorship, hands-on learning via our programs.'
    },
    {
        icon: <Handshake className="h-12 w-12 mx-auto text-red-500 mb-4 transition duration-300 group-hover:scale-110" />,
        title: 'Community & Collaboration',
        desc: 'Strong network where ideas are shared and projects thrive.'
    },
    {
        icon: <ShieldCheck className="h-12 w-12 mx-auto text-red-500 mb-4 transition duration-300 group-hover:scale-110" />,
        title: 'Quality & Reliability',
        desc: 'Performance and longevity guaranteed for all electronic parts offered.'
    },
    {
        icon: <Leaf className="h-12 w-12 mx-auto text-red-500 mb-4 transition duration-300 group-hover:scale-110" />,
        title: 'Sustainable Future',
        desc: 'Responsible sourcing and eco-friendly practices in tech.'
    },
    {
        icon: <Rocket className="h-12 w-12 mx-auto text-red-500 mb-4 transition duration-300 group-hover:scale-110" />,
        title: 'Pioneering Research',
        desc: 'Supporting the next generation of technological breakthroughs.'
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
            <div className="min-h-[100vh] w-full bg-black text-white flex flex-col justify-between">
                
                {/* Hero Section */}
                <div className="relative py-16 px-2 sm:py-24 flex flex-col lg:flex-row container mx-auto gap-12 lg:gap-0 items-center lg:items-start justify-between pt-8">
                    
                    {/* Logo and Title */}
                    <div className="flex-1 flex flex-col items-start justify-center min-w-[320px]">
                        <div className="flex items-center gap-4 mb-8">
                            <span className="block rounded-xl bg-red-600 p-2">
                                {/* Replace with your SVG/logo as needed */}
                                <svg width="44" height="44" fill="currentColor" className="text-white" viewBox="0 0 20 20"><rect x="2" y="2" width="4" height="4" rx="1"/><rect x="8" y="2" width="4" height="4" rx="1"/><rect x="14" y="2" width="4" height="4" rx="1"/><rect x="2" y="8" width="4" height="4" rx="1"/><rect x="8" y="8" width="4" height="4" rx="1"/><rect x="14" y="8" width="4" height="4" rx="1"/><rect x="2" y="14" width="4" height="4" rx="1"/><rect x="8" y="14" width="4" height="4" rx="1"/><rect x="14" y="14" width="4" height="4" rx="1"/></svg>
                            </span>
                            <span className="text-3xl sm:text-4xl font-bold text-white tracking-tight">Electroteca</span>
                        </div>
                        
                        {/* Heading with Gradient Text */}
                        <div className={heroText + " mb-6"}>
                            {/* Apply gradient to the white text */}
                            <span className="text-white bg-clip-text text-transparent bg-gradient-to-r from-white to-gray-200">
                                Powering the Future of{' '}
                            </span>
                            <span className="text-red-600">Tech Innovation</span>
                        </div>
                        
                        {/* Sub-text revised for impact */}
                        <div className="mb-8 text-lg text-gray-800/95 dark:text-gray-300/95 max-w-2xl leading-relaxed">
                            <p className="mb-2">Electroteca is a collaborative hub for advanced electronics, combining practical labs with expert-led instruction.</p>
                            <p className="mb-3 text-lg font-semibold text-red-600">Stop guessing. Start building.</p>
                            <p>Gain hands-on training, project-based education, and connect with a community of committed innovators to accelerate your skills.</p>
                        </div>
                        
                        <Link
                            href="/shop"
                            className="inline-flex items-center rounded-xl px-8 py-4 bg-red-600 text-white font-semibold hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 transition text-lg shadow-lg group"
                        >
                            START YOUR PROJECT TODAY
                            <ArrowRight className="ml-3 group-hover:translate-x-1 transition-transform" />
                        </Link>
                    </div>

                    {/* Features/CTAs */}
                    <div className="flex flex-col gap-8 flex-grow mt-12 lg:mt-0 min-w-[330px] max-w-md mx-auto lg:mx-0">
                        <div className="rounded-3xl bg-gradient-to-br from-red-600 to-red-500 shadow-lg p-8 mb-2">
                            <div className="text-white text-2xl font-bold mb-2">Explore Our Electronics</div>
                            <div className="mb-7 text-white/90">Find the components, kits, and tools you need to bring your ideas to life.</div>
                            <Link href="/shop" className="bg-white rounded-xl text-red-600 px-6 py-3 font-semibold shadow hover:bg-red-50 transition">
                                SHOP NOW
                            </Link>
                        </div>
                        <div className="rounded-3xl bg-neutral-900/80 shadow-lg p-8">
                            <div className="text-red-400 text-2xl font-bold mb-2">Internship Programs</div>
                            <div className="mb-7 text-gray-100/90">Apply for our programs to gain hands-on experience and launch your career in tech.</div>
                            <Link href="/internship-programs" className="bg-red-600 rounded-xl text-white px-6 py-3 font-semibold shadow hover:bg-red-700 transition">
                                VIEW INTERNSHIPS
                            </Link>
                        </div>
                    </div>
                </div>

                {/* --- Social Proof / Metrics Bar (NEW SECTION) --- */}
                <section className="py-6 bg-neutral-900 border-y border-red-600/30">
                    <div className="container mx-auto px-3">
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                            {metrics.map((m) => (
                                <div key={m.label} className="py-2">
                                    <div className="text-3xl font-extrabold text-red-500">{m.value}</div>
                                    <div className="text-sm text-gray-400 uppercase tracking-wider mt-1">{m.label}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
                {/* -------------------------------------------------- */}
                
                <hr className="border-t border-neutral-900" />

                {/* Core Values Section */}
                <section className="py-16 bg-black text-center">
                    <div className="container mx-auto px-3">
                        <h2 className="text-3xl sm:text-4xl font-bold mb-12 text-white">Our Core Values: <span className="text-red-600">Driving Innovation</span></h2>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-10 lg:gap-12 max-w-5xl mx-auto">
                            {coreValues.map((v) => (
                                <div 
                                    key={v.title} 
                                    // Added hover effects: slight scale and subtle shadow
                                    className="group rounded-2xl bg-neutral-900 shadow p-8 flex flex-col items-center transition duration-300 ease-in-out transform hover:scale-[1.03] hover:shadow-xl hover:shadow-red-900/40"
                                >
                                    {/* Icon now receives the group-hover transition */}
                                    {v.icon}
                                    <h3 className="text-xl font-semibold always-white mt-6 mb-2">{v.title}</h3>
                                    <p className="text-gray-300 text-base">{v.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <hr className="border-t border-neutral-900" />

                {/* Latest Innovations / Gallery CTA */}
                <section className="py-24 bg-black">
                    <div className="container mx-auto px-3 text-center">
                        <h2 className="text-4xl font-bold mb-8 text-white">Our Latest Innovations</h2>
                        <div className="mb-8 text-2xl font-bold text-red-500">Ready to jump in?</div>
                        <Link href="#projects" className="inline-block rounded-xl bg-red-600 text-white px-8 py-4 text-lg font-semibold shadow-lg hover:bg-red-700 transition">
                            VIEW FULL PROJECT GALLERY <ArrowRight className="inline-block ml-2" />
                        </Link>
                    </div>
                </section>

                <hr className="border-t border-neutral-800" />
            </div>
        </AppLayout>
    );
}
