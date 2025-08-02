import React, { useState, useEffect, useRef } from 'react';
import { Card, Row, Col, Progress, Tag, Alert, Table, Tabs, Button, Tooltip, Space, Spin, Badge, Statistic } from 'antd';
import {
    CheckCircleOutlined, WarningOutlined, CloseCircleOutlined,
    ReloadOutlined, DownloadOutlined, SettingOutlined,
    DatabaseOutlined, CloudServerOutlined, HddOutlined,
    ApiOutlined, ClockCircleOutlined, ThunderboltOutlined
} from '@ant-design/icons';
import { Line, Area } from '@ant-design/charts';
import axios from 'axios';
import moment from 'moment';

const { TabPane } = Tabs;

const SystemHealthMonitoring = () => {
    const [loading, setLoading] = useState(true);
    const [autoRefresh, setAutoRefresh] = useState(true);
    const [refreshInterval, setRefreshInterval] = useState(30000); // 30 seconds
    const [systemHealth, setSystemHealth] = useState(null);
    const [performanceMetrics, setPerformanceMetrics] = useState(null);
    const [apiEndpoints, setApiEndpoints] = useState(null);
    const [errorLogs, setErrorLogs] = useState(null);
    const [activeTab, setActiveTab] = useState('overview');
    const intervalRef = useRef(null);

    useEffect(() => {
        fetchAllData();
        
        if (autoRefresh) {
            intervalRef.current = setInterval(fetchAllData, refreshInterval);
        }
        
        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, [autoRefresh, refreshInterval]);

    const fetchAllData = async () => {
        setLoading(true);
        try {
            const [healthRes, metricsRes, apiRes, errorsRes] = await Promise.all([
                axios.get('/api/monitoring/health', { params: { include_details: true, check_external: true } }),
                axios.get('/api/monitoring/metrics', { 
                    params: { 
                        metric_types: ['cpu', 'memory', 'disk', 'database', 'cache', 'queue'],
                        time_range: '1h'
                    } 
                }),
                axios.get('/api/monitoring/api-endpoints', { 
                    params: { 
                        include_response_times: true,
                        include_error_rates: true,
                        time_range: '1h'
                    } 
                }),
                axios.get('/api/monitoring/errors', { 
                    params: { 
                        time_range: '24h',
                        group_by: 'severity',
                        limit: 50
                    } 
                })
            ]);

            setSystemHealth(healthRes.data);
            setPerformanceMetrics(metricsRes.data);
            setApiEndpoints(apiRes.data);
            setErrorLogs(errorsRes.data);
        } catch (error) {
            console.error('Failed to fetch monitoring data:', error);
        } finally {
            setLoading(false);
        }
    };

    const getStatusIcon = (status) => {
        switch (status) {
            case 'healthy':
                return <CheckCircleOutlined style={{ color: '#52c41a', fontSize: 24 }} />;
            case 'degraded':
                return <WarningOutlined style={{ color: '#faad14', fontSize: 24 }} />;
            case 'critical':
            case 'unhealthy':
                return <CloseCircleOutlined style={{ color: '#f5222d', fontSize: 24 }} />;
            default:
                return null;
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'healthy':
                return '#52c41a';
            case 'degraded':
                return '#faad14';
            case 'critical':
            case 'unhealthy':
                return '#f5222d';
            default:
                return '#d9d9d9';
        }
    };

    const renderSystemOverview = () => {
        if (!systemHealth) return null;

        return (
            <Row gutter={[16, 16]}>
                <Col span={24}>
                    <Card>
                        <Row align="middle" justify="space-between">
                            <Col>
                                <Space align="center">
                                    {getStatusIcon(systemHealth.status)}
                                    <div>
                                        <h2 style={{ margin: 0 }}>System Status: {systemHealth.status.toUpperCase()}</h2>
                                        <p style={{ margin: 0, color: '#666' }}>
                                            Health Score: {systemHealth.health_score}/100
                                        </p>
                                    </div>
                                </Space>
                            </Col>
                            <Col>
                                <Space>
                                    <Button 
                                        icon={<ReloadOutlined />} 
                                        onClick={fetchAllData}
                                        loading={loading}
                                    >
                                        Refresh
                                    </Button>
                                    <Button 
                                        icon={<DownloadOutlined />}
                                        onClick={() => generateReport()}
                                    >
                                        Generate Report
                                    </Button>
                                </Space>
                            </Col>
                        </Row>
                    </Card>
                </Col>

                {Object.entries(systemHealth.components || {}).map(([name, component]) => (
                    <Col xs={24} sm={12} md={8} lg={6} key={name}>
                        <Card
                            title={
                                <Space>
                                    {getComponentIcon(name)}
                                    <span style={{ textTransform: 'capitalize' }}>{name.replace('_', ' ')}</span>
                                </Space>
                            }
                            extra={
                                <Tag color={getStatusColor(component.status)}>
                                    {component.status}
                                </Tag>
                            }
                        >
                            {component.response_time && (
                                <p>Response Time: {component.response_time}ms</p>
                            )}
                            {component.message && (
                                <p style={{ fontSize: 12, color: '#666' }}>{component.message}</p>
                            )}
                            {component.disk_usage && (
                                <Progress 
                                    percent={component.disk_usage.used_percent} 
                                    status={component.disk_usage.used_percent > 90 ? 'exception' : 'normal'}
                                    format={percent => `${percent}% used`}
                                />
                            )}
                        </Card>
                    </Col>
                ))}
            </Row>
        );
    };

    const renderPerformanceMetrics = () => {
        if (!performanceMetrics) return null;

        const cpuData = performanceMetrics.cpu?.history || [];
        const memoryData = performanceMetrics.memory?.history || [];

        const cpuConfig = {
            data: cpuData,
            xField: 'timestamp',
            yField: 'value',
            smooth: true,
            color: '#1890ff',
            areaStyle: { fillOpacity: 0.6 },
            xAxis: {
                type: 'time',
                tickCount: 5,
            },
            yAxis: {
                label: {
                    formatter: (v) => `${v}%`,
                },
            },
        };

        const memoryConfig = {
            data: memoryData,
            xField: 'timestamp',
            yField: 'value',
            smooth: true,
            color: '#52c41a',
            areaStyle: { fillOpacity: 0.6 },
            xAxis: {
                type: 'time',
                tickCount: 5,
            },
            yAxis: {
                label: {
                    formatter: (v) => `${v}%`,
                },
            },
        };

        return (
            <Row gutter={[16, 16]}>
                <Col xs={24} lg={12}>
                    <Card title="CPU Usage">
                        <Statistic
                            value={performanceMetrics.cpu?.current || 0}
                            suffix="%"
                            prefix={<ThunderboltOutlined />}
                            valueStyle={{ color: performanceMetrics.cpu?.current > 80 ? '#cf1322' : '#3f8600' }}
                        />
                        <Area {...cpuConfig} height={200} />
                    </Card>
                </Col>
                <Col xs={24} lg={12}>
                    <Card title="Memory Usage">
                        <Statistic
                            value={performanceMetrics.memory?.current || 0}
                            suffix="%"
                            prefix={<HddOutlined />}
                            valueStyle={{ color: performanceMetrics.memory?.current > 80 ? '#cf1322' : '#3f8600' }}
                        />
                        <Area {...memoryConfig} height={200} />
                    </Card>
                </Col>
                <Col xs={24} lg={8}>
                    <Card title="Database Performance">
                        <Statistic
                            title="Active Connections"
                            value={performanceMetrics.database?.connections || 0}
                            suffix={`/ ${performanceMetrics.database?.max_connections || 100}`}
                        />
                        <Progress 
                            percent={(performanceMetrics.database?.connections / performanceMetrics.database?.max_connections) * 100 || 0}
                            strokeColor={{
                                '0%': '#108ee9',
                                '100%': '#87d068',
                            }}
                        />
                        <p style={{ marginTop: 16, marginBottom: 0 }}>
                            Avg Query Time: {performanceMetrics.database?.avg_query_time || 0}ms
                        </p>
                    </Card>
                </Col>
                <Col xs={24} lg={8}>
                    <Card title="Cache Performance">
                        <Statistic
                            title="Hit Rate"
                            value={performanceMetrics.cache?.hit_rate || 0}
                            suffix="%"
                            valueStyle={{ color: performanceMetrics.cache?.hit_rate > 90 ? '#3f8600' : '#cf1322' }}
                        />
                        <Progress 
                            percent={performanceMetrics.cache?.hit_rate || 0}
                            status={performanceMetrics.cache?.hit_rate > 90 ? 'success' : 'exception'}
                        />
                        <p style={{ marginTop: 16, marginBottom: 0 }}>
                            Memory Used: {performanceMetrics.cache?.memory_used || 0}MB
                        </p>
                    </Card>
                </Col>
                <Col xs={24} lg={8}>
                    <Card title="Queue Status">
                        <Statistic
                            title="Jobs in Queue"
                            value={performanceMetrics.queue?.size || 0}
                            valueStyle={{ color: performanceMetrics.queue?.size > 1000 ? '#cf1322' : '#3f8600' }}
                        />
                        <Progress 
                            percent={Math.min((performanceMetrics.queue?.size / 1000) * 100, 100) || 0}
                            status={performanceMetrics.queue?.size > 1000 ? 'exception' : 'normal'}
                        />
                        <p style={{ marginTop: 16, marginBottom: 0 }}>
                            Processing: {performanceMetrics.queue?.processing || 0} jobs
                        </p>
                    </Card>
                </Col>
            </Row>
        );
    };

    const renderApiMonitoring = () => {
        if (!apiEndpoints) return null;

        const columns = [
            {
                title: 'Endpoint',
                dataIndex: 'endpoint',
                key: 'endpoint',
                render: (text) => <code>{text}</code>
            },
            {
                title: 'Status',
                dataIndex: 'status',
                key: 'status',
                render: (status) => (
                    <Tag color={getStatusColor(status)}>
                        {status}
                    </Tag>
                )
            },
            {
                title: 'Availability',
                dataIndex: 'availability',
                key: 'availability',
                render: (val) => `${val}%`
            },
            {
                title: 'Avg Response Time',
                dataIndex: ['response_times', 'avg'],
                key: 'avg_response',
                render: (val) => `${val}ms`
            },
            {
                title: 'P95 Response Time',
                dataIndex: ['response_times', 'p95'],
                key: 'p95_response',
                render: (val) => `${val}ms`
            },
            {
                title: 'Error Rate',
                dataIndex: ['errors', 'rate'],
                key: 'error_rate',
                render: (val) => (
                    <span style={{ color: val > 5 ? '#f5222d' : 'inherit' }}>
                        {val}%
                    </span>
                )
            }
        ];

        const dataSource = Object.entries(apiEndpoints.endpoints || {}).map(([endpoint, data]) => ({
            key: endpoint,
            endpoint,
            ...data
        }));

        return (
            <Card 
                title="API Endpoint Monitoring"
                extra={
                    <Badge 
                        status={apiEndpoints.overall?.status === 'healthy' ? 'success' : 'warning'} 
                        text={`Overall: ${apiEndpoints.overall?.status || 'Unknown'}`}
                    />
                }
            >
                <Table 
                    columns={columns} 
                    dataSource={dataSource}
                    pagination={false}
                    size="small"
                />
            </Card>
        );
    };

    const renderErrorLogs = () => {
        if (!errorLogs) return null;

        const severityColors = {
            'critical': '#f5222d',
            'error': '#ff7875',
            'warning': '#faad14',
            'info': '#1890ff',
            'debug': '#d9d9d9'
        };

        return (
            <Card title="Recent Errors">
                <Row gutter={[16, 16]}>
                    <Col span={24}>
                        <Space>
                            {Object.entries(errorLogs.grouped || {}).map(([severity, count]) => (
                                <Tag key={severity} color={severityColors[severity]}>
                                    {severity.toUpperCase()}: {count}
                                </Tag>
                            ))}
                        </Space>
                    </Col>
                    <Col span={24}>
                        {errorLogs.recent_errors?.map((error, index) => (
                            <Alert
                                key={error.id || index}
                                message={
                                    <Space>
                                        <Tag color={severityColors[error.severity]}>{error.severity}</Tag>
                                        <span>{error.message}</span>
                                    </Space>
                                }
                                description={
                                    <div>
                                        <p><strong>File:</strong> {error.file}:{error.line}</p>
                                        <p><strong>Time:</strong> {moment(error.created_at).format('YYYY-MM-DD HH:mm:ss')}</p>
                                        {error.user_id && <p><strong>User ID:</strong> {error.user_id}</p>}
                                    </div>
                                }
                                type={error.severity === 'critical' ? 'error' : error.severity}
                                showIcon
                                style={{ marginBottom: 16 }}
                            />
                        ))}
                    </Col>
                </Row>
            </Card>
        );
    };

    const getComponentIcon = (name) => {
        const icons = {
            'database': <DatabaseOutlined />,
            'cache': <CloudServerOutlined />,
            'queue': <ApiOutlined />,
            'filesystem': <HddOutlined />,
            'resources': <ThunderboltOutlined />,
            'external_services': <ApiOutlined />
        };
        return icons[name] || <SettingOutlined />;
    };

    const generateReport = async () => {
        try {
            const response = await axios.post('/api/monitoring/report', {
                report_type: 'detailed',
                include_recommendations: true,
                format: 'pdf'
            });
            
            window.open(response.data.url, '_blank');
        } catch (error) {
            console.error('Failed to generate report:', error);
        }
    };

    if (loading && !systemHealth) {
        return (
            <div style={{ textAlign: 'center', padding: 100 }}>
                <Spin size="large" tip="Loading system health data..." />
            </div>
        );
    }

    return (
        <div style={{ padding: 24 }}>
            <Tabs activeKey={activeTab} onChange={setActiveTab}>
                <TabPane tab="Overview" key="overview">
                    {renderSystemOverview()}
                </TabPane>
                <TabPane tab="Performance" key="performance">
                    {renderPerformanceMetrics()}
                </TabPane>
                <TabPane tab="API Monitoring" key="api">
                    {renderApiMonitoring()}
                </TabPane>
                <TabPane tab="Error Logs" key="errors">
                    {renderErrorLogs()}
                </TabPane>
            </Tabs>
        </div>
    );
};

export default SystemHealthMonitoring;