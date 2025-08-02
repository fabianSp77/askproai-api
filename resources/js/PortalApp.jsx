import React, { useState, useEffect } from 'react';
import { Routes, Route, useNavigate, useLocation } from 'react-router-dom';
import { ConfigProvider, Layout, Menu, Avatar, Dropdown, Space, Button, Switch, Drawer, Grid } from 'antd';
import { 
    DashboardOutlined, 
    PhoneOutlined, 
    CalendarOutlined, 
    TeamOutlined,
    BarChartOutlined,
    SettingOutlined,
    LogoutOutlined,
    UserOutlined,
    DownOutlined,
    CreditCardOutlined,
    MessageOutlined,
    BulbOutlined,
    BulbFilled,
    MenuOutlined,
    CloseOutlined
} from '@ant-design/icons';
import deDE from 'antd/locale/de_DE';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
// Import pages
import Dashboard from './Pages/Portal/Dashboard/ReactIndex';
import CallsIndex from './Pages/Portal/Calls/Index';
import CallShow from './Pages/Portal/Calls/Show';
import CallShowV2 from './Pages/Portal/Calls/ShowV2';
import AppointmentsIndex from './Pages/Portal/Appointments/Index';
import TeamIndex from './Pages/Portal/Team/Index';
import AnalyticsIndex from './Pages/Portal/Analytics/Index';
import SettingsIndex from './Pages/Portal/Settings/Index';
import BillingIndex from './Pages/Portal/Billing/IndexRefactored';
import FeedbackIndex from './Pages/Portal/Feedback/Index';
import CustomersIndex from './Pages/Portal/Customers/Index';

// Import components
import NotificationCenter from './components/NotificationCenter';
import { AuthProvider } from './contexts/AuthContext';
import ErrorBoundary from './components/ErrorBoundary';
import RouteErrorBoundary from './components/RouteErrorBoundary';
import OfflineIndicator from './components/Portal/OfflineIndicator';
import ServiceWorkerManager from './utils/serviceWorker';
import MobileBottomNavAntd from './components/Mobile/MobileBottomNavAntd';

const { Header, Sider, Content } = Layout;
const { useBreakpoint } = Grid;

function PortalApp({ initialAuth, csrfToken }) {
    const [collapsed, setCollapsed] = useState(false);
    const [mobileMenuVisible, setMobileMenuVisible] = useState(false);
    const [mobileDrawerVisible, setMobileDrawerVisible] = useState(false);
    
    // Check for auth override or localStorage user
    const getInitialUser = () => {
        if (window.__AUTH_USER__) {
            return window.__AUTH_USER__;
        }
        if (initialAuth?.user) {
            return initialAuth.user;
        }
        const stored = localStorage.getItem('portal_user');
        if (stored) {
            try {
                return JSON.parse(stored);
            } catch (e) {
                console.error('Failed to parse stored user:', e);
            }
        }
        return null;
    };
    
    const [user] = useState(getInitialUser());
    const [isAdminViewing] = useState(initialAuth?.isAdminViewing || false);
    const [adminViewingCompany] = useState(initialAuth?.adminViewingCompany || '');
    const navigate = useNavigate();
    const location = useLocation();
    const screens = useBreakpoint();
    const isMobile = !screens.md;
    
    // Redirect to login if not authenticated (with delay for auth overrides)
    useEffect(() => {
        // Skip auth check in demo mode or with bypass
        if (window.__DEMO_MODE__ || 
            localStorage.getItem('demo_mode') === 'true' || 
            localStorage.getItem('bypass_active') === 'true' ||
            window.__AUTH_OVERRIDE__) {
            return;
        }
        
        // Add delay to allow auth overrides to work
        const timer = setTimeout(() => {
            if (!user && !initialAuth?.user) {
                // Double-check localStorage before redirecting
                const storedUser = localStorage.getItem('portal_user');
                if (!storedUser) {
                    console.warn('No auth found, would redirect to login but checking session first...');
                    
                    // Check if we have a session via API before redirecting
                    fetch('/business/api/user', {
                        credentials: 'include',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.id) {
                            // We have a session! Store it and reload
                            localStorage.setItem('portal_user', JSON.stringify(data));
                            window.location.reload();
                        } else {
                            // Really no auth, redirect to login
                            window.location.href = '/business/login';
                        }
                    })
                    .catch(() => {
                        // API failed, redirect to login
                        window.location.href = '/business/login';
                    });
                }
            }
        }, 500); // 500ms delay for auth overrides
        
        return () => clearTimeout(timer);
    }, [user, initialAuth]);

    // Service Worker registration
    useEffect(() => {
        if (process.env.NODE_ENV === 'production') {
            ServiceWorkerManager.register().catch(console.error);
        }
    }, []);

    const handleLogout = async () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/business/logout';
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        
        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    };

    const userMenuItems = [
        {
            key: 'profile',
            icon: <UserOutlined />,
            label: 'Profil',
            onClick: () => window.location.href = '/business/settings/profile'
        },
        {
            key: 'settings',
            icon: <SettingOutlined />,
            label: 'Einstellungen',
            onClick: () => navigate('/settings')
        },
        {
            type: 'divider',
        },
        {
            key: 'logout',
            icon: <LogoutOutlined />,
            label: 'Abmelden',
            onClick: handleLogout
        },
    ];

    const menuItems = [
        {
            key: '/',
            icon: <DashboardOutlined />,
            label: 'Dashboard',
            onClick: () => navigate('/'),
        },
        {
            key: '/calls',
            icon: <PhoneOutlined />,
            label: 'Anrufe',
            onClick: () => navigate('/calls'),
        },
        {
            key: '/appointments',
            icon: <CalendarOutlined />,
            label: 'Termine',
            onClick: () => navigate('/appointments'),
        },
        {
            key: '/customers',
            icon: <UserOutlined />,
            label: 'Kunden',
            onClick: () => navigate('/customers'),
        },
        {
            key: '/team',
            icon: <TeamOutlined />,
            label: 'Team',
            onClick: () => navigate('/team'),
        },
        {
            key: '/analytics',
            icon: <BarChartOutlined />,
            label: 'Analysen',
            onClick: () => navigate('/analytics'),
        },
        {
            key: '/billing',
            icon: <CreditCardOutlined />,
            label: 'Abrechnung',
            onClick: () => navigate('/billing'),
        },
        {
            key: '/feedback',
            icon: <MessageOutlined />,
            label: 'Feedback',
            onClick: () => navigate('/feedback'),
        },
        {
            key: '/settings',
            icon: <SettingOutlined />,
            label: 'Einstellungen',
            onClick: () => navigate('/settings'),
        },
    ];

    // Get current page title
    const getPageTitle = () => {
        switch (location.pathname) {
            case '/':
                return 'Dashboard';
            case '/calls':
                return 'Anrufe';
            case '/appointments':
                return 'Termine';
            case '/customers':
                return 'Kunden';
            case '/team':
                return 'Team';
            case '/analytics':
                return 'Analysen';
            case '/settings':
                return 'Einstellungen';
            case '/billing':
                return 'Abrechnung';
            case '/feedback':
                return 'Feedback';
            default:
                return 'Business Portal';
        }
    };

    // Handle navigation with mobile menu close
    const handleNavigate = (path) => {
        navigate(path);
        if (isMobile) {
            setMobileMenuVisible(false);
        }
    };

    // Update menu items to use handleNavigate
    const responsiveMenuItems = menuItems.map(item => ({
        ...item,
        onClick: () => handleNavigate(item.key)
    }));

    return (
        <ConfigProvider locale={deDE}>
            <Layout style={{ minHeight: '100vh' }}>
                {/* Desktop Sidebar */}
                {!isMobile && (
                    <Sider 
                        collapsible 
                        collapsed={collapsed} 
                        onCollapse={setCollapsed}
                        theme="light"
                    >
                        <div style={{ 
                            height: 64, 
                            display: 'flex', 
                            alignItems: 'center', 
                            justifyContent: 'center',
                            borderBottom: '1px solid #f0f0f0'
                        }}>
                            <h2 style={{ margin: 0 }}>{collapsed ? 'AI' : 'AskProAI'}</h2>
                        </div>
                        <Menu 
                            theme="light" 
                            selectedKeys={[location.pathname]} 
                            mode="inline" 
                            items={responsiveMenuItems}
                        />
                    </Sider>
                )}

                
                <Layout>
                    <Header style={{ 
                        padding: isMobile ? '0 16px' : '0 24px', 
                        background: '#fff',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        boxShadow: '0 1px 4px rgba(0,0,0,.08)'
                    }}>
                        <Space>
                            <h1 style={{ 
                                margin: 0, 
                                fontSize: isMobile ? '18px' : '24px'
                            }}>
                                {getPageTitle()}
                            </h1>
                            {isAdminViewing && (
                                <Space style={{ marginLeft: 16 }}>
                                    <Button 
                                        type="primary" 
                                        danger 
                                        size="small"
                                        onClick={() => window.location.href = '/business/admin-exit'}
                                    >
                                        Admin-Ansicht: {adminViewingCompany}
                                    </Button>
                                </Space>
                            )}
                        </Space>
                        
                        <Space size={isMobile ? 'small' : 'large'}>
                            <NotificationCenter csrfToken={csrfToken} />
                            
                            <Dropdown 
                                menu={{ items: userMenuItems }} 
                                trigger={['click']}
                                placement={isMobile ? 'bottomRight' : 'bottom'}
                            >
                                <Space style={{ cursor: 'pointer' }}>
                                    <Avatar 
                                        icon={<UserOutlined />} 
                                        size={isMobile ? 'small' : 'default'}
                                    />
                                    {!isMobile && (
                                        <>
                                            <span>{user?.name || 'User'}</span>
                                            <DownOutlined />
                                        </>
                                    )}
                                </Space>
                            </Dropdown>
                        </Space>
                    </Header>
                    
                    <Content style={{ 
                        margin: isMobile ? '16px' : '24px', 
                        marginBottom: isMobile ? '72px' : '24px', // Extra space for bottom nav
                        minHeight: 280,
                        overflow: 'auto'
                    }}>
                        <RouteErrorBoundary>
                            <Routes>
                                <Route path="/" element={<Dashboard csrfToken={csrfToken} />} />
                                <Route path="/dashboard" element={<Dashboard csrfToken={csrfToken} />} />
                                <Route path="/calls" element={<CallsIndex csrfToken={csrfToken} />} />
                                <Route path="/calls/:id" element={<CallShow csrfToken={csrfToken} />} />
                            <Route path="/calls/:id/v2" element={<CallShowV2 csrfToken={csrfToken} />} />
                            <Route path="/appointments" element={<AppointmentsIndex csrfToken={csrfToken} />} />
                            <Route path="/customers" element={<CustomersIndex csrfToken={csrfToken} />} />
                            <Route path="/team" element={<TeamIndex csrfToken={csrfToken} />} />
                                <Route path="/analytics" element={<AnalyticsIndex csrfToken={csrfToken} />} />
                                <Route path="/settings" element={<SettingsIndex csrfToken={csrfToken} />} />
                                <Route path="/billing" element={<BillingIndex csrfToken={csrfToken} />} />
                                <Route path="/feedback" element={<FeedbackIndex csrfToken={csrfToken} />} />
                            </Routes>
                        </RouteErrorBoundary>
                    </Content>
                </Layout>
            </Layout>
            
            <OfflineIndicator />
            
            {/* Mobile Bottom Navigation */}
            {isMobile && <MobileBottomNavAntd />}
            
            <ToastContainer
                position="top-right"
                autoClose={5000}
                hideProgressBar={false}
                newestOnTop={true}
                closeOnClick
                rtl={false}
                pauseOnFocusLoss
                draggable
                pauseOnHover
                theme="light"
                style={{ zIndex: 9999 }}
            />
        </ConfigProvider>
    );
}

export default PortalApp;

// Mount the app if this file is loaded directly
if (typeof window !== 'undefined' && document.getElementById('app')) {
    import('react-dom/client').then(({ createRoot }) => {
        import('react-router-dom').then(({ BrowserRouter }) => {
            const appElement = document.getElementById('app');
            const authData = appElement.dataset.auth ? JSON.parse(appElement.dataset.auth) : {};
            const csrfToken = appElement.dataset.csrf || '';
            const initialRoute = appElement.dataset.initialRoute || '/';
            
            const root = createRoot(appElement);
            root.render(
                <BrowserRouter basename="/business">
                    <AuthProvider csrfToken={csrfToken} initialAuth={authData}>
                        <PortalApp 
                            initialAuth={authData} 
                            csrfToken={csrfToken}
                            initialRoute={initialRoute}
                        />
                    </AuthProvider>
                </BrowserRouter>
            );
        });
    });
}