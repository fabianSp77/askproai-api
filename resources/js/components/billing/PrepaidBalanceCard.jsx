import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Progress } from '../ui/progress';
import { Euro, Gift, Clock } from 'lucide-react';
import { formatCurrency } from '../../utils/currency';

export const PrepaidBalanceCard = ({ prepaidBalance, balanceMonitoring }) => {
    if (!prepaidBalance) return null;

    const totalBalance = parseFloat(prepaidBalance.total_balance || 0);
    const bonusBalance = parseFloat(prepaidBalance.bonus_balance || 0);
    const normalBalance = parseFloat(prepaidBalance.balance || 0);
    const availableMinutes = balanceMonitoring?.available_minutes || 0;

    return (
        <Card className="h-full">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Euro className="h-5 w-5" />
                    Prepaid Guthaben
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                {/* Main Balance */}
                <div>
                    <div className="flex justify-between items-baseline mb-2">
                        <span className="text-sm text-muted-foreground">Normales Guthaben</span>
                        <span className="text-2xl font-bold">€{formatCurrency(normalBalance)}</span>
                    </div>
                    
                    {/* Bonus Balance */}
                    {bonusBalance > 0 && (
                        <div className="flex justify-between items-center p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                            <div className="flex items-center gap-2">
                                <Gift className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                <span className="text-sm font-medium">Bonus Guthaben</span>
                            </div>
                            <span className="font-semibold text-amber-600 dark:text-amber-400">
                                €{formatCurrency(bonusBalance)}
                            </span>
                        </div>
                    )}
                </div>

                {/* Total Balance */}
                <div className="border-t pt-4">
                    <div className="flex justify-between items-baseline">
                        <span className="text-sm font-medium">Gesamtguthaben</span>
                        <span className="text-3xl font-bold text-primary">
                            €{formatCurrency(totalBalance)}
                        </span>
                    </div>
                </div>

                {/* Available Minutes */}
                <div className="border-t pt-4">
                    <div className="flex items-center gap-2 mb-2">
                        <Clock className="h-4 w-4 text-muted-foreground" />
                        <span className="text-sm text-muted-foreground">Verfügbare Minuten</span>
                    </div>
                    <div className="text-xl font-semibold">{availableMinutes} Min.</div>
                </div>

                {/* Low Balance Warning */}
                {totalBalance < 10 && (
                    <div className="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <p className="text-sm text-red-600 dark:text-red-400 font-medium">
                            Niedriges Guthaben! Bitte aufladen.
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
};