import React, { useState, useEffect } from 'react';
import { Card, Statistic, Row, Col, Table, Tag, Spin, Alert, Button } from 'antd';
import { 
    PhoneOutlined, 
    CalendarOutlined, 
    TeamOutlined,
    PercentageOutlined,
    ClockCircleOutlined,
    ReloadOutlined
} from '@ant-design/icons';
import axiosInstance from '../../../services/axiosInstance';

function Dashboard() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [dashboardData, setDashboardData] = useState(null);

    useEffect(() => {
        fetchDashboardData();
    }, []);

    const fetchDashboardData = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await axiosInstance.get('/dashboard');
            setDashboardData(response.data);
        } catch (err) {
            setError(err.response?.data?.message || err.message);
        } finally {
            setLoading(false);
        }
    };

    const callColumns = [
        {
            title: 'Anrufer',
            dataIndex: 'from_number',
            key: 'from_number',
        },
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
            render: (status) => {
                const colors = {
                    'completed': 'green',
                    'in_progress': 'blue',
                    'ended': 'default',
                };
                return <Tag color={colors[status] || 'default'}>{status}</Tag>;
            }
        },
        {
            title: 'Richtung',
            dataIndex: 'direction',
            key: 'direction',
            render: (direction) => direction === 'inbound' ? 'Eingehend' : 'Ausgehend'
        },
        {
            title: 'Dauer',
            dataIndex: 'duration',
            key: 'duration',
            render: (duration) => duration ? `${duration}s` : '-'
        },
    ];

    const appointmentColumns = [
        {
            title: 'Kunde',
            dataIndex: 'customer_name',
            key: 'customer_name',
        },
        {
            title: 'Service',
            dataIndex: 'service_name',
            key: 'service_name',
        },
        {
            title: 'Mitarbeiter',
            dataIndex: 'staff_name',
            key: 'staff_name',
        },
        {
            title: 'Termin',
            dataIndex: 'starts_at',
            key: 'starts_at',
            render: (date) => new Date(date).toLocaleString('de-DE')
        },
    ];

    if (loading) {
        return (
            <div style={{ 
                display: 'flex', 
                justifyContent: 'center', 
                alignItems: 'center', 
                minHeight: 400 
            }}>
                <Spin size="large" />
            </div>
        );
    }

    if (error) {
        return (
            <Alert
                message="Fehler beim Laden"
                description={error}
                type="error"
                showIcon
                action={
                    <Button size="small" onClick={fetchDashboardData}>
                        Erneut versuchen
                    </Button>
                }
            />
        );
    }

    if (!dashboardData) {
        return null;
    }

    return (
        <div>
            <div style={{ marginBottom: 24, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <div>
                    <h2 style={{ margin: 0 }}>Willkommen zurück!</h2>
                    <p style={{ color: '#666', marginTop: 8, marginBottom: 0 }}>
                        Hier ist Ihre Übersicht für heute
                    </p>
                </div>
                <Button 
                    icon={<ReloadOutlined />} 
                    onClick={fetchDashboardData}
                    loading={loading}
                >
                    Aktualisieren
                </Button>
            </div>

            <Row gutter={16} style={{ marginBottom: 24 }}>
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="Anrufe heute"
                            value={dashboardData.stats?.calls_today || 0}
                            prefix={<PhoneOutlined />}
                        />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="Termine heute"
                            value={dashboardData.stats?.appointments_today || 0}
                            prefix={<CalendarOutlined />}
                        />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="Neue Kunden"
                            value={dashboardData.stats?.new_customers || 0}
                            prefix={<TeamOutlined />}
                        />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card>
                        <Statistic
                            title="Umsatz heute"
                            value={dashboardData.stats?.revenue_today || 0}
                            prefix="€"
                            precision={2}
                        />
                    </Card>
                </Col>
            </Row>

            <Row gutter={16} style={{ marginBottom: 24 }}>
                <Col span={12}>
                    <Card 
                        title="Letzte Anrufe" 
                        extra={<a href="/business/calls">Alle anzeigen</a>}
                    >
                        <Table
                            columns={callColumns}
                            dataSource={dashboardData.recentCalls || []}
                            rowKey="id"
                            pagination={false}
                            size="small"
                        />
                    </Card>
                </Col>
                <Col span={12}>
                    <Card 
                        title="Nächste Termine"
                        extra={<a href="/business/appointments">Alle anzeigen</a>}
                    >
                        {dashboardData.upcomingAppointments && dashboardData.upcomingAppointments.length > 0 ? (
                            <Table
                                columns={appointmentColumns}
                                dataSource={dashboardData.upcomingAppointments}
                                rowKey="id"
                                pagination={false}
                                size="small"
                            />
                        ) : (
                            <div style={{ textAlign: 'center', padding: '40px 0', color: '#999' }}>
                                Keine anstehenden Termine
                            </div>
                        )}
                    </Card>
                </Col>
            </Row>

            <Row gutter={16}>
                <Col span={24}>
                    <Card title="Anruf-Trend (letzte 7 Tage)">
                        <div style={{ display: 'flex', justifyContent: 'space-around', alignItems: 'flex-end', height: 200 }}>
                            {(dashboardData.chartData?.daily || []).map((day, index) => (
                                <div key={index} style={{ textAlign: 'center', flex: 1 }}>
                                    <div style={{
                                        height: day.calls * 10 + 20,
                                        background: '#1890ff',
                                        margin: '0 4px',
                                        borderRadius: '4px 4px 0 0',
                                        position: 'relative',
                                        minHeight: 20,
                                    }}>
                                        <span style={{
                                            position: 'absolute',
                                            top: -20,
                                            left: '50%',
                                            transform: 'translateX(-50%)',
                                            fontWeight: 'bold',
                                        }}>
                                            {day.calls}
                                        </span>
                                    </div>
                                    <div style={{ marginTop: 8, fontSize: 12 }}>{day.date}</div>
                                </div>
                            ))}
                        </div>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

export default Dashboard;