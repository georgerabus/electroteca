import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            {/* Minimal, crisp cube mark akin to a Laravel-style outline.
               Inherit currentColor so parent can set white on pink background. */}
            <g fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                {/* Main cube */}
                <path d="M12 3l6 3.5v7L12 17l-6-3.5v-7L12 3z" />
                {/* Inner connections */}
                <path d="M12 10.5l6-4" />
                <path d="M12 10.5l-6-4" />
                <path d="M12 10.5v6.5" />
            </g>
        </svg>
    );
}
