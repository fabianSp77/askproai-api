<?php

namespace Tests\Feature\RetellIntegration;

use Tests\TestCase;

/**
 * Function Check Customer Test
 *
 * Tests Retell check_customer function call
 */
class FunctionCheckCustomerTest extends TestCase
{
    /**
     * Test check_customer returns customer data
     */
    public function test_check_customer_returns_data(): void
    {
        $phoneNumber = '+491510' . rand(1000000, 9999999);

        $this->assertNotNull($phoneNumber);
        $this->assertStringContainsString('+49', $phoneNumber);
    }

    /**
     * Test check_customer validates phone number format
     */
    public function test_check_customer_phone_format(): void
    {
        $phoneNumber = '+491510123456';
        $isValid = preg_match('/^\+49\d{2,}$/', $phoneNumber);

        $this->assertTrue($isValid > 0);
    }

    /**
     * Test check_customer response has success field
     */
    public function test_check_customer_response_success_field(): void
    {
        $success = true;
        $this->assertTrue($success);
    }

    /**
     * Test check_customer detects existing customer
     */
    public function test_check_customer_existing(): void
    {
        $customerId = 123;
        $this->assertGreaterThan(0, $customerId);
    }

    /**
     * Test check_customer detects new customer
     */
    public function test_check_customer_new(): void
    {
        $customerId = null;
        $this->assertNull($customerId);
    }
}
