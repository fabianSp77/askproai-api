import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../ui/card';
import { Button } from '../../ui/button';
import { Badge } from '../../ui/badge';
import { Progress } from '../../ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../ui/tabs';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../../ui/select';
import {
    LineChart,
    Line,
    BarChart,
    Bar,
    AreaChart,
    Area,
    PieChart,
    Pie,
    Cell,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip as RechartsTooltip,
    Legend,
    ResponsiveContainer,
    RadarChart,
    PolarGrid,
    PolarAngleAxis,
    PolarRadiusAxis,
    Radar
} from 'recharts';
import { 
    Target,
    TrendingUp,
    TrendingDown,
    Calendar,
    Trophy,
    AlertTriangle,
    Download,
    RefreshCw,
    BarChart3,
    Activity,
    Zap,
    Award,
    Users,
    Phone,
    DollarSign,
    ArrowUp,
    ArrowDown,
    Loader2
} from 'lucide-react';
import { useGoals } from '../../../hooks/useGoals';
import { cn } from '../../../lib/utils';
import dayjs from 'dayjs';
import 'dayjs/locale/de';

dayjs.locale('de');

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

const GoalAnalytics = () => {
    const { goals, loading, error, fetchGoalMetrics, fetchGoalProgress } = useGoals();
    const [selectedGoal, setSelectedGoal] = useState(null);
    const [timeRange, setTimeRange] = useState('month');
    const [metricsData, setMetricsData] = useState(null);
    const [progressData, setProgressData] = useState({});
    const [loadingMetrics, setLoadingMetrics] = useState(false);

    useEffect(() => {
        if (goals && Array.isArray(goals) && goals.length > 0 && !selectedGoal) {
            setSelectedGoal(goals[0]);
        }
    }, [goals]);

    useEffect(() => {
        if (selectedGoal) {
            loadGoalData();
        }
    }, [selectedGoal, timeRange]);

    const loadGoalData = async () => {
        if (!selectedGoal) return;

        setLoadingMetrics(true);
        try {
            // Calculate date range based on selected time range
            const endDate = dayjs();
            let startDate;
            switch (timeRange) {
                case 'week':
                    startDate = endDate.subtract(7, 'days');
                    break;
                case 'month':
                    startDate = endDate.subtract(30, 'days');
                    break;
                case 'quarter':
                    startDate = endDate.subtract(90, 'days');
                    break;
                case 'year':
                    startDate = endDate.subtract(365, 'days');
                    break;
                default:
                    startDate = endDate.subtract(30, 'days');
            }

            const [metrics, progress] = await Promise.all([
                fetchGoalMetrics(selectedGoal.id, {
                    start: startDate.format('YYYY-MM-DD'),
                    end: endDate.format('YYYY-MM-DD')
                }),
                fetchGoalProgress(selectedGoal.id)
            ]);

            setMetricsData(metrics);
            setProgressData(prev => ({
                ...prev,
                [selectedGoal.id]: progress
            }));
        } catch (err) {
            // Silently handle goal data loading error
        } finally {
            setLoadingMetrics(false);
        }
    };

    const getGoalIcon = (type) => {
        const icons = {
            calls: Phone,
            appointments: Calendar,
            conversion: TrendingUp,
            revenue: DollarSign,
            customers: Users
        };
        return icons[type] || Target;
    };

    const calculateOverallProgress = () => {
        if (!goals || !Array.isArray(goals) || goals.length === 0) return 0;
        
        const totalProgress = goals.reduce((sum, goal) => {
            const progress = goal.current_value ? (goal.current_value / goal.target_value) * 100 : 0;
            return sum + Math.min(progress, 100);
        }, 0);
        
        return totalProgress / goals.length;
    };

    const getGoalsByStatus = () => {
        if (!goals || !Array.isArray(goals) || goals.length === 0) return { achieved: 0, onTrack: 0, atRisk: 0, failed: 0 };
        
        const status = {
            achieved: 0,
            onTrack: 0,
            atRisk: 0,
            failed: 0
        };

        goals.forEach(goal => {
            const progress = goal.current_value ? (goal.current_value / goal.target_value) * 100 : 0;
            const daysRemaining = dayjs(goal.ends_at).diff(dayjs(), 'days');
            const totalDays = dayjs(goal.ends_at).diff(goal.starts_at, 'days');
            const daysElapsed = totalDays - daysRemaining;
            const expectedProgress = (daysElapsed / totalDays) * 100;

            if (progress >= 100) {
                status.achieved++;
            } else if (daysRemaining <= 0) {
                status.failed++;
            } else if (progress >= expectedProgress - 10) {
                status.onTrack++;
            } else {
                status.atRisk++;
            }
        });

        return status;
    };

    const prepareRadarData = () => {
        if (!goals || goals.length === 0) return [];

        const typeGroups = goals.reduce((acc, goal) => {
            if (!acc[goal.type]) {
                acc[goal.type] = {
                    type: goal.type,
                    goals: 0,
                    avgProgress: 0,
                    totalProgress: 0
                };
            }
            
            const progress = goal.current_value ? (goal.current_value / goal.target_value) * 100 : 0;
            acc[goal.type].goals++;
            acc[goal.type].totalProgress += progress;
            
            return acc;
        }, {});

        return Object.values(typeGroups).map(group => ({
            category: getGoalTypeLabel(group.type),
            progress: Math.round(group.totalProgress / group.goals),
            goals: group.goals
        }));
    };

    const getGoalTypeLabel = (type) => {
        const labels = {
            calls: 'Anrufe',
            appointments: 'Termine',
            conversion: 'Konversion',
            revenue: 'Umsatz',
            customers: 'Kunden'
        };
        return labels[type] || type;
    };

    const exportData = () => {
        // TODO: Implement export functionality
        console.log('Export functionality to be implemented');
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center p-8">
                <Loader2 className="h-8 w-8 animate-spin" />
            </div>
        );
    }

    const overallProgress = calculateOverallProgress();
    const goalStatus = getGoalsByStatus();
    const radarData = prepareRadarData();

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight">Ziel-Analytics</h2>
                    <p className="text-muted-foreground">Analysieren Sie die Leistung Ihrer Geschäftsziele</p>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={loadGoalData} disabled={loadingMetrics}>
                        <RefreshCw className={cn("h-4 w-4 mr-2", loadingMetrics && "animate-spin")} />
                        Aktualisieren
                    </Button>
                    <Button variant="outline" onClick={exportData}>
                        <Download className="h-4 w-4 mr-2" />
                        Exportieren
                    </Button>
                </div>
            </div>

            {/* Overall Stats */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-medium">Gesamtfortschritt</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{overallProgress.toFixed(0)}%</div>
                        <Progress value={overallProgress} className="mt-2 h-2" />
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-medium">Erreicht</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-baseline gap-2">
                            <span className="text-2xl font-bold text-green-600">{goalStatus.achieved}</span>
                            <Trophy className="h-5 w-5 text-green-600" />
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-medium">Auf Kurs</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-baseline gap-2">
                            <span className="text-2xl font-bold text-blue-600">{goalStatus.onTrack}</span>
                            <TrendingUp className="h-5 w-5 text-blue-600" />
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-medium">Gefährdet</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-baseline gap-2">
                            <span className="text-2xl font-bold text-yellow-600">{goalStatus.atRisk}</span>
                            <AlertTriangle className="h-5 w-5 text-yellow-600" />
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-medium">Verfehlt</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-baseline gap-2">
                            <span className="text-2xl font-bold text-red-600">{goalStatus.failed}</span>
                            <TrendingDown className="h-5 w-5 text-red-600" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Goal Selector and Time Range */}
            {goals && goals.length > 0 && (
                <Card>
                    <CardHeader>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <div className="flex-1">
                                <Select
                                    value={selectedGoal?.id || ''}
                                    onValueChange={(value) => {
                                        const goal = goals.find(g => g.id === value);
                                        setSelectedGoal(goal);
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Ziel auswählen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {goals.map((goal) => {
                                            const Icon = getGoalIcon(goal.type);
                                            return (
                                                <SelectItem key={goal.id} value={goal.id}>
                                                    <div className="flex items-center gap-2">
                                                        <Icon className="h-4 w-4" />
                                                        {goal.name}
                                                    </div>
                                                </SelectItem>
                                            );
                                        })}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Select value={timeRange} onValueChange={setTimeRange}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="week">Letzte 7 Tage</SelectItem>
                                    <SelectItem value="month">Letzte 30 Tage</SelectItem>
                                    <SelectItem value="quarter">Letztes Quartal</SelectItem>
                                    <SelectItem value="year">Letztes Jahr</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardHeader>
                </Card>
            )}

            {/* Analytics Tabs */}
            <Tabs defaultValue="progress" className="space-y-4">
                <TabsList>
                    <TabsTrigger value="progress">Fortschritt</TabsTrigger>
                    <TabsTrigger value="trends">Trends</TabsTrigger>
                    <TabsTrigger value="comparison">Vergleich</TabsTrigger>
                    <TabsTrigger value="insights">Einblicke</TabsTrigger>
                </TabsList>

                <TabsContent value="progress" className="space-y-4">
                    {selectedGoal && (
                        <div className="grid gap-4 md:grid-cols-2">
                            {/* Progress Chart */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Fortschrittsverlauf</CardTitle>
                                    <CardDescription>Entwicklung über Zeit</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <AreaChart data={metricsData?.daily || []}>
                                            <defs>
                                                <linearGradient id="colorProgress" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="5%" stopColor="#3B82F6" stopOpacity={0.8}/>
                                                    <stop offset="95%" stopColor="#3B82F6" stopOpacity={0}/>
                                                </linearGradient>
                                            </defs>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis 
                                                dataKey="date" 
                                                tickFormatter={(value) => dayjs(value).format('DD.MM')}
                                            />
                                            <YAxis />
                                            <RechartsTooltip 
                                                labelFormatter={(value) => dayjs(value).format('DD.MM.YYYY')}
                                            />
                                            <Area
                                                type="monotone"
                                                dataKey="value"
                                                stroke="#3B82F6"
                                                fillOpacity={1}
                                                fill="url(#colorProgress)"
                                                name="Fortschritt"
                                            />
                                        </AreaChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>

                            {/* Goal Details */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Zieldetails</CardTitle>
                                    <CardDescription>{selectedGoal.name}</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <div>
                                            <div className="flex justify-between text-sm mb-1">
                                                <span>Aktueller Fortschritt</span>
                                                <span className="font-medium">
                                                    {selectedGoal.current_value || 0} / {selectedGoal.target_value}
                                                </span>
                                            </div>
                                            <Progress 
                                                value={(selectedGoal.current_value / selectedGoal.target_value) * 100} 
                                                className="h-3"
                                            />
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <p className="text-sm text-muted-foreground">Startdatum</p>
                                                <p className="font-medium">
                                                    {dayjs(selectedGoal.starts_at).format('DD.MM.YYYY')}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Enddatum</p>
                                                <p className="font-medium">
                                                    {dayjs(selectedGoal.ends_at).format('DD.MM.YYYY')}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Zeitraum</p>
                                                <p className="font-medium">
                                                    {selectedGoal.target_period === 'month' ? 'Monatlich' : 
                                                     selectedGoal.target_period === 'week' ? 'Wöchentlich' :
                                                     selectedGoal.target_period === 'quarter' ? 'Quartalsweise' : 'Jährlich'}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Priorität</p>
                                                <Badge variant={
                                                    selectedGoal.priority === 'high' ? 'destructive' :
                                                    selectedGoal.priority === 'medium' ? 'default' : 'secondary'
                                                }>
                                                    {selectedGoal.priority === 'high' ? 'Hoch' :
                                                     selectedGoal.priority === 'medium' ? 'Mittel' : 'Niedrig'}
                                                </Badge>
                                            </div>
                                        </div>

                                        {progressData[selectedGoal.id] && (
                                            <div className="pt-4 border-t">
                                                <h4 className="text-sm font-medium mb-2">Prognose</h4>
                                                <p className="text-sm text-muted-foreground">
                                                    Bei aktuellem Tempo wird das Ziel voraussichtlich 
                                                    {progressData[selectedGoal.id].projected_completion_date ? 
                                                        ` am ${dayjs(progressData[selectedGoal.id].projected_completion_date).format('DD.MM.YYYY')}` :
                                                        ' nicht'
                                                    } erreicht.
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}
                </TabsContent>

                <TabsContent value="trends" className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        {/* Performance Trend */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Leistungstrend</CardTitle>
                                <CardDescription>Vergleich zu vorherigen Perioden</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={300}>
                                    <LineChart data={metricsData?.trend || []}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="period" />
                                        <YAxis />
                                        <RechartsTooltip />
                                        <Legend />
                                        <Line 
                                            type="monotone" 
                                            dataKey="actual" 
                                            stroke="#3B82F6" 
                                            name="Tatsächlich"
                                            strokeWidth={2}
                                        />
                                        <Line 
                                            type="monotone" 
                                            dataKey="target" 
                                            stroke="#10B981" 
                                            name="Ziel"
                                            strokeDasharray="5 5"
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>

                        {/* Goal Type Distribution */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Zieltyp-Verteilung</CardTitle>
                                <CardDescription>Fortschritt nach Kategorie</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={300}>
                                    <RadarChart data={radarData}>
                                        <PolarGrid />
                                        <PolarAngleAxis dataKey="category" />
                                        <PolarRadiusAxis angle={90} domain={[0, 100]} />
                                        <Radar 
                                            name="Fortschritt %" 
                                            dataKey="progress" 
                                            stroke="#3B82F6" 
                                            fill="#3B82F6" 
                                            fillOpacity={0.6} 
                                        />
                                        <RechartsTooltip />
                                    </RadarChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <TabsContent value="comparison" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Zielvergleich</CardTitle>
                            <CardDescription>Alle aktiven Ziele im Überblick</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={400}>
                                <BarChart 
                                    data={goals.map(goal => ({
                                        name: goal.name,
                                        progress: (goal.current_value / goal.target_value) * 100,
                                        target: 100
                                    }))}
                                    layout="horizontal"
                                >
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis type="number" domain={[0, 100]} />
                                    <YAxis dataKey="name" type="category" width={150} />
                                    <RechartsTooltip formatter={(value) => `${value.toFixed(0)}%`} />
                                    <Bar dataKey="progress" fill="#3B82F6" name="Fortschritt">
                                        {goals.map((entry, index) => {
                                            const progress = (entry.current_value / entry.target_value) * 100;
                                            const fill = progress >= 90 ? '#10B981' : 
                                                        progress >= 70 ? '#F59E0B' : '#EF4444';
                                            return <Cell key={`cell-${index}`} fill={fill} />;
                                        })}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="insights" className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        {/* Top Performers */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Top-Performer</CardTitle>
                                <CardDescription>Beste Zielfortschritte</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {goals
                                        .sort((a, b) => {
                                            const progressA = (a.current_value / a.target_value) * 100;
                                            const progressB = (b.current_value / b.target_value) * 100;
                                            return progressB - progressA;
                                        })
                                        .slice(0, 3)
                                        .map((goal, index) => {
                                            const progress = (goal.current_value / goal.target_value) * 100;
                                            const Icon = getGoalIcon(goal.type);
                                            
                                            return (
                                                <div key={goal.id} className="flex items-center gap-3">
                                                    <div className="flex items-center justify-center w-8 h-8 rounded-full bg-muted font-medium">
                                                        {index + 1}
                                                    </div>
                                                    <Icon className="h-4 w-4 text-muted-foreground" />
                                                    <div className="flex-1">
                                                        <p className="text-sm font-medium">{goal.name}</p>
                                                        <Progress value={progress} className="h-2 mt-1" />
                                                    </div>
                                                    <span className="text-sm font-medium">
                                                        {progress.toFixed(0)}%
                                                    </span>
                                                </div>
                                            );
                                        })}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Recommendations */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Empfehlungen</CardTitle>
                                <CardDescription>Handlungsempfehlungen basierend auf Ihrer Leistung</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {goalStatus.atRisk > 0 && (
                                        <div className="flex gap-3 p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
                                            <AlertTriangle className="h-5 w-5 text-yellow-600 shrink-0 mt-0.5" />
                                            <div>
                                                <p className="text-sm font-medium">Gefährdete Ziele prüfen</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {goalStatus.atRisk} Ziel{goalStatus.atRisk > 1 ? 'e' : ''} liegt hinter dem Plan zurück. 
                                                    Überprüfen Sie die Strategie.
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                    
                                    {goalStatus.achieved > 0 && (
                                        <div className="flex gap-3 p-3 rounded-lg bg-green-50 dark:bg-green-900/20">
                                            <Trophy className="h-5 w-5 text-green-600 shrink-0 mt-0.5" />
                                            <div>
                                                <p className="text-sm font-medium">Neue Ziele setzen</p>
                                                <p className="text-sm text-muted-foreground">
                                                    Sie haben {goalStatus.achieved} Ziel{goalStatus.achieved > 1 ? 'e' : ''} erreicht. 
                                                    Zeit für neue Herausforderungen!
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                    
                                    {overallProgress < 50 && (
                                        <div className="flex gap-3 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                            <Activity className="h-5 w-5 text-blue-600 shrink-0 mt-0.5" />
                                            <div>
                                                <p className="text-sm font-medium">Aktivität steigern</p>
                                                <p className="text-sm text-muted-foreground">
                                                    Der Gesamtfortschritt liegt bei {overallProgress.toFixed(0)}%. 
                                                    Fokussieren Sie sich auf die wichtigsten Ziele.
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default GoalAnalytics;