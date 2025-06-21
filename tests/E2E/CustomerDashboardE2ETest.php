<?php

namespace Tests\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Call;
use App\Events\CustomerActivityTracked;
use Carbon\Carbon;

class CustomerDashboardE2ETest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected Staff $staff;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test environment
        $this->company = Company::factory()->create([
            'name' => 'Test Salon',
            'settings' => [
                'customer_portal' => true,
                'portal_features' => [
                    'appointments' => true,
                    'invoices' => true,
                    'profile' => true,
                    'booking' => true,
                ],
                'currency' => 'EUR',
                'timezone' => 'Europe/Berlin',
            ],
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Downtown Location',
            'address' => 'Main Street 123, Berlin',
            'phone' => '+493012345678',
        ]);

        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'John Stylist',
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Haircut',
            'duration' => 30,
            'price' => 35.00,
        ]);

        // Create customer with history
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+4915123456789',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'portal_access_enabled' => true,
            'total_appointments' => 5,
            'completed_appointments' => 4,
            'no_show_count' => 1,
            'lifetime_value' => 140.00,
            'last_appointment_at' => now()->subDays(14),
        ]);

        // Login customer
        $this->actingAs($this->customer, 'customer');
    }

    /** @test */
    public function customer_sees_personalized_dashboard()
    {
        Event::fake();

        // Create appointment history
        $pastAppointments = [
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'customer_id' => $this->customer->id,
                'staff_id' => $this->staff->id,
                'service_id' => $this->service->id,
                'start_time' => now()->subMonths(2),
                'end_time' => now()->subMonths(2)->addMinutes(30),
                'status' => 'completed',
                'price' => 35.00,
            ]),
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'customer_id' => $this->customer->id,
                'staff_id' => $this->staff->id,
                'service_id' => $this->service->id,
                'start_time' => now()->subMonth(),
                'end_time' => now()->subMonth()->addMinutes(30),
                'status' => 'completed',
                'price' => 35.00,
            ]),
        ];

        // Create upcoming appointment
        $upcomingAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => now()->addDays(3)->setTime(14, 0),
            'end_time' => now()->addDays(3)->setTime(14, 30),
            'status' => 'confirmed',
            'price' => 35.00,
            'confirmation_code' => 'CONF123',
        ]);

        // Visit dashboard
        $response = $this->get('/customer');
        
        $response->assertStatus(200);
        
        // Check welcome message
        $response->assertSee('Welcome back, Jane!');
        
        // Check stats widget
        $response->assertSee('Your Activity');
        $response->assertSee('4'); // Completed appointments
        $response->assertSee('Total Appointments');
        $response->assertSee('€140.00'); // Lifetime spending
        $response->assertSee('Total Spent');
        
        // Check next appointment card
        $response->assertSee('Your Next Appointment');
        $response->assertSee('Haircut with John Stylist');
        $response->assertSee($upcomingAppointment->start_time->format('l, F j'));
        $response->assertSee($upcomingAppointment->start_time->format('g:i A'));
        $response->assertSee('Downtown Location');
        $response->assertSee('CONF123');
        
        // Check quick actions
        $response->assertSee('Book New Appointment');
        $response->assertSee('View All Appointments');
        $response->assertSee('Update Profile');
        
        // Check recent activity
        $response->assertSee('Recent Activity');
        $response->assertSee('Appointment completed'); // Past appointments
        
        // Verify activity tracking
        Event::assertDispatched(CustomerActivityTracked::class, function ($event) {
            return $event->customer->id === $this->customer->id &&
                   $event->activity === 'viewed_dashboard';
        });

        // Test responsive navigation
        $response->assertSee('nav-menu');
        $response->assertSee('Dashboard');
        $response->assertSee('Appointments');
        $response->assertSee('Invoices');
        $response->assertSee('Profile');
        $response->assertSee('Logout');
    }

    /** @test */
    public function dashboard_shows_loyalty_program_status()
    {
        // Update customer with loyalty status
        $this->customer->update([
            'loyalty_status' => 'gold',
            'loyalty_points' => 850,
            'referral_count' => 2,
        ]);

        $response = $this->get('/customer');
        
        $response->assertStatus(200);
        
        // Check loyalty widget
        $response->assertSee('Loyalty Status');
        $response->assertSee('Gold Member');
        $response->assertSee('850 Points');
        $response->assertSee('150 points to Platinum');
        
        // Check benefits
        $response->assertSee('Your Benefits');
        $response->assertSee('10% discount on all services');
        $response->assertSee('Priority booking');
        $response->assertSee('Free birthday service');
        
        // Check referral program
        $response->assertSee('Referral Program');
        $response->assertSee('2 friends referred');
        $response->assertSee('Earn 100 points per referral');
    }

    /** @test */
    public function dashboard_shows_personalized_recommendations()
    {
        // Create appointment patterns
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'start_time' => now()->subMonths(1),
            'status' => 'completed',
        ]);

        // Create a different service the customer hasn't tried
        $newService = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Hair Color',
            'duration' => 90,
            'price' => 80.00,
            'related_services' => [$this->service->id],
        ]);

        $response = $this->get('/customer');
        
        $response->assertStatus(200);
        
        // Check recommendations widget
        $response->assertSee('Recommended for You');
        $response->assertSee('Hair Color');
        $response->assertSee('Popular with customers who book Haircut');
        $response->assertSee('Book Now');
        
        // Check booking reminder
        $response->assertSee('Time for your next appointment?');
        $response->assertSee('You usually book every 4 weeks');
        $response->assertSee('Book with John Stylist');
    }

    /** @test */
    public function dashboard_handles_new_customer_experience()
    {
        // Create new customer with no history
        $newCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'new@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'portal_access_enabled' => true,
            'total_appointments' => 0,
        ]);

        $this->actingAs($newCustomer, 'customer');

        $response = $this->get('/customer');
        
        $response->assertStatus(200);
        
        // Check welcome message for new customer
        $response->assertSee('Welcome to ' . $this->company->name . '!');
        $response->assertSee('Book your first appointment and get 20% off');
        
        // Check onboarding checklist
        $response->assertSee('Get Started');
        $response->assertSee('Complete your profile');
        $response->assertSee('Book your first appointment');
        $response->assertSee('Download our mobile app');
        $response->assertSee('Refer a friend');
        
        // Should not see recent activity
        $response->assertDontSee('Recent Activity');
        
        // Should see service catalog preview
        $response->assertSee('Our Services');
        $response->assertSee('Haircut');
        $response->assertSee('30 minutes');
        $response->assertSee('€35.00');
    }

    /** @test */
    public function dashboard_shows_important_notifications()
    {
        // Create appointment tomorrow (reminder needed)
        $tomorrowAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'start_time' => now()->addDay()->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        // Create overdue invoice
        $overdueInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'amount' => 35.00,
            'due_date' => now()->subDays(5),
            'status' => 'overdue',
        ]);

        $response = $this->get('/customer');
        
        $response->assertStatus(200);
        
        // Check notifications banner
        $response->assertSee('notifications-banner');
        
        // Appointment reminder
        $response->assertSee('Reminder: Appointment tomorrow at 10:00 AM');
        $response->assertSee('View Details');
        
        // Overdue invoice alert
        $response->assertSee('You have an overdue invoice');
        $response->assertSee('€35.00 was due 5 days ago');
        $response->assertSee('Pay Now');
    }

    /** @test */
    public function dashboard_respects_portal_feature_settings()
    {
        // Disable certain features
        $this->company->update([
            'settings' => array_merge($this->company->settings, [
                'portal_features' => [
                    'appointments' => true,
                    'invoices' => false, // Disabled
                    'profile' => true,
                    'booking' => false, // Disabled
                ],
            ]),
        ]);

        $response = $this->get('/customer');
        
        $response->assertStatus(200);
        
        // Should see appointments
        $response->assertSee('Appointments');
        
        // Should not see invoices
        $response->assertDontSee('Invoices');
        
        // Should not see booking actions
        $response->assertDontSee('Book New Appointment');
        
        // Navigation should reflect available features
        $response->assertDontSee('href="/customer/invoices"');
    }

    /** @test */
    public function dashboard_shows_multi_branch_information()
    {
        // Create additional branch
        $branch2 = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Westside Location',
            'address' => 'West Avenue 456, Berlin',
        ]);

        // Create appointments at different branches
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch2->id,
            'customer_id' => $this->customer->id,
            'start_time' => now()->addWeek(),
            'status' => 'scheduled',
        ]);

        $response = $this->get('/customer');
        
        $response->assertStatus(200);
        
        // Should see branch selector or indicator
        $response->assertSee('Your Locations');
        $response->assertSee('Downtown Location');
        $response->assertSee('Westside Location');
        
        // Should see appointments grouped by branch
        $response->assertSee('Upcoming at Downtown Location');
        $response->assertSee('Upcoming at Westside Location');
    }

    /** @test */
    public function dashboard_tracks_page_performance_metrics()
    {
        $startTime = microtime(true);
        
        $response = $this->get('/customer');
        
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        
        // Page should load within reasonable time
        $this->assertLessThan(500, $loadTime, 'Dashboard took too long to load');
        
        // Check for performance optimization headers
        $response->assertHeader('Cache-Control');
        $response->assertHeader('X-Response-Time');
        
        // Verify lazy loading is implemented
        $response->assertSee('data-lazy-load');
    }

    /** @test */
    public function dashboard_handles_real_time_updates()
    {
        $response = $this->get('/customer');
        
        $response->assertStatus(200);
        
        // Check for WebSocket/Pusher configuration
        $response->assertSee('window.Echo');
        $response->assertSee("channel('customer.{$this->customer->id}')");
        
        // Verify real-time event listeners
        $response->assertSee('.listen(\'AppointmentUpdated\'');
        $response->assertSee('.listen(\'NewInvoiceCreated\'');
        
        // Check for automatic refresh meta tag as fallback
        $response->assertSee('<meta http-equiv="refresh" content="300">'); // 5 minutes
    }

    /** @test */
    public function dashboard_shows_weather_aware_recommendations()
    {
        // Mock weather API response
        $this->mockWeatherApi([
            'temperature' => 28,
            'condition' => 'sunny',
            'humidity' => 65,
        ]);

        $response = $this->get('/customer');
        
        $response->assertStatus(200);
        
        // Check weather-based recommendations
        $response->assertSee('Perfect weather for a new hairstyle!');
        $response->assertSee('High humidity alert');
        $response->assertSee('Consider our anti-frizz treatment');
    }

    /** @test */
    public function dashboard_handles_multiple_customer_sessions()
    {
        // Create family member linked to same account
        $familyMember = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'primary_customer_id' => $this->customer->id,
            'portal_access_enabled' => true,
        ]);

        $response = $this->get('/customer');
        
        $response->assertStatus(200);
        
        // Should see account switcher
        $response->assertSee('Switch Account');
        $response->assertSee('Jane Doe (You)');
        $response->assertSee('John Doe');
        
        // Should see combined family appointments
        $response->assertSee('Family Appointments');
    }

    /**
     * Mock weather API for testing
     */
    protected function mockWeatherApi(array $data): void
    {
        $this->app->bind('WeatherService', function () use ($data) {
            return new class($data) {
                public function __construct(private array $data) {}
                public function getCurrentWeather($location) { return $this->data; }
            };
        });
    }
}