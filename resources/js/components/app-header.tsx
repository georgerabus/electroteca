import { Breadcrumbs } from '@/components/breadcrumbs';
import { Icon } from '@/components/icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle
} from '@/components/ui/navigation-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BadgePercent,
    BookOpen,
    Folder,
    LayoutGrid,
    Menu,
    Package,
    Search,
    ShoppingCart,
    UserCircle2,
    Wallet
} from 'lucide-react';
import ThemeToggleButton from '@/components/ui/theme-toggle';
import AppLogoIcon from './app-logo-icon';
import { useCart } from '@/hooks/use-cart';

const mainNavItems: NavItem[] = [
    {
        title: 'Shop',
        href: '/shop',
        icon: LayoutGrid,
    },
    {
        title: 'Blog',
        href: '/blog',
    },
    {
        title: 'Internship programs',
        href: '/internship-programs',
    },
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'My Loans',
        href: '/loans/my-loans',
        icon: Package,
    },
    {
        title: 'Wallet',
        href: '/wallet',
        icon: Wallet,
    },
    {
        title: 'Contact',
        href: '/contact',
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Admin Panel',
        href: '/admin',
        icon: LayoutGrid,
    },
    {
        title: 'Reputation',
        href: '/admin/reputation-tiers',
        icon: BadgePercent,
    },
];

const rightNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

const activeItemStyles =
    'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100';

interface AppHeaderProps {
    breadcrumbs?: BreadcrumbItem[];
}

export function AppHeader({ breadcrumbs = [] }: AppHeaderProps) {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const authUser = auth?.user ?? null;
    const getInitials = useInitials();
    const { itemCount } = useCart();

    return (
        <>
            <div className="border-b border-sidebar-border/80">
                <div className="mx-auto flex h-16 items-center px-4 max-w-full md:max-w-7xl lg:max-w-10xl">
                    {/* Mobile Menu */}
                    <div className="lg:hidden">
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="mr-2 h-[34px] w-[34px]"
                                >
                                    <Menu className="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent
                                side="left"
                                className="flex h-full w-64 flex-col items-stretch justify-between bg-sidebar"
                            >
                                <SheetTitle className="sr-only">
                                    Navigation Menu
                                </SheetTitle>
                                <SheetHeader className="flex justify-start text-left">
                                    <AppLogoIcon className="h-6 w-6 fill-current text-black dark:text-white" />
                                </SheetHeader>
                                <div className="flex h-full flex-1 flex-col space-y-4 p-4">
                                    <div className="flex h-full flex-col justify-between text-sm">
                                        <div className="flex flex-col space-y-4">
                                            {mainNavItems.map((item) => (
                                                <Link
                                                    key={item.title}
                                                    href={item.href}
                                                    className="flex items-center space-x-2 font-medium"
                                                >
                                                    {item.icon && (
                                                        <Icon
                                                            iconNode={item.icon}
                                                            className="h-5 w-5"
                                                        />
                                                    )}
                                                    <span>{item.title}</span>
                                                </Link>
                                            ))}
                                            {authUser?.admin && (
                                                <>
                                                    <div className="my-2 border-t border-white/10"></div>
                                                    {adminNavItems.map((item) => (
                                                        <Link
                                                            key={item.title}
                                                            href={item.href}
                                                            className="flex items-center space-x-2 font-medium text-red-400"
                                                        >
                                                            {item.icon && (
                                                                <Icon
                                                                    iconNode={item.icon}
                                                                    className="h-5 w-5"
                                                                />
                                                            )}
                                                            <span>{item.title}</span>
                                                        </Link>
                                                    ))}
                                                </>
                                            )}
                                        </div>

                                        {/* Removed rightNavItems from mobile navigation */}
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>

                    {/* <Link
                        href={products()}
                        prefetch
                        className="flex items-center space-x-2"
                    >
                        <AppLogo />
                    </Link> */}
                    <div className="flex items-center space-x-2">
                        <Link href="/" className="flex items-center gap-2 group" style={{ textDecoration: 'none' }}>
                            <AppLogoIcon className="h-8 w-8 text-white bg-red-600 rounded-xl p-1" />
                            <span
                                className="text-2xl font-bold tracking-tight text-red-600 group-hover:text-red-700 transition-colors"
                                style={{ letterSpacing: '0.01em' }}
                            >
                                Electroteca
                            </span>
                        </Link>
                    </div>


                    {/* Desktop Navigation */}
                    <div className="ml-6 hidden h-full items-center space-x-6 lg:flex">
                        <NavigationMenu className="flex h-full items-stretch">
                            <NavigationMenuList className="flex h-full items-stretch space-x-2">
                                {mainNavItems.map((item, index) => (
                                    <NavigationMenuItem
                                        key={index}
                                        className="relative flex h-full items-center"
                                    >
                                        <Link
                                            href={item.href}
                                            className={cn(
                                                navigationMenuTriggerStyle(),
                                                page.url ===
                                                    (typeof item.href ===
                                                    'string'
                                                        ? item.href
                                                        : item.href.url) &&
                                                    activeItemStyles,
                                                'h-9 cursor-pointer px-3',
                                            )}
                                        >
                                            {item.icon && (
                                                <Icon
                                                    iconNode={item.icon}
                                                    className="mr-2 h-4 w-4"
                                                />
                                            )}
                                            {item.title}
                                        </Link>
                                        {page.url === item.href && (
                                            <div className="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"></div>
                                        )}
                                    </NavigationMenuItem>
                                ))}
                                {authUser?.admin && adminNavItems.map((item, index) => (
                                    <NavigationMenuItem
                                        key={`admin-${index}`}
                                        className="relative flex h-full items-center"
                                    >
                                        <Link
                                            href={item.href}
                                            className={cn(
                                                navigationMenuTriggerStyle(),
                                                page.url ===
                                                    (typeof item.href ===
                                                    'string'
                                                        ? item.href
                                                        : item.href.url) &&
                                                    activeItemStyles,
                                                'h-9 cursor-pointer px-3 border-l border-white/10 ml-2 pl-4',
                                            )}
                                        >
                                            {item.icon && (
                                                <Icon
                                                    iconNode={item.icon}
                                                    className="mr-2 h-4 w-4"
                                                />
                                            )}
                                            {item.title}
                                        </Link>
                                        {page.url === item.href && (
                                            <div className="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"></div>
                                        )}
                                    </NavigationMenuItem>
                                ))}
                            </NavigationMenuList>
                        </NavigationMenu>
                    </div>

                    <div className="ml-auto flex items-center space-x-3">
                        {authUser && (
                            <div className="hidden text-sm text-gray-300 md:block">
                                <span className="text-black dark:text-white opacity-70 mr-1">Balance:</span>
                                <span className="font-semibold text-white">
                                    {authUser.wallet_balance?.toFixed
                                        ? authUser.wallet_balance.toFixed(2)
                                        : Number(authUser.wallet_balance ?? 0).toFixed(2)}{' '}
                                    CR
                                </span>
                            </div>
                        )}
                        <div className="relative flex items-center space-x-1">
                            {/* Cart Icon */}
                            <Link href="/cart" className="relative flex items-center justify-center group rounded hover:bg-white/10 transition p-1">
                                <ShoppingCart className="h-6 w-6 text-black dark:text-white opacity-80 group-hover:opacity-100" />
                                {itemCount > 0 && (
                                    <span className="absolute -top-0.5 -right-0.5 px-2 py-0.5 text-xs rounded-full bg-red-600 text-white font-bold" style={{fontSize:'10px'}}>{itemCount}</span>
                                )}
                            </Link>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="group h-9 w-9 cursor-pointer"
                            >
                                <Search className="!size-5 opacity-80 group-hover:opacity-100" />
                            </Button>
                            {/* Theme toggle button placed next to other header controls */}
                            <ThemeToggleButton />
                            {/* Removed rightNavItems from desktop navigation */}
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                {authUser ? (
                                    // Authenticated
                                    <Button
                                        variant="ghost"
                                        className="size-10 rounded-full p-1"
                                    >
                                        <Avatar className="size-8 overflow-hidden rounded-full">
                                            <AvatarImage
                                                src={authUser.avatar}
                                                alt={authUser.name}
                                            />
                                            <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                                {getInitials(authUser.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                    </Button>
                                ) : (
                                    // Guest
                                    <Button
                                        variant="ghost"
                                        className="size-10 rounded-full p-1"
                                    >
                                        <UserCircle2 className="h-8 w-8 text-neutral-800 dark:text-neutral-100" />
                                    </Button>
                                )}
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                <UserMenuContent user={authUser} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>
            {breadcrumbs.length > 1 && (
                <div className="flex w-full border-b border-sidebar-border/70">
                    <div className="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 max-w-full md:max-w-7xl lg:max-w-10xl">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            )}
        </>
    );
}
