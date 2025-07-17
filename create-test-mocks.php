<?php

echo "\n🏗️ Creating Test Mock Services\n";
echo "==============================\n\n";

// Create Mocks directory
if (!is_dir('tests/Mocks')) {
    mkdir('tests/Mocks', 0755, true);
    echo "✅ Created tests/Mocks directory\n";
}

// 1. CalcomServiceMock
$calcomMock = <<<'PHP'
<?php

namespace Tests\Mocks;

class CalcomServiceMock
{
    public function getAvailability($date = null, $eventTypeId = null)
    {
        return [
            'slots' => [
                ['time' => '09:00', 'available' => true],
                ['time' => '10:00', 'available' => true],
                ['time' => '11:00', 'available' => false],
                ['time' => '14:00', 'available' => true],
                ['time' => '15:00', 'available' => true],
            ]
        ];
    }
    
    public function createBooking($data)
    {
        return [
            'id' => 'booking_' . uniqid(),
            'uid' => 'uid_' . uniqid(),
            'title' => $data['title'] ?? 'Test Booking',
            'startTime' => $data['start'],
            'endTime' => $data['end'],
            'status' => 'ACCEPTED'
        ];
    }
    
    public function getEventTypes()
    {
        return [
            ['id' => 1, 'title' => 'Consultation', 'length' => 30],
            ['id' => 2, 'title' => 'Follow-up', 'length' => 15],
        ];
    }
}
PHP;

file_put_contents('tests/Mocks/CalcomServiceMock.php', $calcomMock);
echo "✅ Created CalcomServiceMock\n";

// 2. RetellServiceMock
$retellMock = <<<'PHP'
<?php

namespace Tests\Mocks;

class RetellServiceMock
{
    public function createCall($phoneNumber, $agentId = null)
    {
        return [
            'call_id' => 'call_' . uniqid(),
            'status' => 'completed',
            'duration' => 120,
            'recording_url' => 'https://example.com/recording.mp3',
            'transcript' => 'Mock transcript for testing'
        ];
    }
    
    public function getCallDetails($callId)
    {
        return [
            'call_id' => $callId,
            'status' => 'completed',
            'duration_sec' => 180,
            'cost' => 0.50,
            'transcript' => 'Test call transcript',
            'sentiment' => 'positive',
            'recording_url' => 'https://example.com/recording.mp3'
        ];
    }
    
    public function listCalls($limit = 10)
    {
        $calls = [];
        for ($i = 0; $i < $limit; $i++) {
            $calls[] = [
                'call_id' => 'call_' . ($i + 1),
                'created_at' => now()->subMinutes($i * 10)->toIso8601String(),
                'duration_sec' => rand(60, 300),
                'status' => 'completed'
            ];
        }
        return ['calls' => $calls];
    }
}
PHP;

file_put_contents('tests/Mocks/RetellServiceMock.php', $retellMock);
echo "✅ Created RetellServiceMock\n";

// 3. StripeServiceMock
$stripeMock = <<<'PHP'
<?php

namespace Tests\Mocks;

class StripeServiceMock
{
    public function createCustomer($email, $name = null)
    {
        return [
            'id' => 'cus_' . uniqid(),
            'email' => $email,
            'name' => $name,
            'created' => time()
        ];
    }
    
    public function createPaymentIntent($amount, $currency = 'eur')
    {
        return [
            'id' => 'pi_' . uniqid(),
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'succeeded',
            'client_secret' => 'pi_secret_' . uniqid()
        ];
    }
    
    public function createInvoice($customerId, $items)
    {
        return [
            'id' => 'inv_' . uniqid(),
            'customer' => $customerId,
            'amount_due' => array_sum(array_column($items, 'amount')),
            'status' => 'paid',
            'pdf' => 'https://example.com/invoice.pdf'
        ];
    }
}
PHP;

file_put_contents('tests/Mocks/StripeServiceMock.php', $stripeMock);
echo "✅ Created StripeServiceMock\n";

// 4. EmailServiceMock
$emailMock = <<<'PHP'
<?php

namespace Tests\Mocks;

class EmailServiceMock
{
    private array $sentEmails = [];
    
    public function send($to, $subject, $body, $attachments = [])
    {
        $email = [
            'id' => 'email_' . uniqid(),
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'attachments' => $attachments,
            'sent_at' => now()
        ];
        
        $this->sentEmails[] = $email;
        
        return [
            'success' => true,
            'message_id' => $email['id']
        ];
    }
    
    public function getSentEmails()
    {
        return $this->sentEmails;
    }
    
    public function clearSentEmails()
    {
        $this->sentEmails = [];
    }
}
PHP;

file_put_contents('tests/Mocks/EmailServiceMock.php', $emailMock);
echo "✅ Created EmailServiceMock\n";

// 5. Create TestsWithMocks trait
$testsWithMocks = <<<'PHP'
<?php

namespace Tests\Traits;

use Tests\Mocks\CalcomServiceMock;
use Tests\Mocks\RetellServiceMock;
use Tests\Mocks\StripeServiceMock;
use Tests\Mocks\EmailServiceMock;
use App\Services\CalcomService;
use App\Services\RetellService;
use App\Services\StripeService;
use App\Services\EmailService;

trait TestsWithMocks
{
    protected function mockExternalServices(): void
    {
        $this->mockCalcom();
        $this->mockRetell();
        $this->mockStripe();
        $this->mockEmail();
    }
    
    protected function mockCalcom(): void
    {
        $this->app->singleton(CalcomService::class, function () {
            return new CalcomServiceMock();
        });
    }
    
    protected function mockRetell(): void
    {
        $this->app->singleton(RetellService::class, function () {
            return new RetellServiceMock();
        });
    }
    
    protected function mockStripe(): void
    {
        $this->app->singleton(StripeService::class, function () {
            return new StripeServiceMock();
        });
    }
    
    protected function mockEmail(): void
    {
        $this->app->singleton(EmailService::class, function () {
            return new EmailServiceMock();
        });
    }
    
    protected function getEmailMock(): EmailServiceMock
    {
        return app(EmailService::class);
    }
}
PHP;

mkdir('tests/Traits', 0755, true);
file_put_contents('tests/Traits/TestsWithMocks.php', $testsWithMocks);
echo "✅ Created TestsWithMocks trait\n";

// 6. Update TestCase to use mocks
$testCaseContent = <<<'PHP'
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\TestsWithMocks;

abstract class TestCase extends BaseTestCase
{
    use TestsWithMocks;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock external services by default
        $this->mockExternalServices();
    }
}
PHP;

file_put_contents('tests/TestCase.php', $testCaseContent);
echo "✅ Updated TestCase.php\n";

echo "\n✅ All mock services created!\n";
echo "\nNow run: php comprehensive-test-runner.php\n";