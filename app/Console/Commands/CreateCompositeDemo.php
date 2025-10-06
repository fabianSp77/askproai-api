<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;

class CreateCompositeDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'composite:demo
                            {--company= : Company ID or name to use}
                            {--branch= : Branch ID or name to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create demo composite services with segments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating Composite Service Demo...');

        // Find or select company
        $company = $this->getCompany();
        if (!$company) {
            $this->error('No company found. Please create a company first.');
            return Command::FAILURE;
        }

        // Find or select branch
        $branch = $this->getBranch($company);
        if (!$branch) {
            $this->error('No branch found. Please create a branch first.');
            return Command::FAILURE;
        }

        $this->info("Using Company: {$company->name}");
        $this->info("Using Branch: {$branch->name}");

        // Create composite services
        $services = $this->createCompositeServices($company, $branch);

        // Create staff and assign to services
        $staff = $this->createStaffMembers($company, $branch);
        $this->assignStaffToServices($services, $staff);

        $this->info('✅ Demo composite services created successfully!');

        // Display summary
        $this->displaySummary($services, $staff);

        return Command::SUCCESS;
    }

    private function getCompany()
    {
        if ($this->option('company')) {
            $identifier = $this->option('company');
            return Company::where('id', $identifier)
                ->orWhere('name', 'LIKE', "%{$identifier}%")
                ->first();
        }

        return Company::first();
    }

    private function getBranch($company)
    {
        if ($this->option('branch')) {
            $identifier = $this->option('branch');
            return Branch::where('company_id', $company->id)
                ->where(function ($query) use ($identifier) {
                    $query->where('id', $identifier)
                        ->orWhere('name', 'LIKE', "%{$identifier}%");
                })
                ->first();
        }

        return Branch::where('company_id', $company->id)->first();
    }

    private function createCompositeServices($company, $branch): array
    {
        $services = [];

        // Temporarily disable observer for demo creation
        Service::unsetEventDispatcher();

        // Service 1: Hair Treatment with Break
        $services[] = Service::updateOrCreate(
            [
                'name' => 'Premium Hair Treatment',
                'company_id' => $company->id,
            ],
            [
                'branch_id' => $branch->id,
                'description' => 'Multi-phase hair treatment with development time',
                'duration_minutes' => 120,
                'price' => 150.00,
                'composite' => true,
                'calcom_event_type_id' => 'demo-composite-' . uniqid(),
                'segments' => [
                    [
                        'key' => 'A',
                        'name' => 'Hair Preparation',
                        'duration' => 30,
                        'gap_after' => 15,
                    ],
                    [
                        'key' => 'B',
                        'name' => 'Treatment Application',
                        'duration' => 20,
                        'gap_after' => 30,
                    ],
                    [
                        'key' => 'C',
                        'name' => 'Final Styling',
                        'duration' => 25,
                        'gap_after' => 0,
                    ],
                ],
                'pause_bookable_policy' => 'free',
                'is_active' => true,
                'is_online' => true,
            ]
        );

        // Service 2: Therapy Session with Breaks
        $services[] = Service::updateOrCreate(
            [
                'name' => 'Comprehensive Therapy Session',
                'company_id' => $company->id,
            ],
            [
                'branch_id' => $branch->id,
                'description' => 'Multi-part therapy session with reflection periods',
                'duration_minutes' => 150,
                'price' => 200.00,
                'composite' => true,
                'calcom_event_type_id' => 'demo-composite-' . uniqid(),
                'segments' => [
                    [
                        'key' => 'A',
                        'name' => 'Initial Assessment',
                        'duration' => 45,
                        'gap_after' => 10,
                    ],
                    [
                        'key' => 'B',
                        'name' => 'Main Therapy',
                        'duration' => 60,
                        'gap_after' => 15,
                    ],
                    [
                        'key' => 'C',
                        'name' => 'Review & Planning',
                        'duration' => 20,
                        'gap_after' => 0,
                    ],
                ],
                'pause_bookable_policy' => 'blocked',
                'is_active' => true,
                'is_online' => true,
            ]
        );

        // Service 3: Complex Medical Procedure
        $services[] = Service::updateOrCreate(
            [
                'name' => 'Medical Examination Series',
                'company_id' => $company->id,
            ],
            [
                'branch_id' => $branch->id,
                'description' => 'Multiple examination phases with waiting periods',
                'duration_minutes' => 180,
                'price' => 350.00,
                'composite' => true,
                'calcom_event_type_id' => 'demo-composite-' . uniqid(),
                'segments' => [
                    [
                        'key' => 'A',
                        'name' => 'Blood Draw & Initial Tests',
                        'duration' => 15,
                        'gap_after' => 45,
                    ],
                    [
                        'key' => 'B',
                        'name' => 'Physical Examination',
                        'duration' => 30,
                        'gap_after' => 20,
                    ],
                    [
                        'key' => 'C',
                        'name' => 'Specialist Consultation',
                        'duration' => 40,
                        'gap_after' => 10,
                    ],
                    [
                        'key' => 'D',
                        'name' => 'Results Discussion',
                        'duration' => 20,
                        'gap_after' => 0,
                    ],
                ],
                'pause_bookable_policy' => 'flexible',
                'is_active' => true,
                'is_online' => false,
            ]
        );

        // Re-enable observer
        Service::setEventDispatcher(app('events'));

        return $services;
    }

    private function createStaffMembers($company, $branch): array
    {
        $staff = [];

        $staffData = [
            ['name' => 'Dr. Sarah Johnson', 'email' => 'sarah.johnson@demo.com'],
            ['name' => 'Michael Chen', 'email' => 'michael.chen@demo.com'],
            ['name' => 'Emma Williams', 'email' => 'emma.williams@demo.com'],
            ['name' => 'David Martinez', 'email' => 'david.martinez@demo.com'],
        ];

        foreach ($staffData as $data) {
            $staffMember = Staff::where('email', $data['email'])
                ->where('company_id', $company->id)
                ->first();

            if (!$staffMember) {
                $staffMember = Staff::create([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'active' => true,
                    'is_bookable' => true,
                ]);
            }

            $staff[] = $staffMember;
        }

        return $staff;
    }

    private function assignStaffToServices($services, $staff): void
    {
        // Assign staff to services with different segment capabilities

        // Service 1: Hair Treatment
        if (isset($services[0]) && count($staff) >= 2) {
            // Senior specialist can handle all segments
            $services[0]->staff()->syncWithoutDetaching([
                $staff[0]->id => [
                    'is_primary' => true,
                    'can_book' => true,
                    'allowed_segments' => json_encode(['A', 'B', 'C']),
                    'skill_level' => 'expert',
                    'weight' => 2.0,
                ],
                // Junior can only handle preparation and styling
                $staff[2]->id => [
                    'is_primary' => false,
                    'can_book' => true,
                    'allowed_segments' => json_encode(['A', 'C']),
                    'skill_level' => 'junior',
                    'weight' => 1.0,
                ],
            ]);
        }

        // Service 2: Therapy Session
        if (isset($services[1]) && count($staff) >= 2) {
            $services[1]->staff()->syncWithoutDetaching([
                $staff[1]->id => [
                    'is_primary' => true,
                    'can_book' => true,
                    'allowed_segments' => json_encode(['A', 'B', 'C']),
                    'skill_level' => 'senior',
                    'weight' => 1.5,
                ],
                $staff[3]->id => [
                    'is_primary' => false,
                    'can_book' => true,
                    'allowed_segments' => json_encode(['B', 'C']),
                    'skill_level' => 'expert',
                    'weight' => 2.5,
                ],
            ]);
        }

        // Service 3: Medical Examination
        if (isset($services[2]) && count($staff) >= 4) {
            $services[2]->staff()->syncWithoutDetaching([
                $staff[0]->id => [
                    'is_primary' => true,
                    'can_book' => true,
                    'allowed_segments' => json_encode(['C', 'D']),
                    'skill_level' => 'expert',
                    'weight' => 3.0,
                    'specialization_notes' => json_encode('Specialist consultation and results'),
                ],
                $staff[1]->id => [
                    'is_primary' => false,
                    'can_book' => true,
                    'allowed_segments' => json_encode(['B']),
                    'skill_level' => 'senior',
                    'weight' => 2.0,
                    'specialization_notes' => json_encode('Physical examination specialist'),
                ],
                $staff[2]->id => [
                    'is_primary' => false,
                    'can_book' => true,
                    'allowed_segments' => json_encode(['A']),
                    'skill_level' => 'regular',
                    'weight' => 1.0,
                    'specialization_notes' => json_encode('Blood draw and initial tests'),
                ],
            ]);
        }
    }

    private function displaySummary($services, $staff): void
    {
        $this->newLine();
        $this->table(
            ['Service', 'Segments', 'Total Duration', 'Policy'],
            collect($services)->map(function ($service) {
                $segments = collect($service->segments);
                $totalActive = $segments->sum('duration');
                $totalGaps = $segments->slice(0, -1)->sum('gap_after');
                $total = $totalActive + $totalGaps;

                return [
                    $service->name,
                    $segments->count() . ' segments',
                    "{$total} min ({$totalActive} active + {$totalGaps} gaps)",
                    $service->pause_bookable_policy,
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info('Staff assignments created:');
        foreach ($services as $service) {
            $this->line("  {$service->name}:");
            foreach ($service->staff as $staffMember) {
                $allowedSegments = json_decode($staffMember->pivot->allowed_segments ?? '[]', true);
                $segments = implode(', ', $allowedSegments);
                $this->line("    - {$staffMember->name} ({$staffMember->pivot->skill_level}): Segments [{$segments}]");
            }
        }
    }
}