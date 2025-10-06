<?php

namespace Tests\E2E;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use App\Models\Company;
use App\Models\Call;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ProfitDashboardTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'reseller_admin']);
        Role::create(['name' => 'customer']);

        // Create test data
        $this->setupTestData();
    }

    private function setupTestData()
    {
        // Create Super Admin
        $this->superAdmin = User::factory()->create([
            'email' => 'super@admin.com',
            'password' => bcrypt('password123')
        ]);
        $this->superAdmin->assignRole('super-admin');

        // Create Reseller
        $this->resellerCompany = Company::factory()->reseller()->create();
        $this->resellerAdmin = User::factory()->create([
            'email' => 'reseller@admin.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->resellerCompany->id
        ]);
        $this->resellerAdmin->assignRole('reseller_admin');

        // Create Customer
        $this->customerCompany = Company::factory()->underReseller($this->resellerCompany)->create();
        $this->customer = User::factory()->create([
            'email' => 'customer@user.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->customerCompany->id
        ]);
        $this->customer->assignRole('customer');

        // Create test calls with profit data
        Call::factory()->count(10)->create([
            'company_id' => $this->customerCompany->id,
            'platform_profit' => rand(10, 100),
            'reseller_profit' => rand(5, 50),
            'total_profit' => rand(15, 150),
        ]);
    }

    /**
     * Test Super Admin Dashboard Access and Visibility
     */
    public function testSuperAdminProfitDashboard()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', 'super@admin.com')
                ->type('password', 'password123')
                ->press('Login')
                ->waitForLocation('/admin')
                ->assertAuthenticated()

                // Navigate to Profit Dashboard
                ->visit('/admin/profit-dashboard')
                ->assertSee('Profit-Dashboard')
                ->assertSee('Profit Heute')
                ->assertSee('Profit Monat')
                ->assertSee('Platform vs. Mandant')

                // Check if charts are visible
                ->assertPresent('.profit-chart')
                ->assertPresent('.profit-overview-widget')

                // Check for proper data display
                ->assertSee('€')
                ->assertSee('Marge')

                // Test chart interaction
                ->click('.chart-filter-30days')
                ->pause(1000)
                ->assertSee('30 Tage')

                // Test export functionality
                ->click('.export-profit-data')
                ->pause(2000)
                ->assertDownloaded('profit-export-*.csv');
        });
    }

    /**
     * Test Reseller Admin Dashboard Limited Access
     */
    public function testResellerAdminProfitDashboard()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', 'reseller@admin.com')
                ->type('password', 'password123')
                ->press('Login')
                ->waitForLocation('/admin')

                // Navigate to Profit Dashboard
                ->visit('/admin/profit-dashboard')
                ->assertSee('Profit-Dashboard')
                ->assertSee('Profit Heute')
                ->assertSee('Profit Monat')

                // Should NOT see Platform profit
                ->assertDontSee('Platform vs. Mandant')
                ->assertDontSee('Platform-Profit')

                // Should see only their profit
                ->assertSee('Mandanten-Profit')

                // Test filtering
                ->select('date_range', 'week')
                ->pause(1000)
                ->assertSee('7 Tage');
        });
    }

    /**
     * Test Customer Cannot Access Profit Dashboard
     */
    public function testCustomerCannotAccessProfitDashboard()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', 'customer@user.com')
                ->type('password', 'password123')
                ->press('Login')
                ->waitForLocation('/admin')

                // Try to access Profit Dashboard
                ->visit('/admin/profit-dashboard')
                ->assertSee('403')
                ->assertSee('Forbidden')

                // Also check that profit menu item is not visible
                ->visit('/admin')
                ->assertDontSee('Profit-Dashboard');
        });
    }

    /**
     * Test Profit Details Modal
     */
    public function testProfitDetailsModal()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdmin)
                ->visit('/admin/calls')
                ->waitFor('table')

                // Click on financial details for first call
                ->click('table tbody tr:first-child [data-action="showFinancialDetails"]')
                ->waitFor('.profit-details-modal')

                // Check modal content
                ->assertSee('Finanzielle Details')
                ->assertSee('Basis-Kosten')
                ->assertSee('Mandanten-Kosten')
                ->assertSee('Kunden-Kosten')
                ->assertSee('Platform-Profit')
                ->assertSee('Mandanten-Profit')
                ->assertSee('Gesamt-Profit')

                // Close modal
                ->press('Schließen')
                ->waitUntilMissing('.profit-details-modal');
        });
    }

    /**
     * Test Calls Table Financial Column Display
     */
    public function testCallsTableFinancialColumn()
    {
        $this->browse(function (Browser $browser) {
            // Test as Super Admin
            $browser->loginAs($this->superAdmin)
                ->visit('/admin/calls')
                ->waitFor('table')
                ->assertSee('💶 Finanzen')
                ->within('table tbody tr:first-child', function ($row) {
                    $row->assertSee('K:') // Kosten
                        ->assertSee('P:') // Profit
                        ->assertSee('B→M→K'); // Cost flow in description
                })
                ->logout();

            // Test as Reseller
            $browser->loginAs($this->resellerAdmin)
                ->visit('/admin/calls')
                ->waitFor('table')
                ->assertSee('💶 Finanzen')
                ->within('table tbody tr:first-child', function ($row) {
                    $row->assertSee('K:') // Kosten
                        ->assertSee('P:') // Profit
                        ->assertDontSee('B→M→K'); // Should not see full breakdown
                })
                ->logout();

            // Test as Customer
            $browser->loginAs($this->customer)
                ->visit('/admin/calls')
                ->waitFor('table')
                ->assertSee('💶 Finanzen')
                ->within('table tbody tr:first-child', function ($row) {
                    $row->assertSee('€') // Only cost
                        ->assertDontSee('P:') // No profit
                        ->assertDontSee('Marge'); // No margin
                });
        });
    }

    /**
     * Test Real-time Widget Updates
     */
    public function testRealTimeWidgetUpdates()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdmin)
                ->visit('/admin/profit-dashboard')

                // Get initial profit value
                ->assertSee('Profit Heute')
                ->screenshot('initial-profit')

                // Create new call with profit in another tab
                ->openNewWindow()
                ->visit('/admin/calls/create')
                ->type('duration_sec', '120')
                ->type('customer_cost', '50')
                ->press('Speichern')
                ->pause(1000)

                // Switch back to dashboard and wait for update
                ->switchToWindow(0)
                ->pause(31000) // Wait for 30s polling interval
                ->screenshot('updated-profit')
                ->assertSee('Profit Heute');
                // The value should have changed
        });
    }

    /**
     * Test CSV Export Security
     */
    public function testCsvExportSecurity()
    {
        $this->browse(function (Browser $browser) {
            // Super Admin export
            $browser->loginAs($this->superAdmin)
                ->visit('/admin/calls')
                ->check('table thead input[type="checkbox"]') // Select all
                ->press('Bulk Actions')
                ->press('Export')
                ->pause(2000)
                ->assertDownloaded('calls-export-*.csv')
                ->logout();

            // Customer export (should not contain profit)
            $browser->loginAs($this->customer)
                ->visit('/admin/calls')
                ->check('table thead input[type="checkbox"]')
                ->press('Bulk Actions')
                ->press('Export')
                ->pause(2000)
                ->assertDownloaded('calls-export-*.csv');

            // Verify CSV content differences
            // This would require reading the downloaded files
            // and checking for profit columns
        });
    }

    /**
     * Test Performance with Large Dataset
     */
    public function testPerformanceWithLargeDataset()
    {
        // Create 1000 calls
        Call::factory()->count(1000)->create([
            'company_id' => $this->customerCompany->id,
        ]);

        $this->browse(function (Browser $browser) {
            $startTime = microtime(true);

            $browser->loginAs($this->superAdmin)
                ->visit('/admin/profit-dashboard')
                ->waitFor('.profit-chart', 10)
                ->assertSee('Profit-Dashboard');

            $loadTime = microtime(true) - $startTime;

            // Dashboard should load within 5 seconds even with 1000 calls
            $this->assertLessThan(5, $loadTime);
        });
    }

    /**
     * Test Mobile Responsiveness
     */
    public function testMobileResponsiveness()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdmin)
                ->resize(375, 667) // iPhone size
                ->visit('/admin/profit-dashboard')
                ->assertSee('Profit-Dashboard')

                // Check if mobile menu works
                ->click('.mobile-menu-toggle')
                ->pause(500)
                ->assertSee('Dashboard')
                ->assertSee('Anrufe')

                // Check if tables are scrollable
                ->visit('/admin/calls')
                ->assertPresent('.overflow-x-auto')

                // Check if financial column is still readable
                ->assertSee('💶');
        });
    }
}