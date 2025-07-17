import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../ui/table';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { 
    Download, 
    FileText, 
    Phone, 
    CreditCard, 
    RefreshCw,
    Gift,
    Wallet
} from 'lucide-react';
import dayjs from 'dayjs';
import 'dayjs/locale/de';
import { formatCurrency } from '../../utils/currency';

dayjs.locale('de');

export const TransactionHistory = ({ transactions = [], onRefresh, loading, showViewAll = false }) => {
    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = showViewAll ? 5 : 10;

    const getTransactionIcon = (type) => {
        switch(type) {
            case 'topup': return <Wallet className="h-4 w-4" />;
            case 'charge': return <Phone className="h-4 w-4" />;
            case 'refund': return <RefreshCw className="h-4 w-4" />;
            default: return <CreditCard className="h-4 w-4" />;
        }
    };

    const getStatusBadge = (status) => {
        const variants = {
            'completed': { variant: 'default', label: 'Abgeschlossen' },
            'pending': { variant: 'secondary', label: 'Ausstehend' },
            'failed': { variant: 'destructive', label: 'Fehlgeschlagen' },
            'refunded': { variant: 'outline', label: 'Erstattet' }
        };

        const config = variants[status] || { variant: 'secondary', label: status };
        return <Badge variant={config.variant}>{config.label}</Badge>;
    };

    const getAmountDisplay = (amount, type) => {
        const isPositive = type === 'topup' || type === 'refund';
        const prefix = isPositive ? '+' : '-';
        const className = isPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
        
        return (
            <span className={`font-semibold ${className}`}>
                {prefix}€{formatCurrency(Math.abs(parseFloat(amount || 0)))}
            </span>
        );
    };

    // Pagination
    const safeTransactions = Array.isArray(transactions) ? transactions : [];
    const totalPages = Math.ceil(safeTransactions.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const currentTransactions = safeTransactions.slice(startIndex, endIndex);

    return (
        <Card>
            <CardHeader>
                <div className="flex justify-between items-center">
                    <CardTitle>Transaktionsverlauf</CardTitle>
                    <Button 
                        variant="outline" 
                        size="sm"
                        onClick={onRefresh}
                        disabled={loading}
                    >
                        <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                        Aktualisieren
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                {safeTransactions.length === 0 ? (
                    <div className="text-center py-8">
                        <p className="text-muted-foreground">Keine Transaktionen vorhanden</p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-gray-50/50">
                                        <TableHead className="font-semibold">Datum</TableHead>
                                        <TableHead className="font-semibold">Beschreibung</TableHead>
                                        <TableHead className="text-right font-semibold">Betrag</TableHead>
                                        <TableHead className="font-semibold">Status</TableHead>
                                        <TableHead className="text-right font-semibold">Aktion</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {currentTransactions.map((transaction, index) => (
                                        <TableRow 
                                            key={transaction.id} 
                                            className={cn(
                                                "transition-colors hover:bg-gray-50/50",
                                                index % 2 === 0 ? "bg-white" : "bg-gray-50/30"
                                            )}
                                        >
                                            <TableCell className="font-medium">
                                                <div className="space-y-1">
                                                    <div className="text-sm font-semibold">
                                                        {dayjs(transaction.created_at).format('DD. MMM YYYY')}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {dayjs(transaction.created_at).format('HH:mm')} Uhr
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <div className="p-2 bg-gray-100 rounded-lg">
                                                        {getTransactionIcon(transaction.type)}
                                                    </div>
                                                    <div>
                                                        <span className="font-medium">{transaction.description}</span>
                                                        {transaction.bonus_amount > 0 && (
                                                            <Badge variant="secondary" className="ml-2 bg-green-100 text-green-700 border-green-200">
                                                                <Gift className="h-3 w-3 mr-1" />
                                                                +€{formatCurrency(transaction.bonus_amount || 0)} Bonus
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="font-semibold text-lg">
                                                    {getAmountDisplay(transaction.amount, transaction.type)}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {getStatusBadge(transaction.status)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {transaction.invoice_url && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="hover:bg-gray-100"
                                                        onClick={() => window.open(transaction.invoice_url, '_blank')}
                                                    >
                                                        <FileText className="h-4 w-4 mr-1" />
                                                        Rechnung
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination */}
                        {totalPages > 1 && (
                            <div className="flex items-center justify-between px-2 py-4">
                                <div className="text-sm text-muted-foreground">
                                    Zeige {startIndex + 1} bis {Math.min(endIndex, safeTransactions.length)} von {safeTransactions.length} Einträgen
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                                        disabled={currentPage === 1}
                                    >
                                        Zurück
                                    </Button>
                                    <div className="text-sm">
                                        Seite {currentPage} von {totalPages}
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                                        disabled={currentPage === totalPages}
                                    >
                                        Weiter
                                    </Button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </CardContent>
        </Card>
    );
};