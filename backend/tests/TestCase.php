<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tests\Traits\ApiTestTrait;
use Tests\Traits\MockDatabaseTrait;
use Tests\Traits\ControllerTestTrait;

abstract class TestCase extends BaseTestCase
{
    use ApiTestTrait;
    use MockDatabaseTrait;
    use ControllerTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        // Global setup if needed
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Global teardown if needed
    }
}
