import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Badge } from '../../../components/ui/badge';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Progress } from '../../../components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { 
    LineChart, Line, BarChart, Bar, PieChart, Pie, Cell,
    AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend
} from 'recharts';
import { 
    Phone, 
    Calendar, 
    Users, 
    TrendingUp,
    TrendingDown,
    Clock,
    DollarSign,
    AlertTriangle,
    CheckCircle,
    RefreshCw,
    ArrowUp,
    ArrowDown,
    Activity,
    BarChart3,
    Target,
    Award,
    PhoneCall,
    PhoneIncoming,
    PhoneOutgoing,
    UserPlus
} from 'lucide-react';
import { useAuth } from '../../../hooks/useAuth';
import { cn } from '../../../lib/utils';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import GoalDashboard from '../../../components/Portal/Goals/GoalDashboard';
import { useIsMobile } from '../../../hooks/useMediaQuery';
import MobileDashboard from '../../../components/Mobile/MobileDashboard';
import axiosInstance from '../../../services/axiosInstance';

dayjs.locale('de');

const DashboardIndex = () => {
    const { csrfToken } = useAuth();
    const isMobile = useIsMobile();
    const [loading, setLoading] = useState(true);
    const [timeRange, setTimeRange] = useState('today');
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState(null);
    const [dashboardData, setDashboardData] = useState({
        stats: {
            calls_today: 0,
            appointments_today: 0,
            new_customers: 0,
            revenue_today: 0
        },
        trends: {
            calls: { value: 0, change: 0 },
            appointments: { value: 0, change: 0 },
            customers: { value: 0, change: 0 },
            revenue: { value: 0, change: 0 }
        },
        chartData: {
            daily: [],
            hourly: [],
            sources: [],
            performance: []
        },
        recentCalls: [],
        upcomingAppointments: [],
        performance: {
            answer_rate: 0,
            booking_rate: 0,
            avg_call_duration: 0,
            customer_satisfaction: 0
        },
        alerts: []
    });

    useEffect(() => {
        fetchDashboardData();
        const interval = setInterval(fetchDashboardData, 60000); // Refresh every minute
        return () => clearInterval(interval);
    }, [timeRange]);

    const fetchDashboardData = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await axiosInstance.get('/dashboard', {
                params: { range: timeRange }
            });
            setDashboardData(response.data);
        } catch (error) {
            setError('Failed to load dashboard data. Please try again.');
            // Silently handle the error, state is already set
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    const handleRefresh = () => {
        setRefreshing(true);
        fetchDashboardData();
    };

    // Stat Card Component
    const StatCard = ({ title, value, icon: Icon, trend, color = 'blue', format = 'number' }) => {
        const isPositive = trend?.change >= 0;
        const colorClasses = {
            blue: 'text-blue-600 bg-blue-100 dark:bg-blue-900/20',
            green: 'text-green-600 bg-green-100 dark:bg-green-900/20',
            purple: 'text-purple-600 bg-purple-100 dark:bg-purple-900/20',
            amber: 'text-amber-600 bg-amber-100 dark:bg-amber-900/20'
        };

        const formatValue = (val) => {
            switch (format) {
                case 'currency':
                    return `€${Number(val).toFixed(2)}`;
                case 'percent':
                    return `${val}%`;
                default:
                    return val;
            }
        };

        return (
            <Card>
                <CardHeader className="pb-2">
                    <div className="flex items-center justify-between">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            {title}
                        </CardTitle>
                        <div className={cn('p-2 rounded-lg', colorClasses[color])}>
                            <Icon className="h-4 w-4" />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="flex items-end justify-between">
                        <div>
                            <p className="text-2xl font-bold">{formatValue(value)}</p>
                            {trend && (
                                <div className="flex items-center mt-1 text-sm">
                                    {isPositive ? (
                                        <ArrowUp className="h-3 w-3 text-green-600 mr-1" />
                                    ) : (
                                        <ArrowDown className="h-3 w-3 text-red-600 mr-1" />
                                    )}
                                    <span className={isPositive ? 'text-green-600' : 'text-red-600'}>
                                        {Math.abs(trend.change)}%
                                    </span>
                                    <span className="text-muted-foreground ml-1">vs. gestern</span>
                                </div>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>
        );
    };

    // Colors for charts
    const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];

    // Mobile View
    if (isMobile) {
        return (
            <MobileDashboard 
                stats={{
                    calls_today: dashboardData.stats.calls_today,
                    new_calls: dashboardData.stats.new_calls || 0,
                    appointments_today: dashboardData.stats.appointments_today,
                    avg_duration: dashboardData.performance.avg_call_duration,
                    action_required: dashboardData.alerts?.filter(a => a.type === 'action_required').length || 0
                }}
                recentCalls={dashboardData.recentCalls}
                upcomingAppointments={dashboardData.upcomingAppointments}
            />
        );
    }

    // Desktop View
    return (
        <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Dashboard</h1>
                        <p className="text-muted-foreground mt-1">
                            Übersicht über Ihre wichtigsten Kennzahlen
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Select value={timeRange} onValueChange={setTimeRange}>
                            <SelectTrigger className="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="today">Heute</SelectItem>
                                <SelectItem value="week">Diese Woche</SelectItem>
                                <SelectItem value="month">Dieser Monat</SelectItem>
                                <SelectItem value="year">Dieses Jahr</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button
                            variant="outline"
                            onClick={handleRefresh}
                            disabled={refreshing}
                        >
                            <RefreshCw className={cn('h-4 w-4 mr-2', refreshing && 'animate-spin')} />
                            Aktualisieren
                        </Button>
                    </div>
                </div>

            {/* Alerts */}
            {dashboardData.alerts?.length > 0 && (
                <Alert>
                    <AlertTriangle className="h-4 w-4" />
                    <AlertDescription>
                        {dashboardData.alerts[0]}
                    </AlertDescription>
                </Alert>
            )}

            {/* Error State */}
            {error && (
                <Alert variant="destructive">
                    <AlertTriangle className="h-4 w-4" />
                    <AlertDescription>
                        {error}
                    </AlertDescription>
                </Alert>
            )}

            {/* Main Stats */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    title="Anrufe heute"
                    value={dashboardData.stats.calls_today}
                    icon={Phone}
                    trend={dashboardData.trends.calls}
                    color="blue"
                />
                <StatCard
                    title="Termine heute"
                    value={dashboardData.stats.appointments_today}
                    icon={Calendar}
                    trend={dashboardData.trends.appointments}
                    color="green"
                />
                <StatCard
                    title="Neue Kunden"
                    value={dashboardData.stats.new_customers}
                    icon={UserPlus}
                    trend={dashboardData.trends.customers}
                    color="purple"
                />
                <StatCard
                    title="Umsatz heute"
                    value={dashboardData.stats.revenue_today}
                    icon={DollarSign}
                    trend={dashboardData.trends.revenue}
                    color="amber"
                    format="currency"
                />
            </div>

            {/* Charts Section */}
            <div className="grid gap-6 md:grid-cols-2">
                {/* Call Volume Chart */}
                <Card>
                    <CardHeader>
                        <CardTitle>Anrufvolumen</CardTitle>
                        <CardDescription>Anrufe der letzten 7 Tage</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[300px]">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={dashboardData.chartData.daily || []}>
                                    <defs>
                                        <linearGradient id="colorCalls" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#3B82F6" stopOpacity={0.8}/>
                                            <stop offset="95%" stopColor="#3B82F6" stopOpacity={0}/>
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                    <XAxis 
                                        dataKey="date" 
                                        className="text-xs"
                                        tickFormatter={(value) => dayjs(value).format('DD.MM')}
                                    />
                                    <YAxis className="text-xs" />
                                    <Tooltip 
                                        labelFormatter={(value) => dayjs(value).format('DD.MM.YYYY')}
                                        contentStyle={{ 
                                            backgroundColor: 'hsl(var(--card))',
                                            border: '1px solid hsl(var(--border))'
                                        }}
                                    />
                                    <Area
                                        type="monotone"
                                        dataKey="calls"
                                        stroke="#3B82F6"
                                        fillOpacity={1}
                                        fill="url(#colorCalls)"
                                        name="Anrufe"
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>

                {/* Conversion Funnel */}
                <Card>
                    <CardHeader>
                        <CardTitle>Conversion Funnel</CardTitle>
                        <CardDescription>Von Anruf zu Termin</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[300px]">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart 
                                    data={dashboardData.chartData.performance || []}
                                    layout="horizontal"
                                >
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                    <XAxis dataKey="stage" className="text-xs" />
                                    <YAxis className="text-xs" />
                                    <Tooltip 
                                        contentStyle={{ 
                                            backgroundColor: 'hsl(var(--card))',
                                            border: '1px solid hsl(var(--border))'
                                        }}
                                    />
                                    <Bar dataKey="value" fill="#10B981" radius={[4, 4, 0, 0]}>
                                        {dashboardData.chartData.performance?.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Performance Metrics */}
            <div className="grid gap-6 md:grid-cols-3">
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Annahmequote</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between">
                            <div className="flex items-baseline gap-2">
                                <span className="text-3xl font-bold">
                                    {dashboardData.performance.answer_rate}%
                                </span>
                                <Badge variant="secondary" className="text-xs">
                                    Gut
                                </Badge>
                            </div>
                            <Activity className="h-8 w-8 text-muted-foreground" />
                        </div>
                        <Progress 
                            value={dashboardData.performance.answer_rate} 
                            className="mt-3"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Buchungsrate</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between">
                            <div className="flex items-baseline gap-2">
                                <span className="text-3xl font-bold">
                                    {dashboardData.performance.booking_rate}%
                                </span>
                                <Badge variant="secondary" className="text-xs">
                                    Sehr gut
                                </Badge>
                            </div>
                            <Target className="h-8 w-8 text-muted-foreground" />
                        </div>
                        <Progress 
                            value={dashboardData.performance.booking_rate} 
                            className="mt-3"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Ø Anrufdauer</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between">
                            <div className="flex items-baseline gap-2">
                                <span className="text-3xl font-bold">
                                    {dashboardData.stats.calls_today === 0 ? '-' : `${Math.floor(dashboardData.performance.avg_call_duration / 60)}:${(dashboardData.performance.avg_call_duration % 60).toString().padStart(2, '0')}`}
                                </span>
                                <span className="text-sm text-muted-foreground">Min</span>
                            </div>
                            <Clock className="h-8 w-8 text-muted-foreground" />
                        </div>
                        <div className="mt-3 text-sm text-muted-foreground">
                            Optimal: 2-5 Minuten
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Goals Widget */}
            <div className="grid gap-6 md:grid-cols-2">
                <GoalDashboard 
                    compact={true} 
                    onViewDetails={() => window.location.href = '/business/analytics?tab=goals'} 
                />
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Activity className="h-5 w-5" />
                            Quick Actions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-3">
                            <Button 
                                variant="outline" 
                                className="justify-start"
                                onClick={() => window.location.href = '/business/appointments/create'}
                            >
                                <Calendar className="h-4 w-4 mr-2" />
                                Neuen Termin erstellen
                            </Button>
                            <Button 
                                variant="outline" 
                                className="justify-start"
                                onClick={() => window.location.href = '/business/customers'}
                            >
                                <UserPlus className="h-4 w-4 mr-2" />
                                Kunde hinzufügen
                            </Button>
                            <Button 
                                variant="outline" 
                                className="justify-start"
                                onClick={() => window.location.href = '/business/analytics'}
                            >
                                <BarChart3 className="h-4 w-4 mr-2" />
                                Detaillierte Analytics
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Tabs for detailed views */}
            <Tabs defaultValue="calls" className="w-full">
                <TabsList className="grid w-full grid-cols-3">
                    <TabsTrigger value="calls">Letzte Anrufe</TabsTrigger>
                    <TabsTrigger value="appointments">Kommende Termine</TabsTrigger>
                    <TabsTrigger value="insights">Insights</TabsTrigger>
                </TabsList>

                <TabsContent value="calls" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>Letzte Anrufe</CardTitle>
                                <Button variant="ghost" size="sm" onClick={() => window.location.href = '/business/calls'}>
                                    Alle anzeigen
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {dashboardData.recentCalls?.slice(0, 5).map((call, index) => (
                                    <div key={index} className="flex items-center justify-between p-3 rounded-lg border">
                                        <div className="flex items-center gap-3">
                                            <div className={cn(
                                                'p-2 rounded-full',
                                                call.status === 'answered' ? 'bg-green-100' : 'bg-red-100'
                                            )}>
                                                {call.direction === 'inbound' ? (
                                                    <PhoneIncoming className={cn(
                                                        'h-4 w-4',
                                                        call.status === 'answered' ? 'text-green-600' : 'text-red-600'
                                                    )} />
                                                ) : (
                                                    <PhoneOutgoing className="h-4 w-4 text-blue-600" />
                                                )}
                                            </div>
                                            <div>
                                                <p className="font-medium">{call.from_number}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {dayjs(call.created_at).format('HH:mm')} Uhr • {call.duration}s
                                                </p>
                                            </div>
                                        </div>
                                        <Badge variant={call.appointment_created ? 'success' : 'secondary'}>
                                            {call.appointment_created ? 'Termin gebucht' : 'Kein Termin'}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="appointments" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>Kommende Termine</CardTitle>
                                <Button variant="ghost" size="sm" onClick={() => window.location.href = '/business/appointments'}>
                                    Alle anzeigen
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {dashboardData.upcomingAppointments?.slice(0, 5).map((appointment, index) => (
                                    <div key={index} className="flex items-center justify-between p-3 rounded-lg border">
                                        <div className="flex items-center gap-3">
                                            <div className="p-2 rounded-full bg-blue-100">
                                                <Calendar className="h-4 w-4 text-blue-600" />
                                            </div>
                                            <div>
                                                <p className="font-medium">{appointment.customer_name}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {dayjs(appointment.starts_at).format('DD.MM.YYYY HH:mm')} Uhr
                                                </p>
                                            </div>
                                        </div>
                                        <Badge>{appointment.service_name}</Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="insights" className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        {/* Call Sources */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Anrufquellen</CardTitle>
                                <CardDescription>Woher kommen Ihre Anrufe?</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[300px]">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={dashboardData.chartData.sources || []}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={60}
                                                outerRadius={100}
                                                paddingAngle={2}
                                                dataKey="value"
                                            >
                                                {dashboardData.chartData.sources?.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                            <Legend />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Peak Hours */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Stoßzeiten</CardTitle>
                                <CardDescription>Anrufe nach Tageszeit</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[300px]">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <LineChart data={dashboardData.chartData.hourly || []}>
                                            <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                            <XAxis 
                                                dataKey="hour" 
                                                className="text-xs"
                                                tickFormatter={(value) => `${value}:00`}
                                            />
                                            <YAxis className="text-xs" />
                                            <Tooltip 
                                                labelFormatter={(value) => `${value}:00 Uhr`}
                                                contentStyle={{ 
                                                    backgroundColor: 'hsl(var(--card))',
                                                    border: '1px solid hsl(var(--border))'
                                                }}
                                            />
                                            <Line 
                                                type="monotone" 
                                                dataKey="calls" 
                                                stroke="#3B82F6" 
                                                strokeWidth={2}
                                                dot={{ fill: '#3B82F6', strokeWidth: 2 }}
                                                name="Anrufe"
                                            />
                                        </LineChart>
                                    </ResponsiveContainer>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>
            </Tabs>
            </div>
    );
};

export default DashboardIndex;