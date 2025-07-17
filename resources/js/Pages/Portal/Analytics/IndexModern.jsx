import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Badge } from '../../../components/ui/badge';
import { Progress } from '../../../components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../../../components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '../../../components/ui/table';
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
    BarChart3, 
    Phone,
    Calendar,
    Users,
    Clock,
    TrendingUp,
    TrendingDown,
    DollarSign,
    Trophy,
    Download,
    RefreshCw,
    Building,
    Loader2,
    ArrowUpRight,
    ArrowDownRight,
    Activity,
    Target,
    Award,
    PhoneCall
} from 'lucide-react';
import { useAuth } from '../../../hooks/useAuth';
import { cn } from '../../../lib/utils';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import GoalConfiguration from '../../../components/Portal/Goals/GoalConfiguration';
import GoalDashboard from '../../../components/Portal/Goals/GoalDashboard';
import GoalAnalytics from '../../../components/Portal/Goals/GoalAnalytics';
import axiosInstance from '../../../services/axiosInstance';

dayjs.locale('de');

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

const AnalyticsIndex = () => {
    const { } = useAuth();
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
            
            setOverview(data.overview || {
                calls: { total: 0, trend: 0 },
                appointments: { total: 0, trend: 0 },
                customers: { total: 0, trend: 0 },
                revenue: { total: 0, trend: 0 }
            });
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
            window.URL.revokeObjectURL(url);
        } catch (error) {
            // Show user-friendly error message could be added here
        }
    };

    const getStatCard = (title, value, trend, icon, color) => {
        const Icon = icon;
        const isPositive = trend >= 0;
        const TrendIcon = isPositive ? TrendingUp : TrendingDown;
        
        return (
            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">{title}</CardTitle>
                    <Icon className={cn("h-4 w-4", color)} />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">{value.toLocaleString('de-DE')}</div>
                    <p className={cn(
                        "text-xs flex items-center mt-1",
                        isPositive ? "text-green-600" : "text-red-600"
                    )}>
                        <TrendIcon className="h-3 w-3 mr-1" />
                        {Math.abs(trend)}% gegenüber Vorperiode
                    </p>
                </CardContent>
            </Card>
        );
    };

    return (
        <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Analytics</h1>
                        <p className="text-muted-foreground">Analysieren Sie Ihre Geschäftsdaten und Leistung</p>
                    </div>
                    <div className="flex gap-2">
                        <Button 
                            variant="outline" 
                            size="icon"
                            onClick={fetchAnalytics}
                        >
                            <RefreshCw className="h-4 w-4" />
                        </Button>
                        <Button 
                            variant="outline"
                            onClick={() => exportAnalytics('csv')}
                        >
                            <Download className="h-4 w-4 mr-2" />
                            CSV
                        </Button>
                        <Button 
                            variant="outline"
                            onClick={() => exportAnalytics('pdf')}
                        >
                            <Download className="h-4 w-4 mr-2" />
                            PDF
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <Select value={timeRange} onValueChange={handleTimeRangeChange}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Zeitraum wählen" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="today">Heute</SelectItem>
                                    <SelectItem value="week">Letzte 7 Tage</SelectItem>
                                    <SelectItem value="month">Letzte 30 Tage</SelectItem>
                                    <SelectItem value="quarter">Letztes Quartal</SelectItem>
                                    <SelectItem value="year">Letztes Jahr</SelectItem>
                                </SelectContent>
                            </Select>
                            
                            <Select value={branch} onValueChange={setBranch}>
                                <SelectTrigger className="w-[200px]">
                                    <SelectValue placeholder="Filiale wählen" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Alle Filialen</SelectItem>
                                    {branches.map((b) => (
                                        <SelectItem key={b.id} value={b.id}>
                                            {b.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardHeader>
                </Card>

                {loading ? (
                    <div className="flex items-center justify-center p-8">
                        <Loader2 className="h-8 w-8 animate-spin" />
                    </div>
                ) : (
                    <Tabs defaultValue="overview" className="space-y-4">
                        <TabsList>
                            <TabsTrigger value="overview">Übersicht</TabsTrigger>
                            <TabsTrigger value="calls">Anrufe</TabsTrigger>
                            <TabsTrigger value="appointments">Termine</TabsTrigger>
                            <TabsTrigger value="performance">Leistung</TabsTrigger>
                            <TabsTrigger value="conversion">Konversion</TabsTrigger>
                            <TabsTrigger value="goals">Ziele</TabsTrigger>
                        </TabsList>

                        <TabsContent value="overview" className="space-y-4">
                            {/* Stats Grid */}
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                {getStatCard(
                                    "Anrufe",
                                    overview.calls?.total || 0,
                                    overview.calls?.trend || 0,
                                    Phone,
                                    "text-blue-600"
                                )}
                                {getStatCard(
                                    "Termine",
                                    overview.appointments?.total || 0,
                                    overview.appointments?.trend || 0,
                                    Calendar,
                                    "text-green-600"
                                )}
                                {getStatCard(
                                    "Kunden",
                                    overview.customers?.total || 0,
                                    overview.customers?.trend || 0,
                                    Users,
                                    "text-purple-600"
                                )}
                                {getStatCard(
                                    "Umsatz",
                                    overview.revenue?.total || 0,
                                    overview.revenue?.trend || 0,
                                    DollarSign,
                                    "text-yellow-600"
                                )}
                            </div>

                            {/* Trend Charts */}
                            <div className="grid gap-4 md:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Anruf-Trend</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <LineChart data={callsData}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="date" />
                                                <YAxis />
                                                <RechartsTooltip />
                                                <Line 
                                                    type="monotone" 
                                                    dataKey="count" 
                                                    stroke="#3B82F6" 
                                                    strokeWidth={2} 
                                                />
                                            </LineChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Termin-Trend</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <AreaChart data={appointmentsData}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="date" />
                                                <YAxis />
                                                <RechartsTooltip />
                                                <Area 
                                                    type="monotone" 
                                                    dataKey="count" 
                                                    stroke="#10B981" 
                                                    fill="#10B981" 
                                                    fillOpacity={0.3}
                                                />
                                            </AreaChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Services Distribution */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Beliebteste Services</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <PieChart>
                                            <Pie
                                                data={servicesData}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                label={(entry) => `${entry.name}: ${entry.value}`}
                                                outerRadius={80}
                                                fill="#8884d8"
                                                dataKey="value"
                                            >
                                                {servicesData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <RechartsTooltip />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="calls" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Anrufstatistiken</CardTitle>
                                    <CardDescription>Detaillierte Analyse Ihrer Anrufdaten</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-6">
                                        {/* Peak Hours */}
                                        <div>
                                            <h4 className="text-sm font-medium mb-4">Stoßzeiten</h4>
                                            <ResponsiveContainer width="100%" height={300}>
                                                <BarChart data={peakHours}>
                                                    <CartesianGrid strokeDasharray="3 3" />
                                                    <XAxis dataKey="hour" />
                                                    <YAxis />
                                                    <RechartsTooltip />
                                                    <Bar dataKey="calls" fill="#3B82F6" />
                                                </BarChart>
                                            </ResponsiveContainer>
                                        </div>

                                        {/* Call Duration Distribution */}
                                        <div className="grid gap-4 md:grid-cols-3">
                                            <Card>
                                                <CardContent className="p-6">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <p className="text-sm text-muted-foreground">Ø Anrufdauer</p>
                                                            <p className="text-2xl font-bold">2:34</p>
                                                        </div>
                                                        <Clock className="h-8 w-8 text-muted-foreground" />
                                                    </div>
                                                </CardContent>
                                            </Card>
                                            <Card>
                                                <CardContent className="p-6">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <p className="text-sm text-muted-foreground">Anrufvolumen</p>
                                                            <p className="text-2xl font-bold">1,234</p>
                                                        </div>
                                                        <PhoneCall className="h-8 w-8 text-muted-foreground" />
                                                    </div>
                                                </CardContent>
                                            </Card>
                                            <Card>
                                                <CardContent className="p-6">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <p className="text-sm text-muted-foreground">Erfolgsquote</p>
                                                            <p className="text-2xl font-bold">87%</p>
                                                        </div>
                                                        <Target className="h-8 w-8 text-muted-foreground" />
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="appointments" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Terminstatistiken</CardTitle>
                                    <CardDescription>Analyse Ihrer Terminbuchungen</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-6">
                                        {/* Appointment Status Distribution */}
                                        <div className="grid gap-4 md:grid-cols-4">
                                            <Card>
                                                <CardContent className="p-4">
                                                    <div className="text-center">
                                                        <p className="text-sm text-muted-foreground">Geplant</p>
                                                        <p className="text-2xl font-bold text-blue-600">234</p>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                            <Card>
                                                <CardContent className="p-4">
                                                    <div className="text-center">
                                                        <p className="text-sm text-muted-foreground">Abgeschlossen</p>
                                                        <p className="text-2xl font-bold text-green-600">892</p>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                            <Card>
                                                <CardContent className="p-4">
                                                    <div className="text-center">
                                                        <p className="text-sm text-muted-foreground">Abgesagt</p>
                                                        <p className="text-2xl font-bold text-red-600">45</p>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                            <Card>
                                                <CardContent className="p-4">
                                                    <div className="text-center">
                                                        <p className="text-sm text-muted-foreground">No-Show</p>
                                                        <p className="text-2xl font-bold text-gray-600">12</p>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        </div>

                                        {/* Services Chart */}
                                        <ResponsiveContainer width="100%" height={300}>
                                            <BarChart data={servicesData} layout="horizontal">
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis type="number" />
                                                <YAxis dataKey="name" type="category" width={100} />
                                                <RechartsTooltip />
                                                <Bar dataKey="value" fill="#10B981" />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="performance" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Mitarbeiterleistung</CardTitle>
                                    <CardDescription>Top-Performer in Ihrem Team</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Mitarbeiter</TableHead>
                                                <TableHead>Anrufe</TableHead>
                                                <TableHead>Termine</TableHead>
                                                <TableHead>Konversionsrate</TableHead>
                                                <TableHead>Bewertung</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {staffPerformance.map((staff, index) => (
                                                <TableRow key={staff.id}>
                                                    <TableCell className="font-medium">
                                                        <div className="flex items-center gap-2">
                                                            {index < 3 && (
                                                                <Trophy className={cn(
                                                                    "h-4 w-4",
                                                                    index === 0 && "text-yellow-500",
                                                                    index === 1 && "text-gray-400",
                                                                    index === 2 && "text-orange-600"
                                                                )} />
                                                            )}
                                                            {staff.name}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>{staff.calls}</TableCell>
                                                    <TableCell>{staff.appointments}</TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <Progress value={staff.conversion_rate} className="w-20" />
                                                            <span className="text-sm">{staff.conversion_rate}%</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={staff.rating >= 4.5 ? "default" : "secondary"}>
                                                            ⭐ {staff.rating}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="conversion" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Konversions-Funnel</CardTitle>
                                    <CardDescription>Von Anruf zu Termin zu Kunde</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-6">
                                        {/* Conversion Rate */}
                                        <div className="text-center">
                                            <p className="text-4xl font-bold text-green-600">
                                                {conversionData.rate}%
                                            </p>
                                            <p className="text-muted-foreground">Gesamtkonversionsrate</p>
                                        </div>

                                        {/* Funnel Visualization */}
                                        <div className="space-y-4">
                                            {conversionData.funnel.map((stage, index) => (
                                                <div key={index} className="relative">
                                                    <div 
                                                        className="bg-primary/10 rounded-lg p-4"
                                                        style={{ 
                                                            width: `${(stage.count / conversionData.funnel[0].count) * 100}%`,
                                                            minWidth: '200px'
                                                        }}
                                                    >
                                                        <div className="flex justify-between items-center">
                                                            <span className="font-medium">{stage.name}</span>
                                                            <span className="text-sm text-muted-foreground">
                                                                {stage.count} ({stage.percentage}%)
                                                            </span>
                                                        </div>
                                                    </div>
                                                    {index < conversionData.funnel.length - 1 && (
                                                        <ArrowDownRight className="h-4 w-4 text-muted-foreground mt-2 ml-4" />
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="goals" className="space-y-4">
                            <Tabs defaultValue="dashboard" className="space-y-4">
                                <TabsList className="grid w-full grid-cols-3">
                                    <TabsTrigger value="dashboard">Dashboard</TabsTrigger>
                                    <TabsTrigger value="configuration">Konfiguration</TabsTrigger>
                                    <TabsTrigger value="analytics">Analysen</TabsTrigger>
                                </TabsList>
                                
                                <TabsContent value="dashboard">
                                    <GoalDashboard />
                                </TabsContent>
                                
                                <TabsContent value="configuration">
                                    <GoalConfiguration />
                                </TabsContent>
                                
                                <TabsContent value="analytics">
                                    <GoalAnalytics />
                                </TabsContent>
                            </Tabs>
                        </TabsContent>
                    </Tabs>
                )}
            </div>
    );
};

export default AnalyticsIndex;