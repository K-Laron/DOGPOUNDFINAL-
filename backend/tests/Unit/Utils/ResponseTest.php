<?php

namespace Tests\Unit\Utils;

use Tests\TestCase;
use Response;

class ResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure PHPUNIT_RUNNING is defined (it is defined in RateLimiterTest but might not persist if run separately? 
        // Actually constants persist in process. But good to be safe.)
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
    }

    public function testSuccessReturnsCorrectStructure(): void
    {
        ob_start();
        try {
            Response::success(['id' => 1], 'Operation successful');
        } catch (\RuntimeException $e) { if ($e->getMessage() !== 'RESPONSE_EXIT') throw $e; }
        $output = ob_get_clean();
        $code = http_response_code();

        $this->assertEquals(200, $code);
        
        $json = json_decode($output, true);
        $this->assertTrue($json['success']);
        $this->assertEquals('Operation successful', $json['message']);
        $this->assertEquals(['id' => 1], $json['data']);
        $this->assertArrayHasKey('timestamp', $json);
    }

    public function testErrorReturnsCorrectStructure(): void
    {
        ob_start();
        try {
            Response::error('Something went wrong', 400);
        } catch (\RuntimeException $e) { if ($e->getMessage() !== 'RESPONSE_EXIT') throw $e; }
        $output = ob_get_clean();
        $code = http_response_code();

        $this->assertEquals(400, $code);
        
        $json = json_decode($output, true);
        $this->assertFalse($json['success']);
        $this->assertEquals('Something went wrong', $json['message']);
        $this->assertArrayNotHasKey('data', $json);
        $this->assertArrayHasKey('timestamp', $json);
    }

    public function testErrorIncludesErrorsWhenProvided(): void
    {
        $errors = ['field' => 'Invalid value'];
        
        ob_start();
        try {
            Response::error('Validation failed', 422, $errors);
        } catch (\RuntimeException $e) { if ($e->getMessage() !== 'RESPONSE_EXIT') throw $e; }
        $output = ob_get_clean();
        
        $json = json_decode($output, true);
        $this->assertEquals($errors, $json['errors']);
    }

    public function testPaginatedReturnsCorrectStructure(): void
    {
        $data = [['id' => 1], ['id' => 2]];
        $page = 1;
        $perPage = 10;
        $total = 20;

        ob_start();
        try {
            Response::paginated($data, $page, $perPage, $total);
        } catch (\RuntimeException $e) { if ($e->getMessage() !== 'RESPONSE_EXIT') throw $e; }
        $output = ob_get_clean();
        $code = http_response_code();

        $this->assertEquals(200, $code);

        $json = json_decode($output, true);
        $this->assertTrue($json['success']);
        $this->assertEquals($data, $json['data']);
        $this->assertArrayHasKey('pagination', $json);
        
        $cPagination = $json['pagination'];
        $this->assertEquals(1, $cPagination['current_page']);
        $this->assertEquals(10, $cPagination['per_page']);
        $this->assertEquals(20, $cPagination['total_items']);
        $this->assertEquals(2, $cPagination['total_pages']);
        $this->assertTrue($cPagination['has_next']);
    }

    public function testCreatedReturns201StatusCode(): void
    {
        ob_start();
        try {
            Response::created(['id' => 1]);
        } catch (\RuntimeException $e) { if ($e->getMessage() !== 'RESPONSE_EXIT') throw $e; }
        ob_get_clean();
        $code = http_response_code();

        $this->assertEquals(201, $code);
    }

    public function testNotFoundReturns404(): void
    {
        ob_start();
        try {
            Response::notFound();
        } catch (\RuntimeException $e) { if ($e->getMessage() !== 'RESPONSE_EXIT') throw $e; }
        ob_get_clean();
        $code = http_response_code();

        $this->assertEquals(404, $code);
    }

    public function testValidationErrorReturns422(): void
    {
        ob_start();
        try {
            Response::validationError(['field' => 'error']);
        } catch (\RuntimeException $e) { if ($e->getMessage() !== 'RESPONSE_EXIT') throw $e; }
        ob_get_clean();
        $code = http_response_code();

        $this->assertEquals(422, $code);
    }
}
