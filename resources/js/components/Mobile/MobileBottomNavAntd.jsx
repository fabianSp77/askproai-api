import React, { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { Badge, Drawer } from 'antd';
import {
    HomeOutlined,
    PhoneOutlined,
    CalendarOutlined,
    TeamOutlined,
    MoreOutlined,
    BarChartOutlined,
    SettingOutlined,
    WalletOutlined,
} from '@ant-design/icons';

const MobileBottomNavAntd = () => {
    const location = useLocation();
    const navigate = useNavigate();
    const [moreDrawerVisible, setMoreDrawerVisible] = useState(false);

    const navItems = [
        {
            key: '/business/dashboard',
            icon: <HomeOutlined />,
            label: 'Dashboard',
            path: '/business/dashboard',
        },
        {
            key: '/business/calls',
            icon: <PhoneOutlined />,
            label: 'Anrufe',
            path: '/business/calls',
        },
        {
            key: '/business/appointments',
            icon: <CalendarOutlined />,
            label: 'Termine',
            path: '/business/appointments',
        },
        {
            key: '/business/customers',
            icon: <TeamOutlined />,
            label: 'Kunden',
            path: '/business/customers',
        },
    ];

    const moreItems = [
        {
            key: '/business/team',
            icon: <TeamOutlined />,
            label: 'Team',
            path: '/business/team',
        },
        {
            key: '/business/analytics',
            icon: <BarChartOutlined />,
            label: 'Analytics',
            path: '/business/analytics',
        },
        {
            key: '/business/settings',
            icon: <SettingOutlined />,
            label: 'Einstellungen',
            path: '/business/settings',
        },
        {
            key: '/business/billing',
            icon: <WalletOutlined />,
            label: 'Abrechnung',
            path: '/business/billing',
        },
    ];

    const isActive = (path) => {
        return location.pathname === path || location.pathname.startsWith(path + '/');
    };

    const handleNavigate = (path) => {
        navigate(path);
        // Add haptic feedback on supported devices
        if (window.navigator.vibrate) {
            window.navigator.vibrate(10);
        }
    };

    const handleMoreClick = () => {
        setMoreDrawerVisible(true);
        if (window.navigator.vibrate) {
            window.navigator.vibrate(10);
        }
    };

    const handleMoreItemClick = (path) => {
        handleNavigate(path);
        setMoreDrawerVisible(false);
    };

    return (
        <>
            <nav 
                style={{
                    position: 'fixed',
                    bottom: 0,
                    left: 0,
                    right: 0,
                    height: 56,
                    backgroundColor: '#fff',
                    borderTop: '1px solid #f0f0f0',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-around',
                    zIndex: 1000,
                    paddingBottom: 'env(safe-area-inset-bottom, 0)',
                }}
                className="mobile-bottom-nav lg:hidden"
            >
                {navItems.map((item) => {
                    const active = isActive(item.path);
                    return (
                        <div
                            key={item.key}
                            onClick={() => handleNavigate(item.path)}
                            style={{
                                flex: 1,
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                justifyContent: 'center',
                                height: '100%',
                                cursor: 'pointer',
                                position: 'relative',
                                color: active ? '#1890ff' : '#8c8c8c',
                                transition: 'all 0.3s',
                            }}
                        >
                            {active && (
                                <div
                                    style={{
                                        position: 'absolute',
                                        top: 0,
                                        left: '50%',
                                        transform: 'translateX(-50%)',
                                        width: 32,
                                        height: 2,
                                        backgroundColor: '#1890ff',
                                        borderRadius: 1,
                                    }}
                                />
                            )}
                            <div style={{ 
                                fontSize: 20,
                                marginBottom: 2,
                                transform: active ? 'scale(1.1)' : 'scale(1)',
                                transition: 'transform 0.3s',
                            }}>
                                {item.icon}
                            </div>
                            <div style={{ 
                                fontSize: 11, 
                                fontWeight: active ? 600 : 400,
                            }}>
                                {item.label}
                            </div>
                        </div>
                    );
                })}
                
                {/* More button */}
                <div
                    onClick={handleMoreClick}
                    style={{
                        flex: 1,
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        justifyContent: 'center',
                        height: '100%',
                        cursor: 'pointer',
                        color: '#8c8c8c',
                    }}
                >
                    <div style={{ fontSize: 20, marginBottom: 2 }}>
                        <MoreOutlined />
                    </div>
                    <div style={{ fontSize: 11 }}>
                        Mehr
                    </div>
                </div>
            </nav>

            {/* More drawer */}
            <Drawer
                placement="bottom"
                open={moreDrawerVisible}
                onClose={() => setMoreDrawerVisible(false)}
                height="auto"
                bodyStyle={{ padding: 0 }}
                style={{ borderTopLeftRadius: 16, borderTopRightRadius: 16 }}
            >
                <div style={{ padding: '16px 0' }}>
                    <h3 style={{ 
                        fontSize: 18, 
                        fontWeight: 600, 
                        marginBottom: 16,
                        paddingLeft: 16,
                    }}>
                        Weitere Optionen
                    </h3>
                    {moreItems.map((item) => {
                        const active = isActive(item.path);
                        return (
                            <div
                                key={item.key}
                                onClick={() => handleMoreItemClick(item.path)}
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    padding: '12px 16px',
                                    cursor: 'pointer',
                                    backgroundColor: active ? '#f0f0f0' : 'transparent',
                                    transition: 'background-color 0.3s',
                                }}
                            >
                                <div style={{
                                    width: 40,
                                    height: 40,
                                    backgroundColor: '#f0f0f0',
                                    borderRadius: 8,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    marginRight: 12,
                                    fontSize: 20,
                                    color: active ? '#1890ff' : '#8c8c8c',
                                }}>
                                    {item.icon}
                                </div>
                                <span style={{
                                    fontSize: 16,
                                    fontWeight: active ? 600 : 400,
                                    color: active ? '#1890ff' : '#262626',
                                }}>
                                    {item.label}
                                </span>
                            </div>
                        );
                    })}
                </div>
            </Drawer>
        </>
    );
};

export default MobileBottomNavAntd;