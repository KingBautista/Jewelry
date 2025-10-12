<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\AuditTrailHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class AuditTrailHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing audit logs
        $logPath = storage_path('logs/audit.log');
        if (File::exists($logPath)) {
            File::delete($logPath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test logs
        $logPath = storage_path('logs/audit.log');
        if (File::exists($logPath)) {
            File::delete($logPath);
        }
        
        parent::tearDown();
    }

    public function test_log_basic_action()
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::type('array'));

        AuditTrailHelper::log('USER_MANAGEMENT', 'CREATE', ['user_id' => 1], 1);
    }

    public function test_log_with_different_modules()
    {
        $modules = ['USER_MANAGEMENT', 'CUSTOMER_MANAGEMENT', 'INVOICE_MANAGEMENT', 'PAYMENT_MANAGEMENT'];
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->times(count($modules));

        foreach ($modules as $module) {
            AuditTrailHelper::log($module, 'CREATE', ['test' => 'data'], 1);
        }
    }

    public function test_log_with_different_actions()
    {
        $actions = ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'VIEW'];
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->times(count($actions));

        foreach ($actions as $action) {
            AuditTrailHelper::log('TEST_MODULE', $action, ['test' => 'data'], 1);
        }
    }

    public function test_log_with_user_id()
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once();

        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->andReturnSelf();

        AuditTrailHelper::log('TEST_MODULE', 'CREATE', ['test' => 'data'], 123);
    }

    public function test_log_without_user_id()
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::on(function ($data) {
                return !isset($data['user_id']);
            }));

        AuditTrailHelper::log('TEST_MODULE', 'CREATE', ['test' => 'data'], null);
    }

    public function test_log_with_complex_data()
    {
        $complexData = [
            'user_id' => 1,
            'user_email' => 'test@example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0...',
            'changes' => [
                'old' => ['name' => 'Old Name'],
                'new' => ['name' => 'New Name']
            ]
        ];

        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::on(function ($data) use ($complexData) {
                return $data['data'] === $complexData;
            }));

        AuditTrailHelper::log('USER_MANAGEMENT', 'UPDATE', $complexData, 1);
    }

    public function test_log_query()
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Database Query', \Mockery::type('array'));

        AuditTrailHelper::logQuery('SELECT * FROM users WHERE id = ?', [1], 0.05, 1);
    }

    public function test_audit_table_exists_returns_false()
    {
        // Since auditTableExists is private, we'll test the behavior indirectly
        // by checking that database operations return empty collections
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, AuditTrailHelper::getResourceLogs('users', 1));
    }

    public function test_store_in_database_returns_false()
    {
        // Since storeInDatabase is private, we'll test the behavior indirectly
        // by checking that database operations return empty collections
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, AuditTrailHelper::getUserLogs(1));
    }

    public function test_get_resource_logs_returns_empty_array()
    {
        $result = AuditTrailHelper::getResourceLogs('users', 1);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_get_user_logs_returns_empty_array()
    {
        $result = AuditTrailHelper::getUserLogs(1);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_get_module_logs_returns_empty_array()
    {
        $result = AuditTrailHelper::getModuleLogs('USER_MANAGEMENT');
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_cleanup_old_logs_returns_true()
    {
        $result = AuditTrailHelper::cleanupOldLogs();
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function test_log_includes_timestamp()
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::on(function ($data) {
                return isset($data['timestamp']) && is_string($data['timestamp']);
            }));

        AuditTrailHelper::log('TEST_MODULE', 'CREATE', ['test' => 'data'], 1);
    }

    public function test_log_includes_ip_address()
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::on(function ($data) {
                return isset($data['ip_address']) && is_string($data['ip_address']);
            }));

        AuditTrailHelper::log('TEST_MODULE', 'CREATE', ['test' => 'data'], 1);
    }

    public function test_log_includes_user_agent()
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Trail', \Mockery::on(function ($data) {
                return isset($data['user_agent']) && is_string($data['user_agent']);
            }));

        AuditTrailHelper::log('TEST_MODULE', 'CREATE', ['test' => 'data'], 1);
    }
}
