<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Models\Company;
use App\Models\PolicyConfiguration;
use App\Models\User;
use App\Events\ConfigurationCreated;
use App\Events\ConfigurationUpdated;
use App\Events\ConfigurationDeleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Test Event System for Configuration Changes
 *
 * @package Tests\Feature\Events
 * @group events
 * @group configuration
 */
class ConfigurationEventSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_fires_configuration_created_event_when_policy_is_created()
    {
        Event::fake([ConfigurationCreated::class]);

        $policy = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
        ]);

        Event::assertDispatched(ConfigurationCreated::class, function ($event) use ($policy) {
            return $event->companyId === (string) $policy->company_id
                && $event->modelId === $policy->id
                && $event->modelType === PolicyConfiguration::class;
        });
    }

    /** @test */
    public function it_fires_configuration_updated_event_when_policy_is_updated()
    {
        Event::fake([ConfigurationUpdated::class]);

        $policy = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
            'config' => ['hours_before' => 24, 'fee_percentage' => 0],
        ]);

        // Update policy
        $policy->update([
            'config' => ['hours_before' => 48, 'fee_percentage' => 50],
        ]);

        Event::assertDispatched(ConfigurationUpdated::class);
    }

    /** @test */
    public function it_fires_configuration_deleted_event_when_policy_is_soft_deleted()
    {
        Event::fake([ConfigurationDeleted::class]);

        $policy = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
        ]);

        $policy->delete();

        Event::assertDispatched(ConfigurationDeleted::class, function ($event) use ($policy) {
            return $event->companyId === (string) $policy->company_id
                && $event->modelId === $policy->id
                && $event->isSoftDelete === true;
        });
    }

    /** @test */
    public function it_fires_configuration_deleted_event_when_policy_is_force_deleted()
    {
        Event::fake([ConfigurationDeleted::class]);

        $policy = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
        ]);

        $policy->forceDelete();

        Event::assertDispatched(ConfigurationDeleted::class, function ($event) use ($policy) {
            return $event->companyId === (string) $policy->company_id
                && $event->isSoftDelete === false;
        });
    }

    /** @test */
    public function configuration_updated_event_includes_old_and_new_values()
    {
        Event::fake([ConfigurationUpdated::class]);

        $policy = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
            'config' => ['hours_before' => 24],
        ]);

        $policy->update(['config' => ['hours_before' => 48]]);

        Event::assertDispatched(ConfigurationUpdated::class, function ($event) {
            return $event->configKey === 'config'
                && isset($event->oldValue['hours_before'])
                && $event->oldValue['hours_before'] === 24
                && isset($event->newValue['hours_before'])
                && $event->newValue['hours_before'] === 48;
        });
    }

    /** @test */
    public function configuration_events_include_source_metadata()
    {
        Event::fake([ConfigurationCreated::class]);

        $policy = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
        ]);

        Event::assertDispatched(ConfigurationCreated::class, function ($event) {
            return $event->source === 'ui' // Default for web requests
                && isset($event->metadata['ip'])
                && isset($event->metadata['timestamp']);
        });
    }

    /** @test */
    public function configuration_events_include_user_id()
    {
        Event::fake([ConfigurationCreated::class]);

        $policy = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
        ]);

        Event::assertDispatched(ConfigurationCreated::class, function ($event) {
            return $event->userId === $this->user->id;
        });
    }

    /** @test */
    public function sensitive_configuration_changes_are_masked()
    {
        Event::fake([ConfigurationUpdated::class]);

        $policy = PolicyConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'api_key_config',
        ]);

        Event::assertDispatched(ConfigurationUpdated::class, function ($event) {
            // Event should detect sensitive keys containing 'api_key', 'secret', 'password', 'token'
            if (str_contains(strtolower($event->configKey), 'api_key')) {
                $maskedValue = $event->getMaskedNewValue();
                // Should be masked
                return str_contains($maskedValue, '••••••••') || $maskedValue === '[REDACTED]';
            }
            return true;
        });
    }
}
