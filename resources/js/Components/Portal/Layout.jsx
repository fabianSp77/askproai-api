import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Layout, Menu, Button, Dropdown, Avatar, Space } from 'antd';
import {
    DashboardOutlined,
    PhoneOutlined,
    CalendarOutlined,
    DollarOutlined,
    BarChartOutlined,
    TeamOutlined,
    SettingOutlined,
    LogoutOutlined,
    MenuFoldOutlined,
    MenuUnfoldOutlined,
} from '@ant-design/icons';
import LanguageSelector from '../../components/LanguageSelector';

const { Header, Sider, Content } = Layout;

export default function PortalLayout({ children, auth }) {
    const [collapsed, setCollapsed] = useState(false);

    const menuItems = [
        {
            key: 'dashboard',
            icon: <DashboardOutlined />,
            label: <Link href="/business">Dashboard</Link>,
        },
        {
            key: 'calls',
            icon: <PhoneOutlined />,
            label: <Link href="/business/calls">Anrufe</Link>,
        },
        {
            key: 'appointments',
            icon: <CalendarOutlined />,
            label: <Link href="/business/appointments">Termine</Link>,
        },
        {
            key: 'billing',
            icon: <DollarOutlined />,
            label: <Link href="/business/billing">Abrechnung</Link>,
        },
        {
            key: 'analytics',
            icon: <BarChartOutlined />,
            label: <Link href="/business/analytics">Analysen</Link>,
        },
        {
            key: 'team',
            icon: <TeamOutlined />,
            label: <Link href="/business/team">Team</Link>,
        },
        {
            key: 'settings',
            icon: <SettingOutlined />,
            label: <Link href="/business/settings">Einstellungen</Link>,
        },
    ];

    const userMenuItems = [
        {
            key: 'profile',
            label: 'Mein Profil',
            onClick: () => window.location.href = '/business/settings/profile',
        },
        {
            key: 'logout',
            label: 'Abmelden',
            icon: <LogoutOutlined />,
            onClick: () => {
                document.getElementById('logout-form').submit();
            },
        },
    ];

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Sider trigger={null} collapsible collapsed={collapsed}>
                <div className="logo" style={{ height: 32, margin: 16, background: 'rgba(255, 255, 255, 0.3)' }} />
                <Menu theme="dark" mode="inline" defaultSelectedKeys={['dashboard']} items={menuItems} />
            </Sider>
            <Layout>
                <Header style={{ padding: 0, background: '#fff', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Button
                        type="text"
                        icon={collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />}
                        onClick={() => setCollapsed(!collapsed)}
                        style={{
                            fontSize: '16px',
                            width: 64,
                            height: 64,
                        }}
                    />
                    <div style={{ paddingRight: 24, display: 'flex', alignItems: 'center', gap: 16 }}>
                        <LanguageSelector showLabel={false} />
                        <Dropdown menu={{ items: userMenuItems }} trigger={['click']}>
                            <Space style={{ cursor: 'pointer' }}>
                                <Avatar style={{ backgroundColor: '#1890ff' }}>
                                    {auth.user?.name?.charAt(0).toUpperCase()}
                                </Avatar>
                                <span>{auth.user?.name}</span>
                            </Space>
                        </Dropdown>
                    </div>
                </Header>
                <Content
                    style={{
                        margin: '24px 16px',
                        padding: 24,
                        minHeight: 280,
                        background: '#fff',
                        borderRadius: 8,
                    }}
                >
                    {children}
                </Content>
            </Layout>
            
            {/* Hidden logout form */}
            <form id="logout-form" action="/business/logout" method="POST" style={{ display: 'none' }}>
                <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]').content} />
            </form>
        </Layout>
    );
}