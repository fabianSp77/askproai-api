<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Computed;

/**
 * Cal.com Booking Page
 *
 * Provides Cal.com Atoms integration for appointment booking in Filament admin panel.
 */
class CalcomBooking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.calcom-booking';

    protected static ?string $navigationGroup = 'Appointments';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Book Appointment';

    /**
     * Livewire state for selected branch
     */
    public ?int $selectedBranchId = null;

    /**
     * Livewire state for branches data
     */
    public array $branches = [];

    /**
     * Check if current user is admin (for branch selector visibility)
     */
    #[Computed]
    public function isAdmin(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['super_admin', 'Admin', 'company_owner', 'company_admin']);
    }

    /**
     * Initialize component state
     */
    public function mount(): void
    {
        $user = auth()->user();

        // Set default branch from user profile
        $this->selectedBranchId = $user->branch_id;
    }

    /**
     * Get branches for the current company
     * Called via Livewire wire:init
     */
    public function loadBranches(): void
    {
        $user = auth()->user();

        if (!$user || !$user->company_id) {
            $this->branches = [];
            return;
        }

        try {
            $branches = \App\Models\Branch::where('company_id', $user->company_id)
                ->withCount(['services' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->get()
                ->map(function ($branch) use ($user) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'services_count' => $branch->services_count,
                        'is_default' => $branch->id === $user->branch_id,
                    ];
                })
                ->toArray();

            $this->branches = $branches;

        } catch (\Exception $e) {
            \Log::error('[CalcomBooking] Failed to load branches', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'error' => $e->getMessage()
            ]);
            $this->branches = [];
        }
    }

    /**
     * Handle branch selection change
     * ERR-004: Validates branch belongs to user's company before selection
     */
    public function selectBranch(int $branchId): void
    {
        $user = auth()->user();

        // Validate branch exists and belongs to user's company
        $branch = \App\Models\Branch::where('id', $branchId)
            ->where('company_id', $user->company_id)
            ->first();

        if (!$branch) {
            \Log::warning('[CalcomBooking] Invalid branch selection attempt', [
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'company_id' => $user->company_id,
            ]);

            // Reset to user's default branch
            $this->selectedBranchId = $user->branch_id;
            $this->dispatch('branch-selection-error', message: 'Invalid branch selection');
            return;
        }

        $this->selectedBranchId = $branchId;

        // Store in localStorage via JavaScript (dispatched in blade view)
        $this->dispatch('branch-changed', branchId: $branchId);
    }

    public static function getNavigationLabel(): string
    {
        return 'Cal.com Booking';
    }

    public function getHeading(): string | Htmlable
    {
        return 'Book New Appointment';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Use Cal.com\'s interface to book appointments with real-time availability';
    }

    /**
     * Check if user has access to Cal.com booking
     * User must belong to a company to book appointments
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->company_id !== null;
    }
}
