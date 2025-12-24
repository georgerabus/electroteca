import { Link } from '@inertiajs/react';

export default function Footer() {
    return (
        <footer className="bg-neutral-900 py-12 px-3 border-t border-neutral-800 mt-8">
            <div className="container mx-auto flex flex-col md:flex-row justify-between items-center md:items-stretch">
                <div className="mb-8 md:mb-0 flex-1">
                    <div className="flex items-center gap-3 mb-3">
                        <span className="block rounded-xl bg-red-600 p-2">
                            <svg width="32" height="32" fill="currentColor" className="text-white" viewBox="0 0 20 20"><rect x="2" y="2" width="4" height="4" rx="1"/><rect x="8" y="2" width="4" height="4" rx="1"/><rect x="14" y="2" width="4" height="4" rx="1"/><rect x="2" y="8" width="4" height="4" rx="1"/><rect x="8" y="8" width="4" height="4" rx="1"/><rect x="14" y="8" width="4" height="4" rx="1"/><rect x="2" y="14" width="4" height="4" rx="1"/><rect x="8" y="14" width="4" height="4" rx="1"/><rect x="14" y="14" width="4" height="4" rx="1"/></svg>
                        </span>
                        <span className="text-xl text-white font-bold tracking-tight always-white">Electroteca</span>
                    </div>
                    <div className="text-gray-300 leading-relaxed">
                        Studentilor Street 9/11, Chișinău, Moldova<br />
                        andrei.bragarenco@microlab.utm.md<br />
                        0 (79) 993255
                    </div>
                </div>
                <div className="flex-1 text-gray-400 text-center md:text-right">
                    <div className="mb-4 md:mb-2 text-xl text-white font-bold always-white">Our Latest Innovations</div>
                    <span className="inline-block mb-2">
                        <Link href="#top" className="text-red-500 font-semibold hover:text-red-700">Back to top ↑</Link>
                    </span>
                    <div className="text-gray-500 mt-2">© {new Date().getFullYear()} Electroteca. All rights reserved.</div>
                </div>
            </div>
        </footer>
    );
}
