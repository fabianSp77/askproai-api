import React, { useState } from 'react';
import { Button } from '../../../components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Alert, AlertDescription } from '../../../components/ui/alert';
import { PrepaidBalanceCard } from '../../../components/billing/PrepaidBalanceCard';
import { AutoTopupCard } from '../../../components/billing/AutoTopupCard';
import { SpendingLimitsCard } from '../../../components/billing/SpendingLimitsCard';
import { TransactionHistory } from '../../../components/billing/TransactionHistory';
import { AutoTopupSettingsModal } from '../../../components/billing/AutoTopupSettingsModal';
import { BalanceHeader } from '../../../components/billing/BalanceHeader';
import { BonusMatrixCard } from '../../../components/billing/BonusMatrixCard';
import { UsageStatsCard } from '../../../components/billing/UsageStatsCard';
import { TopupEmbed } from '../../../components/billing/TopupEmbed';
import { useBilling } from '../../../hooks/useBilling';
import { useAuth } from '../../../hooks/useAuth.jsx';
import { 
    CreditCard, 
    RefreshCw, 
    Plus,
    AlertTriangle,
    Phone,
    Clock,
    ArrowUpRight,
    TrendingUp,
    TrendingDown,
    Calendar,
    Activity,
    Euro
} from 'lucide-react';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import { formatCurrency, getNumericValue } from '../../../utils/currency';

dayjs.locale('de');

const BillingIndex = () => {
    const { csrfToken } = useAuth();
    const {
        loading,
        error,
        billingData,
        transactions,
        usage,
        topup,
        updateAutoTopup,
        refresh
    } = useBilling(csrfToken);

    const [autoTopupModalOpen, setAutoTopupModalOpen] = useState(false);
    const [showTopupEmbed, setShowTopupEmbed] = useState(false);

    const calculateBonusDisplay = (amount) => {
        if (!amount) return 0;
        
        // Hardcoded bonus tiers matching backend
        let percentage = 0;
        if (amount >= 5000) percentage = 50;
        else if (amount >= 3000) percentage = 40;
        else if (amount >= 2000) percentage = 30;
        else if (amount >= 1000) percentage = 20;
        else if (amount >= 500) percentage = 15;
        else if (amount >= 250) percentage = 10;
        
        return amount * percentage / 100;
    };


    const handleAutoTopupSave = async (settings) => {
        try {
            await updateAutoTopup(settings);
            setAutoTopupModalOpen(false);
        } catch (error) {
            // Auto-topup update failed - error handled by useBilling hook
        }
    };

    if (error) {
        return (
            <div className="p-6">
                <Alert variant="destructive">
                    <AlertTriangle className="h-4 w-4" />
                    <AlertDescription>
                        Fehler beim Laden der Abrechnungsdaten: {error}
                    </AlertDescription>
                </Alert>
            </div>
        );
    }

    const currentBalance = getNumericValue(billingData, 'prepaid_balance.current_balance', 0);
    const bonusBalance = getNumericValue(billingData, 'prepaid_balance.bonus_balance', 0);
    const isLowBalance = currentBalance < 20;

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Header Section */}
            <div className="bg-white border-b">
                <div className="max-w-7xl xl:max-w-[1400px] 2xl:max-w-[1600px] mx-auto px-6 py-4">
                    <div className="flex justify-between items-center">
                        <div>
                            <h1 className="text-2xl font-bold">Abrechnung & Guthaben</h1>
                            <p className="text-muted-foreground">
                                Verwalten Sie Ihr Prepaid-Guthaben und Ihre Abrechnungen
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            onClick={refresh}
                            disabled={loading}
                            size="sm"
                        >
                            <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                            Aktualisieren
                        </Button>
                    </div>
                </div>
            </div>

            <div className="max-w-7xl xl:max-w-[1400px] 2xl:max-w-[1600px] mx-auto px-6 py-6 space-y-6">
                {/* Balance Header */}
                <BalanceHeader 
                    balance={currentBalance}
                    bonusBalance={bonusBalance}
                    isLowBalance={isLowBalance}
                    onTopupClick={() => {
                        const companyId = billingData?.company?.id;
                        if (companyId) {
                            setShowTopupEmbed(true);
                        }
                    }}
                    ratePerMinute={getNumericValue(billingData, 'billing_rate.rate_per_minute', 0.39)}
                    loading={loading}
                />

                {/* Quick Stats Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5">
                    <Card className="border-0 shadow-md hover:shadow-lg transition-shadow">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div className="space-y-2">
                                    <p className="text-sm font-medium text-muted-foreground">Anrufe heute</p>
                                    <div className="flex items-baseline gap-2">
                                        <p className="text-3xl font-bold">
                                            {usage?.today_calls || billingData?.monthly_usage?.total_calls || 0}
                                        </p>
                                        <span className="text-xs text-muted-foreground">Anrufe</span>
                                    </div>
                                </div>
                                <div className="p-3 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl">
                                    <Phone className="h-6 w-6 text-blue-600" />
                                </div>
                            </div>
                            <div className="mt-3 flex items-center gap-1 text-xs text-muted-foreground">
                                <ArrowUpRight className="h-3 w-3 text-green-500" />
                                <span>+12% zum Vortag</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-0 shadow-md hover:shadow-lg transition-shadow">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div className="space-y-2">
                                    <p className="text-sm font-medium text-muted-foreground">Minuten heute</p>
                                    <div className="flex items-baseline gap-2">
                                        <p className="text-3xl font-bold">
                                            {usage?.today_minutes || Math.round(billingData?.monthly_usage?.total_duration_minutes || 0)}
                                        </p>
                                        <span className="text-xs text-muted-foreground">Min.</span>
                                    </div>
                                </div>
                                <div className="p-3 bg-gradient-to-br from-green-100 to-green-200 rounded-xl">
                                    <Clock className="h-6 w-6 text-green-600" />
                                </div>
                            </div>
                            <div className="mt-3 flex items-center gap-1 text-xs text-muted-foreground">
                                <Activity className="h-3 w-3 text-blue-500" />
                                <span>Ø 2,5 Min./Anruf</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-0 shadow-md hover:shadow-lg transition-shadow">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div className="space-y-2">
                                    <p className="text-sm font-medium text-muted-foreground">Kosten heute</p>
                                    <div className="flex items-baseline gap-1">
                                        <p className="text-3xl font-bold">
                                            €{formatCurrency(usage?.today_cost || billingData?.monthly_usage?.total_charged || 0)}
                                        </p>
                                    </div>
                                </div>
                                <div className="p-3 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl">
                                    <Euro className="h-6 w-6 text-purple-600" />
                                </div>
                            </div>
                            <div className="mt-3 flex items-center gap-1 text-xs text-muted-foreground">
                                <TrendingDown className="h-3 w-3 text-green-500" />
                                <span>-8% zum Vortag</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-0 shadow-md hover:shadow-lg transition-shadow bg-gradient-to-br from-orange-50 to-orange-100">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div className="space-y-2">
                                    <p className="text-sm font-medium text-orange-800">Monatsverbrauch</p>
                                    <div className="flex items-baseline gap-1">
                                        <p className="text-3xl font-bold text-orange-900">
                                            €{formatCurrency(billingData?.monthly_usage?.total_charged || 0)}
                                        </p>
                                    </div>
                                </div>
                                <div className="p-3 bg-white/80 rounded-xl shadow-sm">
                                    <TrendingUp className="h-6 w-6 text-orange-600" />
                                </div>
                            </div>
                            <div className="mt-3 flex items-center gap-1 text-xs text-orange-700">
                                <Calendar className="h-3 w-3" />
                                <span>{new Date().toLocaleDateString('de-DE', { month: 'long' })}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Content Grid */}
                <div className="grid gap-6 lg:grid-cols-3 2xl:grid-cols-4">
                    {/* Left Column - 2/3 width */}
                    <div className="lg:col-span-2 2xl:col-span-3 space-y-6">
                        {/* Settings Cards */}
                        <div className="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                            <AutoTopupCard 
                                prepaidBalance={billingData?.prepaid_balance}
                                billingRate={billingData?.billing_rate}
                                onSettingsClick={() => setAutoTopupModalOpen(true)}
                            />
                            <SpendingLimitsCard 
                                spendingLimits={billingData?.spending_limits}
                            />
                            {/* Usage Stats - only on ultra-wide screens */}
                            <div className="hidden 2xl:block">
                                <UsageStatsCard 
                                    usage={usage}
                                    monthlyUsage={billingData?.monthly_usage}
                                />
                            </div>
                        </div>

                        {/* Recent Transactions */}
                        <TransactionHistory 
                            transactions={transactions}
                            onRefresh={refresh}
                            loading={loading}
                            showViewAll={true}
                        />
                    </div>

                    {/* Right Column - 1/3 width */}
                    <div className="space-y-6">
                        {/* Bonus Matrix */}
                        <BonusMatrixCard 
                            bonusRules={billingData?.bonus_rules}
                            currentBalance={currentBalance}
                        />

                        {/* Monthly Overview */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Calendar className="h-5 w-5" />
                                    Monatsübersicht
                                </CardTitle>
                                <CardDescription>
                                    {dayjs().format('MMMM YYYY')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-3">
                                    <div className="flex justify-between items-center p-3 bg-muted rounded-lg">
                                        <span className="text-sm text-muted-foreground">Anrufe gesamt</span>
                                        <span className="font-semibold">
                                            {billingData?.monthly_usage?.total_calls || 0}
                                        </span>
                                    </div>
                                    <div className="flex justify-between items-center p-3 bg-muted rounded-lg">
                                        <span className="text-sm text-muted-foreground">Minuten gesamt</span>
                                        <span className="font-semibold">
                                            {Math.round(billingData?.monthly_usage?.total_duration_minutes || 0)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between items-center p-3 bg-muted rounded-lg">
                                        <span className="text-sm text-muted-foreground">Durchschn. Anrufdauer</span>
                                        <span className="font-semibold">
                                            {billingData?.monthly_usage?.total_calls > 0 
                                                ? Math.round((billingData?.monthly_usage?.total_duration_minutes || 0) / billingData?.monthly_usage?.total_calls) 
                                                : 0} Min.
                                        </span>
                                    </div>
                                </div>
                                
                                <div className="pt-3 border-t">
                                    <Button variant="outline" className="w-full" size="sm">
                                        <Activity className="h-4 w-4 mr-2" />
                                        Detaillierte Statistiken
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Topup Embed Section */}
            {showTopupEmbed && billingData?.company?.id && (
                <div className="mt-6">
                    <TopupEmbed 
                        companyId={billingData.company.id}
                        onClose={() => setShowTopupEmbed(false)}
                        className="shadow-lg"
                    />
                </div>
            )}

            {/* Modals */}
            <AutoTopupSettingsModal
                open={autoTopupModalOpen}
                onOpenChange={setAutoTopupModalOpen}
                onSave={handleAutoTopupSave}
                loading={loading}
                currentSettings={billingData?.prepaid_balance}
                paymentMethods={billingData?.payment_methods}
            />
        </div>
    );
};

export default BillingIndex;