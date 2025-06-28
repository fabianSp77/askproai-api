<?php

namespace Tests\Unit\Security;

use App\Helpers\SafeQueryHelper;
use App\Models\Call;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SqlInjectionProtectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]

    public function test_safe_query_helper_escapes_like_wildcards()
    {
        $dangerous = "test%value_with\\special";
        $escaped = SafeQueryHelper::escapeLike($dangerous);
        
        $this->assertEquals("test\\%value\\_with\\\\special", $escaped);
    }
    
    #[Test]
    
    public function test_safe_query_helper_prevents_sql_injection_in_like()
    {
        $maliciousInput = "'; DROP TABLE customers; --";
        $escaped = SafeQueryHelper::escapeLike($maliciousInput);
        
        // The semicolons and SQL keywords should be treated as literal text
        $this->assertStringContainsString("DROP TABLE customers", $escaped);
        $this->assertStringNotContainsString("%", $escaped);
    }
    
    #[Test]
    
    public function test_customer_search_is_safe_from_sql_injection()
    {
        // Create a test customer
        $customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+49123456789'
        ]);
        
        // Try various SQL injection attempts
        $injectionAttempts = [
            "'; DROP TABLE customers; --",
            "' OR '1'='1",
            "test%' OR name LIKE '%",
            "test_' OR email LIKE '_",
            "\\'; DELETE FROM customers; --"
        ];
        
        foreach ($injectionAttempts as $attempt) {
            // This should not throw an exception or cause SQL injection
            $results = Customer::search($attempt)->get();
            
            // The malicious input should be treated as literal text
            $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
        }
        
        // Verify the customer still exists (no tables were dropped)
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }
    
    #[Test]
    
    public function test_call_phone_search_is_safe_from_sql_injection()
    {
        // Create a test call
        $call = Call::factory()->create([
            'from_number' => '+49123456789'
        ]);
        
        // Try SQL injection via phone number search
        $maliciousPhone = "'; DROP TABLE calls; --";
        
        // This should not throw an exception or cause SQL injection
        $results = Call::fromNumber($maliciousPhone)->get();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
        
        // Verify the call still exists
        $this->assertDatabaseHas('calls', ['id' => $call->id]);
    }
    
    #[Test]
    
    public function test_sanitize_column_prevents_injection()
    {
        // Valid columns
        $this->assertEquals('name', SafeQueryHelper::sanitizeColumn('name'));
        $this->assertEquals('users.email', SafeQueryHelper::sanitizeColumn('users.email'));
        $this->assertEquals('created_at', SafeQueryHelper::sanitizeColumn('created_at'));
        
        // Invalid columns should be sanitized
        $this->assertEquals('name', SafeQueryHelper::sanitizeColumn('name; DROP TABLE users;'));
        $this->assertEquals('email', SafeQueryHelper::sanitizeColumn('email\' OR 1=1 --'));
        
        // Columns starting with numbers should throw exception
        $this->expectException(\InvalidArgumentException::class);
        SafeQueryHelper::sanitizeColumn('1_invalid');
    }
    
    #[Test]
    
    public function test_where_lower_prevents_injection()
    {
        Customer::factory()->create([
            'email' => 'Test@Example.com'
        ]);
        
        // Build query with potentially dangerous input
        $query = Customer::query();
        SafeQueryHelper::whereLower($query, 'email', "test@example.com'; DROP TABLE customers; --");
        
        // Should find the customer without SQL injection
        $results = $query->get();
        $this->assertCount(1, $results);
        
        // Tables should still exist
        $this->assertDatabaseHas('customers', ['email' => 'Test@Example.com']);
    }
    
    #[Test]
    
    public function test_order_by_safe_with_whitelist()
    {
        $allowedColumns = ['name', 'email', 'created_at'];
        
        $query = Customer::query();
        
        // Valid column should work
        SafeQueryHelper::orderBySafe($query, 'name', 'asc', $allowedColumns);
        
        // Invalid column should throw exception
        $this->expectException(\InvalidArgumentException::class);
        SafeQueryHelper::orderBySafe($query, 'password', 'asc', $allowedColumns);
    }
    
    #[Test]
    
    public function test_full_text_search_escaping()
    {
        if (\DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Full-text search test requires MySQL');
        }
        
        // Create a document with special characters
        \DB::table('knowledge_documents')->insert([
            'title' => 'Test Document',
            'content' => 'This is a test with special chars: % _ \\ and SQL: DROP TABLE',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Search with potentially dangerous input
        $query = \DB::table('knowledge_documents');
        SafeQueryHelper::whereFullText($query, ['title', 'content'], "DROP TABLE; DELETE FROM users;");
        
        // Should execute without SQL injection
        $results = $query->get();
        
        // Verify tables still exist
        $this->assertTrue(\Schema::hasTable('knowledge_documents'));
        $this->assertTrue(\Schema::hasTable('users'));
    }
}