<?php

namespace Tests\Feature\Seeders;

use App\Models\Company;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use Database\Seeders\ThomasIncidentCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration Tests for ThomasIncidentCategoriesSeeder
 *
 * Verifies complete category hierarchy and output configuration setup.
 */
class ThomasIncidentCategoriesSeederTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create(['is_active' => true]);
    }

    /** @test */
    public function it_seeds_complete_category_hierarchy_for_company()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        // Assert parent categories exist
        $this->assertDatabaseHas('service_case_categories', [
            'company_id' => $this->company->id,
            'name' => 'Network & Connectivity',
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('service_case_categories', [
            'company_id' => $this->company->id,
            'name' => 'Server / Virtualization / VDI',
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('service_case_categories', [
            'company_id' => $this->company->id,
            'name' => 'Microsoft 365 & Collaboration',
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('service_case_categories', [
            'company_id' => $this->company->id,
            'name' => 'Security & Email Security',
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('service_case_categories', [
            'company_id' => $this->company->id,
            'name' => 'Unified Communications / VoIP',
            'parent_id' => null,
        ]);

        $this->assertDatabaseHas('service_case_categories', [
            'company_id' => $this->company->id,
            'name' => 'General',
            'parent_id' => null,
        ]);
    }

    /** @test */
    public function it_creates_child_categories_with_correct_hierarchy()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        // Get parent category
        $parent = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'network-connectivity')
            ->first();

        $this->assertNotNull($parent, 'Parent category should exist');

        // Assert child categories exist
        $this->assertDatabaseHas('service_case_categories', [
            'company_id' => $this->company->id,
            'name' => 'WAN/Internet',
            'parent_id' => $parent->id,
        ]);

        $this->assertDatabaseHas('service_case_categories', [
            'company_id' => $this->company->id,
            'name' => 'Remote Access (VPN)',
            'parent_id' => $parent->id,
        ]);
    }

    /** @test */
    public function it_creates_incident_type_categories_at_leaf_level()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        // Get WAN/Internet category
        $wan = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'wan-internet')
            ->first();

        $this->assertNotNull($wan, 'WAN/Internet category should exist');

        // Assert leaf categories exist
        $this->assertDatabaseHas('service_case_categories', [
            'company_id' => $this->company->id,
            'slug' => 'n1-internetstorung-einzelperson',
            'parent_id' => $wan->id,
        ]);

        $this->assertDatabaseHas('service_case_categories', [
            'company_id' => $this->company->id,
            'slug' => 'n2-internetstorung-standort',
            'parent_id' => $wan->id,
        ]);
    }

    /** @test */
    public function it_creates_output_configuration_for_each_category()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        $categories = ServiceCaseCategory::where('company_id', $this->company->id)->get();

        foreach ($categories as $category) {
            $this->assertNotNull(
                $category->output_configuration_id,
                "Category {$category->name} should have output configuration"
            );

            $this->assertInstanceOf(
                ServiceOutputConfiguration::class,
                $category->outputConfiguration,
                "Category {$category->name} should have valid output configuration relationship"
            );
        }
    }

    /** @test */
    public function it_sets_correct_priorities_by_category_type()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        // Security = critical
        $security = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'sec-1-verdachtige-email')
            ->first();
        $this->assertEquals('critical', $security->default_priority);

        // N2 (multi-user) = critical
        $n2 = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'n2-internetstorung-standort')
            ->first();
        $this->assertEquals('critical', $n2->default_priority);

        // Infrastructure = high
        $srv = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'srv1-netzlaufwerke-terminalserver-nicht-erreichbar')
            ->first();
        $this->assertEquals('high', $srv->default_priority);

        // General = normal
        $general = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'allgemeine-anfrage')
            ->first();
        $this->assertEquals('normal', $general->default_priority);
    }

    /** @test */
    public function it_sets_correct_confidence_thresholds()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        // Security - high threshold
        $security = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'sec-1-verdachtige-email')
            ->first();
        $this->assertEquals(0.85, $security->confidence_threshold);

        // Standard categories - medium threshold
        $vpn = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'v1-vpn-verbindet-nicht')
            ->first();
        $this->assertEquals(0.75, $vpn->confidence_threshold);

        // General - low threshold
        $general = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'allgemeine-anfrage')
            ->first();
        $this->assertEquals(0.50, $general->confidence_threshold);
    }

    /** @test */
    public function it_populates_intent_keywords_for_all_categories()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        $categories = ServiceCaseCategory::where('company_id', $this->company->id)->get();

        foreach ($categories as $category) {
            $this->assertNotNull(
                $category->intent_keywords,
                "Category {$category->name} should have intent keywords"
            );
            $this->assertIsArray(
                $category->intent_keywords,
                "Category {$category->name} keywords should be an array"
            );
            $this->assertNotEmpty(
                $category->intent_keywords,
                "Category {$category->name} should have at least one keyword"
            );
        }
    }

    /** @test */
    public function it_creates_all_thomas_incident_types()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        $expectedIncidentTypes = [
            'n1-internetstorung-einzelperson',
            'n2-internetstorung-standort',
            'v1-vpn-verbindet-nicht',
            'srv1-netzlaufwerke-terminalserver-nicht-erreichbar',
            'm365-1-onedrive-nicht-im-finder',
            'sec-1-verdachtige-email',
            'uc-1-apparat-klingelt-nicht',
            'allgemeine-anfrage',
        ];

        foreach ($expectedIncidentTypes as $slug) {
            $this->assertDatabaseHas('service_case_categories', [
                'company_id' => $this->company->id,
                'slug' => $slug,
            ], "Incident type {$slug} should exist");
        }
    }

    /** @test */
    public function it_sets_all_categories_as_active()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        $inactiveCount = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('is_active', false)
            ->count();

        $this->assertEquals(0, $inactiveCount, 'All seeded categories should be active');
    }

    /** @test */
    public function it_sets_correct_case_types()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        // Most categories should be incidents
        $n1 = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'n1-internetstorung-einzelperson')
            ->first();
        $this->assertEquals('incident', $n1->default_case_type);

        // General should be inquiry
        $general = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'allgemeine-anfrage')
            ->first();
        $this->assertEquals('inquiry', $general->default_case_type);
    }

    /** @test */
    public function it_creates_output_configs_with_correct_templates()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        // Security config should wait for enrichment
        $securityCategory = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('slug', 'sec-1-verdachtige-email')
            ->first();
        $securityConfig = $securityCategory->outputConfiguration;

        $this->assertTrue(
            $securityConfig->wait_for_enrichment,
            'Security config should wait for enrichment'
        );
        $this->assertEquals(300, $securityConfig->enrichment_timeout_seconds);
        $this->assertEquals('link', $securityConfig->email_audio_option);
        $this->assertTrue($securityConfig->include_transcript);
        $this->assertTrue($securityConfig->include_summary);
    }

    /** @test */
    public function it_creates_categories_for_multiple_companies()
    {
        // Create second company
        $company2 = Company::factory()->create(['is_active' => true]);

        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        // Verify both companies have categories
        $company1Count = ServiceCaseCategory::where('company_id', $this->company->id)->count();
        $company2Count = ServiceCaseCategory::where('company_id', $company2->id)->count();

        $this->assertGreaterThan(0, $company1Count, 'Company 1 should have categories');
        $this->assertGreaterThan(0, $company2Count, 'Company 2 should have categories');
        $this->assertEquals(
            $company1Count,
            $company2Count,
            'Both companies should have same number of categories'
        );
    }

    /** @test */
    public function it_uses_correct_sort_order()
    {
        $seeder = new ThomasIncidentCategoriesSeeder();
        $seeder->run();

        $categories = ServiceCaseCategory::where('company_id', $this->company->id)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        // Verify sort order increases
        $previousOrder = 0;
        foreach ($categories as $category) {
            $this->assertGreaterThan(
                $previousOrder,
                $category->sort_order,
                "Category {$category->name} should have increasing sort order"
            );
            $previousOrder = $category->sort_order;
        }
    }
}
