import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { ChevronsUpDown, UserCircle2 } from 'lucide-react';

export function NavUser() {
    const { auth } = usePage<SharedData>().props;
    const user = auth?.user ?? null;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    const dropdownSide =
        isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom';

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent"
                            data-test="sidebar-menu-button"
                        >
                            {user ? (
                                <UserInfo user={user} />
                            ) : (
                                <div className="flex items-center gap-2">
                                    <UserCircle2 className="h-5 w-5" />
                                    <span className="text-sm font-medium">
                                        Account
                                    </span>
                                </div>
                            )}
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        side={dropdownSide}
                    >
                        <UserMenuContent user={user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
