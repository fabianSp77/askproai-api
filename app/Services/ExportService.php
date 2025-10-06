<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomersExport;
use App\Exports\AppointmentsExport;
use App\Exports\InvoicesExport;
use App\Exports\TransactionsExport;
use App\Exports\CallsExport;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Call;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Exception;

class ExportService
{
    /**
     * Export customers to Excel/CSV
     */
    public function exportCustomers($query = null, string $format = 'xlsx'): string
    {
        try {
            $query = $query ?: Customer::query();

            $filename = "exports/customers-" . now()->format('Y-m-d-His') . "." . $format;

            Excel::store(new CustomersExport($query), $filename);

            Log::info('Customers exported', [
                'filename' => $filename,
                'count' => $query->count(),
                'format' => $format
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to export customers', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Export appointments to Excel/CSV
     */
    public function exportAppointments($query = null, string $format = 'xlsx'): string
    {
        try {
            $query = $query ?: Appointment::query();

            $filename = "exports/appointments-" . now()->format('Y-m-d-His') . "." . $format;

            Excel::store(new AppointmentsExport($query), $filename);

            Log::info('Appointments exported', [
                'filename' => $filename,
                'count' => $query->count(),
                'format' => $format
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to export appointments', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Export invoices to Excel/CSV
     */
    public function exportInvoices($query = null, string $format = 'xlsx'): string
    {
        try {
            $query = $query ?: Invoice::query();

            $filename = "exports/invoices-" . now()->format('Y-m-d-His') . "." . $format;

            Excel::store(new InvoicesExport($query), $filename);

            Log::info('Invoices exported', [
                'filename' => $filename,
                'count' => $query->count(),
                'format' => $format
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to export invoices', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Export calls to Excel/CSV
     */
    public function exportCalls($query = null, string $format = 'xlsx'): string
    {
        try {
            $query = $query ?: Call::query();

            $filename = "exports/calls-" . now()->format('Y-m-d-His') . "." . $format;

            Excel::store(new CallsExport($query), $filename);

            Log::info('Calls exported', [
                'filename' => $filename,
                'count' => $query->count(),
                'format' => $format
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to export calls', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Export transactions to Excel/CSV
     */
    public function exportTransactions($query = null, string $format = 'xlsx'): string
    {
        try {
            $query = $query ?: \App\Models\Transaction::query();

            $filename = "exports/transactions-" . now()->format('Y-m-d-His') . "." . $format;

            Excel::store(new TransactionsExport($query), $filename);

            Log::info('Transactions exported', [
                'filename' => $filename,
                'count' => $query->count(),
                'format' => $format
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to export transactions', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Export company report
     */
    public function exportCompanyReport(Company $company, array $options = []): string
    {
        try {
            $startDate = $options['start_date'] ?? now()->startOfMonth();
            $endDate = $options['end_date'] ?? now()->endOfMonth();
            $format = $options['format'] ?? 'xlsx';

            $data = [
                'company' => $company,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'customers' => $company->customers()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get(),
                'appointments' => $company->appointments()
                    ->whereBetween('starts_at', [$startDate, $endDate])
                    ->get(),
                'invoices' => $company->invoices()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get(),
                'statistics' => $this->calculateCompanyStatistics($company, $startDate, $endDate),
            ];

            $filename = "exports/company-report-{$company->id}-" . now()->format('Y-m-d') . "." . $format;

            // Create a custom export class for company report
            Excel::store(new \App\Exports\CompanyReportExport($data), $filename);

            Log::info('Company report exported', [
                'company_id' => $company->id,
                'filename' => $filename,
                'format' => $format
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to export company report', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Export custom query results
     */
    public function exportCustomQuery(Builder $query, array $columns, string $filename, string $format = 'xlsx'): string
    {
        try {
            $results = $query->get($columns);

            $exportFilename = "exports/custom/{$filename}-" . now()->format('Y-m-d-His') . "." . $format;

            // Create anonymous export class
            $export = new class($results, $columns) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                protected $data;
                protected $columns;

                public function __construct($data, $columns)
                {
                    $this->data = $data;
                    $this->columns = $columns;
                }

                public function collection()
                {
                    return $this->data;
                }

                public function headings(): array
                {
                    return $this->columns;
                }
            };

            Excel::store($export, $exportFilename);

            Log::info('Custom query exported', [
                'filename' => $exportFilename,
                'count' => $results->count(),
                'format' => $format
            ]);

            return $exportFilename;

        } catch (Exception $e) {
            Log::error('Failed to export custom query', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Export to JSON
     */
    public function exportToJson($data, string $filename): string
    {
        try {
            $jsonFilename = "exports/json/{$filename}-" . now()->format('Y-m-d-His') . ".json";

            Storage::put($jsonFilename, json_encode($data, JSON_PRETTY_PRINT));

            Log::info('Data exported to JSON', [
                'filename' => $jsonFilename
            ]);

            return $jsonFilename;

        } catch (Exception $e) {
            Log::error('Failed to export to JSON', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Export to XML
     */
    public function exportToXml($data, string $filename, string $rootElement = 'data'): string
    {
        try {
            $xmlFilename = "exports/xml/{$filename}-" . now()->format('Y-m-d-His') . ".xml";

            $xml = new \SimpleXMLElement("<?xml version=\"1.0\"?><{$rootElement}></{$rootElement}>");
            $this->arrayToXml($data, $xml);

            Storage::put($xmlFilename, $xml->asXML());

            Log::info('Data exported to XML', [
                'filename' => $xmlFilename
            ]);

            return $xmlFilename;

        } catch (Exception $e) {
            Log::error('Failed to export to XML', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate export summary
     */
    public function generateExportSummary(string $exportPath): array
    {
        if (!Storage::exists($exportPath)) {
            throw new Exception('Export file not found');
        }

        return [
            'path' => $exportPath,
            'size' => Storage::size($exportPath),
            'created_at' => Storage::lastModified($exportPath),
            'url' => Storage::url($exportPath),
            'mime_type' => Storage::mimeType($exportPath),
        ];
    }

    /**
     * Clean old exports
     */
    public function cleanOldExports(int $daysToKeep = 30): int
    {
        $count = 0;
        $cutoffDate = now()->subDays($daysToKeep)->timestamp;

        $files = Storage::allFiles('exports');

        foreach ($files as $file) {
            if (Storage::lastModified($file) < $cutoffDate) {
                Storage::delete($file);
                $count++;
            }
        }

        Log::info('Old exports cleaned', [
            'deleted_count' => $count,
            'days_kept' => $daysToKeep
        ]);

        return $count;
    }

    /**
     * Calculate company statistics
     */
    protected function calculateCompanyStatistics(Company $company, $startDate, $endDate): array
    {
        return [
            'total_customers' => $company->customers()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'total_appointments' => $company->appointments()
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->count(),
            'total_revenue' => $company->invoices()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_amount'),
            'total_calls' => $company->calls()
                ->whereBetween('started_at', [$startDate, $endDate])
                ->count(),
            'new_customers' => $company->customers()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'completed_appointments' => $company->appointments()
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->count(),
        ];
    }

    /**
     * Convert array to XML
     */
    protected function arrayToXml($data, &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key;
                }
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    /**
     * Download export file
     */
    public function download(string $path)
    {
        if (!Storage::exists($path)) {
            throw new Exception('Export file not found');
        }

        return Storage::download($path);
    }

    /**
     * Delete export file
     */
    public function delete(string $path): bool
    {
        if (Storage::exists($path)) {
            return Storage::delete($path);
        }

        return false;
    }
}