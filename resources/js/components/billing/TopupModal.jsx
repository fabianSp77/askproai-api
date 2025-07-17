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
import { Badge } from '../ui/badge';
import { Alert, AlertDescription } from '../ui/alert';
import { Card } from '../ui/card';
import { 
    Gift, 
    CreditCard, 
    Info, 
    TrendingUp, 
    Star,
    ArrowRight,
    Calculator,
    Zap,
    Crown
} from 'lucide-react';
import { cn } from '../../lib/utils';
import { formatCurrency } from '../../utils/currency';

export const TopupModal = ({ 
    open, 
    onOpenChange, 
    onTopup, 
    loading,
    suggestedAmounts,
    bonusRules,
    calculateBonus,
    companyId 
}) => {
    const [selectedAmount, setSelectedAmount] = useState(null);
    const [customAmount, setCustomAmount] = useState('');
    const [bonusAmount, setBonusAmount] = useState(0);
    const [bonusPercentage, setBonusPercentage] = useState(0);

    // Predefined amounts with labels
    const amounts = [
        { value: 50, label: null },
        { value: 250, label: 'BELIEBT', popular: true },
        { value: 500, label: null },
        { value: 1000, label: 'ENTERPRISE', enterprise: true },
        { value: 2000, label: null },
        { value: 5000, label: null }
    ];

    // Bonus tiers (hardcoded to match backend)
    const bonusTiers = [
        { min: 50, percentage: 0 },
        { min: 250, percentage: 10 },
        { min: 500, percentage: 15 },
        { min: 1000, percentage: 20 },
        { min: 2000, percentage: 30 },
        { min: 3000, percentage: 40 },
        { min: 5000, percentage: 50 }
    ];

    useEffect(() => {
        const amount = Number(customAmount) || selectedAmount || 0;
        
        // Calculate bonus percentage based on amount
        let percentage = 0;
        for (const tier of bonusTiers) {
            if (amount >= tier.min) {
                percentage = tier.percentage;
            }
        }
        
        setBonusPercentage(percentage);
        setBonusAmount(Math.floor(amount * percentage / 100));
    }, [selectedAmount, customAmount]);

    const handleSubmit = () => {
        const amount = Number(customAmount) || selectedAmount;
        if (amount && amount >= 50) {
            // Instead of using the API, redirect to the public topup page
            if (companyId) {
                // Redirect to the working public topup page with the amount
                window.location.href = `/topup/${companyId}?amount=${amount}`;
            } else {
                // Fallback to API if no company ID (shouldn't happen)
                onTopup(amount);
            }
        }
    };

    const handleClose = () => {
        setSelectedAmount(null);
        setCustomAmount('');
        setBonusAmount(0);
        setBonusPercentage(0);
        onOpenChange(false);
    };

    const totalAmount = (parseFloat(customAmount) || selectedAmount || 0) + bonusAmount;
    const currentAmount = parseFloat(customAmount) || selectedAmount || 0;

    // Calculate how many calls the amount covers (assuming 0.39€/min and 2 min avg call)
    const possibleCalls = totalAmount > 0 ? Math.floor(totalAmount / 0.78) : 0;

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="text-2xl">Guthaben aufladen</DialogTitle>
                    <DialogDescription>
                        Wählen Sie einen Betrag und sehen Sie sofort Ihren Bonus
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6 py-4">
                    {/* Amount Selection */}
                    <div>
                        <Label className="text-base font-medium mb-4 block">
                            Betrag auswählen
                        </Label>
                        <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                            {amounts.map(({ value, label, popular, enterprise }) => {
                                const isSelected = selectedAmount === value && !customAmount;
                                const bonus = Math.floor(value * (bonusTiers.find(t => value >= t.min)?.percentage || 0) / 100);
                                
                                return (
                                    <button
                                        key={value}
                                        onClick={() => {
                                            setSelectedAmount(value);
                                            setCustomAmount('');
                                        }}
                                        className={cn(
                                            "relative p-4 rounded-lg border-2 transition-all overflow-hidden",
                                            isSelected 
                                                ? "border-primary bg-primary/5 shadow-lg" 
                                                : "border-border hover:border-primary/50 hover:shadow-md"
                                        )}
                                    >
                                        {/* Label Header */}
                                        {label && (
                                            <div className={cn(
                                                "absolute top-0 left-0 right-0 py-1 px-2 text-xs font-bold text-white flex items-center justify-center gap-1",
                                                popular && "bg-blue-600",
                                                enterprise && "bg-purple-600"
                                            )}>
                                                {popular && <Star className="h-3 w-3" />}
                                                {enterprise && <Crown className="h-3 w-3" />}
                                                {label}
                                            </div>
                                        )}
                                        
                                        <div className={cn("space-y-1", label && "mt-6")}>
                                            <div className="text-2xl font-bold">€{value}</div>
                                            {bonus > 0 && (
                                                <div className="text-sm text-green-600 font-medium">
                                                    +€{formatCurrency(bonus)} Bonus
                                                </div>
                                            )}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Custom Amount */}
                    <div>
                        <Label htmlFor="custom-amount" className="text-base font-medium">
                            Eigenen Betrag wählen
                        </Label>
                        <div className="relative mt-2">
                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                €
                            </span>
                            <Input
                                id="custom-amount"
                                type="number"
                                min="50"
                                max="10000"
                                step="10"
                                placeholder="z.B. 750"
                                value={customAmount}
                                onChange={(e) => {
                                    const value = e.target.value;
                                    if (value === '' || (Number(value) >= 0 && Number(value) <= 10000)) {
                                        setCustomAmount(value);
                                        setSelectedAmount(null);
                                    }
                                }}
                                className="pl-8 text-lg"
                            />
                        </div>
                        {customAmount && Number(customAmount) < 50 && (
                            <p className="text-sm text-muted-foreground mt-1">
                                Mindestbetrag: €50
                            </p>
                        )}
                        {customAmount && bonusPercentage > 0 && (
                            <p className="text-sm text-green-600 mt-1 font-medium">
                                Bei €{customAmount} erhalten Sie {bonusPercentage}% Bonus
                            </p>
                        )}
                    </div>

                    {/* Live Calculator */}
                    {currentAmount >= 50 && (
                        <Card className="p-6 bg-gradient-to-br from-blue-50 to-purple-50 border-primary/20">
                            <div className="flex items-center gap-2 mb-4">
                                <Calculator className="h-5 w-5 text-primary" />
                                <h3 className="font-semibold text-lg">Ihre Aufladung</h3>
                            </div>
                            
                            <div className="space-y-3">
                                <div className="flex justify-between items-center">
                                    <span className="text-muted-foreground">Sie zahlen:</span>
                                    <span className="text-xl font-bold">€{formatCurrency(currentAmount)}</span>
                                </div>
                                
                                {bonusAmount > 0 && (
                                    <>
                                        <div className="flex justify-between items-center text-green-600">
                                            <span className="flex items-center gap-1">
                                                <Gift className="h-4 w-4" />
                                                Bonus geschenkt ({bonusPercentage}%):
                                            </span>
                                            <span className="text-xl font-bold">+€{formatCurrency(bonusAmount)}</span>
                                        </div>
                                        
                                        <div className="border-t pt-3 flex justify-between items-center">
                                            <span className="font-medium">Guthaben danach:</span>
                                            <span className="text-2xl font-bold text-primary">€{formatCurrency(totalAmount)}</span>
                                        </div>
                                    </>
                                )}
                                
                                <div className="mt-4 p-3 bg-white/70 rounded-lg">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">Mögliche Anrufe:</span>
                                        <span className="font-medium">~{possibleCalls} Gespräche</span>
                                    </div>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Bei ø 2 Min. pro Anruf (0,39€/Min.)
                                    </p>
                                </div>
                            </div>
                        </Card>
                    )}

                    {/* Bonus Matrix Info */}
                    <div className="border rounded-lg p-4 bg-muted/30">
                        <div className="flex items-center gap-2 mb-3">
                            <TrendingUp className="h-5 w-5 text-primary" />
                            <h4 className="font-semibold">Je mehr Sie aufladen, desto mehr sparen Sie!</h4>
                        </div>
                        <div className="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">ab €250:</span>
                                <span className="font-medium text-green-600">10% Bonus</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">ab €500:</span>
                                <span className="font-medium text-green-600">15% Bonus</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">ab €1000:</span>
                                <span className="font-medium text-green-600">20% Bonus</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">ab €2000:</span>
                                <span className="font-medium text-green-600">30% Bonus</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">ab €3000:</span>
                                <span className="font-medium text-green-600">40% Bonus</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">ab €5000:</span>
                                <span className="font-medium text-green-600">50% Bonus</span>
                            </div>
                        </div>
                    </div>

                    {/* Payment Info */}
                    <Alert>
                        <Info className="h-4 w-4" />
                        <AlertDescription>
                            Die Zahlung wird sicher über Stripe abgewickelt. Nach erfolgreicher Zahlung wird Ihr Guthaben sofort aufgeladen.
                        </AlertDescription>
                    </Alert>
                </div>

                <DialogFooter className="flex-col sm:flex-row gap-3">
                    <Button variant="outline" onClick={handleClose} className="w-full sm:w-auto">
                        Abbrechen
                    </Button>
                    <Button 
                        onClick={handleSubmit} 
                        disabled={loading || !currentAmount || currentAmount < 50}
                        className="w-full sm:w-auto text-base"
                        size="lg"
                    >
                        <CreditCard className="h-5 w-5 mr-2" />
                        {currentAmount >= 50 ? (
                            <>Jetzt €{formatCurrency(currentAmount, 0)} aufladen</>
                        ) : (
                            <>Betrag wählen</>
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};