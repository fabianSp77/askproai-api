import React from 'react';
import { Card } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { 
    Wallet, 
    TrendingUp, 
    TrendingDown, 
    AlertTriangle,
    Plus,
    Zap
} from 'lucide-react';
import { cn } from '../../lib/utils';
import { formatCurrency } from '../../utils/currency';

export const BalanceHeader = ({ 
    balance, 
    bonusBalance = 0, 
    isLowBalance = false,
    onTopupClick,
    ratePerMinute = 0.39,
    loading = false
}) => {
    const totalBalance = parseFloat(balance || 0) + parseFloat(bonusBalance || 0);
    const safeRatePerMinute = parseFloat(ratePerMinute || 0.39);
    const estimatedMinutes = totalBalance > 0 ? Math.floor(totalBalance / safeRatePerMinute) : 0;
    const estimatedCalls = totalBalance > 0 ? Math.floor(totalBalance / (safeRatePerMinute * 2)) : 0;
    
    // Determine status
    const getStatus = () => {
        if (totalBalance <= 5) return { color: 'text-red-600 bg-red-50', icon: AlertTriangle, text: 'Kritisch' };
        if (totalBalance <= 20) return { color: 'text-orange-600 bg-orange-50', icon: TrendingDown, text: 'Niedrig' };
        return { color: 'text-green-600 bg-green-50', icon: TrendingUp, text: 'Gut' };
    };
    
    const status = getStatus();
    const StatusIcon = status.icon;

    return (
        <Card className="overflow-hidden shadow-xl">
            <div className="bg-gradient-to-br from-blue-600 via-blue-700 to-purple-700 p-8 text-white relative">
                {/* Background Pattern */}
                <div className="absolute inset-0 opacity-10">
                    <div className="absolute inset-0" style={{
                        backgroundImage: 'radial-gradient(circle at 20% 50%, white 1px, transparent 1px), radial-gradient(circle at 80% 80%, white 1px, transparent 1px)',
                        backgroundSize: '50px 50px'
                    }} />
                </div>
                
                <div className="relative z-10">
                    <div className="flex items-center justify-between mb-6">
                        <div className="flex items-center gap-4">
                            <div className="p-4 bg-white/20 rounded-xl backdrop-blur-sm shadow-lg">
                                <Wallet className="h-8 w-8" />
                            </div>
                            <div>
                                <h2 className="text-xl font-semibold opacity-95 mb-1">Ihr Guthaben</h2>
                                <div className="flex items-baseline gap-3">
                                    <span className="text-4xl font-bold tracking-tight">
                                        €{formatCurrency(totalBalance)}
                                    </span>
                                    {bonusBalance > 0 && (
                                        <span className="text-sm opacity-80 bg-white/10 px-2 py-1 rounded-full">
                                            +€{formatCurrency(bonusBalance)} Bonus
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                        
                        <Button 
                            onClick={onTopupClick}
                            size="lg"
                            variant="secondary"
                            className="bg-white text-blue-600 hover:bg-gray-50 shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
                            disabled={loading}
                        >
                            <Zap className="h-5 w-5 mr-2" />
                            Jetzt aufladen
                        </Button>
                </div>
                
                    <div className="grid grid-cols-3 gap-4 mt-6">
                        <div className="bg-white/10 rounded-xl p-4 backdrop-blur-sm transform transition-all hover:bg-white/15 hover:scale-105">
                            <div className="text-xs opacity-75 mb-2">Status</div>
                            <div className="flex items-center gap-2">
                                <StatusIcon className="h-5 w-5" />
                                <span className="font-semibold text-lg">{status.text}</span>
                            </div>
                        </div>
                        
                        <div className="bg-white/10 rounded-xl p-4 backdrop-blur-sm transform transition-all hover:bg-white/15 hover:scale-105">
                            <div className="text-xs opacity-75 mb-2">Verfügbare Minuten</div>
                            <div className="font-semibold text-lg">~{estimatedMinutes} Min.</div>
                        </div>
                        
                        <div className="bg-white/10 rounded-xl p-4 backdrop-blur-sm transform transition-all hover:bg-white/15 hover:scale-105">
                            <div className="text-xs opacity-75 mb-2">Mögliche Anrufe</div>
                            <div className="font-semibold text-lg">~{estimatedCalls} Anrufe</div>
                        </div>
                    </div>
                </div>
            </div>
            
            {/* Quick Actions Bar */}
            <div className="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 flex items-center justify-between border-t">
                <div className="flex items-center gap-6 text-sm">
                    <div className="flex items-center gap-2">
                        <div className="p-1.5 bg-blue-100 rounded-lg">
                            <Zap className="h-4 w-4 text-blue-600" />
                        </div>
                        <span className="text-muted-foreground">
                            Tarif: <span className="font-semibold text-foreground">€{formatCurrency(safeRatePerMinute)}/Min.</span>
                        </span>
                    </div>
                    {bonusBalance > 0 && (
                        <Badge variant="secondary" className="gap-1 bg-green-100 text-green-700 border-green-200">
                            <TrendingUp className="h-3 w-3" />
                            Bonus aktiv
                        </Badge>
                    )}
                </div>
                
                <Button variant="ghost" size="sm" className="text-xs hover:bg-white/80 transition-colors">
                    <Plus className="h-3 w-3 mr-1" />
                    Auto-Aufladung einrichten
                </Button>
            </div>
        </Card>
    );
};