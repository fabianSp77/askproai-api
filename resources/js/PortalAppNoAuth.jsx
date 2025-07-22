import React, { useState } from 'react';
import { Routes, Route, useNavigate, useLocation } from 'react-router-dom';
import { ConfigProvider, Layout, Menu, Avatar, Dropdown, Space, Button, Grid } from 'antd';
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
    MenuOutlined,
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
import MobileBottomNavAntd from './components/Mobile/MobileBottomNavAntd';

const { Header, Sider, Content } = Layout;
const { useBreakpoint } = Grid;

function PortalAppNoAuth({ initialAuth, csrfToken }) {
    const [collapsed, setCollapsed] = useState(false);
    
    // HARDCODED USER - NO AUTH CHECK!
    const [user] = useState({
        id: 41,
        name: 'Demo Benutzer',
        email: 'demo@askproai.de',
        company_id: 1,
        role: 'user'
    });
    
    const [isAdminViewing] = useState(false);
    const [adminViewingCompany] = useState('');
    const navigate = useNavigate();
    const location = useLocation();
    const screens = useBreakpoint();
    const isMobile = !screens.md;
    
    // NO AUTH REDIRECT - ALWAYS LOGGED IN!

    const handleLogout = async () => {
        // Just redirect to login
        window.location.href = '/business/login';
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
                            items={menuItems}
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
                                            <span>{user.name}</span>
                                            <DownOutlined />
                                        </>
                                    )}
                                </Space>
                            </Dropdown>
                        </Space>
                    </Header>
                    
                    <Content style={{ 
                        margin: isMobile ? '16px' : '24px', 
                        marginBottom: isMobile ? '72px' : '24px',
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

export default PortalAppNoAuth;