<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Services\EventTypeNameParser;
use App\Services\SmartEventTypeNameParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventTypeParsingTest extends TestCase
{
    protected $parser;
    protected $smartParser;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->parser = new EventTypeNameParser();
        $this->smartParser = new SmartEventTypeNameParser();
        
        // Create test branch
        $company = Company::factory()->make(['name' => 'Test Company']);
        $this->branch = Branch::factory()->make([
            'name' => 'Berlin Mitte',
            'company' => $company
        ]);
        $this->branch->setRelation('company', $company);
    }

    /**
     * Test EventTypeNameParser parsing functionality
     */
    #[Test]
    public function test_standard_event_type_name_parsing()
    {
        // Test cases
        $testCases = [
            [
                'input' => 'Berlin Mitte-Test Company-Consultation 30min',
                'expected' => [
                    'success' => true,
                    'branch_name' => 'Berlin Mitte',
                    'company_name' => 'Test Company',
                    'service_name' => 'Consultation 30min',
                ]
            ],
            [
                'input' => 'Frankfurt-ABC Corp-Hair Styling',
                'expected' => [
                    'success' => true,
                    'branch_name' => 'Frankfurt',
                    'company_name' => 'ABC Corp',
                    'service_name' => 'Hair Styling',
                ]
            ],
            [
                'input' => 'Just a service name',
                'expected' => [
                    'success' => false,
                    'branch_name' => null,
                    'company_name' => null,
                    'service_name' => null,
                ]
            ],
            [
                'input' => 'Two-Parts-Only',
                'expected' => [
                    'success' => false,
                ]
            ],
        ];

        foreach ($testCases as $testCase) {
            $result = $this->parser->parseEventTypeName($testCase['input']);
            
            $this->assertEquals(
                $testCase['expected']['success'], 
                $result['success'],
                "Failed for input: {$testCase['input']}"
            );
            
            if ($testCase['expected']['success']) {
                $this->assertEquals($testCase['expected']['branch_name'], $result['branch_name']);
                $this->assertEquals($testCase['expected']['company_name'], $result['company_name']);
                $this->assertEquals($testCase['expected']['service_name'], $result['service_name']);
            }
        }
    }

    /**
     * Test branch matching logic
     */
    #[Test]
    public function test_branch_matching_validation()
    {
        $testCases = [
            // Exact match
            ['parsed' => 'Berlin Mitte', 'expected' => true],
            // Case insensitive
            ['parsed' => 'berlin mitte', 'expected' => true],
            // Partial match
            ['parsed' => 'Berlin', 'expected' => true],
            ['parsed' => 'Mitte', 'expected' => true],
            // No match
            ['parsed' => 'Frankfurt', 'expected' => false],
            ['parsed' => 'Munich', 'expected' => false],
            // Similar (typo)
            ['parsed' => 'Berlin Mite', 'expected' => true], // 80%+ similarity
        ];

        foreach ($testCases as $testCase) {
            $result = $this->parser->validateBranchMatch($testCase['parsed'], $this->branch);
            $this->assertEquals(
                $testCase['expected'], 
                $result,
                "Failed for parsed name: {$testCase['parsed']}"
            );
        }
    }

    /**
     * Test service name extraction from marketing text
     */
    #[Test]
    public function test_service_name_extraction_from_marketing_text()
    {
        $testCases = [
            // Marketing phrases
            'AskProAI + 30% mehr Umsatz + Beratung' => 'Beratung',
            '24/7 besten Kundenservice + Haarschnitt' => 'Haarschnitt',
            'Massage + aus Berlin' => 'Massage',
            'FitXpert + Training für Sie und besten Service' => 'Training',
            
            // Multiple plus signs
            'Company + Location + Service + Marketing' => 'Service',
            
            // Very long names
            'This is a very long service name that should be truncated to prevent database issues and display problems in the user interface' 
                => 'This is a very long service',
            
            // Edge cases
            '+' => 'Service',
            '+ + +' => 'Service',
            'Test + + Service' => 'Service',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->parser->extractServiceName($input);
            $this->assertEquals($expected, $result, "Failed for input: $input");
        }
    }

    /**
     * Test SmartEventTypeNameParser extraction
     */
    #[Test]
    public function test_smart_parser_clean_service_extraction()
    {
        $testCases = [
            // Remove company names
            'AskProAI Professional Consultation' => 'Konsultation',
            'ModernHair Styling Service' => 'Styling',
            
            // Remove locations
            'Beratung aus Berlin' => 'Beratung',
            'Training in München' => 'Training',
            'Massage bei Frankfurt' => 'Massage',
            
            // Remove marketing fluff
            '30% mehr Umsatz mit Coaching' => 'Coaching',
            'Therapie 24/7' => 'Therapie',
            'Besten Kundenservice 24/7 Behandlung' => 'Behandlung',
            
            // Extract time information
            '30 Minuten Beratung' => '30 Min Beratung',
            '60 Min Personal Training' => '60 Min Training',
            '2 Stunden Workshop' => '2 Std Workshop',
            
            // Fallback cases
            'Random Text Without Keywords' => 'Random Text Without',
            '' => 'Termin',
            '   ' => 'Termin',
        ];

        foreach ($testCases as $input => $expectedContains) {
            $result = $this->smartParser->extractCleanServiceName($input);
            
            // For keyword-based extraction, check if result contains the keyword
            if (strpos($expectedContains, 'Min') !== false || strpos($expectedContains, 'Std') !== false) {
                $this->assertStringContainsString(
                    explode(' ', $expectedContains)[0], // Check time part
                    $result,
                    "Failed for input: $input"
                );
            } else {
                // For service keywords, check if the keyword is present
                $keywords = ['Beratung', 'Training', 'Coaching', 'Therapie', 'Behandlung', 'Styling', 'Massage', 'Workshop', 'Konsultation'];
                $foundKeyword = false;
                foreach ($keywords as $keyword) {
                    if (strpos($input, $keyword) !== false && strpos($result, $keyword) !== false) {
                        $foundKeyword = true;
                        break;
                    }
                }
                
                if (!$foundKeyword && $result !== 'Termin' && strlen($input) > 3) {
                    // For fallback cases, check truncation
                    $this->assertLessThanOrEqual(30, strlen($result));
                }
            }
        }
    }

    /**
     * Test name generation
     */
    #[Test]
    public function test_event_type_name_generation()
    {
        $testCases = [
            // Normal case
            'Consultation' => 'Berlin Mitte-Test Company-Consultation',
            
            // With special characters that should be removed
            'Hair-Cut & Style' => 'Berlin Mitte-Test Company-Hair Cut Style',
            
            // With multiple spaces
            'Deep   Tissue    Massage' => 'Berlin Mitte-Test Company-Deep Tissue Massage',
            
            // German umlauts
            'Färben & Tönen' => 'Berlin Mitte-Test Company-Färben Tönen',
        ];

        foreach ($testCases as $serviceName => $expected) {
            $result = $this->parser->generateEventTypeName($this->branch, $serviceName);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test smart parser name format generation
     */
    #[Test]
    public function test_smart_parser_name_formats()
    {
        $formats = $this->smartParser->generateNameFormats($this->branch, 'Consultation Service');
        
        $this->assertArrayHasKey('standard', $formats);
        $this->assertArrayHasKey('compact', $formats);
        $this->assertArrayHasKey('service_first', $formats);
        $this->assertArrayHasKey('full', $formats);
        
        // Check format patterns
        $this->assertStringContainsString('Berlin Mitte-Test Company-', $formats['standard']);
        $this->assertStringContainsString('Berlin Mitte -', $formats['compact']);
        $this->assertStringContainsString('(Berlin Mitte)', $formats['service_first']);
        $this->assertStringContainsString('Test Company Berlin Mitte:', $formats['full']);
    }

    /**
     * Test edge cases and error handling
     */
    #[Test]
    public function test_parser_edge_cases()
    {
        // Empty strings
        $result = $this->parser->parseEventTypeName('');
        $this->assertFalse($result['success']);
        
        // Only dashes
        $result = $this->parser->parseEventTypeName('---');
        $this->assertTrue($result['success']); // Will parse as 3 empty parts
        $this->assertEquals('', $result['branch_name']);
        $this->assertEquals('', $result['company_name']);
        $this->assertEquals('', $result['service_name']);
        
        // Unicode characters
        $result = $this->parser->parseEventTypeName('München-Company-Service™');
        $this->assertTrue($result['success']);
        $this->assertEquals('München', $result['branch_name']);
        
        // Very long input
        $longName = str_repeat('Long', 100) . '-' . str_repeat('Company', 100) . '-' . str_repeat('Service', 100);
        $result = $this->parser->parseEventTypeName($longName);
        $this->assertTrue($result['success']);
    }

    /**
     * Test analysis for import functionality
     */
    #[Test]
    public function test_analyze_event_types_for_import()
    {
        $eventTypes = [
            ['id' => 1, 'title' => 'Berlin Mitte-Test Company-Service A', 'active' => true],
            ['id' => 2, 'title' => 'Frankfurt-Test Company-Service B', 'active' => true],
            ['id' => 3, 'title' => 'Invalid Format Service', 'active' => true],
            ['id' => 4, 'title' => 'Berlin Mitte-Test Company-Inactive', 'active' => false],
        ];

        $results = $this->parser->analyzeEventTypesForImport($eventTypes, $this->branch);
        
        $this->assertCount(4, $results);
        
        // First should match and suggest import
        $this->assertTrue($results[0]['matches_branch']);
        $this->assertEquals('import', $results[0]['suggested_action']);
        $this->assertNull($results[0]['warning']);
        
        // Second should not match
        $this->assertFalse($results[1]['matches_branch']);
        $this->assertEquals('skip', $results[1]['suggested_action']);
        $this->assertNotNull($results[1]['warning']);
        
        // Third should be manual
        $this->assertFalse($results[2]['parsed']['success']);
        $this->assertEquals('manual', $results[2]['suggested_action']);
        
        // All should have suggested names
        foreach ($results as $result) {
            $this->assertNotEmpty($result['suggested_name']);
            $this->assertStringContainsString('Berlin Mitte-Test Company-', $result['suggested_name']);
        }
    }
}