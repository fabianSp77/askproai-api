import React, { useState, useEffect } from 'react';
import { Card, Row, Col, Statistic, DatePicker, Select, Button, Spin, Alert, Tabs, Progress } from 'antd';
import {
    LineChart, Line, AreaChart, Area, BarChart, Bar, PieChart, Pie,
    XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, Cell
} from 'recharts';
import {
    RiseOutlined, FallOutlined, DollarOutlined,
    CalendarOutlined, PhoneOutlined, UserOutlined, SyncOutlined,
    WarningOutlined, CheckCircleOutlined, ExportOutlined
} from '@ant-design/icons';
import moment from 'moment';
import axios from 'axios';

const { RangePicker } = DatePicker;
const { Option } = Select;
const { TabPane } = Tabs;

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

const AnalyticsDashboard = () => {
    const [loading, setLoading] = useState(true);
    const [dateRange, setDateRange] = useState([moment().subtract(30, 'days'), moment()]);
    const [branchId, setBranchId] = useState(null);
    const [metrics, setMetrics] = useState({});
    const [predictions, setPredictions] = useState({});
    const [insights, setInsights] = useState({});
    const [anomalies, setAnomalies] = useState([]);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        fetchAnalytics();
    }, [dateRange, branchId]);

    const fetchAnalytics = async () => {
        setLoading(true);
        try {
            const [metricsRes, predictionsRes, insightsRes, anomaliesRes] = await Promise.all([
                // Business metrics
                axios.get('/api/analytics/metrics', {
                    params: {
                        date_from: dateRange[0].format('YYYY-MM-DD'),
                        date_to: dateRange[1].format('YYYY-MM-DD'),
                        branch_id: branchId,
                        comparison_period: 'previous_period'
                    }
                }),
                // Revenue predictions
                axios.get('/api/analytics/predict-revenue', {
                    params: {
                        prediction_days: 30,
                        branch_id: branchId
                    }
                }),
                // Performance insights
                axios.get('/api/analytics/insights', {
                    params: {
                        branch_id: branchId,
                        include_recommendations: true
                    }
                }),
                // Anomaly detection
                axios.get('/api/analytics/anomalies', {
                    params: {
                        metric_type: 'revenue',
                        sensitivity: 'medium',
                        lookback_days: 30
                    }
                })
            ]);

            setMetrics(metricsRes.data);
            setPredictions(predictionsRes.data);
            setInsights(insightsRes.data);
            setAnomalies(anomaliesRes.data.anomalies || []);
        } catch (error) {
            console.error('Failed to fetch analytics:', error);
        } finally {
            setLoading(false);
        }
    };

    const renderMetricCard = (title, value, prefix, comparison, icon) => {
        const trend = comparison?.change_percent || 0;
        const isPositive = trend >= 0;

        return (
            <Card>
                <Statistic
                    title={title}
                    value={value}
                    prefix={prefix}
                    suffix={
                        trend !== 0 && (
                            <span style={{ fontSize: 14, color: isPositive ? '#3f8600' : '#cf1322' }}>
                                {isPositive ? <RiseOutlined /> : <FallOutlined />}
                                {Math.abs(trend)}%
                            </span>
                        )
                    }
                    valueStyle={{ color: '#1890ff' }}
                />
                <div style={{ marginTop: 8, fontSize: 12, color: '#666' }}>
                    vs. previous period: {comparison?.previous_value || 0}
                </div>
            </Card>
        );
    };

    const renderRevenueChart = () => {
        const data = predictions.predictions || [];
        
        return (
            <Card title="Revenue Forecast" extra={
                <span style={{ fontSize: 12, color: '#666' }}>
                    Confidence: {(predictions.summary?.confidence_level || 0.9) * 100}%
                </span>
            }>
                <ResponsiveContainer width="100%" height={300}>
                    <AreaChart data={data}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis dataKey="date" />
                        <YAxis />
                        <Tooltip formatter={(value) => `€${value}`} />
                        <Area 
                            type="monotone" 
                            dataKey="upper_bound" 
                            stroke="#8884d8" 
                            fill="#8884d8" 
                            fillOpacity={0.2}
                            name="Upper Bound"
                        />
                        <Area 
                            type="monotone" 
                            dataKey="predicted_value" 
                            stroke="#82ca9d" 
                            fill="#82ca9d" 
                            fillOpacity={0.6}
                            name="Predicted"
                        />
                        <Area 
                            type="monotone" 
                            dataKey="lower_bound" 
                            stroke="#ffc658" 
                            fill="#ffc658" 
                            fillOpacity={0.2}
                            name="Lower Bound"
                        />
                    </AreaChart>
                </ResponsiveContainer>
                <div style={{ marginTop: 16, textAlign: 'center' }}>
                    <Statistic 
                        title="Expected Revenue (30 days)" 
                        value={predictions.summary?.total_predicted || 0}
                        prefix="€"
                        valueStyle={{ color: '#3f8600' }}
                    />
                </div>
            </Card>
        );
    };

    const renderPerformanceInsights = () => {
        const { insights: performanceData, recommendations, quick_wins } = insights;
        
        return (
            <Card title="Performance Insights">
                <Tabs>
                    <TabPane tab="Key Metrics" key="metrics">
                        {performanceData?.staff_productivity && (
                            <div style={{ marginBottom: 24 }}>
                                <h4>Staff Productivity</h4>
                                <Progress 
                                    percent={performanceData.staff_productivity.efficiency_score || 0} 
                                    status={performanceData.staff_productivity.efficiency_score > 80 ? 'success' : 'normal'}
                                />
                                <p style={{ fontSize: 12, color: '#666', marginTop: 8 }}>
                                    Average appointments per staff: {performanceData.staff_productivity.avg_appointments_per_staff || 0}
                                </p>
                            </div>
                        )}
                        
                        {performanceData?.revenue_optimization && (
                            <div>
                                <h4>Revenue Optimization</h4>
                                <Row gutter={16}>
                                    <Col span={12}>
                                        <Statistic 
                                            title="Avg. Transaction Value" 
                                            value={performanceData.revenue_optimization.avg_transaction_value || 0}
                                            prefix="€"
                                        />
                                    </Col>
                                    <Col span={12}>
                                        <Statistic 
                                            title="Utilization Rate" 
                                            value={performanceData.revenue_optimization.utilization_rate || 0}
                                            suffix="%"
                                        />
                                    </Col>
                                </Row>
                            </div>
                        )}
                    </TabPane>
                    
                    <TabPane tab="Recommendations" key="recommendations">
                        {recommendations?.map((rec, index) => (
                            <Alert
                                key={index}
                                message={rec.title}
                                description={rec.description}
                                type={rec.priority === 'high' ? 'warning' : 'info'}
                                showIcon
                                style={{ marginBottom: 16 }}
                                action={
                                    rec.action && (
                                        <Button size="small" type="primary">
                                            {rec.action}
                                        </Button>
                                    )
                                }
                            />
                        ))}
                    </TabPane>
                    
                    <TabPane tab="Quick Wins" key="quick_wins">
                        {quick_wins?.map((win, index) => (
                            <Alert
                                key={index}
                                message={win.title}
                                description={
                                    <div>
                                        <p>{win.description}</p>
                                        <p><strong>Expected Impact:</strong> {win.expected_impact}</p>
                                    </div>
                                }
                                type="success"
                                showIcon
                                icon={<CheckCircleOutlined />}
                                style={{ marginBottom: 16 }}
                            />
                        ))}
                    </TabPane>
                </Tabs>
            </Card>
        );
    };

    const renderAnomalies = () => {
        if (!anomalies.length) {
            return (
                <Card title="Anomaly Detection">
                    <Alert
                        message="No anomalies detected"
                        description="All metrics are within expected ranges."
                        type="success"
                        showIcon
                    />
                </Card>
            );
        }

        return (
            <Card title="Anomaly Detection" extra={
                <span style={{ color: '#ff4d4f' }}>
                    <WarningOutlined /> {anomalies.length} anomalies detected
                </span>
            }>
                {anomalies.slice(0, 5).map((anomaly, index) => (
                    <Alert
                        key={index}
                        message={`${anomaly.type === 'spike' ? 'Unusual spike' : 'Unusual drop'} on ${moment(anomaly.date).format('MMM DD')}`}
                        description={
                            <div>
                                <p>Value: €{anomaly.value} (Expected: €{anomaly.expected_range.min} - €{anomaly.expected_range.max})</p>
                                <p>Possible causes: {anomaly.possible_causes?.join(', ')}</p>
                            </div>
                        }
                        type={anomaly.severity === 'critical' ? 'error' : 'warning'}
                        showIcon
                        style={{ marginBottom: 16 }}
                    />
                ))}
            </Card>
        );
    };

    const exportData = async (format) => {
        try {
            const response = await axios.post('/api/analytics/export', {
                export_type: 'processed_analytics',
                date_from: dateRange[0].format('YYYY-MM-DD'),
                date_to: dateRange[1].format('YYYY-MM-DD'),
                format: format
            });
            
            window.open(response.data.export_url, '_blank');
        } catch (error) {
            console.error('Export failed:', error);
        }
    };

    if (loading) {
        return (
            <div style={{ textAlign: 'center', padding: 100 }}>
                <Spin size="large" tip="Loading analytics..." />
            </div>
        );
    }

    return (
        <div style={{ padding: 24 }}>
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col xs={24} sm={12} md={8}>
                    <RangePicker
                        value={dateRange}
                        onChange={setDateRange}
                        style={{ width: '100%' }}
                    />
                </Col>
                <Col xs={24} sm={12} md={8}>
                    <Select
                        placeholder="All branches"
                        value={branchId}
                        onChange={setBranchId}
                        style={{ width: '100%' }}
                        allowClear
                    >
                        {/* Branch options would be loaded here */}
                    </Select>
                </Col>
                <Col xs={24} sm={12} md={8}>
                    <Button icon={<SyncOutlined />} onClick={fetchAnalytics} style={{ marginRight: 8 }}>
                        Refresh
                    </Button>
                    <Button.Group>
                        <Button icon={<ExportOutlined />} onClick={() => exportData('excel')}>
                            Excel
                        </Button>
                        <Button icon={<ExportOutlined />} onClick={() => exportData('pdf')}>
                            PDF
                        </Button>
                    </Button.Group>
                </Col>
            </Row>

            <Tabs activeKey={activeTab} onChange={setActiveTab}>
                <TabPane tab="Overview" key="overview">
                    <Row gutter={[16, 16]}>
                        <Col xs={24} sm={12} md={6}>
                            {renderMetricCard(
                                'Revenue',
                                metrics.metrics?.revenue?.total || 0,
                                <DollarOutlined />,
                                metrics.comparison?.revenue,
                                'euro'
                            )}
                        </Col>
                        <Col xs={24} sm={12} md={6}>
                            {renderMetricCard(
                                'Appointments',
                                metrics.metrics?.appointments?.total || 0,
                                <CalendarOutlined />,
                                metrics.comparison?.appointments,
                                'calendar'
                            )}
                        </Col>
                        <Col xs={24} sm={12} md={6}>
                            {renderMetricCard(
                                'Calls',
                                metrics.metrics?.calls?.total || 0,
                                <PhoneOutlined />,
                                metrics.comparison?.calls,
                                'phone'
                            )}
                        </Col>
                        <Col xs={24} sm={12} md={6}>
                            {renderMetricCard(
                                'New Customers',
                                metrics.metrics?.customers?.new_count || 0,
                                <UserOutlined />,
                                metrics.comparison?.customers,
                                'user'
                            )}
                        </Col>
                    </Row>

                    <Row gutter={[16, 16]} style={{ marginTop: 24 }}>
                        <Col xs={24} lg={12}>
                            {renderRevenueChart()}
                        </Col>
                        <Col xs={24} lg={12}>
                            {renderAnomalies()}
                        </Col>
                    </Row>
                </TabPane>

                <TabPane tab="Insights" key="insights">
                    {renderPerformanceInsights()}
                </TabPane>

                <TabPane tab="Predictions" key="predictions">
                    <Row gutter={[16, 16]}>
                        <Col xs={24}>
                            {renderRevenueChart()}
                        </Col>
                    </Row>
                </TabPane>
            </Tabs>
        </div>
    );
};

export default AnalyticsDashboard;