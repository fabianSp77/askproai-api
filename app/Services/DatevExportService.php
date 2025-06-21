<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DatevExportService
{
    protected InvoiceComplianceService $complianceService;

    public function __construct()
    {
        $this->complianceService = new InvoiceComplianceService();
    }

    /**
     * Exportiert Rechnungen im DATEV-Format
     */
    public function exportInvoices(Company $company, Carbon $startDate, Carbon $endDate, string $format = 'EXTF'): array
    {
        $config = DB::table('datev_configurations')->where('company_id', $company->id)->first();
        
        if (!$config) {
            // Erstelle Standard-Konfiguration
            $config = $this->createDefaultConfiguration($company);
        }

        $invoices = Invoice::where('company_id', $company->id)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->whereIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_OPEN])
            ->whereNotNull('finalized_at') // Nur finalisierte Rechnungen
            ->orderBy('invoice_date')
            ->orderBy('invoice_number')
            ->get();

        if ($format === 'EXTF') {
            return $this->generateExtfFormat($config, $invoices, $startDate, $endDate);
        } else {
            return $this->generateCsvFormat($config, $invoices, $startDate, $endDate);
        }
    }

    /**
     * Generiert DATEV EXTF Format (Erweiterte Formatbeschreibung)
     */
    protected function generateExtfFormat($config, $invoices, Carbon $startDate, Carbon $endDate): array
    {
        $header = $this->generateExtfHeader($config, $startDate, $endDate);
        $bookings = [];

        foreach ($invoices as $invoice) {
            // Hauptbuchung
            $bookings[] = $this->createExtfBooking($invoice, $config);

            // Steuerbuchung (wenn nicht Kleinunternehmer)
            if (!$invoice->company->is_small_business && $invoice->tax_amount > 0) {
                $bookings[] = $this->createExtfTaxBooking($invoice, $config);
            }
        }

        $content = $this->formatExtfContent($header, $bookings);

        return [
            'format' => 'EXTF',
            'filename' => $this->generateFilename($invoice->company, 'EXTF', $startDate, $endDate),
            'content' => $content,
            'bookings_count' => count($bookings),
            'total_amount' => $invoices->sum('total'),
        ];
    }

    /**
     * Generiert CSV Format für DATEV
     */
    protected function generateCsvFormat($config, $invoices, Carbon $startDate, Carbon $endDate): array
    {
        $headers = [
            'Umsatz (ohne Soll/Haben-Kz)',
            'Soll/Haben-Kennzeichen',
            'WKZ Umsatz',
            'Kurs',
            'Basis-Umsatz',
            'WKZ Basis-Umsatz',
            'Konto',
            'Gegenkonto (ohne BU-Schlüssel)',
            'BU-Schlüssel',
            'Belegdatum',
            'Belegfeld 1',
            'Belegfeld 2',
            'Skonto',
            'Buchungstext',
            'Postensperre',
            'Diverse Adressnummer',
            'Geschäftspartnerbank',
            'Sachverhalt',
            'Zinssperre',
            'Beleglink',
            'Beleginfo - Art 1',
            'Beleginfo - Inhalt 1',
            'Beleginfo - Art 2',
            'Beleginfo - Inhalt 2',
            'Beleginfo - Art 3',
            'Beleginfo - Inhalt 3',
            'Beleginfo - Art 4',
            'Beleginfo - Inhalt 4',
            'Beleginfo - Art 5',
            'Beleginfo - Inhalt 5',
            'Beleginfo - Art 6',
            'Beleginfo - Inhalt 6',
            'Beleginfo - Art 7',
            'Beleginfo - Inhalt 7',
            'Beleginfo - Art 8',
            'Beleginfo - Inhalt 8',
            'KOST1 - Kostenstelle',
            'KOST2 - Kostenstelle',
            'Kost-Menge',
            'EU-Land u. UStID',
            'EU-Steuersatz',
        ];

        $rows = [$headers];

        foreach ($invoices as $invoice) {
            $rows[] = $this->createCsvRow($invoice, $config);

            // Steuerbuchung
            if (!$invoice->company->is_small_business && $invoice->tax_amount > 0) {
                $rows[] = $this->createCsvTaxRow($invoice, $config);
            }
        }

        $content = $this->generateCsvContent($rows);

        return [
            'format' => 'CSV',
            'filename' => $this->generateFilename($invoice->company, 'CSV', $startDate, $endDate),
            'content' => $content,
            'bookings_count' => count($rows) - 1, // Minus header
            'total_amount' => $invoices->sum('total'),
        ];
    }

    /**
     * Erstellt EXTF Header
     */
    protected function generateExtfHeader($config, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'EXTF' => '510',
            'DATEVFormat' => 'EXTF',
            'Version' => '5.1',
            'Datenkategorie' => '21', // Buchungssätze
            'Formatname' => 'Buchungssätze',
            'Formatversion' => '7',
            'Erzeugt am' => now()->format('YmdHis'),
            'Importiert' => '',
            'Herkunft' => 'AS',
            'Exportiert von' => 'AskProAI',
            'Berater' => $config->consultant_number ?? '',
            'Mandant' => $config->client_number ?? '',
            'WJ-Beginn' => $startDate->startOfYear()->format('Ymd'),
            'Sachkonten-Länge' => '4',
            'Datum vom' => $startDate->format('Ymd'),
            'Datum bis' => $endDate->format('Ymd'),
            'Bezeichnung' => 'AskProAI Export',
            'Diktatkürzel' => 'ASK',
            'Buchungstyp' => '1', // Finanzbuchführung
            'Rechnungslegungszweck' => '0', // Handels- und Steuerrecht
            'Festschreibung' => '0',
            'WKZ' => 'EUR',
            'Reserviert' => '',
            'Derivatskennzeichen' => '',
            'Reserviert2' => '',
            'Reserviert3' => '',
            'SKR' => '03', // Standardkontenrahmen 03
            'Branchen-Lösungs-ID' => '',
            'Reserviert4' => '',
            'Reserviert5' => '',
        ];
    }

    /**
     * Erstellt EXTF Buchungssatz
     */
    protected function createExtfBooking(Invoice $invoice, $config): array
    {
        $accountMapping = json_decode($config->account_mapping ?? '{}', true);
        
        return [
            'Umsatz' => $this->formatAmount($invoice->total),
            'Soll/Haben' => 'S', // Soll
            'Konto' => $accountMapping['debitor_account'] ?? '10000',
            'Gegenkonto' => $this->getRevenueAccount($invoice, $accountMapping),
            'BU-Schlüssel' => $this->getTaxKey($invoice),
            'Belegdatum' => $invoice->invoice_date->format('dmY'),
            'Belegfeld1' => $invoice->invoice_number,
            'Belegfeld2' => '',
            'Buchungstext' => $this->getBookingText($invoice),
            'Beleginfo1' => 'Rechnung',
            'Beleginfo2' => $invoice->invoice_number,
            'EU-Steuersatz' => $invoice->is_reverse_charge ? '0' : '',
            'EU-Land' => $invoice->customer_vat_id ? substr($invoice->customer_vat_id, 0, 2) : '',
            'EU-UStID' => $invoice->customer_vat_id ?? '',
        ];
    }

    /**
     * Erstellt EXTF Steuerbuchung
     */
    protected function createExtfTaxBooking(Invoice $invoice, $config): array
    {
        $accountMapping = json_decode($config->account_mapping ?? '{}', true);
        
        return [
            'Umsatz' => $this->formatAmount($invoice->tax_amount),
            'Soll/Haben' => 'S', // Soll
            'Konto' => $accountMapping['debitor_account'] ?? '10000',
            'Gegenkonto' => $this->getTaxAccount($invoice, $accountMapping),
            'BU-Schlüssel' => '0', // Automatikbuchung
            'Belegdatum' => $invoice->invoice_date->format('dmY'),
            'Belegfeld1' => $invoice->invoice_number,
            'Belegfeld2' => 'VSt',
            'Buchungstext' => 'Vorsteuer zu RG ' . $invoice->invoice_number,
        ];
    }

    /**
     * Erstellt CSV Zeile
     */
    protected function createCsvRow(Invoice $invoice, $config): array
    {
        $accountMapping = json_decode($config->account_mapping ?? '{}', true);
        
        return [
            $this->formatAmount($invoice->total), // Umsatz
            'S', // Soll
            'EUR', // Währung
            '', // Kurs
            '', // Basis-Umsatz
            '', // WKZ Basis
            $accountMapping['debitor_account'] ?? '10000', // Konto
            $this->getRevenueAccount($invoice, $accountMapping), // Gegenkonto
            $this->getTaxKey($invoice), // BU-Schlüssel
            $invoice->invoice_date->format('dmY'), // Belegdatum
            $invoice->invoice_number, // Belegfeld 1
            '', // Belegfeld 2
            '', // Skonto
            $this->getBookingText($invoice), // Buchungstext
            '', // Postensperre
            '', // Diverse Adressnummer
            '', // Geschäftspartnerbank
            '', // Sachverhalt
            '', // Zinssperre
            '', // Beleglink
            'Rechnung', // Beleginfo Art 1
            $invoice->invoice_number, // Beleginfo Inhalt 1
            'Datum', // Beleginfo Art 2
            $invoice->invoice_date->format('d.m.Y'), // Beleginfo Inhalt 2
            '', '', '', '', '', '', '', '', '', '', '', '', // Weitere Beleginfos
            '', '', '', // Kostenstellen
            $invoice->customer_vat_id ? substr($invoice->customer_vat_id, 0, 2) : '', // EU-Land
            $invoice->is_reverse_charge ? '0' : '', // EU-Steuersatz
        ];
    }

    /**
     * Erstellt CSV Steuerzeile
     */
    protected function createCsvTaxRow(Invoice $invoice, $config): array
    {
        $accountMapping = json_decode($config->account_mapping ?? '{}', true);
        
        $row = $this->createCsvRow($invoice, $config);
        $row[0] = $this->formatAmount($invoice->tax_amount); // Steuerbetrag
        $row[7] = $this->getTaxAccount($invoice, $accountMapping); // Steuerkonto
        $row[8] = '0'; // Kein BU-Schlüssel für Automatikbuchung
        $row[11] = 'VSt'; // Belegfeld 2
        $row[13] = 'Vorsteuer zu RG ' . $invoice->invoice_number;
        
        return $row;
    }

    /**
     * Formatiert Betrag für DATEV
     */
    protected function formatAmount(float $amount): string
    {
        return str_replace('.', ',', sprintf('%.2f', abs($amount)));
    }

    /**
     * Ermittelt Erlöskonto
     */
    protected function getRevenueAccount(Invoice $invoice, array $mapping): string
    {
        // Könnte nach Service-Typ differenziert werden
        return $mapping['revenue_account'] ?? '8400';
    }

    /**
     * Ermittelt Steuerkonto
     */
    protected function getTaxAccount(Invoice $invoice, array $mapping): string
    {
        $taxRate = $invoice->items->first()->tax_rate ?? 19;
        
        return match ($taxRate) {
            19.0 => $mapping['tax_19_account'] ?? '1576',
            7.0 => $mapping['tax_7_account'] ?? '1571',
            0.0 => '',
            default => $mapping['tax_19_account'] ?? '1576',
        };
    }

    /**
     * Ermittelt Steuerschlüssel
     */
    protected function getTaxKey(Invoice $invoice): string
    {
        if ($invoice->company->is_small_business || $invoice->is_tax_exempt) {
            return '0';
        }

        if ($invoice->is_reverse_charge) {
            return '94'; // Reverse Charge
        }

        $taxRate = $invoice->items->first()->tax_rate ?? 19;
        
        return match ($taxRate) {
            19.0 => '3', // 19% USt
            7.0 => '2',  // 7% USt
            0.0 => '0',  // Keine Steuer
            default => '3',
        };
    }

    /**
     * Generiert Buchungstext
     */
    protected function getBookingText(Invoice $invoice): string
    {
        $text = "RG {$invoice->invoice_number}";
        
        if ($invoice->branch) {
            $text .= " {$invoice->branch->name}";
        }
        
        // Maximal 60 Zeichen für DATEV
        return substr($text, 0, 60);
    }

    /**
     * Formatiert EXTF Content
     */
    protected function formatExtfContent(array $header, array $bookings): string
    {
        $lines = [];
        
        // Header
        foreach ($header as $key => $value) {
            $lines[] = sprintf('"%s";"%s"', $key, $value);
        }
        
        $lines[] = ''; // Leerzeile zwischen Header und Daten
        
        // Spaltenüberschriften
        $columns = array_keys($bookings[0] ?? []);
        $lines[] = '"' . implode('";"', $columns) . '"';
        
        // Buchungssätze
        foreach ($bookings as $booking) {
            $values = array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', array_values($booking));
            $lines[] = implode(';', $values);
        }
        
        return implode("\r\n", $lines);
    }

    /**
     * Generiert CSV Content
     */
    protected function generateCsvContent(array $rows): string
    {
        $lines = [];
        
        foreach ($rows as $row) {
            $values = array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row);
            $lines[] = implode(';', $values);
        }
        
        return implode("\r\n", $lines);
    }

    /**
     * Generiert Dateinamen
     */
    protected function generateFilename(Company $company, string $format, Carbon $startDate, Carbon $endDate): string
    {
        return sprintf(
            'DATEV_%s_%s_%s_%s.%s',
            preg_replace('/[^A-Za-z0-9]/', '', $company->name),
            $startDate->format('Ymd'),
            $endDate->format('Ymd'),
            now()->format('His'),
            strtolower($format)
        );
    }

    /**
     * Erstellt Standard-Konfiguration
     */
    protected function createDefaultConfiguration(Company $company): object
    {
        $data = [
            'company_id' => $company->id,
            'consultant_number' => '',
            'client_number' => '',
            'export_format' => 'EXTF',
            'account_mapping' => json_encode([
                'revenue_account' => '8400',
                'debitor_account' => '10000',
                'tax_19_account' => '1576',
                'tax_7_account' => '1571',
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('datev_configurations')->insert($data);

        return (object) $data;
    }
}