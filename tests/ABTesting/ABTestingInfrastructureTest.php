<?php

namespace Tests\ABTesting;

use Tests\TestCase;
use App\Services\ABTestingService;
use App\Services\FeatureFlagService;
use App\Services\ExperimentAnalyticsService;
use App\Models\User;
use App\Models\Experiment;
use App\Models\ExperimentVariant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ABTestingInfrastructureTest extends TestCase
{
    protected $abTesting;
    protected $featureFlags;
    protected $analytics;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->abTesting = new ABTestingService();
        $this->featureFlags = new FeatureFlagService();
        $this->analytics = new ExperimentAnalyticsService();
    }

    /**
     * Test: A/B test setup and configuration
     */
    public function test_ab_test_configuration()
    {
        // Create a new experiment
        $experiment = $this->abTesting->createExperiment([
            'name' => 'New Booking Flow',
            'description' => 'Test new appointment booking UI',
            'hypothesis' => 'Simplified booking flow will increase conversion by 15%',
            'variants' => [
                [
                    'name' => 'control',
                    'weight' => 50,
                    'config' => ['ui_version' => 'v1']
                ],
                [
                    'name' => 'treatment',
                    'weight' => 50,
                    'config' => ['ui_version' => 'v2']
                ]
            ],
            'metrics' => [
                'primary' => 'booking_conversion_rate',
                'secondary' => ['time_to_booking', 'user_satisfaction']
            ],
            'sample_size' => 10000,
            'duration_days' => 14
        ]);
        
        $this->assertNotNull($experiment->id);
        $this->assertEquals('draft', $experiment->status);
        $this->assertCount(2, $experiment->variants);
        
        // Validate traffic allocation
        $allocation = $this->abTesting->validateTrafficAllocation($experiment);
        $this->assertTrue($allocation['valid']);
        $this->assertEquals(100, $allocation['total_weight']);
    }

    /**
     * Test: User assignment to variants
     */
    public function test_user_variant_assignment()
    {
        $experiment = Experiment::factory()->create([
            'status' => 'active',
            'targeting_rules' => [
                'user_type' => ['new', 'returning'],
                'device' => ['mobile', 'desktop']
            ]
        ]);
        
        $variants = [
            ExperimentVariant::factory()->create([
                'experiment_id' => $experiment->id,
                'name' => 'control',
                'weight' => 50
            ]),
            ExperimentVariant::factory()->create([
                'experiment_id' => $experiment->id,
                'name' => 'treatment',
                'weight' => 50
            ])
        ];
        
        // Test deterministic assignment
        $users = User::factory()->count(1000)->create();
        $assignments = [];
        
        foreach ($users as $user) {
            $variant = $this->abTesting->assignUserToVariant($user, $experiment);
            $assignments[$variant->name] = ($assignments[$variant->name] ?? 0) + 1;
            
            // Verify same user always gets same variant
            $secondAssignment = $this->abTesting->assignUserToVariant($user, $experiment);
            $this->assertEquals($variant->id, $secondAssignment->id);
        }
        
        // Check distribution is roughly 50/50 (with 5% tolerance)
        $this->assertEqualsWithDelta(500, $assignments['control'], 50);
        $this->assertEqualsWithDelta(500, $assignments['treatment'], 50);
    }

    /**
     * Test: Feature flag integration
     */
    public function test_feature_flag_ab_testing()
    {
        // Create feature flag with A/B test
        $flag = $this->featureFlags->create([
            'key' => 'new_checkout_flow',
            'description' => 'New checkout process with one-click booking',
            'type' => 'experiment',
            'rollout_percentage' => 0, // Start with 0%
            'experiment_config' => [
                'variants' => [
                    'control' => ['enabled' => false],
                    'variant_a' => ['enabled' => true, 'theme' => 'blue'],
                    'variant_b' => ['enabled' => true, 'theme' => 'green']
                ],
                'weights' => [34, 33, 33]
            ]
        ]);
        
        // Test gradual rollout
        $rolloutStages = [10, 25, 50, 100];
        $user = User::factory()->create();
        
        foreach ($rolloutStages as $percentage) {
            $this->featureFlags->updateRollout($flag, $percentage);
            
            // Test multiple users at each stage
            $enabledCount = 0;
            for ($i = 0; $i < 100; $i++) {
                $testUser = User::factory()->create();
                if ($this->featureFlags->isEnabled('new_checkout_flow', $testUser)) {
                    $enabledCount++;
                }
            }
            
            $this->assertEqualsWithDelta($percentage, $enabledCount, 10);
        }
    }

    /**
     * Test: Metrics collection and analysis
     */
    public function test_experiment_metrics_collection()
    {
        $experiment = Experiment::factory()->create(['status' => 'active']);
        $control = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->id,
            'name' => 'control'
        ]);
        $treatment = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->id,
            'name' => 'treatment'
        ]);
        
        // Simulate user interactions
        for ($i = 0; $i < 1000; $i++) {
            $user = User::factory()->create();
            $variant = $i % 2 === 0 ? $control : $treatment;
            
            // Track events
            $this->analytics->trackEvent($experiment, $user, $variant, 'page_view', [
                'page' => 'booking_form'
            ]);
            
            // Control: 30% conversion, Treatment: 35% conversion
            if (($variant->name === 'control' && rand(1, 100) <= 30) ||
                ($variant->name === 'treatment' && rand(1, 100) <= 35)) {
                
                $this->analytics->trackEvent($experiment, $user, $variant, 'booking_completed', [
                    'service_id' => rand(1, 5),
                    'value' => rand(30, 100)
                ]);
            }
        }
        
        // Analyze results
        $results = $this->analytics->analyzeExperiment($experiment);
        
        $this->assertArrayHasKey('control', $results['variants']);
        $this->assertArrayHasKey('treatment', $results['variants']);
        
        // Check conversion rates
        $controlConversion = $results['variants']['control']['conversion_rate'];
        $treatmentConversion = $results['variants']['treatment']['conversion_rate'];
        
        $this->assertEqualsWithDelta(0.30, $controlConversion, 0.05);
        $this->assertEqualsWithDelta(0.35, $treatmentConversion, 0.05);
        
        // Check statistical significance
        $this->assertArrayHasKey('statistical_significance', $results);
        $this->assertArrayHasKey('p_value', $results['statistical_significance']);
        $this->assertArrayHasKey('confidence_level', $results['statistical_significance']);
    }

    /**
     * Test: Multi-variant testing (MVT)
     */
    public function test_multivariate_testing()
    {
        // Create MVT experiment
        $mvt = $this->abTesting->createMultivariateTest([
            'name' => 'Homepage Optimization',
            'factors' => [
                'headline' => ['current', 'benefit_focused', 'urgency_focused'],
                'cta_button' => ['book_now', 'get_started', 'try_free'],
                'hero_image' => ['people', 'product', 'abstract']
            ],
            'interactions' => true // Test interaction effects
        ]);
        
        // Should create 3x3x3 = 27 variants
        $this->assertCount(27, $mvt->variants);
        
        // Test orthogonal array for reduced testing
        $reducedVariants = $this->abTesting->generateOrthogonalArray($mvt);
        $this->assertLessThan(27, count($reducedVariants));
        $this->assertGreaterThanOrEqual(9, count($reducedVariants)); // Minimum for 3 factors
    }

    /**
     * Test: Bandit algorithms for optimization
     */
    public function test_multi_armed_bandit_optimization()
    {
        $experiment = Experiment::factory()->create([
            'type' => 'bandit',
            'algorithm' => 'thompson_sampling'
        ]);
        
        $variants = [];
        for ($i = 0; $i < 4; $i++) {
            $variants[] = ExperimentVariant::factory()->create([
                'experiment_id' => $experiment->id,
                'name' => "variant_{$i}",
                'weight' => 25
            ]);
        }
        
        // Simulate different performance for variants
        $trueConversionRates = [0.10, 0.15, 0.25, 0.12];
        
        // Run bandit algorithm
        for ($round = 0; $round < 1000; $round++) {
            $user = User::factory()->create();
            
            // Bandit selects variant based on current knowledge
            $variant = $this->abTesting->selectVariantBandit($experiment, $user);
            $variantIndex = (int) substr($variant->name, -1);
            
            // Simulate conversion based on true rate
            $converted = rand(1, 100) <= ($trueConversionRates[$variantIndex] * 100);
            
            // Update bandit knowledge
            $this->abTesting->updateBanditReward($variant, $converted);
        }
        
        // Check that bandit learned to favor best variant
        $selectionCounts = [];
        for ($i = 0; $i < 100; $i++) {
            $variant = $this->abTesting->selectVariantBandit($experiment, User::factory()->create());
            $selectionCounts[$variant->name] = ($selectionCounts[$variant->name] ?? 0) + 1;
        }
        
        // Best variant (index 2 with 0.25 conversion) should be selected most
        $this->assertGreaterThan(40, $selectionCounts['variant_2']);
    }

    /**
     * Test: Experiment lifecycle management
     */
    public function test_experiment_lifecycle()
    {
        $experiment = Experiment::factory()->create([
            'status' => 'draft',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(15)
        ]);
        
        // Test pre-launch validation
        $validation = $this->abTesting->validateExperiment($experiment);
        $this->assertTrue($validation['ready_to_launch']);
        
        // Launch experiment
        $this->abTesting->launchExperiment($experiment);
        $experiment->refresh();
        $this->assertEquals('scheduled', $experiment->status);
        
        // Simulate time passing to start
        Carbon::setTestNow(now()->addDay());
        $this->abTesting->checkScheduledExperiments();
        $experiment->refresh();
        $this->assertEquals('active', $experiment->status);
        
        // Test early stopping for significance
        $this->analytics->checkEarlyStopping($experiment);
        
        // Test auto-conclusion
        Carbon::setTestNow(now()->addDays(15));
        $this->abTesting->checkExpiredExperiments();
        $experiment->refresh();
        $this->assertEquals('completed', $experiment->status);
        
        // Test results archival
        $archived = $this->abTesting->archiveExperimentResults($experiment);
        $this->assertNotNull($archived['archived_at']);
        $this->assertNotNull($archived['final_results']);
    }

    /**
     * Test: Segmentation and targeting
     */
    public function test_experiment_segmentation()
    {
        $experiment = Experiment::factory()->create([
            'targeting_rules' => [
                'segments' => [
                    [
                        'name' => 'mobile_new_users',
                        'conditions' => [
                            ['field' => 'device_type', 'operator' => '=', 'value' => 'mobile'],
                            ['field' => 'user_type', 'operator' => '=', 'value' => 'new']
                        ]
                    ],
                    [
                        'name' => 'high_value_customers',
                        'conditions' => [
                            ['field' => 'lifetime_value', 'operator' => '>', 'value' => 500],
                            ['field' => 'visit_count', 'operator' => '>', 'value' => 10]
                        ]
                    ]
                ]
            ]
        ]);
        
        // Test user qualification
        $mobileNewUser = User::factory()->create([
            'created_at' => now(),
            'meta' => ['device_type' => 'mobile', 'user_type' => 'new']
        ]);
        
        $highValueUser = User::factory()->create([
            'created_at' => now()->subYear(),
            'meta' => ['lifetime_value' => 750, 'visit_count' => 25]
        ]);
        
        $regularUser = User::factory()->create();
        
        $this->assertTrue($this->abTesting->userQualifiesForExperiment($mobileNewUser, $experiment));
        $this->assertTrue($this->abTesting->userQualifiesForExperiment($highValueUser, $experiment));
        $this->assertFalse($this->abTesting->userQualifiesForExperiment($regularUser, $experiment));
    }

    /**
     * Test: Cross-experiment conflict detection
     */
    public function test_experiment_conflict_detection()
    {
        $experiment1 = Experiment::factory()->create([
            'name' => 'Checkout Flow A/B',
            'affected_features' => ['checkout', 'payment']
        ]);
        
        $experiment2 = Experiment::factory()->create([
            'name' => 'Payment Method Test',
            'affected_features' => ['payment', 'billing']
        ]);
        
        $experiment3 = Experiment::factory()->create([
            'name' => 'Homepage Banner',
            'affected_features' => ['homepage', 'hero']
        ]);
        
        // Detect conflicts
        $conflicts = $this->abTesting->detectExperimentConflicts();
        
        $this->assertCount(1, $conflicts);
        $this->assertEquals('payment', $conflicts[0]['shared_feature']);
        $this->assertContains($experiment1->id, $conflicts[0]['experiments']);
        $this->assertContains($experiment2->id, $conflicts[0]['experiments']);
        
        // Test mutual exclusion
        $user = User::factory()->create();
        $assignments = $this->abTesting->assignUserToExperiments($user, [$experiment1, $experiment2]);
        
        // User should only be in one of the conflicting experiments
        $this->assertCount(1, array_filter($assignments, fn($a) => in_array($a->experiment_id, [$experiment1->id, $experiment2->id])));
    }

    /**
     * Test: Real-time experiment monitoring
     */
    public function test_real_time_experiment_monitoring()
    {
        $experiment = Experiment::factory()->create(['status' => 'active']);
        
        // Simulate real-time metrics
        $monitoring = $this->analytics->getRealTimeMetrics($experiment);
        
        $this->assertArrayHasKey('current_participants', $monitoring);
        $this->assertArrayHasKey('conversion_rate_trend', $monitoring);
        $this->assertArrayHasKey('health_indicators', $monitoring);
        
        // Test SRM (Sample Ratio Mismatch) detection
        $srmCheck = $this->analytics->checkSampleRatioMismatch($experiment);
        $this->assertArrayHasKey('expected_ratio', $srmCheck);
        $this->assertArrayHasKey('actual_ratio', $srmCheck);
        $this->assertArrayHasKey('p_value', $srmCheck);
        $this->assertArrayHasKey('has_srm', $srmCheck);
    }
}
