<?php

namespace App\Livewire;

use App\Enums\BookingStep;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Services\Appointments\Contracts\AvailabilityServiceInterface;
use App\Services\Appointments\Contracts\BookingServiceInterface;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

/**
 * AppointmentBookingWizard
 *
 * Modern wizard-style appointment booking component
 * with 4-step progressive disclosure pattern.
 *
 * Architecture:
 * - Service Layer: Dependency injection for clean separation
 * - State Machine: BookingStep enum for flow control
 * - Event-Driven: Browser events for loose coupling
 * - Progressive Disclosure: One step at a time
 */
class AppointmentBookingWizard extends Component
{
    // ========================================
    // State Management
    // ========================================

    /** @var BookingStep Current wizard step */
    public BookingStep $currentStep = BookingStep::SERVICE;

    /** @var bool Loading state */
    public bool $loading = false;

    /** @var string|null Error message */
    public ?string $error = null;

    // ========================================
    // Step 0: Branch Selection (CRITICAL)
    // ========================================

    public ?string $selectedBranchId = null;
    public array $availableBranches = [];

    // ========================================
    // Step 1: Customer Data
    // ========================================

    public string $customerName = '';
    public string $customerPhone = '';
    public string $customerEmail = '';
    public ?string $customerId = null;

    // Customer search
    public string $customerSearchQuery = '';
    public array $customerSearchResults = [];
    public bool $showNewCustomerForm = false;

    // ========================================
    // Step 2: Service Selection
    // ========================================

    public ?string $selectedServiceId = null;
    public ?Service $selectedService = null;
    public array $availableServices = [];

    // ========================================
    // Step 3: Staff & DateTime Selection
    // ========================================

    public ?string $selectedStaffId = null;
    public array $availableStaff = [];
    public ?string $selectedDate = null;
    public ?string $selectedTime = null;
    public array $availableDates = [];
    public array $timeSlots = [];
    public Carbon $calendarMonth;

    // ========================================
    // Step 4: Confirmation
    // ========================================

    public ?array $confirmationData = null;
    public ?string $notes = null;

    // ========================================
    // Services (Dependency Injection)
    // ========================================

    protected AvailabilityServiceInterface $availabilityService;
    protected BookingServiceInterface $bookingService;

    // ========================================
    // Validation Rules
    // ========================================

    protected function rules(): array
    {
        return [
            'customerName' => 'required|string|min:2|max:255',
            'customerPhone' => 'nullable|string|regex:/^[+]?[0-9\s\-\(\)]+$/|min:8|max:20',
            'customerEmail' => 'nullable|email|max:255',
            'selectedServiceId' => 'required|exists:services,id',
            'selectedStaffId' => 'nullable|exists:staff,id',
            'selectedDate' => 'required|date|after_or_equal:today',
            'selectedTime' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ];
    }

    // ========================================
    // Lifecycle Hooks
    // ========================================

    public function boot(
        AvailabilityServiceInterface $availabilityService,
        BookingServiceInterface $bookingService
    ): void {
        $this->availabilityService = $availabilityService;
        $this->bookingService = $bookingService;
    }

    public function mount(): void
    {
        $this->currentStep = BookingStep::SERVICE;
        $this->calendarMonth = now()->startOfMonth();

        // ← CRITICAL: Load branches FIRST (foundation for all other selections)
        $this->loadAvailableBranches();
        $this->loadAvailableServices();

        Log::info('[AppointmentBookingWizard] Mounted', [
            'step' => $this->currentStep->value,
            'company_id' => auth()->user()->company_id,
            'selected_branch' => $this->selectedBranchId,
            'available_branches' => count($this->availableBranches),
        ]);
    }

    // ========================================
    // Step Navigation
    // ========================================

    /**
     * Move to next step
     */
    public function nextStep(): void
    {
        $this->error = null;

        try {
            // Validate current step before proceeding
            $this->validateCurrentStep();

            // Execute step-specific logic
            match ($this->currentStep) {
                BookingStep::CUSTOMER => $this->completeCustomerStep(),
                BookingStep::SERVICE => $this->completeServiceStep(),
                BookingStep::STAFF_DATETIME => $this->completeStaffDateTimeStep(),
                BookingStep::CONFIRMATION => $this->completeBooking(),
            };

            // Move to next step (if not final)
            if (!$this->currentStep->isLast()) {
                $this->currentStep = $this->currentStep->next();

                Log::debug('[AppointmentBookingWizard] Moved to next step', [
                    'step' => $this->currentStep->value,
                ]);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are handled automatically by Livewire
            throw $e;

        } catch (\Exception $e) {
            Log::error('[AppointmentBookingWizard] Error in nextStep', [
                'step' => $this->currentStep->value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error = 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.';
        }
    }

    /**
     * Move to previous step
     */
    public function previousStep(): void
    {
        if (!$this->currentStep->isFirst()) {
            $this->currentStep = $this->currentStep->previous();
            $this->error = null;

            Log::debug('[AppointmentBookingWizard] Moved to previous step', [
                'step' => $this->currentStep->value,
            ]);
        }
    }

    /**
     * Jump to specific step (only backwards or to completed steps)
     */
    public function goToStep(string $step): void
    {
        $targetStep = BookingStep::from($step);

        // Only allow going backwards
        if ($targetStep->number() < $this->currentStep->number()) {
            $this->currentStep = $targetStep;
            $this->error = null;

            Log::debug('[AppointmentBookingWizard] Jumped to step', [
                'step' => $this->currentStep->value,
            ]);
        }
    }

    // ========================================
    // Branch & Service Loading (Data Preparation)
    // ========================================

    /**
     * Load available branches for company
     *
     * CRITICAL: Must be called FIRST as branch is foundation
     * for service selection, availability checking, and booking.
     */
    protected function loadAvailableBranches(): void
    {
        $this->availableBranches = Branch::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'address'])
            ->toArray();

        // Auto-select if only one branch exists
        if (count($this->availableBranches) === 1) {
            $this->selectedBranchId = $this->availableBranches[0]['id'];

            Log::info('[AppointmentBookingWizard] Auto-selected single branch', [
                'branch_id' => $this->selectedBranchId,
                'branch_name' => $this->availableBranches[0]['name'],
            ]);
        }

        Log::debug('[AppointmentBookingWizard] Branches loaded', [
            'count' => count($this->availableBranches),
            'selected' => $this->selectedBranchId,
        ]);
    }

    /**
     * Load available services for company
     */
    protected function loadAvailableServices(): void
    {
        $this->availableServices = Service::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('priority', 'asc')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'duration_minutes'])
            ->toArray();

        Log::debug('[AppointmentBookingWizard] Services loaded', [
            'count' => count($this->availableServices),
        ]);
    }

    /**
     * Select a branch
     */
    public function selectBranch(string $branchId): void
    {
        $branchExists = collect($this->availableBranches)
            ->firstWhere('id', $branchId);

        if ($branchExists) {
            $this->selectedBranchId = $branchId;

            Log::info('[AppointmentBookingWizard] Branch selected', [
                'branch_id' => $branchId,
                'branch_name' => $branchExists['name'],
            ]);

            $this->dispatch('notify', [
                'message' => "Filiale gewählt: {$branchExists['name']}",
                'type' => 'info',
            ]);
        }
    }

    // ========================================
    // Step-Specific Logic
    // ========================================

    /**
     * Complete Customer Step
     */
    protected function completeCustomerStep(): void
    {
        $this->validate([
            'customerName' => $this->rules()['customerName'],
            'customerPhone' => $this->rules()['customerPhone'],
            'customerEmail' => $this->rules()['customerEmail'],
        ]);

        // If existing customer selected, use that; otherwise create new
        if ($this->customerId) {
            Log::info('[AppointmentBookingWizard] Using existing customer', [
                'customer_id' => $this->customerId,
            ]);
        } else {
            // Find or create customer
            $customer = $this->bookingService->findOrCreateCustomer([
                'name' => $this->customerName,
                'phone' => $this->customerPhone,
                'email' => $this->customerEmail,
            ]);

            $this->customerId = $customer->id;

            Log::info('[AppointmentBookingWizard] New customer created', [
                'customer_id' => $this->customerId,
                'name' => $this->customerName,
            ]);
        }
    }

    /**
     * Complete Service Step
     */
    protected function completeServiceStep(): void
    {
        $this->validate([
            'selectedServiceId' => $this->rules()['selectedServiceId'],
        ]);

        $this->selectedService = Service::findOrFail($this->selectedServiceId);

        // Load available staff for this service
        $this->loadAvailableStaff();

        // Load available dates for next step (without staff filter initially)
        $this->loadAvailableDates();

        Log::info('[AppointmentBookingWizard] Service step completed', [
            'service_id' => $this->selectedServiceId,
            'service_name' => $this->selectedService->name,
        ]);
    }

    /**
     * Complete Staff & DateTime Step
     */
    protected function completeStaffDateTimeStep(): void
    {
        $this->validate([
            'selectedDate' => $this->rules()['selectedDate'],
            'selectedTime' => $this->rules()['selectedTime'],
        ]);

        // Staff is optional - validate only if selected
        if ($this->selectedStaffId) {
            $this->validate([
                'selectedStaffId' => $this->rules()['selectedStaffId'],
            ]);
        }

        // Verify slot is still available
        $datetime = Carbon::parse($this->selectedDate . ' ' . $this->selectedTime);

        $isAvailable = $this->availabilityService->isSlotAvailable(
            $this->selectedServiceId,
            $datetime,
            $this->selectedService->duration_minutes ?? 30,
            $this->selectedStaffId
        );

        if (!$isAvailable) {
            throw new \Exception('Der gewählte Termin ist leider nicht mehr verfügbar.');
        }

        // Prepare confirmation data
        $this->confirmationData = [
            'customer' => [
                'name' => $this->customerName,
                'phone' => $this->customerPhone,
                'email' => $this->customerEmail,
            ],
            'service' => [
                'name' => $this->selectedService->name,
                'duration' => $this->selectedService->duration_minutes,
                'price' => $this->selectedService->price,
            ],
            'datetime' => [
                'date' => $datetime->format('d.m.Y'),
                'time' => $datetime->format('H:i'),
                'day_name' => $datetime->locale('de')->isoFormat('dddd'),
            ],
        ];

        Log::info('[AppointmentBookingWizard] DateTime step completed', [
            'date' => $this->selectedDate,
            'time' => $this->selectedTime,
        ]);
    }

    /**
     * Complete Booking (Final Step)
     *
     * CRITICAL: branch_id is REQUIRED for multi-tenant isolation
     * and Cal.com sync context.
     */
    protected function completeBooking(): void
    {
        $this->loading = true;
        $this->error = null;

        try {
            // ═══════════════════════════════════════════════════════════
            // CRITICAL: Ensure selectedBranchId is set
            // ═══════════════════════════════════════════════════════════
            // Fallback: If not selected, try to auto-select from company
            if (!$this->selectedBranchId) {
                $companyId = auth()->user()->company_id;

                // Try to find and auto-select the only branch
                $branches = Branch::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->get(['id']);

                if ($branches->count() === 1) {
                    // Auto-select the only branch
                    $this->selectedBranchId = $branches->first()->id;

                    Log::warning('[AppointmentBookingWizard] Auto-selected branch in completeBooking', [
                        'branch_id' => $this->selectedBranchId,
                    ]);
                } else {
                    // Multiple or no branches found
                    throw new \Exception(
                        'Keine Filiale ausgewählt. Bitte wählen Sie eine Filiale aus, bevor Sie einen Termin buchen.'
                    );
                }
            }

            $datetime = Carbon::parse($this->selectedDate . ' ' . $this->selectedTime);

            // ═══════════════════════════════════════════════════════════
            // CREATE APPOINTMENT (with required branch_id)
            // ═══════════════════════════════════════════════════════════
            $appointment = $this->bookingService->createAppointment([
                'customer_id' => $this->customerId,
                'service_id' => $this->selectedServiceId,
                'staff_id' => $this->selectedStaffId,
                'branch_id' => $this->selectedBranchId,  // ← CRITICAL: Added!
                'start_time' => $datetime->toDateTimeString(),
                'notes' => $this->notes,
                'company_id' => auth()->user()->company_id,
            ]);

            Log::info('[AppointmentBookingWizard] Booking completed successfully', [
                'appointment_id' => $appointment->id,
                'customer_id' => $this->customerId,
                'service_id' => $this->selectedServiceId,
                'branch_id' => $this->selectedBranchId,
                'starts_at' => $datetime->toIso8601String(),
            ]);

            // Dispatch browser event for success
            $this->dispatch('appointment-booked', [
                'appointment_id' => $appointment->id,
            ]);

            // Redirect to success page (Livewire style)
            $this->redirect(route('filament.admin.resources.appointments.view', [
                'record' => $appointment->id,
            ]));

        } catch (\Exception $e) {
            // Provide user-friendly error messages
            $this->error = $this->getErrorMessage($e);

            Log::error('[AppointmentBookingWizard] Booking failed', [
                'error' => $e->getMessage(),
                'error_code' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->loading = false;
        }
    }

    /**
     * Convert exception to user-friendly error message
     */
    private function getErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();

        // Provide context-specific error messages
        return match(true) {
            str_contains($message, 'branch') => 'Filial-Problem: ' . $message,
            str_contains($message, 'Duplicate booking') => 'Dieser Termin wurde bereits gebucht. Bitte wählen Sie einen anderen Zeitpunkt.',
            str_contains($message, 'past') => 'Der gewählte Zeitpunkt liegt in der Vergangenheit. Bitte wählen Sie einen zukünftigen Zeitpunkt.',
            str_contains($message, 'Cal.com') => 'Synchronisierungsproblem mit dem Kalender. Der Termin wurde lokal erstellt, wird aber bald synchronisiert.',
            str_contains($message, 'SECURITY VIOLATION') => '🔒 Sicherheitsverletzung: Multi-Tenant-Isolation verletzt.',
            str_contains($message, 'CRITICAL') => '⚠️ Kritischer Fehler: ' . $message,
            default => 'Die Buchung konnte nicht abgeschlossen werden: ' . $message,
        };
    }

    // ========================================
    // Data Loading (Availability & Staff)
    // ========================================

    /**
     * Load available staff for selected service
     */
    protected function loadAvailableStaff(): void
    {
        if (!$this->selectedServiceId) {
            return;
        }

        $this->availableStaff = \App\Models\Staff::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->whereHas('services', function($query) {
                $query->where('service_id', $this->selectedServiceId);
            })
            ->orderBy('name')
            ->get()
            ->toArray();

        Log::debug('[AppointmentBookingWizard] Available staff loaded', [
            'service_id' => $this->selectedServiceId,
            'staff_count' => count($this->availableStaff),
        ]);
    }

    /**
     * Load available dates for selected service (filtered by staff if selected)
     */
    protected function loadAvailableDates(): void
    {
        if (!$this->selectedServiceId) {
            return;
        }

        $this->loading = true;

        try {
            $this->availableDates = $this->availabilityService->getAvailableDates(
                $this->selectedServiceId,
                now(),
                30, // Look ahead 30 days
                $this->selectedStaffId // Filter by staff if selected
            );

            Log::debug('[AppointmentBookingWizard] Available dates loaded', [
                'service_id' => $this->selectedServiceId,
                'staff_id' => $this->selectedStaffId,
                'dates_count' => count($this->availableDates),
            ]);

        } catch (\Exception $e) {
            Log::error('[AppointmentBookingWizard] Failed to load dates', [
                'error' => $e->getMessage(),
            ]);
            $this->error = 'Verfügbare Termine konnten nicht geladen werden.';
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Load time slots for selected date (filtered by staff if selected)
     */
    public function loadTimeSlots(): void
    {
        if (!$this->selectedDate || !$this->selectedServiceId) {
            return;
        }

        $this->loading = true;

        try {
            $date = Carbon::parse($this->selectedDate);

            $this->timeSlots = $this->availabilityService->getDaySlots(
                $this->selectedServiceId,
                $date,
                $this->selectedStaffId // Filter by staff if selected
            );

            Log::debug('[AppointmentBookingWizard] Time slots loaded', [
                'date' => $this->selectedDate,
                'staff_id' => $this->selectedStaffId,
                'slots_count' => count($this->timeSlots),
            ]);

        } catch (\Exception $e) {
            Log::error('[AppointmentBookingWizard] Failed to load time slots', [
                'error' => $e->getMessage(),
            ]);
            $this->error = 'Verfügbare Zeiten konnten nicht geladen werden.';
        } finally {
            $this->loading = false;
        }
    }

    // ========================================
    // User Actions
    // ========================================

    /**
     * Select a service
     */
    public function selectService(string $serviceId): void
    {
        $this->selectedServiceId = $serviceId;
        $this->selectedService = Service::find($serviceId);

        Log::debug('[AppointmentBookingWizard] Service selected', [
            'service_id' => $serviceId,
        ]);

        // Automatically proceed to next step after service selection
        $this->nextStep();
    }

    /**
     * Select a staff member
     */
    public function selectStaff(?string $staffId): void
    {
        $this->selectedStaffId = $staffId;
        $this->selectedDate = null; // Reset date when staff changes
        $this->selectedTime = null; // Reset time when staff changes

        // Reload availability with new staff filter
        $this->loadAvailableDates();

        Log::debug('[AppointmentBookingWizard] Staff selected', [
            'staff_id' => $staffId,
        ]);
    }

    /**
     * Livewire hook: Triggered when customerSearchQuery changes
     */
    public function updatedCustomerSearchQuery(): void
    {
        $this->searchCustomers();
    }

    /**
     * Search for existing customers by name
     */
    protected function searchCustomers(): void
    {
        if (strlen($this->customerSearchQuery) < 2) {
            $this->customerSearchResults = [];
            return;
        }

        $companyId = auth()->user()->company_id;
        $query = $this->customerSearchQuery;

        // Fuzzy search by name
        $this->customerSearchResults = Customer::where('company_id', $companyId)
            ->where('name', 'LIKE', "%{$query}%")
            ->orderByRaw("
                CASE
                    WHEN name LIKE ? THEN 1
                    WHEN name LIKE ? THEN 2
                    ELSE 3
                END
            ", ["{$query}%", "%{$query}%"])
            ->limit(10)
            ->get(['id', 'name', 'phone', 'email'])
            ->toArray();

        Log::debug('[AppointmentBookingWizard] Customer search', [
            'query' => $query,
            'results_count' => count($this->customerSearchResults),
        ]);
    }

    /**
     * Select an existing customer from search results
     */
    public function selectExistingCustomer(string $customerId): void
    {
        $customer = Customer::findOrFail($customerId);

        $this->customerId = $customer->id;
        $this->customerName = $customer->name;
        $this->customerPhone = $customer->phone ?? '';
        $this->customerEmail = $customer->email ?? '';

        // Clear search
        $this->customerSearchQuery = '';
        $this->customerSearchResults = [];
        $this->showNewCustomerForm = false;

        Log::info('[AppointmentBookingWizard] Existing customer selected', [
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Toggle new customer form / Clear selected customer
     */
    public function toggleNewCustomerForm(): void
    {
        // Reset customer data
        $this->customerId = null;
        $this->customerName = '';
        $this->customerPhone = '';
        $this->customerEmail = '';

        // Clear search
        $this->customerSearchQuery = '';
        $this->customerSearchResults = [];
        $this->showNewCustomerForm = true;

        Log::debug('[AppointmentBookingWizard] Cleared customer selection');
    }

    /**
     * Select a date
     */
    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedTime = null; // Reset time when date changes
        $this->loadTimeSlots();

        Log::debug('[AppointmentBookingWizard] Date selected', [
            'date' => $date,
        ]);
    }

    /**
     * Select a time slot
     */
    public function selectTime(string $time): void
    {
        $this->selectedTime = $time;

        Log::debug('[AppointmentBookingWizard] Time selected', [
            'time' => $time,
        ]);
    }

    /**
     * Change calendar month
     */
    public function changeMonth(int $offset): void
    {
        $this->calendarMonth = $this->calendarMonth->copy()->addMonths($offset);

        Log::debug('[AppointmentBookingWizard] Calendar month changed', [
            'month' => $this->calendarMonth->format('Y-m'),
        ]);
    }

    // ========================================
    // Computed Properties
    // ========================================

    /**
     * Get step progress percentage
     */
    public function getProgressPercentageProperty(): int
    {
        return (int) (($this->currentStep->number() / 4) * 100);
    }

    /**
     * Check if current step is valid
     */
    protected function validateCurrentStep(): void
    {
        match ($this->currentStep) {
            BookingStep::CUSTOMER => $this->validate([
                'customerName' => $this->rules()['customerName'],
                'customerPhone' => $this->rules()['customerPhone'],
                'customerEmail' => $this->rules()['customerEmail'],
            ]),
            BookingStep::SERVICE => $this->validate([
                'selectedServiceId' => $this->rules()['selectedServiceId'],
            ]),
            BookingStep::STAFF_DATETIME => $this->validate([
                'selectedDate' => $this->rules()['selectedDate'],
                'selectedTime' => $this->rules()['selectedTime'],
            ]),
            BookingStep::CONFIRMATION => null, // No validation needed
        };
    }

    // ========================================
    // Render
    // ========================================

    public function render()
    {
        return view('livewire.appointment-booking-wizard', [
            'steps' => BookingStep::all(),
            'progressPercentage' => $this->progressPercentage,
        ]);
    }
}
