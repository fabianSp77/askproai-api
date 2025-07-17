import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../../components/ui/tabs';
import { Badge } from '../../../components/ui/badge';
import { Progress } from '../../../components/ui/progress';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { useGoals } from '../../../hooks/useGoals';
import { useAuth } from '../../../hooks/useAuth';
import GoalConfiguration from '../../../components/goals/GoalConfiguration';
import GoalDashboard from '../../../components/goals/GoalDashboard';
import {
    LineChart,
    Line,
    AreaChart,
    Area,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
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
    Calendar,
    Download,
    Plus,
    RefreshCw,
    AlertCircle,
    CheckCircle2,
    Info,
    ArrowUp,
    ArrowDown,
    Loader2
} from 'lucide-react';
import { cn } from '../../../lib/utils';

export default function GoalsAnalytics() {
    const { csrfToken } = useAuth();
    const {
        goals,
        loading,
        fetchGoalDetails,
        fetchTrends,
        fetchProjections,
        calculateAchievement
    } = useGoals();

    const [selectedGoal, setSelectedGoal] = useState(null);
    const [selectedPeriod, setSelectedPeriod] = useState('30d');
    const [showConfiguration, setShowConfiguration] = useState(false);
    const [editingGoal, setEditingGoal] = useState(null);
    const [goalData, setGoalData] = useState({
        details: null,
        trends: null,
        projections: null
    });

    useEffect(() => {
        if (selectedGoal) {
            loadGoalData(selectedGoal);
        }
    }, [selectedGoal, selectedPeriod]);

    const loadGoalData = async (goalId) => {
        const [details, trends, projections] = await Promise.all([
            fetchGoalDetails(goalId),
            fetchTrends(goalId, selectedPeriod),
            fetchProjections(goalId)
        ]);

        setGoalData({
            details,
            trends,
            projections
        });
    };

    const handleRefresh = async () => {
        if (selectedGoal) {
            await calculateAchievement(selectedGoal);
            await loadGoalData(selectedGoal);
        }
    };

    const exportData = (format) => {
        // Implementation for exporting data
        // Export functionality - format: ${format}
    };

    const formatValue = (value, type) => {
        switch (type) {
            case 'currency':
                return `€${value.toFixed(2)}`;
            case 'percentage':
                return `${value.toFixed(1)}%`;
            default:
                return value.toString();
        }
    };

    const getConfidenceColor = (confidence) => {
        if (confidence >= 80) return 'text-green-600';
        if (confidence >= 60) return 'text-yellow-600';
        return 'text-red-600';
    };

    if (loading && goals.length === 0) {
        return (
            <div className="flex items-center justify-center min-h-[400px]">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight">Ziele & KPIs</h2>
                    <p className="text-muted-foreground">
                        Verfolgen Sie Ihre Geschäftsziele und analysieren Sie Trends
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={handleRefresh}
                        disabled={!selectedGoal}
                    >
                        <RefreshCw className="h-4 w-4 mr-2" />
                        Aktualisieren
                    </Button>
                    <Button
                        size="sm"
                        onClick={() => {
                            setEditingGoal(null);
                            setShowConfiguration(true);
                        }}
                    >
                        <Plus className="h-4 w-4 mr-2" />
                        Neues Ziel
                    </Button>
                </div>
            </div>

            {showConfiguration ? (
                <Card>
                    <CardHeader>
                        <CardTitle>Ziel konfigurieren</CardTitle>
                        <CardDescription>
                            Wählen Sie eine Vorlage oder erstellen Sie ein individuelles Ziel
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <GoalConfiguration
                            editGoal={editingGoal}
                            onSuccess={() => {
                                setShowConfiguration(false);
                                setEditingGoal(null);
                            }}
                        />
                    </CardContent>
                </Card>
            ) : (
                <Tabs defaultValue="overview" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="overview">Übersicht</TabsTrigger>
                        <TabsTrigger value="details">Details</TabsTrigger>
                        <TabsTrigger value="trends">Trends</TabsTrigger>
                        <TabsTrigger value="projections">Projektionen</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-4">
                        <GoalDashboard 
                            onEdit={(goal) => {
                                setEditingGoal(goal);
                                setShowConfiguration(true);
                            }}
                        />
                    </TabsContent>

                    <TabsContent value="details" className="space-y-4">
                        {/* Goal Selector */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle>Zielanalyse</CardTitle>
                                    <Select value={selectedGoal} onValueChange={setSelectedGoal}>
                                        <SelectTrigger className="w-[250px]">
                                            <SelectValue placeholder="Ziel auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {goals.map((goal) => (
                                                <SelectItem key={goal.id} value={goal.id}>
                                                    {goal.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardHeader>
                        </Card>

                        {selectedGoal && goalData.details && (
                            <>
                                {/* Metrics Overview */}
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {goalData.details.metrics?.map((metric) => (
                                        <Card key={metric.id}>
                                            <CardHeader className="pb-2">
                                                <CardTitle className="text-sm font-medium">
                                                    {metric.name}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="text-2xl font-bold">
                                                    {formatValue(metric.current_value || 0, metric.type)}
                                                </div>
                                                <div className="flex items-center justify-between mt-4">
                                                    <span className="text-sm text-muted-foreground">
                                                        Ziel: {formatValue(metric.target_value, metric.type)}
                                                    </span>
                                                    <span className={cn(
                                                        "text-sm font-medium",
                                                        metric.achievement >= 100 ? "text-green-600" : "text-orange-600"
                                                    )}>
                                                        {metric.achievement?.toFixed(1)}%
                                                    </span>
                                                </div>
                                                <Progress
                                                    value={Math.min(metric.achievement || 0, 100)}
                                                    className="mt-2"
                                                />
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>

                                {/* Funnel Visualization */}
                                {goalData.details.funnel_steps && goalData.details.funnel_steps.length > 0 && (
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Conversion Funnel</CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="space-y-4">
                                                {goalData.details.funnel_steps.map((step, index) => (
                                                    <div key={step.id} className="relative">
                                                        <div className="flex items-center justify-between mb-2">
                                                            <span className="font-medium">{step.name}</span>
                                                            <span className="text-sm text-muted-foreground">
                                                                {step.value} ({step.conversion_rate?.toFixed(1)}%)
                                                            </span>
                                                        </div>
                                                        <div className="relative h-8 bg-muted rounded">
                                                            <div
                                                                className="absolute inset-y-0 left-0 bg-primary rounded"
                                                                style={{
                                                                    width: `${(step.value / goalData.details.funnel_steps[0].value) * 100}%`
                                                                }}
                                                            />
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </CardContent>
                                    </Card>
                                )}
                            </>
                        )}
                    </TabsContent>

                    <TabsContent value="trends" className="space-y-4">
                        <div className="flex items-center justify-between mb-4">
                            <Select value={selectedPeriod} onValueChange={setSelectedPeriod}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="7d">Letzte 7 Tage</SelectItem>
                                    <SelectItem value="30d">Letzte 30 Tage</SelectItem>
                                    <SelectItem value="90d">Letzte 90 Tage</SelectItem>
                                    <SelectItem value="365d">Letztes Jahr</SelectItem>
                                </SelectContent>
                            </Select>
                            <div className="flex gap-2">
                                <Button variant="outline" size="sm" onClick={() => exportData('csv')}>
                                    <Download className="h-4 w-4 mr-2" />
                                    CSV
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => exportData('pdf')}>
                                    <Download className="h-4 w-4 mr-2" />
                                    PDF
                                </Button>
                            </div>
                        </div>

                        {selectedGoal && goalData.trends && (
                            <>
                                {/* Trend Chart */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Fortschrittsverlauf</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <AreaChart data={goalData.trends.data}>
                                                <defs>
                                                    <linearGradient id="colorProgress" x1="0" y1="0" x2="0" y2="1">
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
                                                    dataKey="achievement"
                                                    stroke="#3b82f6"
                                                    fillOpacity={1}
                                                    fill="url(#colorProgress)"
                                                />
                                            </AreaChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>

                                {/* Metric Comparison */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Metrik-Vergleich</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <RadarChart data={goalData.trends.metrics}>
                                                <PolarGrid />
                                                <PolarAngleAxis dataKey="name" />
                                                <PolarRadiusAxis angle={90} domain={[0, 100]} />
                                                <Radar
                                                    name="Aktuell"
                                                    dataKey="current"
                                                    stroke="#3b82f6"
                                                    fill="#3b82f6"
                                                    fillOpacity={0.6}
                                                />
                                                <Radar
                                                    name="Ziel"
                                                    dataKey="target"
                                                    stroke="#10b981"
                                                    fill="#10b981"
                                                    fillOpacity={0.6}
                                                />
                                                <Legend />
                                            </RadarChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>
                            </>
                        )}
                    </TabsContent>

                    <TabsContent value="projections" className="space-y-4">
                        {selectedGoal && goalData.projections && (
                            <>
                                {/* Projection Summary */}
                                <div className="grid gap-4 md:grid-cols-3">
                                    <Card>
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-sm font-medium">
                                                Voraussichtliches Erreichen
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="text-2xl font-bold">
                                                {goalData.projections.estimated_completion_date || 'N/A'}
                                            </div>
                                            <p className="text-sm text-muted-foreground mt-1">
                                                {goalData.projections.days_to_completion} Tage verbleibend
                                            </p>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-sm font-medium">
                                                Konfidenz
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className={cn(
                                                "text-2xl font-bold",
                                                getConfidenceColor(goalData.projections.confidence)
                                            )}>
                                                {goalData.projections.confidence}%
                                            </div>
                                            <p className="text-sm text-muted-foreground mt-1">
                                                Basierend auf aktuellen Trends
                                            </p>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardHeader className="pb-2">
                                            <CardTitle className="text-sm font-medium">
                                                Benötigte Wachstumsrate
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="text-2xl font-bold flex items-center">
                                                {goalData.projections.required_growth_rate > 0 ? (
                                                    <ArrowUp className="h-5 w-5 text-green-600 mr-1" />
                                                ) : (
                                                    <ArrowDown className="h-5 w-5 text-red-600 mr-1" />
                                                )}
                                                {Math.abs(goalData.projections.required_growth_rate)}%
                                            </div>
                                            <p className="text-sm text-muted-foreground mt-1">
                                                Pro Woche
                                            </p>
                                        </CardContent>
                                    </Card>
                                </div>

                                {/* Projection Chart */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Projektionsverlauf</CardTitle>
                                        <CardDescription>
                                            Voraussichtliche Entwicklung basierend auf aktuellen Trends
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <ResponsiveContainer width="100%" height={300}>
                                            <LineChart data={goalData.projections.projection_data}>
                                                <CartesianGrid strokeDasharray="3 3" />
                                                <XAxis dataKey="date" />
                                                <YAxis />
                                                <Tooltip />
                                                <Legend />
                                                <Line
                                                    type="monotone"
                                                    dataKey="actual"
                                                    stroke="#3b82f6"
                                                    strokeWidth={2}
                                                    name="Aktuell"
                                                />
                                                <Line
                                                    type="monotone"
                                                    dataKey="projected"
                                                    stroke="#10b981"
                                                    strokeDasharray="5 5"
                                                    strokeWidth={2}
                                                    name="Projektion"
                                                />
                                                <Line
                                                    type="monotone"
                                                    dataKey="target"
                                                    stroke="#ef4444"
                                                    strokeDasharray="3 3"
                                                    name="Ziel"
                                                />
                                            </LineChart>
                                        </ResponsiveContainer>
                                    </CardContent>
                                </Card>

                                {/* Recommendations */}
                                {goalData.projections.recommendations && (
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Empfehlungen</CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="space-y-3">
                                                {goalData.projections.recommendations.map((rec, index) => (
                                                    <div key={index} className="flex items-start gap-3">
                                                        <Info className="h-5 w-5 text-blue-600 mt-0.5" />
                                                        <div>
                                                            <p className="font-medium">{rec.title}</p>
                                                            <p className="text-sm text-muted-foreground">
                                                                {rec.description}
                                                            </p>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </CardContent>
                                    </Card>
                                )}
                            </>
                        )}
                    </TabsContent>
                </Tabs>
            )}
        </div>
    );
}