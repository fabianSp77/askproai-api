import React, { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { Progress } from '../ui/progress';
import { Switch } from '../ui/switch';
import { useGoals } from '../../hooks/useGoals';
import { useAuth } from '../../hooks/useAuth';
import { 
    Target, 
    TrendingUp, 
    TrendingDown,
    MoreVertical,
    Copy,
    Trash2,
    Calendar,
    Activity,
    Loader2,
    AlertCircle,
    CheckCircle2,
    Clock
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '../ui/dropdown-menu';
import { cn } from '../../lib/utils';

export default function GoalDashboard({ compact = false, onEdit }) {
    const { csrfToken } = useAuth();
    const { 
        activeGoals, 
        loading, 
        toggleGoalActive, 
        deleteGoal,
        fetchGoalDetails,
        trends
    } = useGoals(csrfToken);
    
    const [selectedGoals, setSelectedGoals] = useState([]);
    const [goalDetails, setGoalDetails] = useState({});

    useEffect(() => {
        // Fetch details for active goals
        if (activeGoals && Array.isArray(activeGoals)) {
            activeGoals.forEach(goal => {
                if (!goalDetails[goal.id]) {
                    fetchGoalDetails(goal.id).then(details => {
                    setGoalDetails(prev => ({
                        ...prev,
                        [goal.id]: details
                    }));
                });
            }
        });
        }
    }, [activeGoals]);

    const handleDuplicate = (goal) => {
        // Implementation for duplicating a goal
        // TODO: Implement goal duplication logic
    };

    const getGoalIcon = (type) => {
        switch (type) {
            case 'max_appointments':
                return Target;
            case 'revenue_growth':
                return TrendingUp;
            case 'conversion_optimization':
                return Activity;
            default:
                return Target;
        }
    };

    const getStatusColor = (achievement) => {
        if (achievement >= 100) return 'text-green-600 dark:text-green-400';
        if (achievement >= 75) return 'text-yellow-600 dark:text-yellow-400';
        if (achievement >= 50) return 'text-orange-600 dark:text-orange-400';
        return 'text-red-600 dark:text-red-400';
    };

    const getProgressColor = (achievement) => {
        if (achievement >= 100) return 'bg-green-600';
        if (achievement >= 75) return 'bg-yellow-600';
        if (achievement >= 50) return 'bg-orange-600';
        return 'bg-red-600';
    };

    const calculateDaysRemaining = (endDate) => {
        const end = new Date(endDate);
        const today = new Date();
        const diffTime = Math.abs(end - today);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    };

    const formatMetricValue = (value, type) => {
        switch (type) {
            case 'currency':
                return `€${value.toFixed(2)}`;
            case 'percentage':
                return `${value.toFixed(1)}%`;
            default:
                return value.toString();
        }
    };

    if (loading && (!activeGoals || activeGoals.length === 0)) {
        return (
            <Card>
                <CardContent className="flex items-center justify-center py-8">
                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </CardContent>
            </Card>
        );
    }

    if (!activeGoals || activeGoals.length === 0) {
        return (
            <Card>
                <CardContent className="text-center py-8">
                    <AlertCircle className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                    <h3 className="text-lg font-semibold mb-2">Keine aktiven Ziele</h3>
                    <p className="text-muted-foreground">
                        Erstellen Sie Ihr erstes Ziel, um Ihren Fortschritt zu verfolgen.
                    </p>
                </CardContent>
            </Card>
        );
    }

    const displayGoals = compact && activeGoals ? activeGoals.slice(0, 3) : (activeGoals || []);

    return (
        <div className={cn("space-y-4", compact && "space-y-3")}>
            {displayGoals.map((goal) => {
                const Icon = getGoalIcon(goal.type);
                const details = goalDetails[goal.id];
                const achievement = details?.current_achievement || 0;
                const daysRemaining = calculateDaysRemaining(goal.end_date);

                return (
                    <Card key={goal.id} className="relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-32 h-32 -mr-8 -mt-8">
                            <div className={cn(
                                "w-full h-full rounded-full opacity-10",
                                getProgressColor(achievement)
                            )} />
                        </div>
                        
                        <CardHeader className="pb-3">
                            <div className="flex items-start justify-between">
                                <div className="flex items-center gap-3">
                                    <div className={cn(
                                        "p-2 rounded-lg",
                                        achievement >= 100 ? "bg-green-100 dark:bg-green-900" : "bg-muted"
                                    )}>
                                        <Icon className={cn(
                                            "h-5 w-5",
                                            achievement >= 100 ? "text-green-600 dark:text-green-400" : "text-muted-foreground"
                                        )} />
                                    </div>
                                    <div>
                                        <CardTitle className="text-base">{goal.name}</CardTitle>
                                        {goal.description && (
                                            <p className="text-sm text-muted-foreground mt-1">
                                                {goal.description}
                                            </p>
                                        )}
                                    </div>
                                </div>
                                
                                <div className="flex items-center gap-2">
                                    <Switch
                                        checked={goal.is_active}
                                        onCheckedChange={(checked) => toggleGoalActive(goal.id, checked)}
                                        className="scale-90"
                                    />
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon" className="h-8 w-8">
                                                <MoreVertical className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem onClick={() => onEdit?.(goal)}>
                                                Bearbeiten
                                            </DropdownMenuItem>
                                            <DropdownMenuItem onClick={() => handleDuplicate(goal)}>
                                                <Copy className="h-4 w-4 mr-2" />
                                                Duplizieren
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem 
                                                onClick={() => deleteGoal(goal.id)}
                                                className="text-red-600 dark:text-red-400"
                                            >
                                                <Trash2 className="h-4 w-4 mr-2" />
                                                Löschen
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </CardHeader>
                        
                        <CardContent className="space-y-4">
                            {/* Overall Progress */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">Gesamtfortschritt</span>
                                    <span className={cn("font-semibold", getStatusColor(achievement))}>
                                        {achievement.toFixed(1)}%
                                    </span>
                                </div>
                                <Progress 
                                    value={Math.min(achievement, 100)} 
                                    className="h-2"
                                />
                            </div>

                            {/* Metrics */}
                            {details?.metrics && !compact && (
                                <div className="space-y-3">
                                    {details.metrics.slice(0, 2).map((metric) => (
                                        <div key={metric.id} className="space-y-1">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground">{metric.name}</span>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium">
                                                        {formatMetricValue(metric.current_value || 0, metric.type)}
                                                    </span>
                                                    <span className="text-muted-foreground">/</span>
                                                    <span className="text-muted-foreground">
                                                        {formatMetricValue(metric.target_value, metric.type)}
                                                    </span>
                                                </div>
                                            </div>
                                            <Progress 
                                                value={Math.min((metric.achievement || 0), 100)} 
                                                className="h-1.5"
                                            />
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Footer Info */}
                            <div className="flex items-center justify-between pt-2">
                                <div className="flex items-center gap-4 text-sm">
                                    <div className="flex items-center gap-1 text-muted-foreground">
                                        <Calendar className="h-3.5 w-3.5" />
                                        <span>{daysRemaining} Tage</span>
                                    </div>
                                    {details?.trend && (
                                        <div className={cn(
                                            "flex items-center gap-1",
                                            details.trend > 0 ? "text-green-600 dark:text-green-400" : "text-red-600 dark:text-red-400"
                                        )}>
                                            {details.trend > 0 ? (
                                                <TrendingUp className="h-3.5 w-3.5" />
                                            ) : (
                                                <TrendingDown className="h-3.5 w-3.5" />
                                            )}
                                            <span>{Math.abs(details.trend).toFixed(1)}%</span>
                                        </div>
                                    )}
                                </div>
                                
                                {achievement >= 100 && (
                                    <Badge variant="success" className="gap-1">
                                        <CheckCircle2 className="h-3 w-3" />
                                        Erreicht
                                    </Badge>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
            
            {compact && activeGoals && activeGoals.length > 3 && (
                <Button 
                    variant="outline" 
                    className="w-full"
                    onClick={() => window.location.href = '/analytics#goals'}
                >
                    Alle {activeGoals?.length || 0} Ziele anzeigen
                </Button>
            )}
        </div>
    );
}