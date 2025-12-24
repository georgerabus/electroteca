import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { Clock, User2 } from 'lucide-react';

type Post = {
    id: string;
    title: string;
    excerpt: string;
    author: string;
    date: string;
    readTime: string;
    category: 'All' | 'Tutoriale' | 'Articole Micro Lab';
    href?: string;
};

const categories: Array<Post['category']> = ['All', 'Tutoriale', 'Articole Micro Lab'];

const posts: Post[] = [
    {
        id: 'health-monitoring',
        title: 'Pasul inovativ al dispozitivelor de monitorizare a sănătății',
        excerpt:
            'Monitorizarea sănătății evoluează accelerat prin dispozitive inteligente ce colectează date esențiale în timp real.',
        author: 'Clubul Ingineresc Micro Lab',
        date: '11 mai 2023',
        readTime: '2 min de citit',
        category: 'Articole Micro Lab',
        href: 'https://www.microlab.club/post/pasul-inovativ-al-dispozitivelor-de-monitorizare-a-s%C4%83n%C4%83t%C4%83%C8%9Bii',
    },
    {
        id: 'energy-efficiency',
        title: 'Eficiența energetică în acțiune',
        excerpt:
            'Tehnologii moderne pentru a economisi energie și a reduce impactul asupra mediului înconjurător.',
        author: 'Clubul Ingineresc Micro Lab',
        date: '11 mai 2023',
        readTime: '2 min de citit',
        category: 'Articole Micro Lab',
        href: 'https://www.microlab.club/post/eficien%C8%9Ba-energetic%C4%83-%C3%AEn-ac%C8%9Biune-implementarea-tehnologiilor-moderne-pentru-a-economisi-energie-%C8%99i-a',
    },
    {
        id: 'ev-innovation',
        title: 'Vehiculele electrice — un pas inovativ în tehnologie',
        excerpt:
            'Popularitatea vehiculelor electrice crește datorită progreselor în baterii și infrastructură.',
        author: 'Clubul Ingineresc Micro Lab',
        date: '10 mai 2023',
        readTime: '4 min de citit',
        category: 'Articole Micro Lab',
        href: 'https://www.microlab.club/post/vehiculelor-electrice-un-pas-inovativ-%C3%AEn-tehnologie',
    },
    {
        id: 'agro-bot',
        title: 'Programul Agro Bot: vehicule autonome pentru agricultură de precizie',
        excerpt:
            'Vehicule autonome aduc precizie și eficiență în agricultură, accelerând transformarea digitală.',
        author: 'Clubul Ingineresc Micro Lab',
        date: '10 mai 2023',
        readTime: '2 min de citit',
        category: 'Articole Micro Lab',
        href: 'https://www.microlab.club/post/programul-agro-bot-vehicule-autonome-pentru-agricultura-de-precizie',
    },
    {
        id: 'drones-work',
        title: 'Dronele — jucării sau instrumente de lucru?',
        excerpt:
            'Dronele colaborative deschid noi posibilități în aplicații industriale și cercetare.',
        author: 'Clubul Ingineresc Micro Lab',
        date: '5 mai 2023',
        readTime: '3 min de citit',
        category: 'Articole Micro Lab',
        href: 'https://www.microlab.club/post/dronele-juc%C4%83rii-ai-secolului-xxi-sau-intrumente-de-lucru',
    },
    {
        id: 'future-agriculture',
        title: 'Tehnologiile viitorului în agricultură',
        excerpt:
            'Avansarea tehnologiei revoluționează agricultura: automatizare, senzori inteligenți și decizii bazate pe date.',
        author: 'Clubul Ingineresc Micro Lab',
        date: '3 mai 2023',
        readTime: '2 min de citit',
        category: 'Articole Micro Lab',
        href: 'https://www.microlab.club/post/tehnologiile-viitorului-%C3%AEn-agricultur%C4%83',
    },
];

export default function Blog() {
    return (
        <AppLayout breadcrumbs={[{ title: 'Blog', href: '/blog' }]}>
            <Head title="Blog" />
            <div className="min-h-[100vh] w-full bg-black text-white">
                {/* Hero */}
                <section className="border-b border-neutral-800">
                    <div className="container mx-auto px-4 py-14">
                        <h1 className="text-4xl sm:text-5xl font-extrabold mb-4">
                            <span className="text-red-600">Electroteca</span> Blog
                        </h1>
                        <p className="text-lg text-gray-800/90 dark:text-gray-300/90 max-w-2xl">
                            Idei proaspete, tutoriale și noutăți din comunitatea noastră de electroniști și pasionați de tehnologie.
                        </p>
                    </div>
                </section>

                {/* Category filters removed per request */}

                {/* Posts grid */}
                <section className="container mx-auto px-4 py-10">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {posts.map((post) => (
                            <ArticleCard key={post.id} post={post} />
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

function ArticleCard({ post }: { post: Post }) {
    return (
        <article className="rounded-2xl bg-neutral-900 border border-neutral-800 p-6 flex flex-col">
            <h3
                className={`text-xl font-bold text-white ${[
                    'health-monitoring',
                    'energy-efficiency',
                    'ev-innovation',
                    'agro-bot',
                    'drones-work',
                    'future-agriculture',
                ].includes(post.id)
                    ? 'always-white'
                    : ''}`}
            >
                {post.title}
            </h3>
            <p className="mt-2 text-gray-300 flex-1">{post.excerpt}</p>
            <div className="mt-4 flex items-center justify-between text-gray-400 text-sm">
                <span className="inline-flex items-center gap-2">
                    <User2 className="h-4 w-4" />
                    {post.author}
                </span>
                <span>{post.date}</span>
            </div>
            <div className="mt-2 inline-flex items-center gap-2 text-gray-400 text-sm">
                <Clock className="h-4 w-4" />
                {post.readTime}
            </div>
            <a
                href={post.href || '#'}
                target="_blank"
                rel="noopener noreferrer"
                className="mt-4 inline-block rounded-lg bg-red-600 px-4 py-2 text-center font-semibold hover:bg-red-700 transition"
            >
                Citește mai mult
            </a>
        </article>
    );
}
