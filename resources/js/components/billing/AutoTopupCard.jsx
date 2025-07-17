import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { RefreshCw, Settings, CreditCard } from 'lucide-react';
import { formatCurrency } from '../../utils/currency';

export const AutoTopupCard = ({ prepaidBalance, billingRate, onSettingsClick }) => {
    const isEnabled = prepaidBalance?.auto_topup_enabled || false;
    const threshold = prepaidBalance?.auto_topup_threshold || 0;
    const amount = prepaidBalance?.auto_topup_amount || 0;
    const ratePerMinute = parseFloat(billingRate?.rate_per_minute || 0.42);

    return (
        <Card className="h-full">
            <CardHeader>
                <div className="flex justify-between items-start">
                    <CardTitle className="flex items-center gap-2">
                        <RefreshCw className="h-5 w-5" />
                        Auto-Topup
                    </CardTitle>
                    <Badge variant={isEnabled ? "default" : "secondary"}>
                        {isEnabled ? "Aktiv" : "Inaktiv"}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                {/* Billing Rate */}
                <div>
                    <span className="text-sm text-muted-foreground">Tarif</span>
                    <div className="text-2xl font-bold">€{formatCurrency(ratePerMinute)}/Min</div>
                </div>

                {/* Auto-Topup Settings */}
                {isEnabled && (
                    <div className="space-y-3 p-4 bg-muted/50 rounded-lg">
                        <div className="flex justify-between">
                            <span className="text-sm">Schwellenwert</span>
                            <span className="font-medium">€{threshold}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm">Aufladebetrag</span>
                            <span className="font-medium">€{amount}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-sm">Zahlungsmethode</span>
                            <div className="flex items-center gap-1">
                                <CreditCard className="h-4 w-4" />
                                <span className="text-sm">•••• {prepaidBalance?.payment_method_last4 || '****'}</span>
                            </div>
                        </div>
                    </div>
                )}

                {/* Action Button */}
                <Button 
                    onClick={onSettingsClick}
                    variant={isEnabled ? "outline" : "default"}
                    className="w-full"
                >
                    <Settings className="h-4 w-4 mr-2" />
                    {isEnabled ? 'Einstellungen bearbeiten' : 'Auto-Topup aktivieren'}
                </Button>
            </CardContent>
        </Card>
    );
};