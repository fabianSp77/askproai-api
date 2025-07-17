import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Badge } from '../../../components/ui/badge';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Progress } from '../../../components/ui/progress';
import { 
    Phone, 
    Calendar, 
    Users, 
    TrendingUp,
    Clock,
    DollarSign,
    AlertTriangle,
    CheckCircle,
    RefreshCw,
    ArrowUp,
    ArrowDown,
    Target
} from 'lucide-react';
import { cn } from '../../../lib/utils';
import GoalDashboard from '../../../components/goals/GoalDashboard';
import axiosInstance from '../../../services/axiosInstance';

const DashboardModern = () => {
    const [loading, setLoading] = useState(true);
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
        recentCalls: [],
        upcomingAppointments: [],
        performance: {
            answer_rate: 0,
            booking_rate: 0,
            avg_call_duration: 0
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
            const response = await axiosInstance.get('/dashboard');
            
            // Ensure data structure is correct
            setDashboardData({
                stats: response.data.stats || {
                    calls_today: 0,
                    appointments_today: 0,
                    new_customers: 0,
                    revenue_today: 0
                },
                trends: response.data.trends || {
                    calls: { value: 0, change: 0 },
                    appointments: { value: 0, change: 0 },
                    customers: { value: 0, change: 0 },
                    revenue: { value: 0, change: 0 }
                },
                recentCalls: Array.isArray(response.data.recentCalls) ? response.data.recentCalls : [],
                upcomingAppointments: Array.isArray(response.data.upcomingAppointments) ? response.data.upcomingAppointments : [],
                performance: response.data.performance || {
                    answer_rate: 0,
                    booking_rate: 0,
                    avg_call_duration: 0
                }
            });
        } catch (error) {
            // Silently handle error - dashboard will show empty state
        } finally {
            setLoading(false);
        }
    };

    const StatCard = ({ title, value, icon: Icon, trend, color = 'blue' }) => {
        const isPositive = trend?.change >= 0;
        const colorClasses = {
            blue: 'text-blue-600 bg-blue-100 dark:bg-blue-900/20',
            green: 'text-green-600 bg-green-100 dark:bg-green-900/20',
            purple: 'text-purple-600 bg-purple-100 dark:bg-purple-900/20',
            amber: 'text-amber-600 bg-amber-100 dark:bg-amber-900/20'
        };

        return (
            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">{title}</CardTitle>
                    <div className={cn("p-2 rounded-lg", colorClasses[color])}>
                        <Icon className="h-4 w-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">{value}</div>
                    {trend && (
                        <div className="flex items-center text-xs text-muted-foreground mt-1">
                            <span className={cn(
                                "flex items-center",
                                isPositive ? "text-green-600" : "text-red-600"
                            )}>
                                {isPositive ? <ArrowUp className="h-3 w-3 mr-1" /> : <ArrowDown className="h-3 w-3 mr-1" />}
                                {Math.abs(trend.change)}%
                            </span>
                            <span className="ml-1">gegenüber gestern</span>
                        </div>
                    )}
                </CardContent>
            </Card>
        );
    };

    const formatTime = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
    };

    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('de-DE', { weekday: 'short', day: 'numeric', month: 'short' });
    };

    const getCallStatusBadge = (status) => {
        const variants = {
            'completed': { variant: 'default', label: 'Abgeschlossen' },
            'missed': { variant: 'destructive', label: 'Verpasst' },
            'in_progress': { variant: 'secondary', label: 'Läuft' }
        };
        const config = variants[status] || { variant: 'outline', label: status };
        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold">Dashboard</h1>
                    <p className="text-muted-foreground">Willkommen zurück! Hier ist Ihre Übersicht.</p>
                </div>
                <Button 
                    variant="outline"
                    onClick={fetchDashboardData}
                    disabled={loading}
                >
                    <RefreshCw className={cn("h-4 w-4 mr-2", loading && "animate-spin")} />
                    Aktualisieren
                </Button>
            </div>

            {/* Stats Grid */}
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
                    icon={Users}
                    trend={dashboardData.trends.customers}
                    color="purple"
                />
                <StatCard
                    title="Umsatz heute"
                    value={`€${Number(dashboardData.stats.revenue_today || 0).toFixed(2)}`}
                    icon={DollarSign}
                    trend={dashboardData.trends.revenue}
                    color="amber"
                />
            </div>

            {/* Performance Metrics */}
            <Card>
                <CardHeader>
                    <CardTitle>Performance Metriken</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        <div>
                            <div className="flex justify-between mb-2">
                                <span className="text-sm font-medium">Annahmequote</span>
                                <span className="text-sm text-muted-foreground">
                                    {dashboardData.performance.answer_rate}%
                                </span>
                            </div>
                            <Progress value={dashboardData.performance.answer_rate} />
                        </div>
                        <div>
                            <div className="flex justify-between mb-2">
                                <span className="text-sm font-medium">Buchungsrate</span>
                                <span className="text-sm text-muted-foreground">
                                    {dashboardData.performance.booking_rate}%
                                </span>
                            </div>
                            <Progress value={dashboardData.performance.booking_rate} />
                        </div>
                        <div>
                            <div className="flex justify-between">
                                <span className="text-sm font-medium">Ø Anrufdauer</span>
                                <span className="text-sm text-muted-foreground">
                                    {dashboardData.performance.avg_call_duration} Min.
                                </span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Goals Widget */}
            <div className="lg:col-span-2">
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle className="flex items-center gap-2">
                                <Target className="h-5 w-5" />
                                Aktive Ziele
                            </CardTitle>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => window.location.href = '/analytics#goals'}
                            >
                                Alle anzeigen
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <GoalDashboard compact={true} />
                    </CardContent>
                </Card>
            </div>

            {/* Tabs for Recent Activity */}
            <Tabs defaultValue="calls" className="space-y-4">
                <TabsList>
                    <TabsTrigger value="calls">Letzte Anrufe</TabsTrigger>
                    <TabsTrigger value="appointments">Kommende Termine</TabsTrigger>
                </TabsList>

                <TabsContent value="calls">
                    <Card>
                        <CardHeader>
                            <CardTitle>Letzte Anrufe</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {dashboardData.recentCalls.length === 0 ? (
                                <p className="text-muted-foreground text-center py-4">
                                    Keine Anrufe heute
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {(dashboardData.recentCalls || []).map((call) => (
                                        <div key={call.id} className="flex items-center justify-between p-3 rounded-lg border">
                                            <div className="flex items-center gap-3">
                                                <Phone className="h-4 w-4 text-muted-foreground" />
                                                <div>
                                                    <p className="font-medium">{call.phone_number}</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {formatTime(call.created_at)} • {call.duration}s
                                                    </p>
                                                </div>
                                            </div>
                                            {getCallStatusBadge(call.status)}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="appointments">
                    <Card>
                        <CardHeader>
                            <CardTitle>Kommende Termine</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {dashboardData.upcomingAppointments.length === 0 ? (
                                <p className="text-muted-foreground text-center py-4">
                                    Keine kommenden Termine
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {(dashboardData.upcomingAppointments || []).map((appointment) => (
                                        <div key={appointment.id} className="flex items-center justify-between p-3 rounded-lg border">
                                            <div className="flex items-center gap-3">
                                                <Calendar className="h-4 w-4 text-muted-foreground" />
                                                <div>
                                                    <p className="font-medium">{appointment.customer_name}</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {formatDate(appointment.start_time)} • {formatTime(appointment.start_time)}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {appointment.service_name}
                                                    </p>
                                                </div>
                                            </div>
                                            <Badge variant="outline">{appointment.staff_name}</Badge>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default DashboardModern;