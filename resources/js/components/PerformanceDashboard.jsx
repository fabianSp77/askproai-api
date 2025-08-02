import React, { useState, useEffect } from 'react';
import { Card, Row, Col, Statistic, Progress, Table, Alert, Badge, Tabs, Typography } from 'antd';
import { 
    DashboardOutlined, 
    ClockCircleOutlined, 
    DatabaseOutlined, 
    CloudOutlined,
    WarningOutlined,
    CheckCircleOutlined,
    ArrowUpOutlined,
    ArrowDownOutlined,
    ThunderboltOutlined,
    ApiOutlined
} from '@ant-design/icons';
import { Line, Bar, Gauge } from '@ant-design/charts';
import Echo from 'laravel-echo';

const { Title, Text } = Typography;
const { TabPane } = Tabs;

const PerformanceDashboard = () => {
    const [metrics, setMetrics] = useState({
        responseTime: { current: 0, avg: 0, p95: 0, p99: 0 },
        throughput: { current: 0, total: 0, trend: [] },
        errorRate: { current: 0, total: 0, types: {} },
        database: { queries: 0, slowQueries: 0, connections: 0 },
        cache: { hitRate: 0, misses: 0, memory: 0 },
        system: { cpu: 0, memory: 0, disk: 0 }
    });

    const [alerts, setAlerts] = useState([]);
    const [recommendations, setRecommendations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [realTimeData, setRealTimeData] = useState([]);

    useEffect(() => {
        // Load initial data
        fetchPerformanceData();

        // Setup real-time updates
        const echo = new Echo({
            broadcaster: 'pusher',
            key: process.env.MIX_PUSHER_APP_KEY,
            cluster: process.env.MIX_PUSHER_APP_CLUSTER,
            forceTLS: true
        });

        const channel = echo.channel('performance-metrics');
        
        channel.listen('.metrics.updated', (data) => {
            updateMetrics(data);
        });

        channel.listen('.alert.triggered', (alert) => {
            setAlerts(prev => [alert, ...prev].slice(0, 10));
        });

        // Refresh every 30 seconds
        const interval = setInterval(fetchPerformanceData, 30000);

        return () => {
            channel.stopListening('.metrics.updated');
            channel.stopListening('.alert.triggered');
            echo.leaveChannel('performance-metrics');
            clearInterval(interval);
        };
    }, []);

    const fetchPerformanceData = async () => {
        try {
            const response = await fetch('/api/performance/metrics');
            const data = await response.json();
            
            setMetrics(data.metrics);
            setRecommendations(data.recommendations || []);
            setAlerts(data.alerts || []);
            setLoading(false);
        } catch (error) {
            console.error('Failed to fetch performance data:', error);
            setLoading(false);
        }
    };

    const updateMetrics = (data) => {
        setMetrics(prev => ({
            ...prev,
            ...data
        }));

        // Update real-time chart data
        setRealTimeData(prev => {
            const newData = [...prev, {
                time: new Date().toLocaleTimeString(),
                responseTime: data.responseTime?.current || 0,
                throughput: data.throughput?.current || 0
            }];
            return newData.slice(-20); // Keep last 20 data points
        });
    };

    const getStatusColor = (value, thresholds) => {
        if (value < thresholds.good) return '#52c41a';
        if (value < thresholds.warning) return '#faad14';
        return '#f5222d';
    };

    const responseTimeConfig = {
        data: realTimeData,
        xField: 'time',
        yField: 'responseTime',
        seriesField: 'type',
        smooth: true,
        animation: {
            appear: {
                animation: 'path-in',
                duration: 300,
            },
        },
        point: {
            size: 3,
            shape: 'circle',
        },
        tooltip: {
            showMarkers: true,
        },
        interactions: [
            {
                type: 'marker-active',
            },
        ],
    };

    const gaugeConfig = {
        percent: metrics.system.cpu / 100,
        range: {
            ticks: [0, 0.25, 0.5, 0.75, 1],
            color: ['#30BF78', '#FAAD14', '#F4664A'],
        },
        indicator: {
            pointer: {
                style: {
                    stroke: '#D0D0D0',
                },
            },
            pin: {
                style: {
                    stroke: '#D0D0D0',
                },
            },
        },
        statistic: {
            content: {
                style: {
                    fontSize: '24px',
                    lineHeight: '24px',
                },
            },
        },
    };

    const performanceScore = () => {
        let score = 100;
        
        if (metrics.responseTime.p95 > 500) score -= 20;
        if (metrics.errorRate.current > 0.01) score -= 15;
        if (metrics.cache.hitRate < 0.8) score -= 10;
        if (metrics.database.slowQueries > 10) score -= 15;
        if (metrics.system.memory > 80) score -= 10;
        
        return Math.max(0, score);
    };

    const score = performanceScore();
    const scoreColor = score >= 80 ? '#52c41a' : score >= 60 ? '#faad14' : '#f5222d';

    return (
        <div className="performance-dashboard">
            <Row gutter={[16, 16]}>
                {/* Overall Performance Score */}
                <Col span={24}>
                    <Card>
                        <Row align="middle">
                            <Col span={12}>
                                <Title level={3}>
                                    <DashboardOutlined /> Performance Dashboard
                                </Title>
                            </Col>
                            <Col span={12} style={{ textAlign: 'right' }}>
                                <Statistic
                                    title="Overall Score"
                                    value={score}
                                    suffix="/ 100"
                                    valueStyle={{ color: scoreColor }}
                                    prefix={score >= 80 ? <CheckCircleOutlined /> : <WarningOutlined />}
                                />
                            </Col>
                        </Row>
                    </Card>
                </Col>

                {/* Key Metrics */}
                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Avg Response Time"
                            value={metrics.responseTime.avg}
                            suffix="ms"
                            valueStyle={{ 
                                color: getStatusColor(metrics.responseTime.avg, {
                                    good: 200,
                                    warning: 500
                                })
                            }}
                            prefix={<ClockCircleOutlined />}
                        />
                        <div style={{ marginTop: 8 }}>
                            <Text type="secondary">P95: {metrics.responseTime.p95}ms</Text>
                        </div>
                    </Card>
                </Col>

                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Throughput"
                            value={metrics.throughput.current}
                            suffix="req/s"
                            prefix={<ThunderboltOutlined />}
                        />
                        <div style={{ marginTop: 8 }}>
                            <Text type="secondary">
                                Total: {(metrics.throughput.total / 1000).toFixed(1)}k
                            </Text>
                        </div>
                    </Card>
                </Col>

                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Error Rate"
                            value={metrics.errorRate.current * 100}
                            suffix="%"
                            precision={2}
                            valueStyle={{ 
                                color: metrics.errorRate.current > 0.01 ? '#f5222d' : '#52c41a'
                            }}
                            prefix={metrics.errorRate.current > 0.01 ? 
                                <ArrowUpOutlined /> : <ArrowDownOutlined />
                            }
                        />
                        <div style={{ marginTop: 8 }}>
                            <Text type="secondary">
                                Errors: {metrics.errorRate.total}
                            </Text>
                        </div>
                    </Card>
                </Col>

                <Col xs={24} sm={12} md={6}>
                    <Card>
                        <Statistic
                            title="Cache Hit Rate"
                            value={metrics.cache.hitRate * 100}
                            suffix="%"
                            precision={1}
                            valueStyle={{ 
                                color: metrics.cache.hitRate > 0.8 ? '#52c41a' : '#faad14'
                            }}
                            prefix={<CloudOutlined />}
                        />
                        <div style={{ marginTop: 8 }}>
                            <Text type="secondary">
                                Misses: {metrics.cache.misses}
                            </Text>
                        </div>
                    </Card>
                </Col>

                {/* Detailed Metrics */}
                <Col span={24}>
                    <Card>
                        <Tabs defaultActiveKey="realtime">
                            <TabPane tab="Real-time Metrics" key="realtime">
                                <Row gutter={[16, 16]}>
                                    <Col span={24}>
                                        <Title level={5}>Response Time Trend</Title>
                                        <Line {...responseTimeConfig} height={200} />
                                    </Col>
                                </Row>
                            </TabPane>

                            <TabPane tab="Database" key="database">
                                <Row gutter={[16, 16]}>
                                    <Col span={8}>
                                        <Statistic
                                            title="Active Queries"
                                            value={metrics.database.queries}
                                            prefix={<DatabaseOutlined />}
                                        />
                                    </Col>
                                    <Col span={8}>
                                        <Statistic
                                            title="Slow Queries"
                                            value={metrics.database.slowQueries}
                                            valueStyle={{ 
                                                color: metrics.database.slowQueries > 10 ? '#f5222d' : '#52c41a'
                                            }}
                                        />
                                    </Col>
                                    <Col span={8}>
                                        <Statistic
                                            title="Connections"
                                            value={metrics.database.connections}
                                            suffix="/ 100"
                                        />
                                    </Col>
                                </Row>
                            </TabPane>

                            <TabPane tab="System" key="system">
                                <Row gutter={[16, 16]}>
                                    <Col span={8}>
                                        <Title level={5}>CPU Usage</Title>
                                        <Gauge {...gaugeConfig} height={150} />
                                    </Col>
                                    <Col span={8}>
                                        <Title level={5}>Memory Usage</Title>
                                        <Progress
                                            type="dashboard"
                                            percent={metrics.system.memory}
                                            strokeColor={{
                                                '0%': '#108ee9',
                                                '100%': metrics.system.memory > 80 ? '#f5222d' : '#87d068',
                                            }}
                                        />
                                    </Col>
                                    <Col span={8}>
                                        <Title level={5}>Disk I/O</Title>
                                        <Progress
                                            type="dashboard"
                                            percent={metrics.system.disk}
                                            strokeColor={{
                                                '0%': '#108ee9',
                                                '100%': '#87d068',
                                            }}
                                        />
                                    </Col>
                                </Row>
                            </TabPane>
                        </Tabs>
                    </Card>
                </Col>

                {/* Alerts */}
                {alerts.length > 0 && (
                    <Col span={24}>
                        <Card title="Active Alerts">
                            {alerts.map((alert, index) => (
                                <Alert
                                    key={index}
                                    message={alert.message}
                                    description={alert.description}
                                    type={alert.type || 'warning'}
                                    showIcon
                                    closable
                                    style={{ marginBottom: 8 }}
                                    action={
                                        alert.action && (
                                            <Button size="small" onClick={() => handleAlertAction(alert)}>
                                                {alert.action}
                                            </Button>
                                        )
                                    }
                                />
                            ))}
                        </Card>
                    </Col>
                )}

                {/* Recommendations */}
                {recommendations.length > 0 && (
                    <Col span={24}>
                        <Card title="Performance Recommendations">
                            <Table
                                dataSource={recommendations}
                                columns={[
                                    {
                                        title: 'Type',
                                        dataIndex: 'type',
                                        key: 'type',
                                        render: (type) => (
                                            <Badge 
                                                status={type === 'critical' ? 'error' : 'warning'} 
                                                text={type.toUpperCase()} 
                                            />
                                        ),
                                    },
                                    {
                                        title: 'Issue',
                                        dataIndex: 'issue',
                                        key: 'issue',
                                    },
                                    {
                                        title: 'Recommendation',
                                        dataIndex: 'recommendation',
                                        key: 'recommendation',
                                    },
                                    {
                                        title: 'Impact',
                                        dataIndex: 'impact',
                                        key: 'impact',
                                        render: (impact) => (
                                            <Progress 
                                                percent={impact} 
                                                size="small" 
                                                strokeColor={impact > 50 ? '#f5222d' : '#faad14'}
                                            />
                                        ),
                                    },
                                ]}
                                pagination={false}
                            />
                        </Card>
                    </Col>
                )}
            </Row>
        </div>
    );
};

export default PerformanceDashboard;