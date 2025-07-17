<?php

namespace Tests\Helpers;

use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use App\Models\PortalUser;

trait ApiTestHelper
{
    /**
     * Make authenticated API request
     */
    protected function authenticatedRequest(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $user = $this->createAuthenticatedUser();
        
        return $this->actingAs($user, 'sanctum')
            ->json($method, $uri, $data, $headers);
    }

    /**
     * Create authenticated user for API testing
     */
    protected function createAuthenticatedUser(): PortalUser
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->getTestCompany()->id,
            'role' => 'admin',
        ]);

        Sanctum::actingAs($user);
        
        return $user;
    }

    /**
     * Assert API response structure
     */
    protected function assertApiResponse(TestResponse $response, array $structure = []): void
    {
        $response->assertSuccessful()
            ->assertJsonStructure($structure);
    }

    /**
     * Assert paginated API response
     */
    protected function assertPaginatedResponse(TestResponse $response, array $dataStructure = []): void
    {
        $response->assertJsonStructure([
            'data' => [
                '*' => $dataStructure
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ],
        ]);
    }

    /**
     * Assert API error response
     */
    protected function assertApiError(TestResponse $response, int $status, string $message = null): void
    {
        $response->assertStatus($status);
        
        if ($message) {
            $response->assertJson([
                'message' => $message,
            ]);
        }
    }

    /**
     * Assert validation error response
     */
    protected function assertValidationError(TestResponse $response, array $fields): void
    {
        $response->assertStatus(422)
            ->assertJsonValidationErrors($fields);
    }

    /**
     * Get test company
     */
    protected function getTestCompany()
    {
        return \App\Models\Company::first() 
            ?? \App\Models\Company::factory()->create();
    }

    /**
     * Create API headers with common settings
     */
    protected function apiHeaders(array $additional = []): array
    {
        return array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $additional);
    }

    /**
     * Act as portal user
     */
    protected function actingAsPortalUser(array $attributes = []): PortalUser
    {
        $user = PortalUser::factory()->create(array_merge([
            'company_id' => $this->getTestCompany()->id,
        ], $attributes));

        Sanctum::actingAs($user);
        
        return $user;
    }

    /**
     * Generate webhook signature
     */
    protected function generateWebhookSignature(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Make webhook request
     */
    protected function webhookRequest(string $uri, array $payload, string $signatureHeader, string $signature): TestResponse
    {
        return $this->postJson($uri, $payload, [
            $signatureHeader => $signature,
        ]);
    }
}