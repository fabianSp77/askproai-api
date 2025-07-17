<?php

namespace Tests\Helpers;

use App\Models\PortalUser;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Helper class for API testing
 */
trait ApiTestHelper
{
    /**
     * Create and authenticate a portal user
     */
    protected function actingAsPortalUser(array $attributes = []): PortalUser
    {
        $user = PortalUser::factory()->create(array_merge([
            'password' => Hash::make('password'),
        ], $attributes));

        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    /**
     * Create and authenticate an admin user
     */
    protected function actingAsAdmin(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => 'admin',
        ], $attributes));

        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    /**
     * Make an API request with common headers
     */
    protected function apiRequest(string $method, string $url, array $data = []): TestResponse
    {
        return $this->json($method, $url, $data, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Assert API response has standard pagination structure
     */
    protected function assertHasPaginationStructure(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'data' => [],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);
    }

    /**
     * Assert API response has standard error structure
     */
    protected function assertHasErrorStructure(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'message',
            'errors' => [],
        ]);
    }

    /**
     * Assert successful API response
     */
    protected function assertApiSuccess(TestResponse $response, int $expectedStatus = 200): void
    {
        $response->assertStatus($expectedStatus);
        
        if ($expectedStatus !== 204) {
            $response->assertJsonStructure([
                'success' => [],
                'data' => [],
            ]);
            
            $response->assertJson([
                'success' => true,
            ]);
        }
    }

    /**
     * Assert API validation error
     */
    protected function assertApiValidationError(TestResponse $response, array $expectedErrors = []): void
    {
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => $expectedErrors,
        ]);
    }

    /**
     * Create authenticated API headers
     */
    protected function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    /**
     * Generate webhook signature
     */
    protected function generateWebhookSignature(array $payload, string $secret): string
    {
        $timestamp = time();
        $message = $timestamp . '.' . json_encode($payload);
        $signature = hash_hmac('sha256', $message, $secret);
        
        return "t={$timestamp},v1={$signature}";
    }

    /**
     * Make webhook request with proper headers
     */
    protected function makeWebhookRequest(string $url, array $payload, string $secret, string $provider = 'retell'): TestResponse
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        switch ($provider) {
            case 'retell':
                $headers['x-retell-signature'] = $this->generateWebhookSignature($payload, $secret);
                break;
            case 'calcom':
                $headers['x-cal-signature'] = $this->generateWebhookSignature($payload, $secret);
                break;
            case 'stripe':
                $headers['stripe-signature'] = $this->generateWebhookSignature($payload, $secret);
                break;
        }

        return $this->postJson($url, $payload, $headers);
    }

    /**
     * Assert rate limit headers are present
     */
    protected function assertHasRateLimitHeaders(TestResponse $response): void
    {
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    /**
     * Test rate limiting for an endpoint
     */
    protected function testRateLimit(string $url, int $limit = 60, string $method = 'GET'): void
    {
        $user = $this->actingAsPortalUser();

        // Make requests up to the limit
        for ($i = 0; $i < $limit; $i++) {
            $response = $this->apiRequest($method, $url);
            $response->assertSuccessful();
        }

        // Next request should be rate limited
        $response = $this->apiRequest($method, $url);
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }

    /**
     * Assert response time is within acceptable limits
     */
    protected function assertResponseTime(TestResponse $response, int $maxMilliseconds = 500): void
    {
        $executionTime = (microtime(true) - LARAVEL_START) * 1000;
        
        $this->assertLessThan(
            $maxMilliseconds,
            $executionTime,
            "Response time ({$executionTime}ms) exceeded maximum allowed ({$maxMilliseconds}ms)"
        );
    }

    /**
     * Create test file upload
     */
    protected function createTestFile(string $filename = 'test.pdf', int $sizeKb = 100): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->create($filename, $sizeKb);
    }

    /**
     * Assert JSON response matches snapshot
     */
    protected function assertJsonSnapshot(TestResponse $response, string $snapshotName): void
    {
        $snapshotPath = base_path("tests/snapshots/{$snapshotName}.json");
        
        if (!file_exists($snapshotPath)) {
            // Create snapshot if it doesn't exist
            file_put_contents($snapshotPath, json_encode($response->json(), JSON_PRETTY_PRINT));
            $this->markTestIncomplete('Snapshot created. Run test again to verify.');
        }
        
        $expected = json_decode(file_get_contents($snapshotPath), true);
        $response->assertExactJson($expected);
    }
}