<?php

namespace Tests\Unit\Utils;

use Tests\TestCase;
use Router;

class RouterTest extends TestCase
{
    private $router;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->router = new Router($this->mockPdo);

        // Ensure PHPUNIT_RUNNING is defined
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }

        // Setup TestController
        $src =  dirname(__DIR__, 2) . '/Fixtures/TestController.php';
        $dest = APP_PATH . '/controllers/TestController.php';
        if (!file_exists(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }
        copy($src, $dest);
    }

    protected function tearDown(): void
    {
        // Remove TestController
        $dest = APP_PATH . '/controllers/TestController.php';
        if (file_exists($dest)) {
            unlink($dest);
        }
        parent::tearDown();
    }

    public function testGetRegistersGetRoute(): void
    {
        $this->router->get('/test', 'TestController@index');
        $routes = $this->router->getRoutes();
        
        $this->assertCount(1, $routes);
        $this->assertEquals('GET', $routes[0]['method']);
        $this->assertEquals('/api/v1/test', $routes[0]['path']);
        $this->assertEquals('TestController@index', $routes[0]['handler']);
    }

    public function testDispatchMatchesCorrectRoute(): void
    {
        $this->router->get('/test', 'TestController@index');
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/v1/test';
        $_SERVER['SCRIPT_NAME'] = '/index.php'; // Assume root

        ob_start();
        $this->router->dispatch();
        $output = ob_get_clean();
        
        $json = json_decode($output, true);
        $this->assertEquals('index', $json['action']);
    }

    public function testRoutePatternsConvertParametersToRegex(): void
    {
        $this->router->get('/users/{id}', 'TestController@show');
        $routes = $this->router->getRoutes();
        
        $this->assertStringContainsString('(?P<id>[^/]+)', $routes[0]['pattern']);
    }

    public function testDispatchMatchesParameterizedRoute(): void
    {
        $this->router->get('/users/{id}', 'TestController@show');
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/v1/users/123';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        ob_start();
        $this->router->dispatch();
        $output = ob_get_clean();
        
        $json = json_decode($output, true);
        $this->assertEquals('show', $json['action']);
        $this->assertEquals('123', $json['id']);
    }

    public function testDispatchReturns404ForUnmatchedRoute(): void
    {
        $this->router->get('/test', 'TestController@index');
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/v1/unknown';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        ob_start();
        try {
            $this->router->dispatch(); // Should call Response::notFound -> Response::error
        } catch (\RuntimeException $e) { if ($e->getMessage() !== 'RESPONSE_EXIT') throw $e; }
        $output = ob_get_clean();
        
        $code = http_response_code();
        $this->assertEquals(404, $code);
    }
}
