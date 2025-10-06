<?php

namespace App\Livewire\PublicBooking;

use App\Models\Service;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingLockService;
use App\Services\Communication\NotificationService;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class BookingWizard extends Component
{
    // Wizard Steps
    public int $currentStep = 1;
    public int $totalSteps = 5;

    // Step 1: Service Selection
    public ?int $selectedServiceId = null;
    public ?Service $selectedService = null;
    public array $services = [];
    public string $serviceSearch = '';

    // Step 2: Branch & Staff Selection
    public ?int $selectedBranchId = null;
    public ?Branch $selectedBranch = null;
    public ?int $selectedStaffId = null;
    public ?Staff $selectedStaff = null;
    public array $branches = [];
    public array $availableStaff = [];
    public bool $anyStaff = true;

    // Step 3: Date & Time Selection
    public ?string $selectedDate = null;
    public ?string $selectedTimeSlot = null;
    public array $availableSlots = [];
    public string $timezone = 'Europe/Berlin';

    // Step 4: Customer Information
    public string $customerName = '';
    public string $customerEmail = '';
    public string $customerPhone = '';
    public string $customerNotes = '';
    public bool $acceptsMarketing = false;
    public bool $gdprConsent = false;

    // Step 5: Confirmation
    public ?Appointment $appointment = null;
    public string $confirmationCode = '';

    // UI State
    public bool $isLoading = false;
    public array $errors = [];
    public string $successMessage = '';

    // Rate Limiting
    protected string $rateLimitKey = '';

    protected $rules = [
        'selectedServiceId' => 'required|exists:services,id',
        'selectedBranchId' => 'required|exists:branches,id',
        'selectedDate' => 'required|date|after_or_equal:today',
        'selectedTimeSlot' => 'required|date_format:H:i',
        'customerName' => 'required|string|min:2|max:255',
        'customerEmail' => 'required|email|max:255',
        'customerPhone' => 'nullable|string|min:10|max:20',
        'gdprConsent' => 'accepted',
    ];

    protected $messages = [
        'gdprConsent.accepted' => 'Sie müssen der Datenschutzerklärung zustimmen.',
        'selectedServiceId.required' => 'Bitte wählen Sie einen Service aus.',
        'selectedBranchId.required' => 'Bitte wählen Sie eine Filiale aus.',
        'selectedDate.required' => 'Bitte wählen Sie ein Datum aus.',
        'selectedTimeSlot.required' => 'Bitte wählen Sie eine Uhrzeit aus.',
    ];

    public function mount()
    {
        $this->initializeWizard();
        $this->rateLimitKey = 'booking-wizard:' . request()->ip();
    }

    protected function initializeWizard()
    {
        // Load active services
        $this->services = Cache::remember('public-services-list', 300, function () {
            return Service::with(['company', 'branch'])
                ->where('is_active', true)
                ->where('is_online', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->toArray();
        });

        // Load branches
        $this->branches = Cache::remember('public-branches-list', 300, function () {
            return Branch::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->toArray();
        });

        // Set default date to tomorrow
        $this->selectedDate = Carbon::tomorrow()->format('Y-m-d');

        // Detect timezone
        $this->timezone = request()->header('X-Timezone', 'Europe/Berlin');
    }

    public function updatedSelectedServiceId($value)
    {
        if ($value) {
            $this->selectedService = Service::find($value);
            $this->loadAvailableStaff();
        }
    }

    public function updatedSelectedBranchId($value)
    {
        if ($value) {
            $this->selectedBranch = Branch::find($value);
            $this->loadAvailableStaff();
            $this->loadAvailableSlots();
        }
    }

    public function updatedSelectedDate()
    {
        $this->loadAvailableSlots();
    }

    protected function loadAvailableStaff()
    {
        if (!$this->selectedServiceId || !$this->selectedBranchId) {
            return;
        }

        $this->availableStaff = Cache::remember(
            "staff-{$this->selectedServiceId}-{$this->selectedBranchId}",
            60,
            function () {
                return Staff::whereHas('services', function ($query) {
                    $query->where('service_id', $this->selectedServiceId);
                })
                ->where('branch_id', $this->selectedBranchId)
                ->where('is_active', true)
                ->with(['workingHours'])
                ->get()
                ->toArray();
            }
        );
    }

    protected function loadAvailableSlots()
    {
        if (!$this->selectedServiceId || !$this->selectedBranchId || !$this->selectedDate) {
            return;
        }

        $this->isLoading = true;

        try {
            $availabilityService = app(AvailabilityService::class);

            $slots = $availabilityService->getAvailableSlots(
                serviceId: $this->selectedServiceId,
                branchId: $this->selectedBranchId,
                date: Carbon::parse($this->selectedDate),
                staffId: $this->anyStaff ? null : $this->selectedStaffId,
                timezone: $this->timezone
            );

            $this->availableSlots = $slots->map(function ($slot) {
                return [
                    'time' => Carbon::parse($slot['start'])->format('H:i'),
                    'display' => Carbon::parse($slot['start'])->format('H:i') . ' - ' . Carbon::parse($slot['end'])->format('H:i'),
                    'staff_id' => $slot['staff_id'],
                    'staff_name' => $slot['staff_name'],
                    'available' => $slot['available'],
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to load available slots', [
                'error' => $e->getMessage(),
                'service_id' => $this->selectedServiceId,
                'branch_id' => $this->selectedBranchId,
                'date' => $this->selectedDate,
            ]);

            $this->errors[] = 'Fehler beim Laden der verfügbaren Zeiten. Bitte versuchen Sie es später erneut.';
        }

        $this->isLoading = false;
    }

    public function nextStep()
    {
        // Validate current step
        if (!$this->validateCurrentStep()) {
            return;
        }

        // Rate limiting check
        if (!$this->checkRateLimit()) {
            $this->errors[] = 'Zu viele Anfragen. Bitte warten Sie einen Moment.';
            return;
        }

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;

            // Load data for next step
            if ($this->currentStep == 3) {
                $this->loadAvailableSlots();
            }
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function selectService($serviceId)
    {
        $this->selectedServiceId = $serviceId;
        $this->selectedService = Service::find($serviceId);
        $this->nextStep();
    }

    public function selectTimeSlot($time)
    {
        $this->selectedTimeSlot = $time;

        // Find the staff for this slot
        $slot = collect($this->availableSlots)->firstWhere('time', $time);
        if ($slot && isset($slot['staff_id'])) {
            $this->selectedStaffId = $slot['staff_id'];
            $this->selectedStaff = Staff::find($slot['staff_id']);
        }

        $this->nextStep();
    }

    public function confirmBooking()
    {
        // Final validation
        $this->validate();

        // Rate limit check
        if (!$this->checkRateLimit('booking-create')) {
            $this->errors[] = 'Zu viele Buchungsversuche. Bitte warten Sie 5 Minuten.';
            return;
        }

        $this->isLoading = true;

        DB::beginTransaction();

        try {
            // Get or create customer
            $customer = Customer::firstOrCreate(
                ['email' => $this->customerEmail],
                [
                    'name' => $this->customerName,
                    'phone' => $this->customerPhone,
                    'company_id' => $this->selectedService->company_id,
                    'status' => 'active',
                    'journey_status' => 'lead',
                    'acquisition_channel' => 'website',
                    'accepts_marketing' => $this->acceptsMarketing,
                    'gdpr_consent' => $this->gdprConsent,
                    'gdpr_consent_date' => now(),
                ]
            );

            // Calculate appointment times
            $startsAt = Carbon::parse($this->selectedDate . ' ' . $this->selectedTimeSlot);
            $endsAt = $startsAt->copy()->addMinutes($this->selectedService->duration_minutes);

            // Acquire booking lock
            $lockService = app(BookingLockService::class);
            $lock = $lockService->acquireStaffLock(
                $this->selectedStaffId,
                $startsAt,
                $endsAt
            );

            if (!$lock) {
                throw new \Exception('Der gewählte Termin ist nicht mehr verfügbar.');
            }

            try {
                // Create appointment
                $this->appointment = Appointment::create([
                    'company_id' => $this->selectedService->company_id,
                    'branch_id' => $this->selectedBranchId,
                    'service_id' => $this->selectedServiceId,
                    'customer_id' => $customer->id,
                    'staff_id' => $this->selectedStaffId,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => 'pending',
                    'source' => 'online',
                    'notes' => $this->customerNotes,
                    'price' => $this->selectedService->price,
                    'deposit_amount' => $this->selectedService->deposit_amount,
                    'timezone' => $this->timezone,
                    'confirmation_code' => strtoupper(bin2hex(random_bytes(4))),
                ]);

                $this->confirmationCode = $this->appointment->confirmation_code;

                // Send confirmation
                $notificationService = app(NotificationService::class);
                $notificationService->sendAppointmentConfirmation($this->appointment);

                // Clear relevant caches
                Cache::tags(['appointments', 'availability'])->flush();

                DB::commit();

                // Release lock
                $lock->release();

                // Move to confirmation step
                $this->currentStep = 5;
                $this->successMessage = 'Ihre Buchung wurde erfolgreich erstellt!';

                // Log successful booking
                Log::info('Public booking created', [
                    'appointment_id' => $this->appointment->id,
                    'customer_email' => $this->customerEmail,
                    'service' => $this->selectedService->name,
                ]);

            } catch (\Exception $e) {
                $lock->release();
                throw $e;
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'customer_email' => $this->customerEmail,
                'service_id' => $this->selectedServiceId,
            ]);

            $this->errors[] = 'Es ist ein Fehler aufgetreten: ' . $e->getMessage();
        }

        $this->isLoading = false;
    }

    protected function validateCurrentStep(): bool
    {
        $this->errors = [];

        switch ($this->currentStep) {
            case 1:
                if (!$this->selectedServiceId) {
                    $this->errors[] = 'Bitte wählen Sie einen Service aus.';
                    return false;
                }
                break;

            case 2:
                if (!$this->selectedBranchId) {
                    $this->errors[] = 'Bitte wählen Sie eine Filiale aus.';
                    return false;
                }
                break;

            case 3:
                if (!$this->selectedDate || !$this->selectedTimeSlot) {
                    $this->errors[] = 'Bitte wählen Sie Datum und Uhrzeit aus.';
                    return false;
                }
                break;

            case 4:
                $this->validate([
                    'customerName' => 'required|string|min:2|max:255',
                    'customerEmail' => 'required|email|max:255',
                    'customerPhone' => 'nullable|string|min:10|max:20',
                    'gdprConsent' => 'accepted',
                ]);
                break;
        }

        return empty($this->errors);
    }

    protected function checkRateLimit($key = 'navigation'): bool
    {
        $key = $this->rateLimitKey . ':' . $key;

        if ($key === 'booking-create') {
            // Allow 3 booking attempts per 5 minutes
            return RateLimiter::attempt(
                $key,
                3,
                function() {},
                300
            );
        }

        // Allow 30 navigation actions per minute
        return RateLimiter::attempt(
            $key,
            30,
            function() {},
            60
        );
    }

    public function getStepTitle(): string
    {
        return match($this->currentStep) {
            1 => 'Service auswählen',
            2 => 'Filiale & Mitarbeiter',
            3 => 'Datum & Uhrzeit',
            4 => 'Ihre Daten',
            5 => 'Bestätigung',
            default => 'Buchung',
        };
    }

    public function getStepDescription(): string
    {
        return match($this->currentStep) {
            1 => 'Wählen Sie den gewünschten Service aus unserem Angebot',
            2 => 'Wählen Sie Ihre bevorzugte Filiale und optional einen Mitarbeiter',
            3 => 'Wählen Sie Ihren Wunschtermin aus den verfügbaren Zeiten',
            4 => 'Geben Sie Ihre Kontaktdaten für die Terminbestätigung ein',
            5 => 'Ihre Buchung wurde erfolgreich abgeschlossen',
            default => '',
        };
    }

    public function render()
    {
        return view('livewire.public-booking.booking-wizard');
    }
}