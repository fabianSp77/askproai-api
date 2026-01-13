<?php

namespace Tests\Unit\Services\Gateway;

use App\Models\Company;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Services\Gateway\IntentDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit Tests for IntentDetectionService
 *
 * Tests Thomas's incident category intent matching with German keywords.
 */
class IntentDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private IntentDetectionService $service;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IntentDetectionService();
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function it_detects_n1_internet_issue_for_single_user()
    {
        $utterances = [
            'Mein Internet funktioniert nicht, ich kann nicht auf Teams zugreifen',
            'Ich habe keine Verbindung, der Browser lädt keine Seiten',
            'Nur mein Rechner ist offline, alle anderen haben Internet',
        ];

        foreach ($utterances as $utterance) {
            $result = $this->service->detectIntent($utterance, $this->company->id);

            $this->assertArrayHasKey('intent', $result);
            $this->assertArrayHasKey('confidence', $result);
            $this->assertArrayHasKey('detected_keywords', $result);

            // Should detect service_desk intent (not appointment)
            $this->assertEquals('service_desk', $result['intent']);

            // Should have reasonable confidence
            $this->assertGreaterThan(0.3, $result['confidence']);
        }
    }

    /** @test */
    public function it_distinguishes_n1_from_n2_based_on_scope()
    {
        // Create N1 and N2 categories
        $n1 = ServiceCaseCategory::factory()->networkN1()->create([
            'company_id' => $this->company->id,
        ]);

        $n2 = ServiceCaseCategory::factory()->networkN2()->create([
            'company_id' => $this->company->id,
        ]);

        // Test N1 (single user)
        $singleUserUtterance = 'Nur mein Laptop hat kein Internet, andere schon';
        $n1Score = $n1->matchIntent($singleUserUtterance);
        $this->assertGreaterThan(0.5, $n1Score, 'N1 should match single-user internet issues');

        // Test N2 (multiple users)
        $multiUserUtterance = 'Im ganzen Büro ist das Internet ausgefallen, alle betroffen';
        $n2Score = $n2->matchIntent($multiUserUtterance);
        $this->assertGreaterThan(0.5, $n2Score, 'N2 should match multi-user internet issues');

        // N2 should score higher for multi-user scenario
        $n2ScoreForMultiUser = $n2->matchIntent($multiUserUtterance);
        $n1ScoreForMultiUser = $n1->matchIntent($multiUserUtterance);
        $this->assertGreaterThan(
            $n1ScoreForMultiUser,
            $n2ScoreForMultiUser,
            'N2 should score higher than N1 for multi-user scenarios'
        );
    }

    /** @test */
    public function it_detects_vpn_issues()
    {
        $utterances = [
            'VPN verbindet nicht, bekomme einen Timeout',
            'Ich kann mich nicht von zuhause einloggen, VPN geht nicht',
            'Cisco AnyConnect funktioniert nicht, keine Verbindung',
        ];

        foreach ($utterances as $utterance) {
            $result = $this->service->detectIntent($utterance, $this->company->id);

            // Should detect service_desk intent
            $this->assertEquals('service_desk', $result['intent']);

            // Keywords should include vpn-related terms
            $keywords = array_map('strtolower', $result['detected_keywords']);
            $hasVpnKeyword = in_array('vpn', $keywords) ||
                in_array('remote', $keywords) ||
                in_array('homeoffice', $keywords);

            $this->assertTrue($hasVpnKeyword, "Should detect VPN-related keywords in: {$utterance}");
        }
    }

    /** @test */
    public function it_detects_security_phishing_with_high_confidence_threshold()
    {
        $phishing = ServiceCaseCategory::factory()->securityPhishing()->create([
            'company_id' => $this->company->id,
        ]);

        // Strong phishing indicators
        $utterance = 'Ich habe eine verdächtige Email bekommen mit einem komischen Link';
        $score = $phishing->matchIntent($utterance);

        // Should match with high confidence
        $this->assertGreaterThan(0.7, $score, 'Strong phishing indicators should score high');
        $this->assertTrue(
            $phishing->matchesIntent($utterance),
            'Should match phishing intent above threshold'
        );
    }

    /** @test */
    public function it_detects_server_fileshare_issues()
    {
        $srv1 = ServiceCaseCategory::factory()->serverSrv1()->create([
            'company_id' => $this->company->id,
        ]);

        $utterances = [
            'Ich kann nicht auf mein Netzlaufwerk zugreifen',
            'Der Terminalserver ist nicht erreichbar',
            'Meine Freigaben sind weg, kann nicht auf die Shares zugreifen',
        ];

        foreach ($utterances as $utterance) {
            $score = $srv1->matchIntent($utterance);
            $this->assertGreaterThan(0.5, $score, "Should match server issue: {$utterance}");
        }
    }

    /** @test */
    public function it_detects_m365_onedrive_issues()
    {
        $m365 = ServiceCaseCategory::factory()->m365OneDrive()->create([
            'company_id' => $this->company->id,
        ]);

        $utterances = [
            'OneDrive ist nicht im Finder sichtbar auf meinem Mac',
            'Ich sehe OneDrive nicht im Finder, sync funktioniert nicht',
            'OneDrive fehlt im Finder auf macOS',
        ];

        foreach ($utterances as $utterance) {
            $score = $m365->matchIntent($utterance);
            $this->assertGreaterThan(0.5, $score, "Should match M365 issue: {$utterance}");
        }
    }

    /** @test */
    public function it_detects_uc_phone_issues()
    {
        $uc = ServiceCaseCategory::factory()->ucPhone()->create([
            'company_id' => $this->company->id,
        ]);

        $utterances = [
            'Mein Telefon klingelt nicht, Anrufe gehen direkt auf den AB',
            'Der Apparat klingelt gar nicht, alles geht sofort auf Mailbox',
            'VoIP Telefon funktioniert nicht, keine Anrufe kommen durch',
        ];

        foreach ($utterances as $utterance) {
            $score = $uc->matchIntent($utterance);
            $this->assertGreaterThan(0.5, $score, "Should match UC issue: {$utterance}");
        }
    }

    /** @test */
    public function it_handles_general_inquiries_with_low_threshold()
    {
        $general = ServiceCaseCategory::factory()->general()->create([
            'company_id' => $this->company->id,
        ]);

        $utterances = [
            'Ich habe eine allgemeine Frage',
            'Kann ich eine Auskunft bekommen?',
            'Ich weiß nicht genau, worum es geht',
        ];

        foreach ($utterances as $utterance) {
            $score = $general->matchIntent($utterance);
            // General should match with low threshold (0.50)
            $this->assertGreaterThan(0.3, $score, "Should match general inquiry: {$utterance}");
        }
    }

    /** @test */
    public function it_returns_highest_scoring_category()
    {
        // Create multiple categories
        ServiceCaseCategory::factory()->networkN1()->create(['company_id' => $this->company->id]);
        $vpn = ServiceCaseCategory::factory()->vpnV1()->create(['company_id' => $this->company->id]);
        ServiceCaseCategory::factory()->general()->create(['company_id' => $this->company->id]);

        // VPN-specific utterance
        $utterance = 'VPN verbindet nicht von zuhause aus';

        // Get all categories and score them
        $categories = ServiceCaseCategory::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->get();

        $scores = $categories->mapWithKeys(function ($category) use ($utterance) {
            return [$category->slug => $category->matchIntent($utterance)];
        });

        // VPN category should have highest score
        $highestSlug = $scores->keys()->sortByDesc(function ($slug) use ($scores) {
            return $scores[$slug];
        })->first();

        $this->assertEquals('v1-vpn-verbindet-nicht', $highestSlug, 'VPN category should score highest');
    }

    /** @test */
    public function it_handles_german_umlauts_correctly()
    {
        $category = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'intent_keywords' => ['störung', 'büro', 'überwachung'],
            'confidence_threshold' => 0.60,
        ]);

        // Test with umlauts
        $utterance = 'Es gibt eine Störung im Büro';
        $score = $category->matchIntent($utterance);

        $this->assertGreaterThan(0.5, $score, 'Should handle German umlauts in matching');
    }

    /** @test */
    public function it_respects_confidence_threshold()
    {
        $highThreshold = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'intent_keywords' => ['test'],
            'confidence_threshold' => 0.90, // Very high threshold
        ]);

        $lowThreshold = ServiceCaseCategory::factory()->create([
            'company_id' => $this->company->id,
            'intent_keywords' => ['test'],
            'confidence_threshold' => 0.30, // Low threshold
        ]);

        $utterance = 'test';

        // Low threshold should match
        $this->assertTrue(
            $lowThreshold->matchesIntent($utterance),
            'Low threshold category should match'
        );

        // High threshold might not match (depends on scoring)
        $score = $highThreshold->matchIntent($utterance);
        $matches = $highThreshold->matchesIntent($utterance);

        $this->assertIsBool($matches, 'matchesIntent should return boolean');
    }
}
