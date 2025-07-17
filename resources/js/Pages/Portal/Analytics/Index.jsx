import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Badge } from '../../../components/ui/badge';
import { Progress } from '../../../components/ui/progress';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { useAuth } from '../../../hooks/useAuth';
import GoalsAnalytics from './Goals';
import {
    LineChart,
    Line,
    AreaChart,
    Area,
    BarChart,
    Bar,
    PieChart,
    Pie,
    Cell,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer
} from 'recharts';
import {
    BarChart3,
    Phone,
    Calendar,
    Users,
    TrendingUp,
    TrendingDown,
    Download,
    RefreshCw,
    Clock,
    Target,
    Activity,
    DollarSign,
    Loader2
} from 'lucide-react';
import { cn } from '../../../lib/utils';
import axiosInstance from '../../../services/axiosInstance';

const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

export default function AnalyticsIndex() {
    const { user } = useAuth();
    const [loading, setLoading] = useState(true);
    const [timeRange, setTimeRange] = useState('week');
    const [branch, setBranch] = useState('all');
    const [branches, setBranches] = useState([]);
    
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
    }, [timeRange, branch]);

    const fetchAnalytics = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            
            // Calculate date range based on selection
            const endDate = new Date();
            let startDate = new Date();
            
            switch (timeRange) {
                case 'today':
                    startDate.setHours(0, 0, 0, 0);
                    break;
                case 'week':
                    startDate.setDate(startDate.getDate() - 7);
                    break;
                case 'month':
                    startDate.setDate(startDate.getDate() - 30);
                    break;
                case 'quarter':
                    startDate.setDate(startDate.getDate() - 90);
                    break;
                case 'year':
                    startDate.setDate(startDate.getDate() - 365);
                    break;
            }
            
            params.append('start_date', startDate.toISOString().split('T')[0]);
            params.append('end_date', endDate.toISOString().split('T')[0]);
            
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

    const exportAnalytics = async (format) => {
        try {
            const params = new URLSearchParams();
            params.append('format', format);
            params.append('time_range', timeRange);
            if (branch !== 'all') {
                params.append('branch_id', branch);
            }

            const response = await axiosInstance.get(`/analytics/export?`);

            if (!response.data) throw new Error('Export failed');

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `analytics_${new Date().toISOString().split('T')[0]}.${format}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (error) {
            // Show user-friendly error message could be added here
        }
    };

    const StatCard = ({ title, value, trend, icon: Icon, prefix = '', suffix = '' }) => (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">
                    {prefix}{value}{suffix}
                </div>
                {trend !== undefined && (
                    <p className={cn(
                        "text-xs flex items-center mt-1",
                        trend > 0 ? "text-green-600 dark:text-green-400" : "text-red-600 dark:text-red-400"
                    )}>
                        {trend > 0 ? (
                            <TrendingUp className="h-3 w-3 mr-1" />
                        ) : (
                            <TrendingDown className="h-3 w-3 mr-1" />
                        )}
                        {Math.abs(trend)}% vs. Vorperiode
                    </p>
                )}
            </CardContent>
        </Card>
    );

    return (
        <div className="space-y-6">
            {/* Header with Filters */}
            <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div className="flex items-center gap-4">
                    <Select value={timeRange} onValueChange={setTimeRange}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue />
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
                            <SelectValue placeholder="Filiale" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Alle Filialen</SelectItem>
                            {branches.map(b => (
                                <SelectItem key={b.id} value={b.id}>{b.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={fetchAnalytics}
                        disabled={loading}
                    >
                        <RefreshCw className={cn("h-4 w-4 mr-2", loading && "animate-spin")} />
                        Aktualisieren
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => exportAnalytics('csv')}
                    >
                        <Download className="h-4 w-4 mr-2" />
                        CSV
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => exportAnalytics('pdf')}
                    >
                        <Download className="h-4 w-4 mr-2" />
                        PDF
                    </Button>
                </div>
            </div>

            <Tabs defaultValue="overview" className="space-y-4">
                <TabsList className="grid w-full grid-cols-4 lg:w-auto lg:inline-flex">
                    <TabsTrigger value="overview">Übersicht</TabsTrigger>
                    <TabsTrigger value="performance">Performance</TabsTrigger>
                    <TabsTrigger value="trends">Trends</TabsTrigger>
                    <TabsTrigger value="goals">Ziele</TabsTrigger>
                </TabsList>

                <TabsContent value="overview" className="space-y-4">
                    {loading ? (
                        <div className="flex items-center justify-center h-64">
                            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                        </div>
                    ) : (
                        <>
                            {/* KPI Cards */}
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <StatCard
                                    title="Anrufe"
                                    value={overview.calls?.total || 0}
                                    trend={overview.calls?.trend}
                                    icon={Phone}
                                />
                                <StatCard
                                    title="Termine"
                                    value={overview.appointments?.total || 0}
                                    trend={overview.appointments?.trend}
                                    icon={Calendar}
                                />
                                <StatCard
                                    title="Neue Kunden"
                                    value={overview.customers?.total || 0}
                                    trend={overview.customers?.trend}
                                    icon={Users}
                                />
                                <StatCard
                                    title="Umsatz"
                                    value={overview.revenue?.total?.toFixed(2) || '0.00'}
                                    trend={overview.revenue?.trend}
                                    icon={DollarSign}
                                    prefix="€"
                                />
                            </div>

                            {/* Charts */}
                            <div className="grid gap-4 md:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Anruf-Trend</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <AreaChart data={callsData}>
                                                <defs>
                                                    <linearGradient id="colorCalls" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.8}/>
                                                        <stop offset="95%" stopColor="#3b82f6" stopOpacity={0}/>
                                                    </linearGradient>
                                                </defs>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="date" />
                                                <YAxis />
                                                <Tooltip />
                                                <Area 
                                                    type="monotone" 
                                                    dataKey="count" 
                                                    stroke="#3b82f6" 
                                                    fillOpacity={1} 
                                                    fill="url(#colorCalls)" 
                                                />
                                            </AreaChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Termin-Status</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <LineChart data={appointmentsData}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="date" />
                                                <YAxis />
                                                <Tooltip />
                                                <Legend />
                                                <Line 
                                                    type="monotone" 
                                                    dataKey="scheduled" 
                                                    stroke="#3b82f6" 
                                                    name="Geplant"
                                                />
                                                <Line 
                                                    type="monotone" 
                                                    dataKey="completed" 
                                                    stroke="#10b981" 
                                                    name="Abgeschlossen"
                                                />
                                                <Line 
                                                    type="monotone" 
                                                    dataKey="cancelled" 
                                                    stroke="#ef4444" 
                                                    name="Storniert"
                                                />
                                            </LineChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Additional Charts */}
                            <div className="grid gap-4 md:grid-cols-3">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Top Services</CardTitle>
                                    </CardHeader>
                                    <CardContent>
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
                                                <Tooltip />
                                            </PieChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Konversionsrate</CardTitle>
                                        <CardDescription>
                                            Anrufe zu Terminen
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-4">
                                            <div className="text-center">
                                                <div className="text-4xl font-bold text-primary">
                                                    {conversionData.rate}%
                                                </div>
                                                <p className="text-sm text-muted-foreground mt-1">
                                                    Gesamt-Konversionsrate
                                                </p>
                                            </div>
                                            
                                            <div className="space-y-2">
                                                {conversionData.funnel.map((step, index) => (
                                                    <div key={index}>
                                                        <div className="flex justify-between text-sm mb-1">
                                                            <span>{step.name}</span>
                                                            <span className="font-medium">{step.count}</span>
                                                        </div>
                                                        <Progress 
                                                            value={step.percentage} 
                                                            className="h-2"
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Stoßzeiten</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <BarChart data={peakHours}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="hour" />
                                                <YAxis />
                                                <Tooltip />
                                                <Bar dataKey="calls" fill="#3b82f6" name="Anrufe" />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>
                            </div>
                        </>
                    )}
                </TabsContent>

                <TabsContent value="performance" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Mitarbeiter-Performance</CardTitle>
                            <CardDescription>
                                Leistungsübersicht Ihrer Mitarbeiter
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left p-2">Mitarbeiter</th>
                                            <th className="text-center p-2">Anrufe</th>
                                            <th className="text-center p-2">Termine</th>
                                            <th className="text-center p-2">Konversion</th>
                                            <th className="text-center p-2">Ø Dauer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {staffPerformance.map((staff) => (
                                            <tr key={staff.id} className="border-b">
                                                <td className="p-2 font-medium">{staff.name}</td>
                                                <td className="text-center p-2">{staff.calls_handled}</td>
                                                <td className="text-center p-2">{staff.appointments_completed}</td>
                                                <td className="text-center p-2">
                                                    <div className="flex items-center justify-center">
                                                        <span className="mr-2">{staff.conversion_rate}%</span>
                                                        <Progress 
                                                            value={staff.conversion_rate} 
                                                            className="w-16 h-2"
                                                        />
                                                    </div>
                                                </td>
                                                <td className="text-center p-2">
                                                    {Math.round(staff.avg_call_duration)}s
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="trends" className="space-y-4">
                    <Alert>
                        <Activity className="h-4 w-4" />
                        <AlertDescription>
                            Trend-Analysen zeigen langfristige Entwicklungen und helfen bei strategischen Entscheidungen.
                        </AlertDescription>
                    </Alert>
                    {/* Add more trend visualizations here */}
                </TabsContent>

                <TabsContent value="goals" className="space-y-4">
                    <GoalsAnalytics />
                </TabsContent>
            </Tabs>
        </div>
    );
}