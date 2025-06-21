<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromMultipleSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RoiExport implements WithMultipleSheets
{
    protected array $companyRoi;
    protected array $branchComparison;
    protected array $hourlyBreakdown;
    protected array $businessHoursComparison;
    protected string $dateFrom;
    protected string $dateTo;
    
    public function __construct(
        array $companyRoi,
        array $branchComparison,
        array $hourlyBreakdown,
        array $businessHoursComparison,
        string $dateFrom,
        string $dateTo
    ) {
        $this->companyRoi = $companyRoi;
        $this->branchComparison = $branchComparison;
        $this->hourlyBreakdown = $hourlyBreakdown;
        $this->businessHoursComparison = $businessHoursComparison;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }
    
    public function sheets(): array
    {
        return [
            new CompanySummarySheet($this->companyRoi, $this->dateFrom, $this->dateTo),
            new BranchComparisonSheet($this->branchComparison),
            new HourlyAnalysisSheet($this->hourlyBreakdown),
            new BusinessHoursSheet($this->businessHoursComparison),
        ];
    }
}

class CompanySummarySheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected array $data;
    protected string $dateFrom;
    protected string $dateTo;
    
    public function __construct(array $data, string $dateFrom, string $dateTo)
    {
        $this->data = $data;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }
    
    public function array(): array
    {
        return [
            ['Zeitraum', $this->dateFrom . ' bis ' . $this->dateTo],
            ['ROI (%)', number_format($this->data['roi'], 2) . '%'],
            ['Status', $this->translateStatus($this->data['roi_status'])],
            ['Umsatz (€)', number_format($this->data['revenue'], 2)],
            ['Kosten (€)', number_format($this->data['cost'], 2)],
            ['Gewinn (€)', number_format($this->data['profit'], 2)],
            ['Gesamttermine', $this->data['total_appointments']],
            ['Abgeschlossene Termine', $this->data['completed_appointments']],
            ['Konversionsrate (%)', number_format($this->data['conversion_rate'], 2) . '%'],
            ['Durchschn. Terminwert (€)', number_format($this->data['avg_appointment_value'], 2)],
        ];
    }
    
    public function headings(): array
    {
        return ['Metrik', 'Wert'];
    }
    
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'B2' => ['font' => ['size' => 16, 'bold' => true]],
        ];
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 20,
        ];
    }
    
    public function title(): string
    {
        return 'Zusammenfassung';
    }
    
    private function translateStatus(string $status): string
    {
        return match($status) {
            'excellent' => 'Exzellent',
            'good' => 'Gut',
            'break-even' => 'Break-Even',
            'negative' => 'Negativ',
            default => $status,
        };
    }
}

class BranchComparisonSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected array $branches;
    
    public function __construct(array $branches)
    {
        $this->branches = $branches;
    }
    
    public function array(): array
    {
        $data = [];
        foreach ($this->branches as $branch) {
            $data[] = [
                $branch['name'],
                number_format($branch['roi'], 2) . '%',
                number_format($branch['revenue'], 2),
                number_format($branch['cost'], 2),
                number_format($branch['profit'], 2),
                $branch['appointments'],
                number_format($branch['avg_value'], 2),
            ];
        }
        return $data;
    }
    
    public function headings(): array
    {
        return [
            'Filiale',
            'ROI (%)',
            'Umsatz (€)',
            'Kosten (€)',
            'Gewinn (€)',
            'Termine',
            'Ø Wert (€)',
        ];
    }
    
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
        ];
    }
    
    public function title(): string
    {
        return 'Filialvergleich';
    }
}

class HourlyAnalysisSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected array $hourlyData;
    
    public function __construct(array $hourlyData)
    {
        $this->hourlyData = $hourlyData;
    }
    
    public function array(): array
    {
        $data = [];
        foreach ($this->hourlyData as $hour) {
            $data[] = [
                $hour['hour_label'],
                number_format($hour['roi'], 2) . '%',
                number_format($hour['revenue'], 2),
                $hour['appointments'],
                $hour['is_business_hour'] ? 'Ja' : 'Nein',
            ];
        }
        return $data;
    }
    
    public function headings(): array
    {
        return [
            'Stunde',
            'ROI (%)',
            'Umsatz (€)',
            'Termine',
            'Geschäftszeit',
        ];
    }
    
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15,
        ];
    }
    
    public function title(): string
    {
        return 'Stündliche Analyse';
    }
}

class BusinessHoursSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected array $comparison;
    
    public function __construct(array $comparison)
    {
        $this->comparison = $comparison;
    }
    
    public function array(): array
    {
        return [
            [
                'Geschäftszeiten (8-18 Uhr)',
                number_format($this->comparison['business_hours']['revenue'], 2),
                $this->comparison['business_hours']['appointments'],
                number_format($this->comparison['business_hours']['percentage'], 2) . '%',
            ],
            [
                'Außerhalb Geschäftszeiten',
                number_format($this->comparison['after_hours']['revenue'], 2),
                $this->comparison['after_hours']['appointments'],
                number_format($this->comparison['after_hours']['percentage'], 2) . '%',
            ],
        ];
    }
    
    public function headings(): array
    {
        return [
            'Zeitraum',
            'Umsatz (€)',
            'Termine',
            'Anteil (%)',
        ];
    }
    
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 20,
            'C' => 20,
            'D' => 20,
        ];
    }
    
    public function title(): string
    {
        return 'Geschäftszeiten-Vergleich';
    }
}