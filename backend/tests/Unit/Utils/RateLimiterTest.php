<?php

namespace Tests\Unit\Utils;

use Tests\TestCase;
use RateLimiter;

class RateLimiterTest extends TestCase
{
    private $storageDir;

    protected function setUp(): void
    {
        parent::setUp();
        // Use a test-specific storage directory to avoid conflicts
        // Note: RateLimiter uses RATE_LIMIT_STORAGE constant if defined. 
        // Since config mocks are hard, we assume the environment uses the configured path.
        // We will clean up files created by tests.
        
        // Define PHPUNIT_RUNNING if not defined (handled by phpunit usually, but good to be safe for our Response modification)
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
    }

    protected function tearDown(): void
    {
        // Cleanup all json files in the storage directory that start with 'test_'
        $dir = defined('RATE_LIMIT_STORAGE') ? RATE_LIMIT_STORAGE : sys_get_temp_dir() . '/rate_limits/';
        if (is_dir($dir)) {
            $files = glob($dir . 'test_*.json');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        parent::tearDown();
    }

    public function testCheckAllowsRequestUnderLimit(): void
    {
        $ip = 'test_ip_1';
        $limit = 5;
        $window = 60;
        
        // First request
        $this->assertTrue(RateLimiter::check('test_login', $ip, $limit, $window));
        
        // Check remaining
        $remaining = RateLimiter::getRemaining('test_login', $ip, $limit, $window);
        $this->assertEquals(4, $remaining);
    }

    public function testCheckBlocksRequestOverLimit(): void
    {
        $ip = 'test_ip_2';
        $limit = 2;
        $window = 60;

        $this->assertTrue(RateLimiter::check('test_login', $ip, $limit, $window)); // 1
        $this->assertTrue(RateLimiter::check('test_login', $ip, $limit, $window)); // 2
        
        // Third request should be blocked
        // Since we modified Response to not exit, we can capture output if needed, but the method returns false
        // However, RateLimiter::check returns false ONLY after sending response.
        
        ob_start(); // Capture output from Response::error
        try {
            $result = RateLimiter::check('test_login', $ip, $limit, $window);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'RESPONSE_EXIT') {
                ob_end_clean();
                throw $e;
            }
            // If exception caught, it means check failed (which is expected)
            $result = false;
        }
        ob_end_clean();
        
        $this->assertFalse($result);
    }

    public function testGetRemainingReturnsCorrectCount(): void
    {
        $ip = 'test_ip_3';
        $limit = 10;
        $window = 60;
        
        RateLimiter::check('test_api', $ip, $limit, $window);
        RateLimiter::check('test_api', $ip, $limit, $window);
        
        $remaining = RateLimiter::getRemaining('test_api', $ip, $limit, $window);
        $this->assertEquals(8, $remaining);
    }

    public function testResetClearsRateLimitData(): void
    {
        $ip = 'test_ip_4';
        $limit = 5;
        $window = 60;
        
        RateLimiter::check('test_login', $ip, $limit, $window);
        $this->assertEquals(4, RateLimiter::getRemaining('test_login', $ip, $limit, $window));
        
        RateLimiter::reset('test_login', $ip);
        
        $this->assertEquals(5, RateLimiter::getRemaining('test_login', $ip, $limit, $window));
    }
}
