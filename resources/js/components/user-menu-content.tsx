import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import { type User } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { BadgePercent, LogOut, Settings, LogIn, UserPlus } from 'lucide-react';

interface UserMenuContentProps {
    user?: User | null;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const { csrf_token: csrfToken } = usePage().props as { csrf_token?: string };
    const cleanup = useMobileNavigation();
    const reputationRating = Math.round(Number(user?.reputation_rating ?? 0));
    const reputationDiscount = Math.max(0, Math.round(Number(user?.reputation_discount_percent ?? 0)));
    const reputationMeta = reputationDiscount > 0
        ? `${reputationRating}/100 - ${reputationDiscount}% off`
        : `${reputationRating}/100`;

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    // Guest menu
    if (!user) {
        return (
            <>
                <DropdownMenuLabel className="p-0 font-normal">
                    <div className="flex flex-col gap-0.5 px-1 py-1.5 text-left text-sm">
                        <span className="font-medium">Account</span>
                        <span className="text-xs text-muted-foreground">
                            Sign in to access your profile
                        </span>
                    </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                    <DropdownMenuItem asChild>
                        <Link
                            href="/login"
                            className="flex w-full items-center"
                            onClick={cleanup}
                            prefetch
                        >
                            <LogIn className="mr-2" />
                            Login
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <Link
                            href="/register"
                            className="flex w-full items-center"
                            onClick={cleanup}
                            prefetch
                        >
                            <UserPlus className="mr-2" />
                            Register
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuGroup>
            </>
        );
    }

    // Authenticated user menu
    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem asChild>
                    <Link
                        className="flex w-full items-center justify-between"
                        href="/reputation"
                        onClick={cleanup}
                        prefetch
                    >
                        <span className="flex items-center">
                            <BadgePercent className="mr-2" />
                            Your Reputation
                        </span>
                        <span className="text-xs text-muted-foreground">
                            {reputationMeta}
                        </span>
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full"
                        href={edit()}
                        as="button"
                        prefetch
                        onClick={cleanup}
                    >
                        <Settings className="mr-2" />
                        Settings
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full"
                    href={logout()}
                    method="post"
                    data={{ _token: csrfToken }}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut className="mr-2" />
                    Log out
                </Link>
            </DropdownMenuItem>
        </>
    );
}
