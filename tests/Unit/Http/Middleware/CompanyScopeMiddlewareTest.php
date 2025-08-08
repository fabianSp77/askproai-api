<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\CompanyScopeMiddleware;
use App\Models\User;
use App\Models\Company;
use App\Scopes\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class CompanyScopeMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private CompanyScopeMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CompanyScopeMiddleware();
        
        // Create roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'reseller_owner']);
        Role::create(['name' => 'reseller_admin']);
        Role::create(['name' => 'reseller_support']);
        Role::create(['name' => 'company_owner']);
    }

    public function test_guest_user_passes_through_without_company_scope()
    {
        $request = Request::create('/test');
        $called = false;

        $response = $this->middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return new Response();
        });

        $this->assertTrue($called);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_authenticated_user_gets_default_company_set()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertEquals($company->id, session('current_company'));
        $this->assertEquals($company->id, TenantScope::$tenantId);
    }

    public function test_existing_session_company_is_preserved_if_valid()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        // Set existing session
        session(['current_company' => $company->id]);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertEquals($company->id, session('current_company'));
        $this->assertEquals($company->id, TenantScope::$tenantId);
    }

    public function test_invalid_session_company_resets_to_user_company()
    {
        $userCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $userCompany->id]);
        $this->actingAs($user);

        // Set invalid session company
        session(['current_company' => $otherCompany->id]);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        // Should reset to user's own company
        $this->assertEquals($userCompany->id, session('current_company'));
        $this->assertEquals($userCompany->id, TenantScope::$tenantId);
    }

    public function test_super_admin_can_access_any_company()
    {
        $userCompany = Company::factory()->create();
        $targetCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $userCompany->id]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        session(['current_company' => $targetCompany->id]);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        // Super admin should keep the target company
        $this->assertEquals($targetCompany->id, session('current_company'));
        $this->assertEquals($targetCompany->id, TenantScope::$tenantId);
    }

    public function test_reseller_can_access_child_companies()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);
        $user = User::factory()->create(['company_id' => $reseller->id]);
        $user->assignRole('reseller_owner');
        $this->actingAs($user);

        session(['current_company' => $client->id]);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        // Reseller should be able to access client company
        $this->assertEquals($client->id, session('current_company'));
        $this->assertEquals($client->id, TenantScope::$tenantId);
    }

    public function test_reseller_cannot_access_non_child_companies()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $otherClient = Company::factory()->create(['company_type' => 'client']);
        $user = User::factory()->create(['company_id' => $reseller->id]);
        $user->assignRole('reseller_owner');
        $this->actingAs($user);

        session(['current_company' => $otherClient->id]);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        // Should reset to reseller's own company
        $this->assertEquals($reseller->id, session('current_company'));
        $this->assertEquals($reseller->id, TenantScope::$tenantId);
    }

    public function test_user_can_access_own_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company_owner');
        $this->actingAs($user);

        session(['current_company' => $company->id]);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertEquals($company->id, session('current_company'));
        $this->assertEquals($company->id, TenantScope::$tenantId);
    }

    public function test_regular_user_cannot_access_other_companies()
    {
        $userCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $userCompany->id]);
        $user->assignRole('company_owner');
        $this->actingAs($user);

        session(['current_company' => $otherCompany->id]);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        // Should reset to user's own company
        $this->assertEquals($userCompany->id, session('current_company'));
        $this->assertEquals($userCompany->id, TenantScope::$tenantId);
    }

    public function test_reseller_admin_has_same_access_as_owner()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);
        $user = User::factory()->create(['company_id' => $reseller->id]);
        $user->assignRole('reseller_admin');
        $this->actingAs($user);

        session(['current_company' => $client->id]);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertEquals($client->id, session('current_company'));
        $this->assertEquals($client->id, TenantScope::$tenantId);
    }

    public function test_reseller_support_has_same_access_as_owner()
    {
        $reseller = Company::factory()->create(['company_type' => 'reseller']);
        $client = Company::factory()->create([
            'company_type' => 'client',
            'parent_company_id' => $reseller->id
        ]);
        $user = User::factory()->create(['company_id' => $reseller->id]);
        $user->assignRole('reseller_support');
        $this->actingAs($user);

        session(['current_company' => $client->id]);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertEquals($client->id, session('current_company'));
        $this->assertEquals($client->id, TenantScope::$tenantId);
    }

    public function test_current_company_is_shared_with_views()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        // Check that the current company is shared with views
        $this->assertNotNull(view()->getShared()['currentCompany']);
        $this->assertEquals($company->id, view()->getShared()['currentCompany']->id);
    }

    public function test_non_reseller_company_type_prevents_child_access()
    {
        $company = Company::factory()->create(['company_type' => 'client']);
        $otherCompany = Company::factory()->create([
            'company_type' => 'client', 
            'parent_company_id' => $company->id // This shouldn't grant access
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('reseller_owner'); // Has reseller role but company is not reseller type
        $this->actingAs($user);

        session(['current_company' => $otherCompany->id]);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        // Should reset to user's own company since company is not actually a reseller
        $this->assertEquals($company->id, session('current_company'));
        $this->assertEquals($company->id, TenantScope::$tenantId);
    }

    public function test_tenant_scope_is_reset_on_each_request()
    {
        // Set initial state
        TenantScope::$tenantId = 999;

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $request = Request::create('/test');
        
        $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        // Tenant scope should be updated to the user's company
        $this->assertEquals($company->id, TenantScope::$tenantId);
        $this->assertNotEquals(999, TenantScope::$tenantId);
    }
}