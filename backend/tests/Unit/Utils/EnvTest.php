<?php

namespace Tests\Unit\Utils;

use Tests\TestCase;
use Env;

class EnvTest extends TestCase
{
    private $envFile;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a temporary .env file
        $this->envFile = sys_get_temp_dir() . '/.env.test';
        file_put_contents($this->envFile, "TEST_KEY=test_value\n#Comment\n  SPACED_KEY =  spaced_value  \nQUOTED=\"quoted value\"\nBOOL_TRUE=true\nBOOL_FALSE=false");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->envFile)) {
            unlink($this->envFile);
        }
        
        // Clean up env vars
        putenv('TEST_KEY');
        putenv('SPACED_KEY');
        putenv('QUOTED');
        putenv('BOOL_TRUE');
        putenv('BOOL_FALSE');
        
        parent::tearDown();
    }

    public function testLoadReturnsTrueForExistingFile(): void
    {
        $this->assertTrue(Env::load($this->envFile));
        $this->assertEquals('test_value', getenv('TEST_KEY'));
    }

    public function testLoadParsesKeyValuePairs(): void
    {
        Env::load($this->envFile);
        $this->assertEquals('test_value', getenv('TEST_KEY'));
        $this->assertEquals('spaced_value', getenv('SPACED_KEY'));
        $this->assertEquals('quoted value', getenv('QUOTED'));
    }

    public function testGetReturnsValueWhenSet(): void
    {
        putenv("EXISTING_KEY=some_value");
        $this->assertEquals('some_value', Env::get('EXISTING_KEY'));
        putenv('EXISTING_KEY');
    }

    public function testGetReturnsDefaultWhenNotSet(): void
    {
        $this->assertEquals('default', Env::get('NON_EXISTENT_KEY', 'default'));
    }

    public function testGetConvertsBooleans(): void
    {
        Env::load($this->envFile);
        $this->assertTrue(Env::get('BOOL_TRUE'));
        $this->assertFalse(Env::get('BOOL_FALSE'));
    }

    public function testRequireThrowsExceptionWhenMissing(): void
    {
        $this->expectException(\Exception::class);
        Env::require('MISSING_REQUIRED_KEY');
    }
}
