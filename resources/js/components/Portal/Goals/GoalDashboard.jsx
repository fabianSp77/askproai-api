import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../../ui/card';
import { Progress } from '../../ui/progress';
import { Badge } from '../../ui/badge';
import { Button } from '../../ui/button';
import { 
    Target,
    TrendingUp,
    TrendingDown,
    Calendar,
    Trophy,
    AlertTriangle,
    ChevronRight,
    Phone,
    Users,
    DollarSign,
    BarChart3
} from 'lucide-react';
import { useGoals } from '../../../hooks/useGoals';
import { cn } from '../../../lib/utils';
import dayjs from 'dayjs';
import 'dayjs/locale/de';

dayjs.locale('de');

const GoalDashboard = ({ compact = false, onViewDetails }) => {
    const { goals, loading, error } = useGoals();
    const [topGoals, setTopGoals] = useState([]);

    useEffect(() => {
        if (goals && Array.isArray(goals) && goals.length > 0) {
            // Sort goals by priority and progress
            const sorted = [...goals]
                .sort((a, b) => {
                    // Priority order: high > medium > low
                    const priorityOrder = { high: 3, medium: 2, low: 1 };
                    const priorityDiff = (priorityOrder[b.priority] || 0) - (priorityOrder[a.priority] || 0);
                    if (priorityDiff !== 0) return priorityDiff;
                    
                    // Then by progress (lower progress = higher priority)
                    const progressA = a.current_value ? (a.current_value / a.target_value) * 100 : 0;
                    const progressB = b.current_value ? (b.current_value / b.target_value) * 100 : 0;
                    return progressA - progressB;
                })
                .slice(0, compact ? 3 : 5);
            
            setTopGoals(sorted);
        } else {
            setTopGoals([]);
        }
    }, [goals, compact]);

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

    const getProgressColor = (progress) => {
        if (progress >= 90) return 'text-green-600 bg-green-100';
        if (progress >= 70) return 'text-yellow-600 bg-yellow-100';
        return 'text-red-600 bg-red-100';
    };

    const getStatusMessage = (goal, progress) => {
        const daysRemaining = dayjs(goal.ends_at).diff(dayjs(), 'days');
        
        if (progress >= 100) {
            return { text: 'Ziel erreicht!', type: 'success' };
        }
        
        if (daysRemaining <= 0) {
            return { text: 'Abgelaufen', type: 'error' };
        }
        
        if (daysRemaining <= 7) {
            return { text: `${daysRemaining} Tage verbleibend`, type: 'warning' };
        }
        
        const expectedProgress = ((dayjs().diff(goal.starts_at, 'days') / dayjs(goal.ends_at).diff(goal.starts_at, 'days')) * 100);
        
        if (progress < expectedProgress - 20) {
            return { text: 'Hinter Plan', type: 'warning' };
        }
        
        return { text: 'Auf Kurs', type: 'info' };
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

    if (loading) {
        return (
            <Card>
                <CardContent className="p-6">
                    <div className="animate-pulse space-y-4">
                        <div className="h-4 bg-muted rounded w-3/4"></div>
                        <div className="h-4 bg-muted rounded w-1/2"></div>
                        <div className="h-4 bg-muted rounded w-2/3"></div>
                    </div>
                </CardContent>
            </Card>
        );
    }

    if (error) {
        return (
            <Card>
                <CardContent className="p-6">
                    <div className="flex items-center gap-2 text-red-600">
                        <AlertTriangle className="h-5 w-5" />
                        <p>Fehler beim Laden der Ziele</p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    if (topGoals.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Trophy className="h-5 w-5" />
                        Ihre Ziele
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="text-center py-8">
                        <Target className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                        <p className="text-muted-foreground mb-4">Noch keine Ziele definiert</p>
                        {onViewDetails && (
                            <Button onClick={onViewDetails}>
                                Ziele erstellen
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2">
                        <Trophy className="h-5 w-5" />
                        Ihre Ziele
                    </CardTitle>
                    {onViewDetails && (
                        <Button variant="ghost" size="sm" onClick={onViewDetails}>
                            Alle anzeigen
                            <ChevronRight className="h-4 w-4 ml-1" />
                        </Button>
                    )}
                </div>
            </CardHeader>
            <CardContent>
                <div className="space-y-4">
                    {topGoals.map((goal) => {
                        const Icon = getGoalIcon(goal.type);
                        const progress = goal.current_value ? (goal.current_value / goal.target_value) * 100 : 0;
                        const status = getStatusMessage(goal, progress);
                        
                        return (
                            <div key={goal.id} className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <div className={cn(
                                            "p-1.5 rounded-lg",
                                            getProgressColor(progress)
                                        )}>
                                            <Icon className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p className="font-medium text-sm">{goal.name}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {getGoalTypeLabel(goal.type)} • {goal.target_period === 'month' ? 'Monatlich' : 'Wöchentlich'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="font-medium text-sm">
                                            {goal.current_value || 0} / {goal.target_value}
                                        </p>
                                        <Badge 
                                            variant={
                                                status.type === 'success' ? 'default' :
                                                status.type === 'warning' ? 'secondary' :
                                                status.type === 'error' ? 'destructive' :
                                                'outline'
                                            }
                                            className="text-xs"
                                        >
                                            {status.text}
                                        </Badge>
                                    </div>
                                </div>
                                <Progress value={progress} className="h-2" />
                            </div>
                        );
                    })}
                </div>

                {/* Summary Stats */}
                {!compact && goals && Array.isArray(goals) && goals.length > 0 && (
                    <div className="grid grid-cols-3 gap-4 mt-6 pt-6 border-t">
                        <div className="text-center">
                            <p className="text-2xl font-bold text-green-600">
                                {goals.filter(g => {
                                    const progress = g.current_value ? (g.current_value / g.target_value) * 100 : 0;
                                    return progress >= 100;
                                }).length}
                            </p>
                            <p className="text-xs text-muted-foreground">Erreicht</p>
                        </div>
                        <div className="text-center">
                            <p className="text-2xl font-bold text-yellow-600">
                                {goals.filter(g => {
                                    const progress = g.current_value ? (g.current_value / g.target_value) * 100 : 0;
                                    return progress < 100 && progress >= 50;
                                }).length}
                            </p>
                            <p className="text-xs text-muted-foreground">In Arbeit</p>
                        </div>
                        <div className="text-center">
                            <p className="text-2xl font-bold text-red-600">
                                {goals.filter(g => {
                                    const progress = g.current_value ? (g.current_value / g.target_value) * 100 : 0;
                                    const daysRemaining = dayjs(g.ends_at).diff(dayjs(), 'days');
                                    return progress < 50 && daysRemaining <= 7;
                                }).length}
                            </p>
                            <p className="text-xs text-muted-foreground">Gefährdet</p>
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
};

export default GoalDashboard;