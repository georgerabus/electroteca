import { SidebarInset } from '@/components/ui/sidebar';
import * as React from 'react';

interface AppContentProps extends React.ComponentProps<'main'> {
    variant?: 'header' | 'sidebar';
}

export function AppContent({
    variant = 'header',
    children,
    ...props
}: AppContentProps) {
    if (variant === 'sidebar') {
        return <SidebarInset {...props}>{children}</SidebarInset>;
    }

    return (
        <main
            className="mx-auto flex h-full w-full max-w-full lg:max-w-10xl flex-1 flex-col gap-4 rounded-xl"
            {...props}
        >
            <div className="w-full h-full transform-gpu transition-all duration-300 ease-out animate-fade-in">
                {children}
            </div>
        </main>
    );
}
