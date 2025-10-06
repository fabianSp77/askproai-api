<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Customer Number',
            'Name',
            'Company',
            'Email',
            'Phone',
            'Status',
            'Journey Status',
            'Total Spent',
            'Loyalty Points',
            'VIP',
            'Created At',
            'Last Activity',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->customer_number,
            $customer->name,
            $customer->company_name,
            $customer->email,
            $customer->phone,
            $customer->status,
            $customer->journey_status,
            $customer->total_spent,
            $customer->loyalty_points,
            $customer->is_vip ? 'Yes' : 'No',
            $customer->created_at ? $customer->created_at->format('Y-m-d H:i') : '',
            $customer->last_seen_at ? $customer->last_seen_at->format('Y-m-d H:i') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}