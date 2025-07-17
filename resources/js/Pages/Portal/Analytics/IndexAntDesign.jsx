import React, { useState, useEffect } from 'react';
import { 
    Card, 
    Row, 
    Col, 
    Typography, 
    Select, 
    DatePicker, 
    Space,
    Statistic,
    Progress,
    Table,
    Tag,
    Button,
    Empty,
    Spin,
    Segmented,
    Tooltip
} from 'antd';
import { 
    LineChart, 
    Line, 
    BarChart, 
    Bar,
    PieChart, 
    Pie,
    AreaChart,
    Area,
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip as RechartsTooltip, 
    Legend,
    ResponsiveContainer,
    Cell
} from 'recharts';
import { 
    BarChartOutlined, 
    PhoneOutlined,
    CalendarOutlined,
    UserOutlined,
    ClockCircleOutlined,
    RiseOutlined,
    FallOutlined,
    DollarOutlined,
    TeamOutlined,
    TrophyOutlined,
    DownloadOutlined,
    ReloadOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import axiosInstance from '../../../services/axiosInstance';

dayjs.locale('de');

const { Title, Text } = Typography;
const { RangePicker } = DatePicker;
const { Option } = Select;

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

const AnalyticsIndex = () => {
    const [loading, setLoading] = useState(true);
    const [timeRange, setTimeRange] = useState('week');
    const [dateRange, setDateRange] = useState([
        dayjs().subtract(7, 'days'),
        dayjs()
    ]);
    const [branch, setBranch] = useState('all');
    const [branches, setBranches] = useState([]);
    const [viewType, setViewType] = useState('overview');
    
    // Analytics data
    const [overview, setOverview] = useState({
        calls: { total: 0, trend: 0 },
        appointments: { total: 0, trend: 0 },
        customers: { total: 0, trend: 0 },
        revenue: { total: 0, trend: 0 }
    });
    const [callsData, setCallsData] = useState([]);
    const [appointmentsData, setAppointmentsData] = useState([]);
    const [servicesData, setServicesData] = useState([]);
    const [staffPerformance, setStaffPerformance] = useState([]);
    const [peakHours, setPeakHours] = useState([]);
    const [conversionData, setConversionData] = useState({
        rate: 0,
        funnel: []
    });

    useEffect(() => {
        fetchAnalytics();
        fetchBranches();
    }, [timeRange, dateRange, branch]);

    const fetchAnalytics = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            params.append('start_date', dateRange[0].format('YYYY-MM-DD'));
            params.append('end_date', dateRange[1].format('YYYY-MM-DD'));
            if (branch !== 'all') {
                params.append('branch_id', branch);
            }

            const response = await axiosInstance.get(`/analytics?`);

            if (!response.data) throw new Error('Failed to fetch analytics');

            const data = await response.data;
            
            setOverview(data.overview || {});
            setCallsData(data.calls_trend || []);
            setAppointmentsData(data.appointments_trend || []);
            setServicesData(data.services || []);
            setStaffPerformance(data.staff_performance || []);
            setPeakHours(data.peak_hours || []);
            setConversionData(data.conversion || { rate: 0, funnel: [] });
        } catch (error) {
            // Silently handle error - will show empty state
        } finally {
            setLoading(false);
        }
    };

    const fetchBranches = async () => {
        try {
            const response = await axiosInstance.get('/analytics/filters');

            if (!response.data) throw new Error('Failed to fetch branches');

            const data = await response.data;
            setBranches(data.branches || []);
        } catch (error) {
            // Silently handle branches error
        }
    };

    const handleTimeRangeChange = (value) => {
        setTimeRange(value);
        let start, end = dayjs();
        
        switch (value) {
            case 'today':
                start = dayjs().startOf('day');
                break;
            case 'week':
                start = dayjs().subtract(7, 'days');
                break;
            case 'month':
                start = dayjs().subtract(30, 'days');
                break;
            case 'quarter':
                start = dayjs().subtract(90, 'days');
                break;
            case 'year':
                start = dayjs().subtract(365, 'days');
                break;
            default:
                return;
        }
        
        setDateRange([start, end]);
    };

    const exportAnalytics = async (format) => {
        try {
            const params = new URLSearchParams();
            params.append('start_date', dateRange[0].format('YYYY-MM-DD'));
            params.append('end_date', dateRange[1].format('YYYY-MM-DD'));
            params.append('format', format);
            if (branch !== 'all') {
                params.append('branch_id', branch);
            }

            const response = await axiosInstance.get(`/analytics/export?`);

            if (!response.data) throw new Error('Export failed');

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `analytics_${dayjs().format('YYYY-MM-DD')}.${format}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Error exporting analytics:', error);
        }
    };

    const staffColumns = [
        {
            title: 'Mitarbeiter',
            dataIndex: 'name',
            key: 'name',
            render: (name, record) => (
                <Space>
                    <UserOutlined />
                    <Text strong>{name}</Text>
                </Space>
            )
        },
        {
            title: 'Anrufe',
            dataIndex: 'calls_handled',
            key: 'calls_handled',
            sorter: (a, b) => a.calls_handled - b.calls_handled,
        },
        {
            title: 'Termine',
            dataIndex: 'appointments_completed',
            key: 'appointments_completed',
            sorter: (a, b) => a.appointments_completed - b.appointments_completed,
        },
        {
            title: 'Konversionsrate',
            dataIndex: 'conversion_rate',
            key: 'conversion_rate',
            render: (rate) => (
                <Progress 
                    percent={rate} 
                    size="small" 
                    format={(percent) => `${percent}%`}
                />
            ),
            sorter: (a, b) => a.conversion_rate - b.conversion_rate,
        },
        {
            title: 'Ø Anrufdauer',
            dataIndex: 'avg_call_duration',
            key: 'avg_call_duration',
            render: (duration) => `${Math.round(duration)}s`,
        },
        {
            title: 'Kundenzufriedenheit',
            dataIndex: 'satisfaction_score',
            key: 'satisfaction_score',
            render: (score) => (
                <Rate disabled defaultValue={score} style={{ fontSize: 14 }} />
            ),
        }
    ];

    const Rate = ({ disabled, defaultValue, style }) => {
        return (
            <Space style={style}>
                {[1, 2, 3, 4, 5].map(star => (
                    <span 
                        key={star} 
                        style={{ 
                            color: star <= defaultValue ? '#faad14' : '#d9d9d9',
                            fontSize: style?.fontSize || 16
                        }}
                    >
                        ★
                    </span>
                ))}
            </Space>
        );
    };

    return (
        <div style={{ padding: 24 }}>
            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col span={24}>
                    <Row justify="space-between" align="middle">
                        <Col>
                            <Title level={2}>
                                <BarChartOutlined /> Analytics
                            </Title>
                        </Col>
                        <Col>
                            <Space>
                                <Button
                                    icon={<ReloadOutlined />}
                                    onClick={fetchAnalytics}
                                    loading={loading}
                                >
                                    Aktualisieren
                                </Button>
                                <Button
                                    icon={<DownloadOutlined />}
                                    onClick={() => exportAnalytics('csv')}
                                >
                                    CSV Export
                                </Button>
                                <Button
                                    type="primary"
                                    icon={<DownloadOutlined />}
                                    onClick={() => exportAnalytics('pdf')}
                                >
                                    PDF Report
                                </Button>
                            </Space>
                        </Col>
                    </Row>
                </Col>
            </Row>

            {/* Filters */}
            <Card style={{ marginBottom: 16 }}>
                <Row gutter={[16, 16]} align="middle">
                    <Col xs={24} sm={12} md={6}>
                        <Select
                            style={{ width: '100%' }}
                            value={timeRange}
                            onChange={handleTimeRangeChange}
                        >
                            <Option value="today">Heute</Option>
                            <Option value="week">Letzte 7 Tage</Option>
                            <Option value="month">Letzte 30 Tage</Option>
                            <Option value="quarter">Letztes Quartal</Option>
                            <Option value="year">Letztes Jahr</Option>
                            <Option value="custom">Benutzerdefiniert</Option>
                        </Select>
                    </Col>
                    <Col xs={24} sm={12} md={8}>
                        <RangePicker
                            style={{ width: '100%' }}
                            value={dateRange}
                            onChange={setDateRange}
                            format="DD.MM.YYYY"
                            disabled={timeRange !== 'custom'}
                        />
                    </Col>
                    <Col xs={24} sm={12} md={6}>
                        <Select
                            style={{ width: '100%' }}
                            value={branch}
                            onChange={setBranch}
                            placeholder="Filiale"
                        >
                            <Option value="all">Alle Filialen</Option>
                            {branches.map(b => (
                                <Option key={b.id} value={b.id}>{b.name}</Option>
                            ))}
                        </Select>
                    </Col>
                    <Col xs={24} sm={12} md={4}>
                        <Segmented
                            options={[
                                { label: 'Überblick', value: 'overview' },
                                { label: 'Details', value: 'details' }
                            ]}
                            value={viewType}
                            onChange={setViewType}
                            block
                        />
                    </Col>
                </Row>
            </Card>

            {loading ? (
                <Card style={{ textAlign: 'center', padding: 100 }}>
                    <Spin size="large" />
                </Card>
            ) : viewType === 'overview' ? (
                <>
                    {/* KPI Cards */}
                    <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                        <Col xs={24} sm={12} md={6}>
                            <Card>
                                <Statistic
                                    title="Anrufe"
                                    value={overview.calls?.total || 0}
                                    prefix={<PhoneOutlined />}
                                    suffix={
                                        <span style={{ 
                                            fontSize: 14, 
                                            color: overview.calls?.trend > 0 ? '#52c41a' : '#ff4d4f' 
                                        }}>
                                            {overview.calls?.trend > 0 ? <RiseOutlined /> : <FallOutlined />}
                                            {Math.abs(overview.calls?.trend || 0)}%
                                        </span>
                                    }
                                />
                            </Card>
                        </Col>
                        <Col xs={24} sm={12} md={6}>
                            <Card>
                                <Statistic
                                    title="Termine"
                                    value={overview.appointments?.total || 0}
                                    prefix={<CalendarOutlined />}
                                    suffix={
                                        <span style={{ 
                                            fontSize: 14, 
                                            color: overview.appointments?.trend > 0 ? '#52c41a' : '#ff4d4f' 
                                        }}>
                                            {overview.appointments?.trend > 0 ? <RiseOutlined /> : <FallOutlined />}
                                            {Math.abs(overview.appointments?.trend || 0)}%
                                        </span>
                                    }
                                />
                            </Card>
                        </Col>
                        <Col xs={24} sm={12} md={6}>
                            <Card>
                                <Statistic
                                    title="Neue Kunden"
                                    value={overview.customers?.total || 0}
                                    prefix={<UserOutlined />}
                                    suffix={
                                        <span style={{ 
                                            fontSize: 14, 
                                            color: overview.customers?.trend > 0 ? '#52c41a' : '#ff4d4f' 
                                        }}>
                                            {overview.customers?.trend > 0 ? <RiseOutlined /> : <FallOutlined />}
                                            {Math.abs(overview.customers?.trend || 0)}%
                                        </span>
                                    }
                                />
                            </Card>
                        </Col>
                        <Col xs={24} sm={12} md={6}>
                            <Card>
                                <Statistic
                                    title="Umsatz"
                                    value={overview.revenue?.total || 0}
                                    prefix="€"
                                    precision={2}
                                    suffix={
                                        <span style={{ 
                                            fontSize: 14, 
                                            color: overview.revenue?.trend > 0 ? '#52c41a' : '#ff4d4f' 
                                        }}>
                                            {overview.revenue?.trend > 0 ? <RiseOutlined /> : <FallOutlined />}
                                            {Math.abs(overview.revenue?.trend || 0)}%
                                        </span>
                                    }
                                />
                            </Card>
                        </Col>
                    </Row>

                    {/* Charts Row 1 */}
                    <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                        <Col xs={24} lg={12}>
                            <Card title="Anruf-Trend">
                                <ResponsiveContainer width="100%" height={300}>
                                    <AreaChart data={callsData}>
                                        <defs>
                                            <linearGradient id="colorCalls" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#8884d8" stopOpacity={0.8}/>
                                                <stop offset="95%" stopColor="#8884d8" stopOpacity={0}/>
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="date" />
                                        <YAxis />
                                        <RechartsTooltip />
                                        <Area 
                                            type="monotone" 
                                            dataKey="count" 
                                            stroke="#8884d8" 
                                            fillOpacity={1} 
                                            fill="url(#colorCalls)" 
                                        />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </Card>
                        </Col>
                        <Col xs={24} lg={12}>
                            <Card title="Termin-Trend">
                                <ResponsiveContainer width="100%" height={300}>
                                    <LineChart data={appointmentsData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="date" />
                                        <YAxis />
                                        <RechartsTooltip />
                                        <Legend />
                                        <Line 
                                            type="monotone" 
                                            dataKey="scheduled" 
                                            stroke="#8884d8" 
                                            name="Geplant"
                                        />
                                        <Line 
                                            type="monotone" 
                                            dataKey="completed" 
                                            stroke="#82ca9d" 
                                            name="Abgeschlossen"
                                        />
                                        <Line 
                                            type="monotone" 
                                            dataKey="cancelled" 
                                            stroke="#ff8042" 
                                            name="Storniert"
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </Card>
                        </Col>
                    </Row>

                    {/* Charts Row 2 */}
                    <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
                        <Col xs={24} lg={8}>
                            <Card title="Top Services">
                                <ResponsiveContainer width="100%" height={300}>
                                    <PieChart>
                                        <Pie
                                            data={servicesData}
                                            cx="50%"
                                            cy="50%"
                                            labelLine={false}
                                            label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                            outerRadius={80}
                                            fill="#8884d8"
                                            dataKey="count"
                                        >
                                            {servicesData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <RechartsTooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                            </Card>
                        </Col>
                        <Col xs={24} lg={8}>
                            <Card title="Konversionstrichter">
                                <div style={{ padding: '20px 0' }}>
                                    {conversionData.funnel.map((step, index) => (
                                        <div key={index} style={{ marginBottom: 16 }}>
                                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 4 }}>
                                                <Text>{step.name}</Text>
                                                <Text strong>{step.count}</Text>
                                            </div>
                                            <Progress 
                                                percent={step.percentage} 
                                                showInfo={false}
                                                strokeColor={{
                                                    '0%': '#108ee9',
                                                    '100%': '#87d068',
                                                }}
                                            />
                                        </div>
                                    ))}
                                    <div style={{ textAlign: 'center', marginTop: 24 }}>
                                        <Statistic 
                                            title="Gesamt-Konversionsrate" 
                                            value={conversionData.rate} 
                                            suffix="%" 
                                            valueStyle={{ color: '#3f8600' }}
                                        />
                                    </div>
                                </div>
                            </Card>
                        </Col>
                        <Col xs={24} lg={8}>
                            <Card title="Stoßzeiten">
                                <ResponsiveContainer width="100%" height={300}>
                                    <BarChart data={peakHours}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="hour" />
                                        <YAxis />
                                        <RechartsTooltip />
                                        <Bar dataKey="calls" fill="#8884d8" name="Anrufe" />
                                    </BarChart>
                                </ResponsiveContainer>
                            </Card>
                        </Col>
                    </Row>
                </>
            ) : (
                <>
                    {/* Detailed View */}
                    <Row gutter={[16, 16]}>
                        <Col span={24}>
                            <Card title="Mitarbeiter-Performance">
                                <Table
                                    columns={staffColumns}
                                    dataSource={staffPerformance}
                                    rowKey="id"
                                    pagination={false}
                                    scroll={{ x: 800 }}
                                />
                            </Card>
                        </Col>
                    </Row>

                    <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                        <Col xs={24} lg={12}>
                            <Card title="Service-Auslastung">
                                <ResponsiveContainer width="100%" height={400}>
                                    <BarChart 
                                        data={servicesData}
                                        layout="vertical"
                                        margin={{ left: 100 }}
                                    >
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis type="number" />
                                        <YAxis dataKey="name" type="category" />
                                        <RechartsTooltip />
                                        <Bar dataKey="count" fill="#8884d8">
                                            {servicesData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </Card>
                        </Col>
                        <Col xs={24} lg={12}>
                            <Card title="Wochentags-Verteilung">
                                <ResponsiveContainer width="100%" height={400}>
                                    <BarChart data={[
                                        { day: 'Mo', calls: 120, appointments: 45 },
                                        { day: 'Di', calls: 150, appointments: 52 },
                                        { day: 'Mi', calls: 180, appointments: 68 },
                                        { day: 'Do', calls: 160, appointments: 55 },
                                        { day: 'Fr', calls: 200, appointments: 72 },
                                        { day: 'Sa', calls: 80, appointments: 30 },
                                        { day: 'So', calls: 20, appointments: 5 },
                                    ]}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="day" />
                                        <YAxis />
                                        <RechartsTooltip />
                                        <Legend />
                                        <Bar dataKey="calls" fill="#8884d8" name="Anrufe" />
                                        <Bar dataKey="appointments" fill="#82ca9d" name="Termine" />
                                    </BarChart>
                                </ResponsiveContainer>
                            </Card>
                        </Col>
                    </Row>
                </>
            )}
        </div>
    );
};

export default AnalyticsIndex;