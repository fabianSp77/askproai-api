import React, { useState, useEffect } from 'react';
import { Routes, Route, useNavigate, useLocation } from 'react-router-dom';
import { ConfigProvider, Layout, Menu, Avatar, Dropdown, Space, Button, Switch, Drawer, Grid } from 'antd';
import { 
    DashboardOutlined, 
    TeamOutlined,
    UserOutlined,
    PhoneOutlined,
    CalendarOutlined,
    ShopOutlined,
    SettingOutlined,
    ApiOutlined,
    MonitorOutlined,
    BellOutlined,
    LogoutOutlined,
    MenuOutlined,
    CloseOutlined,
    BulbOutlined,
    BulbFilled,
    GlobalOutlined,
    SolutionOutlined,
    DollarOutlined
} from '@ant-design/icons';
import deDE from 'antd/locale/de_DE';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

// Import pages
import Dashboard from './Pages/Admin/Dashboard';
import CompaniesIndex from './Pages/Admin/Companies/Index';
import UsersIndex from './Pages/Admin/Users/Index';
import CallsIndex from './Pages/Admin/Calls/Index';
import AppointmentsIndex from './Pages/Admin/Appointments/Index';
import CustomersIndex from './Pages/Admin/Customers/Index';
import BranchesIndex from './Pages/Admin/Companies/Branches';
import BillingIndex from './Pages/Admin/Billing/Index';
import SystemHealth from './Pages/Admin/System/Health';
import IntegrationsIndex from './Pages/Admin/Integrations/Index';

// Import components
import { AuthProvider } from './contexts/AdminAuthContext';
import ErrorBoundary from './components/ErrorBoundary';
import AdminNotificationCenter from './components/Admin/NotificationCenter';
import ThemeToggle from './components/ThemeToggle';
import DebugTestButton from './components/Admin/DebugTestButton';

const { Header, Sider, Content } = Layout;
const { useBreakpoint } = Grid;

function AdminApp({ csrfToken }) {
    const [collapsed, setCollapsed] = useState(false);
    const [mobileDrawerVisible, setMobileDrawerVisible] = useState(false);
    const [darkMode, setDarkMode] = useState(() => 
        localStorage.getItem('admin_theme') === 'dark'
    );
    const navigate = useNavigate();
    const location = useLocation();
    const screens = useBreakpoint();
    const isMobile = !screens.md;

    useEffect(() => {
        if (isMobile) {
            setCollapsed(true);
        }
    }, [isMobile]);

    useEffect(() => {
        localStorage.setItem('admin_theme', darkMode ? 'dark' : 'light');
        if (darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }, [darkMode]);

    const menuItems = [
        {
            key: '/admin',
            icon: <DashboardOutlined />,
            label: 'Dashboard',
        },
        {
            key: '/admin/companies',
            icon: <ShopOutlined />,
            label: 'Mandanten',
        },
        {
            key: '/admin/branches',
            icon: <ShopOutlined />,
            label: 'Filialen',
        },
        {
            key: '/admin/users',
            icon: <UserOutlined />,
            label: 'Benutzer',
        },
        {
            key: '/admin/calls',
            icon: <PhoneOutlined />,
            label: 'Anrufe',
        },
        {
            key: '/admin/appointments',
            icon: <CalendarOutlined />,
            label: 'Termine',
        },
        {
            key: '/admin/customers',
            icon: <TeamOutlined />,
            label: 'Kunden',
        },
        {
            key: '/admin/billing',
            icon: <DollarOutlined />,
            label: 'Abrechnung',
        },
        {
            key: '/admin/integrations',
            icon: <ApiOutlined />,
            label: 'Integrationen',
        },
        {
            key: '/admin/system',
            icon: <MonitorOutlined />,
            label: 'System',
        },
    ];

    const userMenuItems = [
        {
            key: 'profile',
            icon: <UserOutlined />,
            label: 'Profil',
        },
        {
            key: 'settings',
            icon: <SettingOutlined />,
            label: 'Einstellungen',
        },
        {
            type: 'divider',
        },
        {
            key: 'logout',
            icon: <LogoutOutlined />,
            label: 'Abmelden',
            danger: true,
        },
    ];

    const handleMenuClick = ({ key }) => {
        if (key === 'logout') {
            // Handle logout
            localStorage.removeItem('admin_token');
            window.location.href = '/admin/login';
        } else {
            navigate(key);
        }
        if (isMobile) {
            setMobileDrawerVisible(false);
        }
    };

    const handleUserMenuClick = ({ key }) => {
        if (key === 'logout') {
            localStorage.removeItem('admin_token');
            window.location.href = '/admin/login';
        } else if (key === 'profile') {
            navigate('/admin/profile');
        } else if (key === 'settings') {
            navigate('/admin/settings');
        }
    };

    const MobileMenu = () => (
        <Drawer
            placement="left"
            closable={true}
            onClose={() => setMobileDrawerVisible(false)}
            open={mobileDrawerVisible}
            width={280}
            bodyStyle={{ padding: 0 }}
            closeIcon={<CloseOutlined />}
        >
            <div className="p-4 border-b">
                <h2 className="text-xl font-bold">AskProAI Admin</h2>
            </div>
            <Menu
                mode="inline"
                selectedKeys={[location.pathname]}
                items={menuItems}
                onClick={handleMenuClick}
                style={{ border: 'none' }}
            />
        </Drawer>
    );

    return (
        <ErrorBoundary>
            <AuthProvider>
                <ConfigProvider locale={deDE} theme={{
                    algorithm: darkMode ? 'dark' : 'default',
                    token: {
                        colorPrimary: '#1890ff',
                    },
                }}>
                    <Layout style={{ minHeight: '100vh' }}>
                        {!isMobile && (
                            <Sider 
                                collapsible 
                                collapsed={collapsed} 
                                onCollapse={setCollapsed}
                                theme={darkMode ? 'dark' : 'light'}
                                style={{
                                    overflow: 'auto',
                                    height: '100vh',
                                    position: 'fixed',
                                    left: 0,
                                    top: 0,
                                    bottom: 0,
                                }}
                            >
                                <div className="logo p-4 text-center">
                                    <h2 className={`text-xl font-bold ${darkMode ? 'text-white' : 'text-gray-800'} ${collapsed ? 'hidden' : ''}`}>
                                        AskProAI Admin
                                    </h2>
                                    <h2 className={`text-xl font-bold ${darkMode ? 'text-white' : 'text-gray-800'} ${!collapsed ? 'hidden' : ''}`}>
                                        A
                                    </h2>
                                </div>
                                <Menu
                                    theme={darkMode ? 'dark' : 'light'}
                                    mode="inline"
                                    selectedKeys={[location.pathname]}
                                    items={menuItems}
                                    onClick={handleMenuClick}
                                />
                            </Sider>
                        )}
                        
                        <Layout style={{ marginLeft: isMobile ? 0 : (collapsed ? 80 : 200) }}>
                            <Header style={{ 
                                padding: '0 24px', 
                                background: darkMode ? '#141414' : '#fff',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                                boxShadow: '0 1px 4px rgba(0,0,0,0.08)'
                            }}>
                                <div className="flex items-center">
                                    {isMobile && (
                                        <Button
                                            type="text"
                                            icon={<MenuOutlined />}
                                            onClick={() => setMobileDrawerVisible(true)}
                                            style={{ marginRight: 16 }}
                                        />
                                    )}
                                </div>
                                
                                <Space size="middle">
                                    <Button
                                        type="text"
                                        icon={darkMode ? <BulbFilled /> : <BulbOutlined />}
                                        onClick={() => setDarkMode(!darkMode)}
                                    />
                                    <AdminNotificationCenter />
                                    <Dropdown 
                                        menu={{ 
                                            items: userMenuItems,
                                            onClick: handleUserMenuClick 
                                        }} 
                                        placement="bottomRight"
                                    >
                                        <Space className="cursor-pointer">
                                            <Avatar icon={<UserOutlined />} />
                                            <span className={darkMode ? 'text-white' : 'text-gray-800'}>
                                                Admin
                                            </span>
                                        </Space>
                                    </Dropdown>
                                </Space>
                            </Header>
                            
                            <Content style={{ 
                                margin: '24px 16px', 
                                padding: 24,
                                minHeight: 280,
                                background: darkMode ? '#141414' : '#f0f2f5',
                            }}>
                                <Routes>
                                    <Route path="/" element={<Dashboard />} />
                                    <Route path="/companies" element={<CompaniesIndex />} />
                                    <Route path="/branches" element={<BranchesIndex />} />
                                    <Route path="/users" element={<UsersIndex />} />
                                    <Route path="/calls" element={<CallsIndex />} />
                                    <Route path="/appointments" element={<AppointmentsIndex />} />
                                    <Route path="/customers" element={<CustomersIndex />} />
                                    <Route path="/billing" element={<BillingIndex />} />
                                    <Route path="/integrations" element={<IntegrationsIndex />} />
                                    <Route path="/system" element={<SystemHealth />} />
                                </Routes>
                            </Content>
                        </Layout>
                        
                        {isMobile && <MobileMenu />}
                        
                        {/* Debug Button - Remove in production */}
                        <DebugTestButton />
                    </Layout>
                    
                    <ToastContainer
                        position="top-right"
                        autoClose={3000}
                        hideProgressBar={false}
                        newestOnTop
                        closeOnClick
                        rtl={false}
                        pauseOnFocusLoss
                        draggable
                        pauseOnHover
                        theme={darkMode ? 'dark' : 'light'}
                    />
                </ConfigProvider>
            </AuthProvider>
        </ErrorBoundary>
    );
}

export default AdminApp;