import React, { useState, useEffect } from 'react';
import { Row, Col, Card, Statistic, Typography, Space, Progress, Table, Tag, Avatar, Spin, Alert } from 'antd';
import { 
    PhoneOutlined, 
    CalendarOutlined, 
    TeamOutlined, 
    ShopOutlined,
    ArrowUpOutlined,
    ArrowDownOutlined,
    UserOutlined,
    ClockCircleOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    SyncOutlined
} from '@ant-design/icons';
// Charts temporarily disabled - @ant-design/plots not installed
// import { Line, Column, Pie } from '@ant-design/plots';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/de';

dayjs.locale('de');

const { Title, Text } = Typography;

const Dashboard = () => {
    const [loading, setLoading] = useState(true);
    const [stats, setStats] = useState({
        calls: { total: 0, today: 0, trend: 0, positive_sentiment: 0 },
        appointments: { total: 0, today: 0, upcoming: 0, completed_rate: 0 },
        customers: { total: 0, new_this_month: 0, active: 0, trend: 0 },
        companies: { total: 0, active: 0, trial: 0, premium: 0 }
    });
    const [recentActivity, setRecentActivity] = useState([]);
    const [systemHealth, setSystemHealth] = useState(null);
    const [chartData, setChartData] = useState({
        calls: [],
        appointments: [],
        revenue: []
    });

    // API instance with CSRF token
    const api = axios.create({
        baseURL: '/api/admin',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    });

    useEffect(() => {
        fetchDashboardData();
        const interval = setInterval(fetchDashboardData, 60000); // Refresh every minute
        return () => clearInterval(interval);
    }, []);

    const fetchDashboardData = async () => {
        try {
            setLoading(true);
            
            // Fetch all data in parallel
            const [statsRes, activityRes, healthRes] = await Promise.all([
                api.get('/dashboard/stats'),
                api.get('/dashboard/recent-activity'),
                api.get('/dashboard/system-health')
            ]);

            setStats(statsRes.data);
            setRecentActivity(activityRes.data.slice(0, 10));
            setSystemHealth(healthRes.data);

            // Process chart data
            if (statsRes.data.charts) {
                setChartData(statsRes.data.charts);
            }
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
        } finally {
            setLoading(false);
        }
    };

    // Chart configurations removed - @ant-design/plots not installed

    const recentActivityColumns = [
        {
            title: 'Zeit',
            dataIndex: 'timestamp',
            key: 'timestamp',
            width: 120,
            render: (timestamp) => dayjs(timestamp).format('HH:mm')
        },
        {
            title: 'Typ',
            dataIndex: 'type',
            key: 'type',
            width: 100,
            render: (type) => {
                const typeConfig = {
                    call: { color: 'blue', icon: <PhoneOutlined />, text: 'Anruf' },
                    appointment: { color: 'green', icon: <CalendarOutlined />, text: 'Termin' },
                    customer: { color: 'purple', icon: <UserOutlined />, text: 'Kunde' },
                    webhook: { color: 'orange', icon: <SyncOutlined />, text: 'Webhook' }
                };
                const config = typeConfig[type] || { color: 'default', icon: null, text: type };
                return (
                    <Tag color={config.color} icon={config.icon}>
                        {config.text}
                    </Tag>
                );
            }
        },
        {
            title: 'Beschreibung',
            dataIndex: 'description',
            key: 'description',
            ellipsis: true
        },
        {
            title: 'Mandant',
            dataIndex: 'company',
            key: 'company',
            width: 150,
            render: (company) => company?.name || '-'
        }
    ];

    const getHealthStatusColor = (status) => {
        switch (status) {
            case 'healthy': return '#52c41a';
            case 'warning': return '#faad14';
            case 'critical': return '#f5222d';
            default: return '#d9d9d9';
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-screen">
                <Spin size="large" />
            </div>
        );
    }

    return (
        <div>
            <div className="mb-6">
                <Title level={2}>Dashboard</Title>
                <Text type="secondary">
                    Letztes Update: {dayjs().format('DD.MM.YYYY HH:mm:ss')}
                </Text>
            </div>

            {/* System Health Alert */}
            {systemHealth?.status === 'critical' && (
                <Alert
                    message="Systemwarnung"
                    description={systemHealth.message}
                    type="error"
                    showIcon
                    className="mb-4"
                />
            )}

            {/* Main Statistics */}
            <Row gutter={[16, 16]} className="mb-6">
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Anrufe heute"
                            value={stats.calls?.today || 0}
                            prefix={<PhoneOutlined />}
                            suffix={
                                <span className={stats.calls?.trend > 0 ? 'text-green-500' : 'text-red-500'}>
                                    {stats.calls?.trend > 0 ? <ArrowUpOutlined /> : <ArrowDownOutlined />}
                                    {Math.abs(stats.calls?.trend || 0)}%
                                </span>
                            }
                        />
                        <Progress 
                            percent={stats.calls?.positive_sentiment || 0} 
                            size="small" 
                            format={(percent) => `${percent}% positiv`}
                            strokeColor="#52c41a"
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Termine heute"
                            value={stats.appointments?.today || 0}
                            prefix={<CalendarOutlined />}
                            valueStyle={{ color: '#3f8600' }}
                        />
                        <div className="mt-2">
                            <Text type="secondary">
                                Anstehend: {stats.appointments?.upcoming || 0}
                            </Text>
                        </div>
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Aktive Kunden"
                            value={stats.customers?.active || 0}
                            prefix={<TeamOutlined />}
                        />
                        <div className="mt-2">
                            <Text type="secondary">
                                +{stats.customers?.new_this_month || 0} diesen Monat
                            </Text>
                        </div>
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Aktive Mandanten"
                            value={stats.companies?.active || 0}
                            prefix={<ShopOutlined />}
                            suffix={`/ ${stats.companies?.total || 0}`}
                        />
                        <div className="mt-2">
                            <Space size="small">
                                <Tag color="gold">{stats.companies?.trial || 0} Trial</Tag>
                                <Tag color="green">{stats.companies?.premium || 0} Premium</Tag>
                            </Space>
                        </div>
                    </Card>
                </Col>
            </Row>

            {/* Charts Row */}
            <Row gutter={[16, 16]} className="mb-6">
                <Col xs={24} lg={12}>
                    <Card title="Anrufe (7 Tage)" size="small">
                        <div className="text-center py-8 text-gray-400">
                            Chart-Komponente wird geladen...
                            {/* Line chart disabled - @ant-design/plots not installed */}
                        </div>
                    </Card>
                </Col>
                <Col xs={24} lg={12}>
                    <Card title="Termin-Status" size="small">
                        <div className="space-y-2">
                            <div className="flex justify-between">
                                <span>Abgeschlossen</span>
                                <Tag color="green">{stats.appointments?.completed || 0}</Tag>
                            </div>
                            <div className="flex justify-between">
                                <span>Geplant</span>
                                <Tag color="blue">{stats.appointments?.scheduled || 0}</Tag>
                            </div>
                            <div className="flex justify-between">
                                <span>Abgesagt</span>
                                <Tag color="red">{stats.appointments?.cancelled || 0}</Tag>
                            </div>
                            <div className="flex justify-between">
                                <span>Nicht erschienen</span>
                                <Tag color="gray">{stats.appointments?.no_show || 0}</Tag>
                            </div>
                        </div>
                    </Card>
                </Col>
            </Row>

            {/* Recent Activity & System Health */}
            <Row gutter={[16, 16]}>
                <Col xs={24} lg={16}>
                    <Card 
                        title="Letzte Aktivitäten" 
                        size="small"
                        extra={
                            <Button 
                                type="text" 
                                icon={<SyncOutlined />} 
                                onClick={fetchDashboardData}
                            >
                                Aktualisieren
                            </Button>
                        }
                    >
                        <Table
                            columns={recentActivityColumns}
                            dataSource={recentActivity}
                            rowKey="id"
                            size="small"
                            pagination={false}
                            scroll={{ y: 300 }}
                        />
                    </Card>
                </Col>
                <Col xs={24} lg={8}>
                    <Card title="System Status" size="small">
                        <Space direction="vertical" className="w-full">
                            {systemHealth?.services?.map((service) => (
                                <div key={service.name} className="flex justify-between items-center">
                                    <Text>{service.name}</Text>
                                    <Tag color={getHealthStatusColor(service.status)}>
                                        {service.status === 'healthy' ? 'OK' : service.status.toUpperCase()}
                                    </Tag>
                                </div>
                            ))}
                            <div className="mt-4 pt-4 border-t">
                                <div className="flex justify-between items-center mb-2">
                                    <Text>Queue Jobs</Text>
                                    <Text strong>{systemHealth?.queue?.pending || 0}</Text>
                                </div>
                                <div className="flex justify-between items-center mb-2">
                                    <Text>Failed Jobs</Text>
                                    <Text strong type={systemHealth?.queue?.failed > 0 ? 'danger' : undefined}>
                                        {systemHealth?.queue?.failed || 0}
                                    </Text>
                                </div>
                                <div className="flex justify-between items-center">
                                    <Text>API Response Time</Text>
                                    <Text strong>{systemHealth?.api_response_time || 0}ms</Text>
                                </div>
                            </div>
                        </Space>
                    </Card>
                </Col>
            </Row>
        </div>
    );
};

export default Dashboard;