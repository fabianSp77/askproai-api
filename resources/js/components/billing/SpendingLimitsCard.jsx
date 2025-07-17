import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Progress } from '../ui/progress';
import { Euro, AlertTriangle } from 'lucide-react';
import { formatCurrency } from '../../utils/currency';

export const SpendingLimitsCard = ({ spendingLimits }) => {
    if (!spendingLimits) return null;

    const getLimitStatus = (percentage) => {
        if (percentage >= 90) return 'danger';
        if (percentage >= 75) return 'warning';
        return 'normal';
    };

    const getLimitColor = (percentage) => {
        if (percentage >= 90) return 'text-red-600 dark:text-red-400';
        if (percentage >= 75) return 'text-amber-600 dark:text-amber-400';
        return 'text-green-600 dark:text-green-400';
    };

    return (
        <Card className="h-full">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Euro className="h-5 w-5" />
                    Ausgabenlimits
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                {/* Daily Limit */}
                {spendingLimits.daily && (
                    <div>
                        <div className="flex justify-between items-baseline mb-2">
                            <span className="text-sm text-muted-foreground">Heute</span>
                            <span className={`text-sm font-medium ${getLimitColor(spendingLimits.daily.percentage)}`}>
                                €{formatCurrency(spendingLimits.daily.spent)} / €{formatCurrency(spendingLimits.daily.limit, 0)}
                            </span>
                        </div>
                        <Progress 
                            value={Math.min(100, Math.round((Number(spendingLimits.daily.spent) / Number(spendingLimits.daily.limit)) * 100))}
                            className="h-2"
                        />
                        {spendingLimits.daily.percentage >= 90 && (
                            <div className="flex items-center gap-1 mt-1">
                                <AlertTriangle className="h-3 w-3 text-red-500" />
                                <span className="text-xs text-red-500">Limit fast erreicht!</span>
                            </div>
                        )}
                    </div>
                )}

                {/* Weekly Limit */}
                {spendingLimits.weekly && (
                    <div>
                        <div className="flex justify-between items-baseline mb-2">
                            <span className="text-sm text-muted-foreground">Diese Woche</span>
                            <span className={`text-sm font-medium ${getLimitColor(spendingLimits.weekly.percentage)}`}>
                                €{formatCurrency(spendingLimits.weekly.spent)} / €{formatCurrency(spendingLimits.weekly.limit, 0)}
                            </span>
                        </div>
                        <Progress 
                            value={Math.min(100, Math.round((Number(spendingLimits.weekly.spent) / Number(spendingLimits.weekly.limit)) * 100))}
                            className="h-2"
                        />
                        {spendingLimits.weekly.percentage >= 90 && (
                            <div className="flex items-center gap-1 mt-1">
                                <AlertTriangle className="h-3 w-3 text-red-500" />
                                <span className="text-xs text-red-500">Wochenlimit fast erreicht!</span>
                            </div>
                        )}
                    </div>
                )}

                {/* Monthly Limit */}
                {spendingLimits.monthly && (
                    <div>
                        <div className="flex justify-between items-baseline mb-2">
                            <span className="text-sm text-muted-foreground">Dieser Monat</span>
                            <span className={`text-sm font-medium ${getLimitColor(spendingLimits.monthly.percentage)}`}>
                                €{formatCurrency(spendingLimits.monthly.spent)} / €{formatCurrency(spendingLimits.monthly.limit, 0)}
                            </span>
                        </div>
                        <Progress 
                            value={Math.min(100, Math.round((Number(spendingLimits.monthly.spent) / Number(spendingLimits.monthly.limit)) * 100))}
                            className="h-2"
                        />
                        {spendingLimits.monthly.percentage >= 90 && (
                            <div className="flex items-center gap-1 mt-1">
                                <AlertTriangle className="h-3 w-3 text-red-500" />
                                <span className="text-xs text-red-500">Monatslimit fast erreicht!</span>
                            </div>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
};