<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceTransaction;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionExportController extends Controller
{
    /**
     * Export transactions as CSV
     */
    public function exportCsv(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'type' => 'nullable|in:credit,debit,all'
        ]);

        $company = Company::findOrFail($validated['company_id']);
        
        // Check permissions
        if (!auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== $company->id) {
            abort(403);
        }

        // Build query
        $query = BalanceTransaction::where('company_id', $company->id);
        
        if ($request->from) {
            $query->where('created_at', '>=', $validated['from']);
        }
        
        if ($request->to) {
            $query->where('created_at', '<=', $validated['to'] . ' 23:59:59');
        }
        
        if ($request->type && $request->type !== 'all') {
            $query->where('type', $validated['type']);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->get();
        
        // Generate CSV
        $filename = sprintf(
            'transaktionen_%s_%s.csv',
            \Str::slug($company->name),
            now()->format('Y-m-d')
        );
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($transactions, $company) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header information
            fputcsv($file, ['Transaktionsbericht'], ';');
            fputcsv($file, ['Firma:', $company->name], ';');
            fputcsv($file, ['Erstellt am:', now()->format('d.m.Y H:i')], ';');
            fputcsv($file, [], ';');
            
            // Column headers
            fputcsv($file, [
                'Datum',
                'Uhrzeit',
                'Typ',
                'Beschreibung',
                'Referenz',
                'Betrag (EUR)',
                'Saldo nach Transaktion (EUR)',
                'Erstellt von'
            ], ';');
            
            // Data rows
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->created_at->format('d.m.Y'),
                    $transaction->created_at->format('H:i:s'),
                    $transaction->type === 'credit' ? 'Aufladung' : 'Verbrauch',
                    $transaction->description,
                    $transaction->reference_type ? class_basename($transaction->reference_type) . ' #' . $transaction->reference_id : '',
                    ($transaction->type === 'credit' ? '+' : '-') . number_format($transaction->amount, 2, ',', '.'),
                    number_format($transaction->balance_after, 2, ',', '.'),
                    $transaction->createdBy ? $transaction->createdBy->name : 'System'
                ], ';');
            }
            
            // Summary
            fputcsv($file, [], ';');
            fputcsv($file, ['Zusammenfassung'], ';');
            fputcsv($file, ['Anzahl Transaktionen:', $transactions->count()], ';');
            fputcsv($file, ['Summe Aufladungen:', number_format($transactions->where('type', 'credit')->sum('amount'), 2, ',', '.') . ' EUR'], ';');
            fputcsv($file, ['Summe Verbrauch:', number_format($transactions->where('type', 'debit')->sum('amount'), 2, ',', '.') . ' EUR'], ';');
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, $headers);
    }
    
    /**
     * Export transactions as PDF
     */
    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'type' => 'nullable|in:credit,debit,all'
        ]);

        $company = Company::findOrFail($validated['company_id']);
        
        // Check permissions
        if (!auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== $company->id) {
            abort(403);
        }

        // Build query
        $query = BalanceTransaction::where('company_id', $company->id);
        
        if ($request->from) {
            $query->where('created_at', '>=', $validated['from']);
        }
        
        if ($request->to) {
            $query->where('created_at', '<=', $validated['to'] . ' 23:59:59');
        }
        
        if ($request->type && $request->type !== 'all') {
            $query->where('type', $validated['type']);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->limit(500)->get();
        
        // Calculate summaries
        $summary = [
            'total_transactions' => $transactions->count(),
            'total_credits' => $transactions->where('type', 'credit')->sum('amount'),
            'total_debits' => $transactions->where('type', 'debit')->sum('amount'),
            'date_from' => $request->from ? Carbon::parse($request->from)->format('d.m.Y') : 'Anfang',
            'date_to' => $request->to ? Carbon::parse($request->to)->format('d.m.Y') : 'Heute',
        ];
        
        // Generate PDF
        $pdf = Pdf::loadView('pdf.transaction-report', [
            'company' => $company,
            'transactions' => $transactions,
            'summary' => $summary
        ]);
        
        $filename = sprintf(
            'transaktionen_%s_%s.pdf',
            \Str::slug($company->name),
            now()->format('Y-m-d')
        );
        
        return $pdf->download($filename);
    }
}