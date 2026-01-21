import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    Briefcase,
    Cpu,
    GraduationCap,
    HeartHandshake,
    UsersRound,
    Wrench
} from 'lucide-react';

export default function InternshipPrograms() {
    return (
        <AppLayout breadcrumbs={[{ title: 'Internship Programs', href: '/internship-programs' }]}>
            <Head title="Internship Programs" />
            <div className="min-h-[100vh] w-full bg-black text-white">
                {/* Hero */}
                <section className="relative overflow-hidden border-b border-neutral-800">
                    <div className="container mx-auto px-4 py-16 sm:py-20">
                        <div className="max-w-3xl">
                            <h1 className="text-4xl sm:text-5xl font-extrabold tracking-tight">
                                Programul de <span className="text-red-600">Internship</span> Micro Lab
                            </h1>
                            <p className="mt-4 text-lg text-gray-800/90 dark:text-gray-300/90">
                                Rezolvă probleme reale cu soluții inginerești: IT, Electronică, Software, Automatizări, Mecatronică și mai mult.
                            </p>
                        </div>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <span className="rounded-full bg-neutral-900 border border-neutral-800 px-4 py-1 text-red-400 font-semibold">#INOVEAZĂ</span>
                            <span className="rounded-full bg-neutral-900 border border-neutral-800 px-4 py-1 text-red-400 font-semibold">#CREEAZĂ</span>
                            <span className="rounded-full bg-neutral-900 border border-neutral-800 px-4 py-1 text-red-400 font-semibold">#INSPIRĂ</span>
                        </div>
                        <div className="mt-8">
                            <Link href="#apply" className="inline-flex items-center rounded-xl px-6 py-3 bg-red-600 text-white font-semibold hover:bg-red-700 transition">
                                ÎNREGISTREAZĂ-TE
                                <ArrowRight className="ml-2" />
                            </Link>
                        </div>
                    </div>
                </section>

                <hr className="border-t border-neutral-800" />

                {/* What to expect */}
                <section className="container mx-auto px-4 py-16">
                    <h2 className="text-3xl font-bold mb-10 text-white">Ce te așteaptă la Internship?</h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <FeatureCard icon={<UsersRound className="h-7 w-7" />} title="Mentori de nota 10" desc="Experți în inginerie electronică și R&D, cu îndrumare tehnică pe tot parcursul proiectelor." />
                        <FeatureCard icon={<BadgeCheck className="h-7 w-7" />} title="Validarea orelor de practică" desc="Îți validăm 120 de ore de practică universitară într-o comunitate activă de ingineri." />
                        <FeatureCard icon={<GraduationCap className="h-7 w-7" />} title="Cunoștințe practice" desc="Parcurgi cursurile Micro Lab Academy și aplici teoria în proiecte reale. Primești diplomă de micromaster." />
                        <FeatureCard icon={<Wrench className="h-7 w-7" />} title="Echipamente industriale" desc="Acces la echipamente de ultimă generație pentru dezvoltare la cel mai înalt nivel." />
                        <FeatureCard icon={<Briefcase className="h-7 w-7" />} title="Oportunități de angajare" desc="Interacționezi cu parteneri din topul companiilor locale—posibile oferte după internship." />
                        <FeatureCard icon={<HeartHandshake className="h-7 w-7" />} title="Comunitate" desc="Lucru în echipă, întâlniri live și sprijin reciproc într-un mediu prietenos." />
                    </div>
                </section>

                {/* Why apply */}
                <section className="bg-neutral-900 border-y border-neutral-800">
                    <div className="container mx-auto px-4 py-16">
                        <h2 className="text-3xl font-bold mb-8 always-white">De ce merită să aplici?</h2>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <ReasonCard title="Networking" desc="Cunoști profesioniști din industrie și îți construiești conexiuni valoroase." />
                            <ReasonCard title="Dezvoltarea carierei" desc="Îți consolidezi CV-ul și abilitățile pentru a fi mai competitiv pe piața muncii." />
                            <ReasonCard title="Înțelegerea industriei" desc="Vezi din interior cultura și practicile industriei ca să-ți clarifici direcția." />
                        </div>
                    </div>
                </section>

                {/* What you'll learn */}
                <section className="container mx-auto px-4 py-16">
                    <h2 className="text-3xl font-bold mb-8">Ce vei învăța, concret?</h2>
                    <ul className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-gray-200">
                        {[
                            'Cunoștințe tehnice',
                            'Abilități de lucru în echipă',
                            'Machine learning & AI',
                            'Management de proiect',
                            'Rezolvare de probleme',
                            'Experiență practică',
                            'Programare',
                            'Comunicare și colaborare',
                        ].map((item) => (
                            <li key={item} className="flex items-center gap-3 rounded-lg border border-neutral-800 bg-neutral-900 px-4 py-3">
                                <Cpu className="h-5 w-5 text-red-500" />
                                <span>{item}</span>
                            </li>
                        ))}
                    </ul>
                </section>

                {/* Partners (placeholders for now) */}
                <section className="bg-neutral-900 border-y border-neutral-800">
                    <div className="container mx-auto px-4 py-14">
                        <h3 className="text-xl font-semibold text-gray-200 mb-6">Program realizat cu suportul partenerilor</h3>
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6 opacity-90">
                            {['USAID', 'Sweden', 'UTM', 'FabLab', 'AROBS', 'Mechatronics Center'].map((p) => (
                                <div key={p} className="flex items-center justify-center rounded-lg border border-neutral-800 bg-black/40 px-3 py-6 text-white always-white">
                                    {p}
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Apply CTA */}
                <section id="apply" className="container mx-auto px-4 py-16">
                    <div className="rounded-2xl border border-red-600/30 bg-gradient-to-br from-red-600/10 to-red-500/10 p-8 sm:p-10">
                        <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                            <div>
                                <h3 className="text-2xl font-bold">Gata să pornești?</h3>
                                <p className="mt-1 text-gray-300">Aplică la program și alătură-te comunității noastre de inovație.</p>
                            </div>
                            <Link href="/contact" className="inline-flex items-center rounded-xl px-6 py-3 bg-red-600 text-white font-semibold hover:bg-red-700 transition">
                                Aplică acum
                                <ArrowRight className="ml-2" />
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

function FeatureCard({ icon, title, desc }: { icon: React.ReactNode; title: string; desc: string }) {
    return (
        <div className="rounded-2xl bg-neutral-900 border border-neutral-800 p-6">
            <div className="flex items-center gap-3 text-red-500">{icon}<span className="sr-only">{title}</span></div>
            <h3 className="mt-3 text-lg font-semibold text-white always-white">{title}</h3>
            <p className="mt-2 text-gray-300">{desc}</p>
        </div>
    );
}

function ReasonCard({ title, desc }: { title: string; desc: string }) {
    return (
        <div className="rounded-2xl bg-black border border-neutral-800 p-6">
            <h4 className="text-lg font-semibold text-white">{title}</h4>
            <p className="mt-2 text-gray-800/90 dark:text-gray-300/90">{desc}</p>
        </div>
    );
}
