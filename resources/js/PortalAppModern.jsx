import React, { useState } from 'react';
import { Routes, Route, Navigate, useNavigate, useLocation } from 'react-router-dom';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { useTheme } from './contexts/ThemeContext';
import { useAuth } from './hooks/useAuth.jsx';
import { Button } from './components/ui/button';
import { cn } from './lib/utils';
import {
    LayoutDashboard,
    Phone,
    Calendar,
    Users,
    BarChart3,
    Settings,
    LogOut,
    User,
    ChevronDown,
    CreditCard,
    MessageSquare,
    Menu,
    X,
    Sun,
    Moon,
    Building
} from 'lucide-react';

// Import pages
import Dashboard from './Pages/Portal/Dashboard/ReactIndexModern';
import CallsIndex from './Pages/Portal/Calls/Index';
import CallShow from './Pages/Portal/Calls/Show';
import AppointmentsIndex from './Pages/Portal/Appointments/Index';
import TeamIndex from './Pages/Portal/Team/Index';
import AnalyticsIndex from './Pages/Portal/Analytics/Index';
import SettingsIndex from './Pages/Portal/Settings/Index';
import BillingIndex from './Pages/Portal/Billing/IndexRefactored';
import FeedbackIndex from './Pages/Portal/Feedback/Index';
import CustomersIndex from './Pages/Portal/Customers/Index';
import CustomerShow from './Pages/Portal/Customers/Show';
import BranchesIndex from './Pages/Portal/Branches/Index';

// Import components
import NotificationCenter from './components/NotificationCenterModern';

const navigation = [
    { name: 'Dashboard', href: '/', icon: LayoutDashboard },
    { name: 'Anrufe', href: '/calls', icon: Phone },
    { name: 'Termine', href: '/appointments', icon: Calendar },
    { name: 'Kunden', href: '/customers', icon: Users },
    { name: 'Team', href: '/team', icon: Users },
    { name: 'Filialen', href: '/branches', icon: Building },
    { name: 'Analysen', href: '/analytics', icon: BarChart3 },
    { name: 'Abrechnung', href: '/billing', icon: CreditCard },
    { name: 'Feedback', href: '/feedback', icon: MessageSquare },
    { name: 'Einstellungen', href: '/settings', icon: Settings },
];

function PortalAppModern({ initialAuth, csrfToken, initialRoute }) {
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const navigate = useNavigate();
    const location = useLocation();
    const { isDarkMode, toggleTheme } = useTheme();
    const { user, logout } = useAuth();

    const currentUser = user || initialAuth?.user;
    
    // Navigate to initial route if provided
    React.useEffect(() => {
        if (initialRoute && location.pathname === '/') {
            navigate(initialRoute);
        }
    }, [initialRoute]);

    const getPageTitle = () => {
        const currentRoute = navigation.find(item => item.href === location.pathname);
        return currentRoute?.name || 'Dashboard';
    };

    return (
        <div className={cn("min-h-screen bg-background font-sans antialiased", isDarkMode && "dark")}>
            {/* Mobile menu */}
            <div className={cn(
                "fixed inset-0 z-50 lg:hidden",
                mobileMenuOpen ? "block" : "hidden"
            )}>
                <div className="fixed inset-0 bg-black/50" onClick={() => setMobileMenuOpen(false)} />
                <div className="fixed inset-y-0 left-0 w-full max-w-xs bg-background shadow-xl">
                    <div className="flex h-16 items-center justify-between px-6 border-b">
                        <span className="text-xl font-semibold">AskProAI</span>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => setMobileMenuOpen(false)}
                        >
                            <X className="h-5 w-5" />
                        </Button>
                    </div>
                    <nav className="px-3 py-4">
                        {navigation.map((item) => (
                            <Button
                                key={item.href}
                                variant={location.pathname === item.href ? "secondary" : "ghost"}
                                className={cn(
                                    "w-full justify-start mb-1",
                                    location.pathname === item.href && "bg-secondary"
                                )}
                                onClick={() => {
                                    navigate(item.href);
                                    setMobileMenuOpen(false);
                                }}
                            >
                                <item.icon className="h-4 w-4 mr-3" />
                                {item.name}
                            </Button>
                        ))}
                    </nav>
                </div>
            </div>

            {/* Desktop sidebar */}
            <div className={cn(
                "hidden lg:fixed lg:inset-y-0 lg:flex lg:flex-col transition-all duration-300",
                sidebarOpen ? "lg:w-64" : "lg:w-20"
            )}>
                <div className="flex flex-1 flex-col bg-card border-r">
                    <div className="flex h-16 items-center px-6 border-b">
                        <span className={cn(
                            "text-xl font-semibold transition-opacity duration-300",
                            !sidebarOpen && "opacity-0"
                        )}>
                            AskProAI
                        </span>
                    </div>
                    <nav className="flex-1 space-y-1 px-3 py-4">
                        {navigation.map((item) => (
                            <Button
                                key={item.href}
                                variant={location.pathname === item.href ? "secondary" : "ghost"}
                                className={cn(
                                    "w-full justify-start",
                                    location.pathname === item.href && "bg-secondary",
                                    !sidebarOpen && "px-3"
                                )}
                                onClick={() => navigate(item.href)}
                            >
                                <item.icon className={cn("h-4 w-4", sidebarOpen && "mr-3")} />
                                {sidebarOpen && item.name}
                            </Button>
                        ))}
                    </nav>
                    <div className="border-t p-4">
                        <Button
                            variant="ghost"
                            size="sm"
                            className="w-full justify-center"
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                        >
                            <Menu className="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            {/* Main content */}
            <div className={cn(
                "lg:pl-64 transition-all duration-300",
                !sidebarOpen && "lg:pl-20"
            )}>
                {/* Header */}
                <header className="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                    <div className="flex h-16 items-center gap-4 px-6">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="lg:hidden"
                            onClick={() => setMobileMenuOpen(true)}
                        >
                            <Menu className="h-5 w-5" />
                        </Button>
                        
                        <h1 className="text-xl font-semibold">{getPageTitle()}</h1>
                        
                        <div className="ml-auto flex items-center gap-4">
                            {/* Theme toggle */}
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={toggleTheme}
                            >
                                {isDarkMode ? (
                                    <Sun className="h-5 w-5" />
                                ) : (
                                    <Moon className="h-5 w-5" />
                                )}
                            </Button>

                            {/* Notifications */}
                            <NotificationCenter csrfToken={csrfToken} />

                            {/* User menu */}
                            <div className="relative">
                                <Button
                                    variant="ghost"
                                    className="flex items-center gap-2"
                                    onClick={() => setUserMenuOpen(!userMenuOpen)}
                                >
                                    <div className="h-8 w-8 rounded-full bg-muted flex items-center justify-center">
                                        <User className="h-4 w-4" />
                                    </div>
                                    <span className="hidden sm:inline-block">
                                        {currentUser?.name || 'User'}
                                    </span>
                                    <ChevronDown className="h-4 w-4" />
                                </Button>

                                {userMenuOpen && (
                                    <>
                                        <div 
                                            className="fixed inset-0 z-10" 
                                            onClick={() => setUserMenuOpen(false)}
                                        />
                                        <div className="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-card border z-20">
                                            <div className="py-1">
                                                <Button
                                                    variant="ghost"
                                                    className="w-full justify-start px-4 py-2"
                                                    onClick={() => {
                                                        window.location.href = '/business/settings/profile';
                                                        setUserMenuOpen(false);
                                                    }}
                                                >
                                                    <User className="h-4 w-4 mr-2" />
                                                    Profil
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    className="w-full justify-start px-4 py-2"
                                                    onClick={() => {
                                                        navigate('/settings');
                                                        setUserMenuOpen(false);
                                                    }}
                                                >
                                                    <Settings className="h-4 w-4 mr-2" />
                                                    Einstellungen
                                                </Button>
                                                <hr className="my-1" />
                                                <Button
                                                    variant="ghost"
                                                    className="w-full justify-start px-4 py-2 text-red-600 hover:text-red-700"
                                                    onClick={() => {
                                                        logout();
                                                        setUserMenuOpen(false);
                                                    }}
                                                >
                                                    <LogOut className="h-4 w-4 mr-2" />
                                                    Abmelden
                                                </Button>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </header>

                {/* Page content */}
                <main className="flex-1">
                    <div className="p-6">
                        <Routes>
                            <Route path="/" element={<Dashboard csrfToken={csrfToken} />} />
                            <Route path="/dashboard" element={<Navigate to="/" replace />} />
                            <Route path="/calls" element={<CallsIndex csrfToken={csrfToken} />} />
                            <Route path="/calls/:id" element={<CallShow />} />
                            <Route path="/appointments" element={<AppointmentsIndex csrfToken={csrfToken} />} />
                            <Route path="/customers" element={<CustomersIndex csrfToken={csrfToken} />} />
                            <Route path="/customers/:id" element={<CustomerShow csrfToken={csrfToken} />} />
                            <Route path="/team" element={<TeamIndex csrfToken={csrfToken} />} />
                            <Route path="/branches" element={<BranchesIndex csrfToken={csrfToken} />} />
                            <Route path="/analytics" element={<AnalyticsIndex csrfToken={csrfToken} />} />
                            <Route path="/settings" element={<SettingsIndex csrfToken={csrfToken} />} />
                            <Route path="/billing" element={<BillingIndex />} />
                            <Route path="/feedback" element={<FeedbackIndex csrfToken={csrfToken} />} />
                        </Routes>
                    </div>
                </main>
            </div>

            <ToastContainer
                position="bottom-right"
                autoClose={5000}
                hideProgressBar={false}
                newestOnTop
                closeOnClick
                rtl={false}
                pauseOnFocusLoss
                draggable
                pauseOnHover
                theme={isDarkMode ? 'dark' : 'light'}
            />
        </div>
    );
}

export default PortalAppModern;