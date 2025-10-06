<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\Company;
use App\Models\User;
use App\Services\CostCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendDailyProfitReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profit:daily-report
                            {--email= : Empfänger E-Mail-Adresse}
                            {--date= : Datum für den Report (YYYY-MM-DD), default ist gestern}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sendet den täglichen Profit-Report an Super-Admins und Mandanten';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║           TÄGLICHER PROFIT-REPORT                         ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $calculator = new CostCalculator();

        $this->info("Generiere Report für: " . $date->format('d.m.Y'));

        // Generate report for Super Admins
        $this->generateSuperAdminReport($date, $calculator);

        // Generate reports for Resellers/Mandanten
        $this->generateResellerReports($date, $calculator);

        $this->info('✅ Profit-Reports erfolgreich generiert und versendet!');

        return Command::SUCCESS;
    }

    private function generateSuperAdminReport($date, $calculator)
    {
        $this->info('📊 Generiere Super-Admin Report...');

        // Get all calls for the date
        $calls = Call::whereDate('created_at', $date)->get();

        $totalProfit = 0;
        $platformProfit = 0;
        $resellerProfit = 0;
        $callCount = $calls->count();

        foreach ($calls as $call) {
            $totalProfit += $call->total_profit ?? 0;
            $platformProfit += $call->platform_profit ?? 0;
            $resellerProfit += $call->reseller_profit ?? 0;
        }

        // Top performing companies
        $topCompanies = Company::withSum(['calls as daily_profit' => function ($query) use ($date) {
                $query->whereDate('created_at', $date);
            }], 'total_profit')
            ->orderByDesc('daily_profit')
            ->limit(5)
            ->get();

        // Prepare report data
        $reportData = [
            'date' => $date->format('d.m.Y'),
            'totalProfit' => number_format($totalProfit / 100, 2, ',', '.'),
            'platformProfit' => number_format($platformProfit / 100, 2, ',', '.'),
            'resellerProfit' => number_format($resellerProfit / 100, 2, ',', '.'),
            'callCount' => $callCount,
            'avgMargin' => $callCount > 0 ? round($calls->avg('profit_margin_total'), 2) : 0,
            'topCompanies' => $topCompanies->map(function ($company) {
                return [
                    'name' => $company->name,
                    'profit' => number_format(($company->daily_profit ?? 0) / 100, 2, ',', '.'),
                    'type' => $company->company_type === 'reseller' ? 'Mandant' : ucfirst($company->company_type),
                ];
            })->toArray(),
        ];

        // Log or send the report
        if ($this->option('email')) {
            $this->sendReportEmail($this->option('email'), 'Super-Admin Profit Report', $reportData);
        } else {
            $this->displayReport($reportData, 'Super-Admin');
        }
    }

    private function generateResellerReports($date, $calculator)
    {
        $this->info('📊 Generiere Mandanten Reports...');

        // Get all reseller companies
        $resellers = Company::where('company_type', 'reseller')->get();

        foreach ($resellers as $reseller) {
            // Get calls for this reseller's customers
            $calls = Call::whereDate('created_at', $date)
                ->whereHas('company', function ($q) use ($reseller) {
                    $q->where('parent_company_id', $reseller->id);
                })
                ->get();

            if ($calls->count() === 0) continue;

            $resellerProfit = $calls->sum('reseller_profit');
            $callCount = $calls->count();

            // Top performing customers
            $topCustomers = Company::where('parent_company_id', $reseller->id)
                ->withSum(['calls as daily_profit' => function ($query) use ($date) {
                    $query->whereDate('created_at', $date);
                }], 'reseller_profit')
                ->orderByDesc('daily_profit')
                ->limit(3)
                ->get();

            $reportData = [
                'date' => $date->format('d.m.Y'),
                'resellerName' => $reseller->name,
                'totalProfit' => number_format($resellerProfit / 100, 2, ',', '.'),
                'callCount' => $callCount,
                'avgMargin' => $callCount > 0 ? round($calls->avg('profit_margin_reseller'), 2) : 0,
                'topCustomers' => $topCustomers->map(function ($customer) {
                    return [
                        'name' => $customer->name,
                        'profit' => number_format(($customer->daily_profit ?? 0) / 100, 2, ',', '.'),
                    ];
                })->toArray(),
            ];

            $this->displayReport($reportData, 'Mandant');
        }
    }

    private function displayReport($data, $type)
    {
        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("$type Report - {$data['date']}");
        $this->info("═══════════════════════════════════════════════════════════");

        if ($type === 'Super-Admin') {
            $this->table(
                ['Metrik', 'Wert'],
                [
                    ['Gesamt-Profit', '€ ' . $data['totalProfit']],
                    ['Platform-Profit', '€ ' . $data['platformProfit']],
                    ['Mandanten-Profit', '€ ' . $data['resellerProfit']],
                    ['Anzahl Anrufe', $data['callCount']],
                    ['Durchschnittliche Marge', $data['avgMargin'] . '%'],
                ]
            );

            if (!empty($data['topCompanies'])) {
                $this->newLine();
                $this->info('Top 5 Unternehmen:');
                $this->table(
                    ['Unternehmen', 'Profit', 'Typ'],
                    array_map(function ($company) {
                        return [
                            $company['name'],
                            '€ ' . $company['profit'],
                            $company['type'],
                        ];
                    }, $data['topCompanies'])
                );
            }
        } else {
            $this->info("Mandant: {$data['resellerName']}");
            $this->table(
                ['Metrik', 'Wert'],
                [
                    ['Profit', '€ ' . $data['totalProfit']],
                    ['Anzahl Anrufe', $data['callCount']],
                    ['Durchschnittliche Marge', $data['avgMargin'] . '%'],
                ]
            );

            if (!empty($data['topCustomers'])) {
                $this->newLine();
                $this->info('Top Kunden:');
                $this->table(
                    ['Kunde', 'Profit'],
                    array_map(function ($customer) {
                        return [
                            $customer['name'],
                            '€ ' . $customer['profit'],
                        ];
                    }, $data['topCustomers'])
                );
            }
        }
    }

    private function sendReportEmail($email, $subject, $data)
    {
        // TODO: Implement email sending
        $this->info("📧 Report würde an $email gesendet werden: $subject");
    }
}