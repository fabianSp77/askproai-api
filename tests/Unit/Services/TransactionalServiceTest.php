<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Traits\TransactionalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Mockery;

class TransactionalServiceTest extends TestCase
{
    use TransactionalService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any previous logs
        Log::shouldReceive('debug')->andReturnNull();
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('error')->andReturnNull();
        Log::shouldReceive('warning')->andReturnNull();
    }
    
    public function testSuccessfulTransaction()
    {
        $result = $this->executeInTransaction(function () {
            // Simulate some database operations
            DB::table('users')->insert([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
            
            return 'success';
        });
        
        $this->assertEquals('success', $result);
        
        // Verify the data was committed
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }
    
    public function testTransactionRollbackOnException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');
        
        try {
            $this->executeInTransaction(function () {
                // Insert some data
                DB::table('users')->insert([
                    'name' => 'Rollback User',
                    'email' => 'rollback@example.com',
                    'password' => bcrypt('password'),
                ]);
                
                // Throw an exception to trigger rollback
                throw new Exception('Test exception');
            });
        } catch (Exception $e) {
            // Verify the data was rolled back
            $this->assertDatabaseMissing('users', [
                'email' => 'rollback@example.com'
            ]);
            
            throw $e;
        }
    }
    
    public function testTransactionWithDeadlockRetry()
    {
        $attempts = 0;
        
        $result = $this->executeInTransaction(function () use (&$attempts) {
            $attempts++;
            
            if ($attempts < 2) {
                // Simulate a deadlock on first attempt
                $exception = new Exception('Deadlock found when trying to get lock; try restarting transaction');
                throw $exception;
            }
            
            return 'success after retry';
        }, [], 3);
        
        $this->assertEquals('success after retry', $result);
        $this->assertEquals(2, $attempts);
    }
    
    public function testExecuteInTransactionOrDefault()
    {
        // Test successful execution
        $result = $this->executeInTransactionOrDefault(function () {
            return 'success';
        }, 'default');
        
        $this->assertEquals('success', $result);
        
        // Test with exception - should return default
        $result = $this->executeInTransactionOrDefault(function () {
            throw new Exception('Test exception');
        }, 'default value');
        
        $this->assertEquals('default value', $result);
    }
    
    public function testExecuteMultipleInTransaction()
    {
        $results = $this->executeMultipleInTransaction([
            'operation1' => function () {
                DB::table('users')->insert([
                    'name' => 'User 1',
                    'email' => 'user1@example.com',
                    'password' => bcrypt('password'),
                ]);
                return 'op1_success';
            },
            'operation2' => function () {
                DB::table('users')->insert([
                    'name' => 'User 2',
                    'email' => 'user2@example.com',
                    'password' => bcrypt('password'),
                ]);
                return 'op2_success';
            }
        ]);
        
        $this->assertEquals([
            'operation1' => 'op1_success',
            'operation2' => 'op2_success'
        ], $results);
        
        // Verify both users were created
        $this->assertDatabaseHas('users', ['email' => 'user1@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'user2@example.com']);
    }
    
    public function testExecuteMultipleInTransactionRollback()
    {
        $this->expectException(Exception::class);
        
        try {
            $this->executeMultipleInTransaction([
                'operation1' => function () {
                    DB::table('users')->insert([
                        'name' => 'Multi User 1',
                        'email' => 'multi1@example.com',
                        'password' => bcrypt('password'),
                    ]);
                    return 'success';
                },
                'operation2' => function () {
                    // This will fail
                    throw new Exception('Operation 2 failed');
                }
            ]);
        } catch (Exception $e) {
            // Verify first operation was rolled back
            $this->assertDatabaseMissing('users', ['email' => 'multi1@example.com']);
            throw $e;
        }
    }
    
    public function testIsDeadlockException()
    {
        // MySQL deadlock
        $mysqlDeadlock = new Exception('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found');
        $this->assertTrue($this->isDeadlockException($mysqlDeadlock));
        
        // PostgreSQL deadlock
        $pgDeadlock = new Exception('SQLSTATE[40P01]: deadlock detected');
        $this->assertTrue($this->isDeadlockException($pgDeadlock));
        
        // Regular exception
        $regularException = new Exception('Some other error');
        $this->assertFalse($this->isDeadlockException($regularException));
    }
    
    public function testTransactionMetricsLogging()
    {
        Log::shouldReceive('info')
            ->once()
            ->with(
                'Transaction metrics',
                Mockery::on(function ($context) {
                    return isset($context['service']) &&
                           isset($context['operation']) &&
                           isset($context['duration_ms']) &&
                           isset($context['success']) &&
                           isset($context['memory_usage']) &&
                           isset($context['peak_memory']);
                })
            );
        
        $this->logTransactionMetrics('test_operation', microtime(true) - 1, true, [
            'test_key' => 'test_value'
        ]);
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        DB::table('users')->where('email', 'like', '%@example.com')->delete();
        
        parent::tearDown();
    }
}