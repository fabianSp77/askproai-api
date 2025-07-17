import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Progress } from '../ui/progress';
import { 
    TrendingUp, 
    Phone, 
    Clock, 
    Euro,
    BarChart3
} from 'lucide-react';

export const UsageStatsCard = ({ usage, monthlyUsage }) => {
    const totalMinutes = monthlyUsage?.total_duration_minutes || 0;
    const totalCalls = monthlyUsage?.total_calls || 0;
    const totalCharges = monthlyUsage?.total_charges || 0;
    const avgCallDuration = totalCalls > 0 ? (totalMinutes / totalCalls).toFixed(1) : 0;
    
    // Calculate daily average (assuming 30 days)
    const currentDay = new Date().getDate();
    const dailyAvgCalls = totalCalls > 0 ? (totalCalls / currentDay).toFixed(1) : 0;
    const dailyAvgCost = totalCharges > 0 ? (totalCharges / currentDay).toFixed(2) : 0;

    return (
        <Card className="h-full">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <BarChart3 className="h-5 w-5" />
                    Nutzungsstatistiken
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                {/* Call Volume */}
                <div>
                    <div className="flex justify-between items-baseline mb-2">
                        <span className="text-sm text-muted-foreground flex items-center gap-1">
                            <Phone className="h-3 w-3" />
                            Anrufvolumen
                        </span>
                        <span className="text-sm font-medium">{totalCalls} Anrufe</span>
                    </div>
                    <Progress value={Math.min(100, (totalCalls / 1000) * 100)} className="h-2" />
                    <p className="text-xs text-muted-foreground mt-1">
                        Ø {dailyAvgCalls} Anrufe/Tag
                    </p>
                </div>

                {/* Total Minutes */}
                <div>
                    <div className="flex justify-between items-baseline mb-2">
                        <span className="text-sm text-muted-foreground flex items-center gap-1">
                            <Clock className="h-3 w-3" />
                            Gesprächsminuten
                        </span>
                        <span className="text-sm font-medium">{totalMinutes.toFixed(0)} Min.</span>
                    </div>
                    <Progress value={Math.min(100, (totalMinutes / 5000) * 100)} className="h-2" />
                    <p className="text-xs text-muted-foreground mt-1">
                        Ø {avgCallDuration} Min./Anruf
                    </p>
                </div>

                {/* Cost Analysis */}
                <div>
                    <div className="flex justify-between items-baseline mb-2">
                        <span className="text-sm text-muted-foreground flex items-center gap-1">
                            <Euro className="h-3 w-3" />
                            Kostenanalyse
                        </span>
                        <span className="text-sm font-medium">€{totalCharges.toFixed(2)}</span>
                    </div>
                    <Progress value={Math.min(100, (totalCharges / 500) * 100)} className="h-2" />
                    <p className="text-xs text-muted-foreground mt-1">
                        Ø €{dailyAvgCost}/Tag
                    </p>
                </div>

                {/* Trend Indicator */}
                <div className="border-t pt-4">
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Trend</span>
                        <div className="flex items-center gap-1 text-green-600">
                            <TrendingUp className="h-4 w-4" />
                            <span className="text-sm font-medium">Stabil</span>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
};