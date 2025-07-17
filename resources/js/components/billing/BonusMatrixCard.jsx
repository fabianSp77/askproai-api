import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { TrendingUp, Gift, Star, Zap } from 'lucide-react';
import { cn } from '../../lib/utils';

export const BonusMatrixCard = ({ bonusRules, currentBalance }) => {
    // Hardcoded bonus tiers to match backend
    const bonusTiers = [
        { min: 250, percentage: 10, icon: Gift, color: 'text-green-600' },
        { min: 500, percentage: 15, icon: Gift, color: 'text-green-600' },
        { min: 1000, percentage: 20, icon: Star, color: 'text-blue-600' },
        { min: 2000, percentage: 30, icon: Star, color: 'text-blue-600' },
        { min: 3000, percentage: 40, icon: Zap, color: 'text-purple-600' },
        { min: 5000, percentage: 50, icon: Zap, color: 'text-purple-600' }
    ];

    return (
        <Card className="overflow-hidden">
            <CardHeader className="bg-gradient-to-br from-green-50 via-blue-50 to-purple-50 border-b">
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <div className="p-2 bg-gradient-to-br from-green-500 to-blue-600 rounded-lg text-white">
                                <TrendingUp className="h-5 w-5" />
                            </div>
                            Bonus-Programm
                        </CardTitle>
                        <CardDescription className="mt-1">
                            Je mehr Sie aufladen, desto mehr Bonus erhalten Sie
                        </CardDescription>
                    </div>
                    <Badge variant="secondary" className="text-sm bg-green-100 text-green-700 border-green-200">
                        <Zap className="h-3 w-3 mr-1" />
                        Aktiv
                    </Badge>
                </div>
            </CardHeader>
            <CardContent className="pt-6">
                <div className="space-y-3">
                    {bonusTiers.map((tier, index) => {
                        const Icon = tier.icon;
                        const isHighlighted = index === 1 || index === 3; // Highlight popular tiers
                        const isEnterprise = tier.min === 1000;
                        const isBeliebt = tier.min === 250;
                        
                        return (
                            <div
                                key={tier.min}
                                className={cn(
                                    "relative flex items-center justify-between p-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02]",
                                    isHighlighted 
                                        ? "bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-primary/30 shadow-md" 
                                        : "bg-gray-50 hover:bg-gray-100 border border-gray-200"
                                )}
                            >
                                {/* Highlight Badge */}
                                {(isBeliebt || isEnterprise) && (
                                    <div className="absolute -top-2 -right-2">
                                        <div className={cn(
                                            "px-2 py-1 rounded-full text-xs font-bold text-white shadow-lg",
                                            isBeliebt ? "bg-blue-600" : "bg-purple-600"
                                        )}>
                                            {isBeliebt ? "BELIEBT" : "ENTERPRISE"}
                                        </div>
                                    </div>
                                )}
                                
                                <div className="flex items-center gap-4">
                                    <div className={cn(
                                        "p-3 rounded-xl",
                                        isHighlighted ? "bg-white shadow-sm" : "bg-white/80"
                                    )}>
                                        <Icon className={cn("h-6 w-6", tier.color)} />
                                    </div>
                                    <div>
                                        <span className="font-semibold text-base">
                                            ab €{tier.min.toLocaleString('de-DE')}
                                        </span>
                                        <div className="text-xs text-muted-foreground mt-0.5">
                                            Aufladung
                                        </div>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className={cn(
                                        "text-2xl font-bold",
                                        tier.color,
                                        isHighlighted && "text-3xl"
                                    )}>
                                        {tier.percentage}%
                                    </div>
                                    <div className="text-sm font-medium text-green-600">
                                        +€{Math.floor(tier.min * tier.percentage / 100)} Bonus
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
                
                <div className="mt-6 p-4 bg-muted/50 rounded-lg">
                    <h4 className="font-medium text-sm mb-2">So funktioniert's:</h4>
                    <ul className="space-y-1 text-sm text-muted-foreground">
                        <li>• Bonus wird automatisch gutgeschrieben</li>
                        <li>• Gilt für alle Aufladungen</li>
                        <li>• Keine versteckten Bedingungen</li>
                    </ul>
                </div>
            </CardContent>
        </Card>
    );
};