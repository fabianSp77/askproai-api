<?php

namespace Tests\Unit\Mocks;

use Tests\TestCase;
use App\Services\CalcomService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class MockServicesTest extends TestCase
{
    #[Test]
    public function calcom_mock_is_properly_injected()
    {
        $service = app(CalcomService::class);
        
        $this->assertInstanceOf(\Tests\Mocks\CalcomServiceMock::class, $service);
        
        // Test mock functionality
        $slots = $service->getAvailableSlots('test-event', Carbon::now());
        $this->assertCount(4, $slots);
        $this->assertTrue($slots[0]['available']);
    }

    #[Test]
    public function stripe_mock_is_properly_injected()
    {
        $service = app('stripe.mock');
        
        $this->assertInstanceOf(\Tests\Mocks\StripeServiceMock::class, $service);
        
        // Test mock functionality
        $customer = $service->createCustomer(['email' => 'test@example.com']);
        $this->assertStringStartsWith('cus_mock_', $customer['id']);
        $this->assertEquals('test@example.com', $customer['email']);
    }

    #[Test]
    public function email_mock_is_properly_injected()
    {
        $service = app('email.mock');
        
        $this->assertInstanceOf(\Tests\Mocks\EmailServiceMock::class, $service);
        
        // Test mock functionality
        $result = $service->send('test@example.com', 'Test Subject', 'Test Content');
        $this->assertTrue($result);
        $this->assertTrue($this->emailMock->assertEmailSent('test@example.com', 'Test Subject'));
        $this->assertEquals(1, $this->emailMock->getSentEmailCount());
    }

    #[Test]
    public function mocks_can_simulate_failures()
    {
        // Test CalcomService failure
        $this->calcomMock->simulateFailure();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mock API failure');
        app(CalcomService::class)->getAvailableSlots('test', Carbon::now());
    }

    #[Test]
    public function mocks_reset_between_tests()
    {
        // Add some data to mocks
        $this->emailMock->send('test@example.com', 'Test', 'Content');
        $this->stripeMock->createCustomer(['email' => 'customer@example.com']);
        
        // Manually trigger tearDown and setUp
        $this->tearDown();
        $this->setUp();
        
        // Verify mocks are reset
        $this->assertEquals(0, $this->emailMock->getSentEmailCount());
        $this->assertEmpty($this->stripeMock->customers);
        $this->assertFalse($this->calcomMock->shouldFail);
    }
}