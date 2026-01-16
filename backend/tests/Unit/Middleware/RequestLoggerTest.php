<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use RequestLogger;

class RequestLoggerTest extends TestCase
{
    private $logDir;

    protected function setUp(): void
    {
        parent::setUp();
        // Use a temp dir for logs to avoid polluting real logs
        $this->logDir = sys_get_temp_dir() . '/dogpound_test_logs/requests';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
        
        // We need to use reflection to set private static logDir property
        $reflection = new \ReflectionClass(RequestLogger::class);
        $property = $reflection->getProperty('logDir');
        $property->setAccessible(true);
        $property->setValue(null, $this->logDir);
    }

    protected function tearDown(): void
    {
        // Cleanup logs
        if (is_dir($this->logDir)) {
            $files = glob($this->logDir . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->logDir);
            rmdir(dirname($this->logDir));
        }
        parent::tearDown();
    }

    public function testLogLifecycleCreatesFile(): void
    {
        // 1. Start
        RequestLogger::start();
        
        // 2. Set user
        RequestLogger::setUser(['UserID' => 1, 'Email' => 'test@example.com']);
        
        // 3. End
        RequestLogger::end(200);

        // 4. Verify file created
        $files = glob($this->logDir . '/requests_*.json');
        $this->assertCount(1, $files);
        
        $content = file_get_contents($files[0]);
        $logData = json_decode($content, true);
        
        $this->assertEquals(200, $logData['status_code']);
        $this->assertEquals(1, $logData['user_id']);
    }

    public function testErrorMethodLogsError(): void
    {
        RequestLogger::error("Something bad happened", 500);

        $files = glob($this->logDir . '/requests_*.json');
        $this->assertCount(1, $files);
        
        $content = file_get_contents($files[0]);
        $logData = json_decode($content, true);
        
        $this->assertEquals(500, $logData['status_code']);
        $this->assertEquals("Something bad happened", $logData['error']);
    }

    public function testGetRecentRetrievesLogs(): void
    {
        // Create a dummy log file
        $filename = $this->logDir . '/requests_' . date('Y-m-d') . '.json';
        $entry1 = json_encode(['id' => 1, 'message' => 'test1']) . "\n";
        $entry2 = json_encode(['id' => 2, 'message' => 'test2']) . "\n";
        file_put_contents($filename, $entry1 . $entry2);

        $recent = RequestLogger::getRecent();
        
        $this->assertCount(2, $recent);
        $this->assertEquals(1, $recent[0]['id']);
        $this->assertEquals(2, $recent[1]['id']);
    }
}
