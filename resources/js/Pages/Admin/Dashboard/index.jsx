import React, { useState, useEffect } from 'react';
import { Card, Row, Col, Statistic, Progress, Table, Tag, Space, Spin, Alert } from 'antd';
import { 
    UserOutlined, 
    PhoneOutlined, 
    CalendarOutlined, 
    TeamOutlined,
    ArrowUpOutlined,
    ArrowDownOutlined,
    ShopOutlined,
    DollarOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    SyncOutlined
} from '@ant-design/icons';
// Charts werden später hinzugefügt
import axios from 'axios';
import { toast } from 'react-toastify';

const Dashboard = () => {
    const [loading, setLoading] = useState(true);
    const [stats, setStats] = useState(null);
    const [activities, setActivities] = useState([]);
    const [systemHealth, setSystemHealth] = useState(null);

    useEffect(() => {
        fetchDashboardData();
        const interval = setInterval(fetchDashboardData, 30000); // Refresh every 30 seconds
        return () => clearInterval(interval);
    }, []);

    const fetchDashboardData = async () => {
        try {
            const token = localStorage.getItem('admin_token');
            const headers = { Authorization: `Bearer ${token}` };

            const [statsRes, activitiesRes, healthRes] = await Promise.all([
                axios.get('/api/admin/dashboard/stats', { headers }),
                axios.get('/api/admin/dashboard/recent-activity', { headers }),
                axios.get('/api/admin/dashboard/system-health', { headers })
            ]);

            setStats(statsRes.data);
            setActivities(activitiesRes.data);
            setSystemHealth(healthRes.data);
            setLoading(false);
        } catch (error) {
            console.error('Dashboard data fetch error:', error);
            toast.error('Fehler beim Laden der Dashboard-Daten');
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center h-64">
                <Spin size="large" />
            </div>
        );
    }

    if (!stats) {
        return (
            <Alert
                message="Fehler"
                description="Dashboard-Daten konnten nicht geladen werden"
                type="error"
                showIcon
            />
        );
    }


    // Activity table columns
    const activityColumns = [
        {
            title: 'Aktivität',
            dataIndex: 'message',
            key: 'message',
            render: (text, record) => (
                <Space>
                    {record.icon === 'calendar' && <CalendarOutlined style={{ color: record.color }} />}
                    {record.icon === 'phone' && <PhoneOutlined style={{ color: record.color }} />}
                    {record.icon === 'user' && <UserOutlined style={{ color: record.color }} />}
                    <span>{text}</span>
                </Space>
            ),
        },
        {
            title: 'Zeit',
            dataIndex: 'time',
            key: 'time',
            width: 150,
        },
    ];

    const getHealthIcon = (status) => {
        if (status === 'operational') return <CheckCircleOutlined style={{ color: '#52c41a' }} />;
        if (status === 'degraded') return <SyncOutlined style={{ color: '#faad14' }} spin />;
        return <CloseCircleOutlined style={{ color: '#f5222d' }} />;
    };

    return (
        <div>
            <h1 className="text-2xl font-semibold mb-6">Admin Dashboard</h1>

            {/* Overview Stats */}
            <Row gutter={[16, 16]} className="mb-6">
                <Col xs={24} sm={12} lg={6}>
                    <Card hoverable>
                        <Statistic
                            title="Aktive Mandanten"
                            value={stats.overview.active_companies}
                            prefix={<ShopOutlined />}
                            suffix={`/ ${stats.overview.total_companies}`}
                        />
                        <Progress 
                            percent={Math.round((stats.overview.active_companies / stats.overview.total_companies) * 100)} 
                            strokeColor="#52c41a"
                            showInfo={false}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card hoverable>
                        <Statistic
                            title="Termine heute"
                            value={stats.appointments.today}
                            prefix={<CalendarOutlined />}
                            valueStyle={{ color: '#1890ff' }}
                        />
                        <div className="text-sm text-gray-500 mt-2">
                            Anstehend: {stats.appointments.upcoming}
                        </div>
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card hoverable>
                        <Statistic
                            title="Anrufe heute"
                            value={stats.calls.today}
                            prefix={<PhoneOutlined />}
                            valueStyle={{ color: '#52c41a' }}
                        />
                        <div className="text-sm text-gray-500 mt-2">
                            Ø {Math.round(stats.calls.avg_duration)}s
                        </div>
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card hoverable>
                        <Statistic
                            title="Neue Kunden"
                            value={stats.customers.new_today}
                            prefix={<TeamOutlined />}
                            suffix={
                                <span className="text-sm">
                                    {stats.customers.new_today > 0 ? 
                                        <ArrowUpOutlined style={{ color: '#52c41a' }} /> : 
                                        <ArrowDownOutlined style={{ color: '#f5222d' }} />
                                    }
                                </span>
                            }
                        />
                        <div className="text-sm text-gray-500 mt-2">
                            Gesamt: {stats.customers.total}
                        </div>
                    </Card>
                </Col>
            </Row>

            {/* System Health */}
            <Row gutter={[16, 16]} className="mb-6">
                <Col span={24}>
                    <Card 
                        title="System Status" 
                        extra={
                            <Tag color={systemHealth?.status === 'operational' ? 'success' : 'warning'}>
                                {systemHealth?.status === 'operational' ? 'Betriebsbereit' : 'Eingeschränkt'}
                            </Tag>
                        }
                    >
                        <Row gutter={[16, 16]}>
                            {systemHealth?.services.map((service, index) => (
                                <Col xs={24} sm={12} lg={6} key={index}>
                                    <div className="flex items-center justify-between p-3 bg-gray-50 rounded">
                                        <Space>
                                            {getHealthIcon(service.status)}
                                            <span className="font-medium">{service.name}</span>
                                        </Space>
                                        {service.response_time && (
                                            <span className="text-sm text-gray-500">{service.response_time}</span>
                                        )}
                                    </div>
                                </Col>
                            ))}
                        </Row>
                    </Card>
                </Col>
            </Row>

            {/* Charts and Activity */}
            <Row gutter={[16, 16]}>
                <Col xs={24} lg={12}>
                    <Card title="Termine Übersicht">
                        <div className="space-y-4">
                            <div className="flex justify-between items-center p-3 bg-gray-50 rounded">
                                <span>Heute</span>
                                <span className="font-semibold">{stats.appointments.today}</span>
                            </div>
                            <div className="flex justify-between items-center p-3 bg-gray-50 rounded">
                                <span>Diese Woche</span>
                                <span className="font-semibold">{stats.appointments.this_week}</span>
                            </div>
                            <div className="flex justify-between items-center p-3 bg-gray-50 rounded">
                                <span>Dieser Monat</span>
                                <span className="font-semibold">{stats.appointments.this_month}</span>
                            </div>
                            <div className="flex justify-between items-center p-3 bg-blue-50 rounded">
                                <span className="font-medium">Anstehend</span>
                                <span className="font-bold text-blue-600">{stats.appointments.upcoming}</span>
                            </div>
                        </div>
                    </Card>
                </Col>
                <Col xs={24} lg={12}>
                    <Card 
                        title="Letzte Aktivitäten" 
                        bodyStyle={{ padding: 0 }}
                    >
                        <Table
                            dataSource={activities}
                            columns={activityColumns}
                            pagination={false}
                            size="small"
                            scroll={{ y: 300 }}
                            rowKey={(record, index) => index}
                        />
                    </Card>
                </Col>
            </Row>
        </div>
    );
};

export default Dashboard;