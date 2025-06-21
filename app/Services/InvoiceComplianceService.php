<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class InvoiceComplianceService
{
    /**
     * Generiert eine GoBD-konforme Rechnungsnummer
     * Format: [Mandantenkürzel]-[Jahr]-[Laufende Nr]-[Prüfziffer]
     * Beispiel: ASK-2024-00001-7
     */
    public function generateCompliantInvoiceNumber(Company $company): string
    {
        DB::beginTransaction();
        
        try {
            // Hole aktuelle Nummer mit Lock
            $company = Company::lockForUpdate()->find($company->id);
            
            $prefix = $company->invoice_prefix ?: $this->generatePrefix($company);
            $year = now()->year;
            $number = $company->next_invoice_number;
            
            // Prüfziffer nach Modulo 11 Verfahren
            $checkDigit = $this->calculateCheckDigit($prefix, $year, $number);
            
            // Format: PREFIX-YYYY-NNNNN-C
            $invoiceNumber = sprintf(
                '%s-%04d-%05d-%d',
                $prefix,
                $year,
                $number,
                $checkDigit
            );
            
            // Prüfe ob Nummer bereits existiert (Sicherheit)
            $exists = Invoice::where('invoice_number', $invoiceNumber)->exists();
            if ($exists) {
                throw new \Exception("Invoice number already exists: {$invoiceNumber}");
            }
            
            // Inkrementiere für nächste Rechnung
            $company->increment('next_invoice_number');
            
            DB::commit();
            
            return $invoiceNumber;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate invoice number', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generiert Präfix aus Firmennamen
     */
    private function generatePrefix(Company $company): string
    {
        // Nimm ersten 3 Buchstaben des Firmennamens
        $name = preg_replace('/[^A-Za-z0-9]/', '', $company->name);
        return strtoupper(substr($name, 0, 3));
    }

    /**
     * Berechnet Prüfziffer nach Modulo 11
     */
    private function calculateCheckDigit(string $prefix, int $year, int $number): int
    {
        $string = $prefix . $year . $number;
        $sum = 0;
        $weight = 2;
        
        // Von rechts nach links gewichten
        for ($i = strlen($string) - 1; $i >= 0; $i--) {
            $char = $string[$i];
            $value = is_numeric($char) ? (int)$char : (ord(strtoupper($char)) - 64);
            $sum += $value * $weight;
            $weight = $weight === 7 ? 2 : $weight + 1;
        }
        
        $checkDigit = (11 - ($sum % 11)) % 11;
        return $checkDigit === 10 ? 0 : $checkDigit;
    }

    /**
     * Finalisiert eine Rechnung (macht sie unveränderbar)
     */
    public function finalizeInvoice(Invoice $invoice): void
    {
        if ($invoice->finalized_at) {
            throw new \Exception('Invoice is already finalized');
        }

        DB::transaction(function () use ($invoice) {
            // Setze finalized_at Timestamp
            $invoice->update([
                'finalized_at' => now(),
                'status' => Invoice::STATUS_OPEN,
            ]);

            // Erstelle Archiv-Eintrag
            $this->archiveInvoice($invoice);

            // Audit Log
            $this->createAuditLog($invoice, 'finalized');
        });
    }

    /**
     * Archiviert eine Rechnung GoBD-konform
     */
    public function archiveInvoice(Invoice $invoice): void
    {
        // Generiere PDF/A-3 (in Produktion würde hier ein PDF-Service verwendet)
        $pdfContent = $this->generateInvoicePdf($invoice);
        
        // Berechne Hash für Unveränderbarkeit
        $hash = hash('sha256', $pdfContent);
        
        // Speichere in unveränderlichem Storage
        $path = "invoices/archive/{$invoice->company_id}/{$invoice->invoice_number}.pdf";
        Storage::disk('archive')->put($path, $pdfContent);
        
        // Erstelle Archiv-Eintrag
        DB::table('invoice_archives')->insert([
            'invoice_id' => $invoice->id,
            'file_path' => $path,
            'file_hash' => $hash,
            'archive_format' => 'PDF/A-3',
            'metadata' => json_encode([
                'invoice_number' => $invoice->invoice_number,
                'company_id' => $invoice->company_id,
                'total' => $invoice->total,
                'created_at' => $invoice->created_at,
            ]),
            'archived_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Erstellt Audit-Log Eintrag
     */
    public function createAuditLog(Invoice $invoice, string $action, ?array $changes = null): void
    {
        DB::table('invoice_audit_logs')->insert([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'changes' => $changes ? json_encode($changes) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Validiert Rechnungspflichtangaben nach §14 UStG
     */
    public function validateInvoiceCompliance(Invoice $invoice): array
    {
        $errors = [];
        $warnings = [];
        $company = $invoice->company;

        // Pflichtangaben prüfen
        if (!$invoice->invoice_number) {
            $errors[] = 'Rechnungsnummer fehlt';
        }

        if (!$invoice->invoice_date) {
            $errors[] = 'Rechnungsdatum fehlt';
        }

        if (!$company->name || !$company->address) {
            $errors[] = 'Vollständige Rechnungsanschrift des leistenden Unternehmers fehlt';
        }

        if (!$company->tax_number && !$company->vat_id) {
            $errors[] = 'Steuernummer oder USt-IdNr. fehlt';
        }

        // Empfängerangaben (bei B2B)
        if ($invoice->total > 250) { // Kleinbetragsrechnungen bis 250€ haben vereinfachte Anforderungen
            $customer = $invoice->customer ?? null;
            if (!$customer || !$customer->name || !$customer->address) {
                $warnings[] = 'Empfängeranschrift unvollständig (erforderlich bei Rechnungen über 250€)';
            }
        }

        // Leistungsbeschreibung prüfen
        foreach ($invoice->items as $item) {
            if (!$item->description || strlen($item->description) < 10) {
                $warnings[] = "Position {$item->id}: Leistungsbeschreibung zu kurz oder fehlt";
            }
        }

        // Steuersatz-Angaben
        if (!$company->is_small_business && !$invoice->is_reverse_charge) {
            if ($invoice->tax_amount == 0 && !$invoice->is_tax_exempt) {
                $warnings[] = 'Keine Umsatzsteuer berechnet, aber auch keine Steuerbefreiung angegeben';
            }
        }

        // Kleinunternehmer-Hinweis
        if ($company->is_small_business && !$invoice->tax_note) {
            $errors[] = 'Kleinunternehmer-Hinweis fehlt';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Generiert eine Storno-Rechnung
     */
    public function createCancellationInvoice(Invoice $originalInvoice): Invoice
    {
        if ($originalInvoice->invoice_type === 'cancellation') {
            throw new \Exception('Cannot cancel a cancellation invoice');
        }

        DB::beginTransaction();
        
        try {
            // Erstelle Storno-Rechnung
            $cancellationInvoice = Invoice::create([
                'company_id' => $originalInvoice->company_id,
                'branch_id' => $originalInvoice->branch_id,
                'invoice_number' => $this->generateCompliantInvoiceNumber($originalInvoice->company),
                'invoice_type' => 'cancellation',
                'original_invoice_id' => $originalInvoice->id,
                'status' => Invoice::STATUS_DRAFT,
                'subtotal' => -$originalInvoice->subtotal,
                'tax_amount' => -$originalInvoice->tax_amount,
                'total' => -$originalInvoice->total,
                'currency' => $originalInvoice->currency,
                'invoice_date' => now(),
                'due_date' => now(),
                'tax_configuration' => $originalInvoice->tax_configuration,
                'is_reverse_charge' => $originalInvoice->is_reverse_charge,
                'customer_vat_id' => $originalInvoice->customer_vat_id,
                'is_tax_exempt' => $originalInvoice->is_tax_exempt,
                'tax_note' => "Storno zu Rechnung {$originalInvoice->invoice_number}",
            ]);

            // Kopiere Positionen mit negativen Beträgen
            foreach ($originalInvoice->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $cancellationInvoice->id,
                    'type' => $item->type,
                    'description' => "STORNO: " . $item->description,
                    'quantity' => -$item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'amount' => -$item->amount,
                    'tax_rate' => $item->tax_rate,
                    'tax_rate_id' => $item->tax_rate_id,
                    'tax_amount' => -$item->tax_amount,
                ]);
            }

            // Audit Logs
            $this->createAuditLog($originalInvoice, 'cancelled', ['cancellation_invoice_id' => $cancellationInvoice->id]);
            $this->createAuditLog($cancellationInvoice, 'created', ['original_invoice_id' => $originalInvoice->id]);

            DB::commit();
            
            return $cancellationInvoice;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generiert Rechnungs-PDF (Platzhalter)
     */
    private function generateInvoicePdf(Invoice $invoice): string
    {
        // In Produktion würde hier ein PDF-Service wie DomPDF oder Puppeteer verwendet
        // Für jetzt nur ein Platzhalter
        return "PDF content for invoice {$invoice->invoice_number}";
    }

    /**
     * Prüft ob eine Rechnung geändert werden darf
     */
    public function canModifyInvoice(Invoice $invoice): bool
    {
        // Finalisierte Rechnungen dürfen nicht geändert werden (GoBD)
        if ($invoice->finalized_at) {
            return false;
        }

        // Bezahlte Rechnungen sollten nicht geändert werden
        if ($invoice->status === Invoice::STATUS_PAID) {
            return false;
        }

        // Stornierte Rechnungen nicht änderbar
        if ($invoice->status === Invoice::STATUS_VOID) {
            return false;
        }

        return true;
    }

    /**
     * Generiert DATEV-kompatible Buchungssätze
     */
    public function generateDatevExport(Company $company, Carbon $startDate, Carbon $endDate): array
    {
        $config = DB::table('datev_configurations')->where('company_id', $company->id)->first();
        
        if (!$config) {
            throw new \Exception('DATEV configuration not found for company');
        }

        $invoices = Invoice::where('company_id', $company->id)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->whereIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_OPEN])
            ->get();

        $bookings = [];
        
        foreach ($invoices as $invoice) {
            // DATEV Buchungssatz Format
            $booking = [
                'Umsatz' => number_format($invoice->total, 2, ',', ''),
                'Soll/Haben-Kennzeichen' => 'S', // Soll
                'WKZ Umsatz' => 'EUR',
                'Konto' => $this->getDatevAccount($invoice, $config),
                'Gegenkonto' => $this->getDatevCounterAccount($invoice, $config),
                'BU-Schlüssel' => $this->getDatevTaxKey($invoice),
                'Belegdatum' => $invoice->invoice_date->format('d.m.Y'),
                'Belegfeld 1' => $invoice->invoice_number,
                'Belegfeld 2' => '',
                'Skonto' => '',
                'Buchungstext' => substr($invoice->getDescriptionForExport(), 0, 60),
                'Postensperre' => '',
                'Diverse Adressnummer' => '',
                'Geschäftspartnerbank' => '',
                'Sachverhalt' => '',
                'Zinssperre' => '',
                'Beleglink' => '',
                'Beleginfo - Art 1' => 'Rechnung',
                'Beleginfo - Inhalt 1' => $invoice->invoice_number,
            ];
            
            $bookings[] = $booking;
        }

        return [
            'header' => $this->generateDatevHeader($config, $startDate, $endDate),
            'bookings' => $bookings,
        ];
    }

    /**
     * Generiert DATEV Header
     */
    private function generateDatevHeader(object $config, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'EXTF' => '510', // Format-Version
            'Berater' => $config->consultant_number ?? '',
            'Mandant' => $config->client_number ?? '',
            'WJ-Beginn' => $startDate->format('Ymd'),
            'Datum vom' => $startDate->format('Ymd'),
            'Datum bis' => $endDate->format('Ymd'),
            'Bezeichnung' => 'AskProAI Export',
            'Diktatzeichen' => '',
            'Buchungstyp' => '1', // Finanzbuchführung
            'Rechnungslegungszweck' => '0', // Steuerrecht
            'Festschreibung' => '0',
            'WKZ' => 'EUR',
        ];
    }

    /**
     * Ermittelt DATEV Konto
     */
    private function getDatevAccount(Invoice $invoice, object $config): string
    {
        $mapping = json_decode($config->account_mapping ?? '{}', true);
        
        // Standard: Erlöskonto 8400
        return $mapping['revenue_account'] ?? '8400';
    }

    /**
     * Ermittelt DATEV Gegenkonto
     */
    private function getDatevCounterAccount(Invoice $invoice, object $config): string
    {
        // Debitoren-Sammelbkonto oder individuelle Kundennummer
        $mapping = json_decode($config->account_mapping ?? '{}', true);
        return $mapping['debitor_account'] ?? '10000';
    }

    /**
     * Ermittelt DATEV Steuerschlüssel
     */
    private function getDatevTaxKey(Invoice $invoice): string
    {
        if ($invoice->company->is_small_business || $invoice->is_tax_exempt) {
            return '0'; // Keine Steuer
        }

        if ($invoice->is_reverse_charge) {
            return '94'; // Reverse Charge
        }

        // Standard: 19% USt
        $taxRate = $invoice->items->first()->tax_rate ?? 19;
        
        return match ($taxRate) {
            19.0 => '3', // 19% USt
            7.0 => '2',  // 7% USt
            0.0 => '0',  // Keine Steuer
            default => '3',
        };
    }
}