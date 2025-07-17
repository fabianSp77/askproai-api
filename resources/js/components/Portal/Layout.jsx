import React, { useState, useEffect } from 'react';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';
import { Sheet, SheetContent, SheetTrigger } from '../ui/sheet';
import { 
    LayoutDashboard, 
    Phone, 
    Calendar, 
    CreditCard, 
    BarChart3, 
    Users, 
    Settings,
    LogOut,
    Menu,
    ChevronDown,
    Building,
    User
} from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import ThemeToggle from '../ThemeToggle';
import MobileBottomNav from '../Mobile/MobileBottomNav';
import { MobileHeader } from '../Mobile/MobileLayout';
import { useIsMobile } from '../../hooks/useMediaQuery';

const Navigation = ({ children }) => {
    const { user, csrfToken } = useAuth();
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const [currentPath, setCurrentPath] = useState(window.location.pathname);
    const [companyName, setCompanyName] = useState('Business Portal');
    const [isAdminViewing, setIsAdminViewing] = useState(false);
    const isMobile = useIsMobile();

    useEffect(() => {
        // Get company name from meta tag or session
        const metaCompanyName = document.querySelector('meta[name="company-name"]')?.getAttribute('content');
        const adminViewingCompany = document.querySelector('meta[name="admin-viewing-company"]')?.getAttribute('content');
        
        if (adminViewingCompany) {
            setCompanyName(`${adminViewingCompany} Portal`);
            setIsAdminViewing(true);
        } else if (metaCompanyName) {
            setCompanyName(`${metaCompanyName} Portal`);
        } else if (user?.company?.name) {
            setCompanyName(`${user.company.name} Portal`);
        }
    }, [user]);

    useEffect(() => {
        // Update current path on navigation
        const handleLocationChange = () => {
            setCurrentPath(window.location.pathname);
        };

        window.addEventListener('popstate', handleLocationChange);
        return () => window.removeEventListener('popstate', handleLocationChange);
    }, []);

    const navigation = [
        {
            name: 'Dashboard',
            href: '/business/dashboard',
            icon: LayoutDashboard,
            active: currentPath === '/business/dashboard'
        },
        {
            name: 'Anrufe',
            href: '/business/calls',
            icon: Phone,
            active: currentPath.startsWith('/business/calls')
        },
        {
            name: 'Termine',
            href: '/business/appointments',
            icon: Calendar,
            active: currentPath.startsWith('/business/appointments'),
            permission: 'appointments.view'
        },
        {
            name: 'Abrechnung',
            href: '/business/billing',
            icon: CreditCard,
            active: currentPath.startsWith('/business/billing'),
            permission: 'billing.view'
        },
        {
            name: 'Analysen',
            href: '/business/analytics',
            icon: BarChart3,
            active: currentPath.startsWith('/business/analytics'),
            permission: 'analytics.view_team'
        },
        {
            name: 'Team',
            href: '/business/team',
            icon: Users,
            active: currentPath.startsWith('/business/team'),
            permission: 'team.view'
        }
    ];

    const filteredNavigation = navigation.filter(item => {
        if (isAdminViewing) return true;
        if (!item.permission) return true;
        return user?.permissions?.includes(item.permission);
    });

    const handleLogout = async () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/business/logout';
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    };

    const handleAdminExit = () => {
        window.location.href = '/business/admin/exit';
    };

    const NavLink = ({ item }) => (
        <a
            href={item.href}
            className={cn(
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',
                item.active
                    ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100'
            )}
        >
            <item.icon className="h-4 w-4" />
            {item.name}
        </a>
    );

    // Mobile Layout
    if (isMobile) {
        return (
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                {/* Admin Banner */}
                {isAdminViewing && (
                    <div className="bg-yellow-400 text-yellow-900 px-4 py-2 text-center text-sm font-medium">
                        Sie befinden sich im Admin-Zugriffsmodus
                    </div>
                )}

                {/* Mobile Header */}
                <MobileHeader
                    title={companyName.replace(' Portal', '')}
                    sticky={true}
                    actions={[
                        {
                            icon: User,
                            label: 'Profil',
                            onClick: () => setIsMobileMenuOpen(true)
                        }
                    ]}
                />

                {/* Mobile Menu Sheet */}
                <Sheet open={isMobileMenuOpen} onOpenChange={setIsMobileMenuOpen}>
                    <SheetContent side="right" className="w-72">
                        <div className="flex flex-col gap-4">
                            <div className="flex items-center gap-2 font-semibold">
                                <User className="h-6 w-6" />
                                <span>{isAdminViewing ? 'Admin Access' : user?.name || 'Guest'}</span>
                            </div>
                            <div className="border-t pt-4">
                                <ThemeToggle />
                            </div>
                            <div className="border-t pt-4">
                                {!isAdminViewing && (
                                    <a
                                        href="/business/settings"
                                        onClick={() => setIsMobileMenuOpen(false)}
                                        className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                                    >
                                        <Settings className="h-4 w-4" />
                                        Einstellungen
                                    </a>
                                )}
                                <button
                                    onClick={isAdminViewing ? handleAdminExit : handleLogout}
                                    className="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                                >
                                    <LogOut className="h-4 w-4" />
                                    {isAdminViewing ? 'Admin-Zugriff beenden' : 'Abmelden'}
                                </button>
                            </div>
                        </div>
                    </SheetContent>
                </Sheet>

                {/* Main Content - Account for bottom nav */}
                <main className="pb-20 px-4 py-4">
                    {children}
                </main>
                
                {/* Mobile Bottom Navigation */}
                <MobileBottomNav />
            </div>
        );
    }

    // Desktop Layout
    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
            {/* Admin Banner */}
            {isAdminViewing && (
                <div className="bg-yellow-400 text-yellow-900 px-4 py-2 text-center text-sm font-medium">
                    Sie befinden sich im Admin-Zugriffsmodus
                </div>
            )}

            {/* Navigation Header */}
            <header className="sticky top-0 z-50 w-full border-b bg-white dark:bg-gray-950 dark:border-gray-800">
                <div className="container flex h-16 items-center px-4">
                    {/* Logo */}
                    <a href="/business/dashboard" className="flex items-center gap-2 font-semibold">
                        <Building className="h-6 w-6" />
                        <span className="hidden sm:inline-block">{companyName}</span>
                    </a>

                    {/* Desktop Navigation */}
                    <nav className="hidden md:flex items-center gap-6 mx-6 flex-1">
                        {filteredNavigation.map((item) => (
                            <NavLink key={item.name} item={item} />
                        ))}
                    </nav>

                    {/* User Menu */}
                    <div className="flex items-center gap-4 ml-auto">
                        <ThemeToggle />
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="flex items-center gap-2">
                                    <User className="h-4 w-4" />
                                    <span className="hidden sm:inline-block">
                                        {isAdminViewing ? 'Admin Access' : user?.name || 'Guest'}
                                    </span>
                                    <ChevronDown className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <DropdownMenuLabel>
                                    {isAdminViewing ? 'Admin Zugriff' : 'Mein Konto'}
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                {!isAdminViewing && (
                                    <DropdownMenuItem asChild>
                                        <a href="/business/settings" className="flex items-center">
                                            <Settings className="mr-2 h-4 w-4" />
                                            Einstellungen
                                        </a>
                                    </DropdownMenuItem>
                                )}
                                <DropdownMenuSeparator />
                                {isAdminViewing ? (
                                    <DropdownMenuItem onClick={handleAdminExit}>
                                        <LogOut className="mr-2 h-4 w-4" />
                                        Admin-Zugriff beenden
                                    </DropdownMenuItem>
                                ) : (
                                    <DropdownMenuItem onClick={handleLogout}>
                                        <LogOut className="mr-2 h-4 w-4" />
                                        Abmelden
                                    </DropdownMenuItem>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </header>

            {/* Main Content */}
            <main className="container mx-auto px-4 py-6">
                {children}
            </main>
        </div>
    );
};

export default Navigation;