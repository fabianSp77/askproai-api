import React, { useState, useEffect } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '../ui/dialog';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { Switch } from '../ui/switch';
import { Badge } from '../ui/badge';
import { Alert, AlertDescription } from '../ui/alert';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../ui/select';
import { CreditCard, Info, AlertTriangle } from 'lucide-react';

export const AutoTopupSettingsModal = ({ 
    open, 
    onOpenChange, 
    onSave, 
    loading,
    currentSettings,
    paymentMethods 
}) => {
    const [settings, setSettings] = useState({
        auto_topup_enabled: false,
        auto_topup_threshold: 20,
        auto_topup_amount: 50,
        auto_topup_daily_limit: 1,
        auto_topup_monthly_limit: 5,
        payment_method_id: null
    });

    useEffect(() => {
        if (currentSettings) {
            setSettings({
                auto_topup_enabled: currentSettings.auto_topup_enabled || false,
                auto_topup_threshold: currentSettings.auto_topup_threshold || 20,
                auto_topup_amount: currentSettings.auto_topup_amount || 50,
                auto_topup_daily_limit: currentSettings.auto_topup_daily_limit || 1,
                auto_topup_monthly_limit: currentSettings.auto_topup_monthly_limit || 5,
                payment_method_id: currentSettings.payment_method_id || paymentMethods?.[0]?.id || null
            });
        }
    }, [currentSettings, paymentMethods]);

    const handleSubmit = () => {
        onSave(settings);
    };

    const handleClose = () => {
        onOpenChange(false);
    };

    const hasPaymentMethods = paymentMethods && paymentMethods.length > 0;
    const canEnableAutoTopup = hasPaymentMethods && settings.payment_method_id;

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>Auto-Topup Einstellungen</DialogTitle>
                    <DialogDescription>
                        Konfigurieren Sie die automatische Aufladung Ihres Guthabens.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6 py-4">
                    {/* Enable/Disable Auto-Topup */}
                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="auto-topup-enabled" className="text-base">
                                Auto-Topup aktivieren
                            </Label>
                            <p className="text-sm text-muted-foreground">
                                Automatisch aufladen, wenn das Guthaben niedrig ist
                            </p>
                        </div>
                        <Switch
                            id="auto-topup-enabled"
                            checked={settings.auto_topup_enabled}
                            onCheckedChange={(checked) => 
                                setSettings(prev => ({ ...prev, auto_topup_enabled: checked }))
                            }
                            disabled={!canEnableAutoTopup}
                        />
                    </div>

                    {!hasPaymentMethods && (
                        <Alert variant="warning">
                            <AlertTriangle className="h-4 w-4" />
                            <AlertDescription>
                                Sie müssen zuerst eine Zahlungsmethode hinterlegen, bevor Sie Auto-Topup aktivieren können.
                                <Button 
                                    variant="link" 
                                    className="p-0 h-auto font-medium"
                                    onClick={() => {/* Navigate to payment methods */}}
                                >
                                    Zahlungsmethode hinzufügen
                                </Button>
                            </AlertDescription>
                        </Alert>
                    )}

                    {settings.auto_topup_enabled && hasPaymentMethods && (
                        <>
                            {/* Threshold */}
                            <div>
                                <Label htmlFor="threshold">
                                    Schwellenwert (Guthaben fällt unter)
                                </Label>
                                <div className="relative mt-2">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                        €
                                    </span>
                                    <Input
                                        id="threshold"
                                        type="number"
                                        min="10"
                                        max="500"
                                        step="10"
                                        value={settings.auto_topup_threshold}
                                        onChange={(e) => 
                                            setSettings(prev => ({ 
                                                ...prev, 
                                                auto_topup_threshold: parseInt(e.target.value) || 20 
                                            }))
                                        }
                                        className="pl-8"
                                    />
                                </div>
                                <p className="text-sm text-muted-foreground mt-1">
                                    Wenn Ihr Guthaben unter diesen Betrag fällt, wird automatisch aufgeladen.
                                </p>
                            </div>

                            {/* Amount */}
                            <div>
                                <Label htmlFor="amount">
                                    Aufladebetrag
                                </Label>
                                <div className="relative mt-2">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                        €
                                    </span>
                                    <Input
                                        id="amount"
                                        type="number"
                                        min="50"
                                        max="5000"
                                        step="50"
                                        value={settings.auto_topup_amount}
                                        onChange={(e) => 
                                            setSettings(prev => ({ 
                                                ...prev, 
                                                auto_topup_amount: parseInt(e.target.value) || 50 
                                            }))
                                        }
                                        className="pl-8"
                                    />
                                </div>
                            </div>

                            {/* Daily Limit */}
                            <div>
                                <Label htmlFor="daily-limit">
                                    Maximale Aufladungen pro Tag
                                </Label>
                                <Input
                                    id="daily-limit"
                                    type="number"
                                    min="1"
                                    max="5"
                                    value={settings.auto_topup_daily_limit}
                                    onChange={(e) => 
                                        setSettings(prev => ({ 
                                            ...prev, 
                                            auto_topup_daily_limit: parseInt(e.target.value) || 1 
                                        }))
                                    }
                                    className="mt-2"
                                />
                            </div>

                            {/* Monthly Limit */}
                            <div>
                                <Label htmlFor="monthly-limit">
                                    Maximale Aufladungen pro Monat
                                </Label>
                                <Input
                                    id="monthly-limit"
                                    type="number"
                                    min="5"
                                    max="30"
                                    value={settings.auto_topup_monthly_limit}
                                    onChange={(e) => 
                                        setSettings(prev => ({ 
                                            ...prev, 
                                            auto_topup_monthly_limit: parseInt(e.target.value) || 5 
                                        }))
                                    }
                                    className="mt-2"
                                />
                            </div>

                            {/* Payment Method */}
                            {hasPaymentMethods && (
                                <div>
                                    <Label htmlFor="payment-method">
                                        Zahlungsmethode
                                    </Label>
                                    <Select
                                        value={settings.payment_method_id}
                                        onValueChange={(value) => 
                                            setSettings(prev => ({ ...prev, payment_method_id: value }))
                                        }
                                    >
                                        <SelectTrigger className="mt-2">
                                            <SelectValue placeholder="Zahlungsmethode auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(paymentMethods || []).map(pm => (
                                                <SelectItem key={pm.id} value={pm.id}>
                                                    <div className="flex items-center gap-2">
                                                        <CreditCard className="h-4 w-4" />
                                                        <span>{pm.brand} •••• {pm.last4}</span>
                                                        {pm.is_default && (
                                                            <Badge variant="secondary" className="ml-2">
                                                                Standard
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                        </>
                    )}

                    {/* Info Alert */}
                    <Alert>
                        <Info className="h-4 w-4" />
                        <AlertDescription>
                            Die Limits schützen Sie vor unerwarteten Kosten. Sie können diese Einstellungen jederzeit ändern.
                        </AlertDescription>
                    </Alert>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={handleClose}>
                        Abbrechen
                    </Button>
                    <Button 
                        onClick={handleSubmit} 
                        disabled={loading || (settings.auto_topup_enabled && !canEnableAutoTopup)}
                    >
                        Speichern
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};