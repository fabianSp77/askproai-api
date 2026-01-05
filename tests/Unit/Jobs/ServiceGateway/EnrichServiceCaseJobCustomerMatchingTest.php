<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs\ServiceGateway;

use App\Jobs\ServiceGateway\EnrichServiceCaseJob;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\RetellCallSession;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Tests for Customer Matching in EnrichServiceCaseJob
 *
 * Tests the hierarchical customer matching functionality:
 * 1. Phone exact match (100% confidence)
 * 2. Email match (85% confidence)
 * 3. Name fuzzy match (70% confidence)
 * 4. Unknown placeholder creation (0% confidence)
 *
 * Security tests:
 * - Multi-tenancy isolation (company_id filtering)
 * - Idempotency (skip if customer already linked)
 */
class EnrichServiceCaseJobCustomerMatchingTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected ServiceCaseCategory $category;
    protected PhoneNumber $phoneNumber;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake(); // Prevent actual job dispatching

        $this->company = Company::factory()->create();
        $this->category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create a phone number linked to the company
        $this->phoneNumber = PhoneNumber::factory()->create([
            'company_id' => $this->company->id,
            'number' => '+4915123456789',
        ]);
    }

    #[Test]
    public function skips_matching_if_customer_already_linked(): void
    {
        // Arrange
        $existingCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+4915111111111',
            'to_number' => $this->phoneNumber->number,
        ]);

        $session = RetellCallSession::factory()->create([
            'call_id' => 'retell-call-123',
            'call_status' => 'completed',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'call_id' => $call->id,
            'customer_id' => $existingCustomer->id, // Already linked
            'enrichment_status' => ServiceCase::ENRICHMENT_PENDING,
        ]);

        // Act
        $job = new EnrichServiceCaseJob($call->id, 'retell-call-123');
        $job->handle();

        // Assert - Customer should remain unchanged
        $case->refresh();
        $this->assertEquals($existingCustomer->id, $case->customer_id);
    }

    #[Test]
    public function matches_customer_by_phone_number(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+4915111111111',
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+4915111111111',
            'to_number' => $this->phoneNumber->number,
        ]);

        $session = RetellCallSession::factory()->create([
            'call_id' => 'retell-call-456',
            'call_status' => 'completed',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'call_id' => $call->id,
            'customer_id' => null,
            'enrichment_status' => ServiceCase::ENRICHMENT_PENDING,
        ]);

        // Act
        $job = new EnrichServiceCaseJob($call->id, 'retell-call-456');
        $job->handle();

        // Assert
        $case->refresh();
        $this->assertEquals($customer->id, $case->customer_id);
    }

    #[Test]
    public function matches_customer_by_email(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+4915999999999', // Different phone
            'email' => 'test@example.com',
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+4915222222222', // Won't match phone
            'to_number' => $this->phoneNumber->number,
        ]);

        $session = RetellCallSession::factory()->create([
            'call_id' => 'retell-call-789',
            'call_status' => 'completed',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'call_id' => $call->id,
            'customer_id' => null,
            'enrichment_status' => ServiceCase::ENRICHMENT_PENDING,
            'structured_data' => [
                'email' => 'test@example.com',
            ],
        ]);

        // Act
        $job = new EnrichServiceCaseJob($call->id, 'retell-call-789');
        $job->handle();

        // Assert
        $case->refresh();
        $this->assertEquals($customer->id, $case->customer_id);
    }

    #[Test]
    public function matches_customer_by_name_fuzzy(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Max Mustermann',
            'phone' => '+4915888888888', // Different phone
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+4915333333333', // Won't match phone
            'to_number' => $this->phoneNumber->number,
        ]);

        $session = RetellCallSession::factory()->create([
            'call_id' => 'retell-call-name',
            'call_status' => 'completed',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'call_id' => $call->id,
            'customer_id' => null,
            'enrichment_status' => ServiceCase::ENRICHMENT_PENDING,
            'structured_data' => [
                'caller_name' => 'Mustermann', // Partial match
            ],
        ]);

        // Act
        $job = new EnrichServiceCaseJob($call->id, 'retell-call-name');
        $job->handle();

        // Assert
        $case->refresh();
        $this->assertEquals($customer->id, $case->customer_id);
    }

    #[Test]
    public function creates_unknown_customer_placeholder_when_no_match(): void
    {
        // Arrange
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+4915777777777',
            'to_number' => $this->phoneNumber->number,
        ]);

        $session = RetellCallSession::factory()->create([
            'call_id' => 'retell-call-unknown',
            'call_status' => 'completed',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'call_id' => $call->id,
            'customer_id' => null,
            'enrichment_status' => ServiceCase::ENRICHMENT_PENDING,
        ]);

        // Act
        $job = new EnrichServiceCaseJob($call->id, 'retell-call-unknown');
        $job->handle();

        // Assert
        $case->refresh();
        $this->assertNotNull($case->customer_id);

        $customer = Customer::find($case->customer_id);
        $this->assertEquals('unknown', $customer->customer_type);
        $this->assertEquals($this->company->id, $customer->company_id);
    }

    #[Test]
    public function respects_multi_tenancy_isolation(): void
    {
        // Arrange - Customer exists but in different company
        $otherCompany = Company::factory()->create();
        $customerInOtherCompany = Customer::factory()->create([
            'company_id' => $otherCompany->id,
            'phone' => '+4915444444444',
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+4915444444444', // Same phone as customer in other company
            'to_number' => $this->phoneNumber->number,
        ]);

        $session = RetellCallSession::factory()->create([
            'call_id' => 'retell-call-tenant',
            'call_status' => 'completed',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'call_id' => $call->id,
            'customer_id' => null,
            'enrichment_status' => ServiceCase::ENRICHMENT_PENDING,
        ]);

        // Act
        $job = new EnrichServiceCaseJob($call->id, 'retell-call-tenant');
        $job->handle();

        // Assert - Should NOT match customer from other company
        $case->refresh();
        $this->assertNotEquals($customerInOtherCompany->id, $case->customer_id);

        // Should create unknown placeholder instead
        $this->assertNotNull($case->customer_id);
        $newCustomer = Customer::find($case->customer_id);
        $this->assertEquals('unknown', $newCustomer->customer_type);
        $this->assertEquals($this->company->id, $newCustomer->company_id);
    }

    #[Test]
    public function phone_matching_has_higher_priority_than_email(): void
    {
        // Arrange - Two customers: one with matching phone, one with matching email
        $phoneCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+4915555555555',
            'email' => 'phone-customer@example.com',
        ]);

        $emailCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+4915666666666', // Different phone
            'email' => 'email-customer@example.com',
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+4915555555555', // Matches phoneCustomer
            'to_number' => $this->phoneNumber->number,
        ]);

        $session = RetellCallSession::factory()->create([
            'call_id' => 'retell-call-priority',
            'call_status' => 'completed',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'call_id' => $call->id,
            'customer_id' => null,
            'enrichment_status' => ServiceCase::ENRICHMENT_PENDING,
            'structured_data' => [
                'email' => 'email-customer@example.com', // Matches emailCustomer
            ],
        ]);

        // Act
        $job = new EnrichServiceCaseJob($call->id, 'retell-call-priority');
        $job->handle();

        // Assert - Should match phoneCustomer (higher priority)
        $case->refresh();
        $this->assertEquals($phoneCustomer->id, $case->customer_id);
    }

    #[Test]
    public function updates_call_customer_id_when_matching(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+4915123456000',
        ]);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => '+4915123456000',
            'to_number' => $this->phoneNumber->number,
            'customer_id' => null, // Not linked yet
        ]);

        $session = RetellCallSession::factory()->create([
            'call_id' => 'retell-call-sync',
            'call_status' => 'completed',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'call_id' => $call->id,
            'customer_id' => null,
            'enrichment_status' => ServiceCase::ENRICHMENT_PENDING,
        ]);

        // Act
        $job = new EnrichServiceCaseJob($call->id, 'retell-call-sync');
        $job->handle();

        // Assert - Both case and call should have customer linked
        $case->refresh();
        $call->refresh();

        $this->assertEquals($customer->id, $case->customer_id);
        $this->assertEquals($customer->id, $call->customer_id);
    }

    #[Test]
    public function customer_matching_failure_does_not_block_enrichment(): void
    {
        // Arrange
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'from_number' => null, // Invalid - will cause matching to fail
            'to_number' => $this->phoneNumber->number,
        ]);

        $session = RetellCallSession::factory()->create([
            'call_id' => 'retell-call-error',
            'call_status' => 'completed',
        ]);

        $case = ServiceCase::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'call_id' => $call->id,
            'customer_id' => null,
            'enrichment_status' => ServiceCase::ENRICHMENT_PENDING,
        ]);

        // Act - Should not throw exception
        $job = new EnrichServiceCaseJob($call->id, 'retell-call-error');
        $job->handle();

        // Assert - Case should still be enriched
        $case->refresh();
        $this->assertEquals(ServiceCase::ENRICHMENT_ENRICHED, $case->enrichment_status);
    }
}
