<?php

namespace Tests\Traits;

trait ControllerTestTrait
{
    /**
     * Mock the global request variables
     */
    protected function mockRequest(string $method, array $get = [], array $post = [])
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_GET = $get;
        $_POST = $post;
        $_REQUEST = array_merge($get, $post);
        
        // Reset content type to ensure BaseController reads from $_POST/$_GET
        // instead of trying to read php://input
        unset($_SERVER['CONTENT_TYPE']);
    }

    /**
     * Execute a controller action and capture its output
     * 
     * @param callable $callback The controller action to call
     * @return array The decoded JSON response
     */
    protected function runController(callable $callback): array
    {
        ob_start();
        try {
            $callback();
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'RESPONSE_EXIT') {
                // Expected behavior for tests, just catch and continue to return output
            } else {
                ob_end_clean();
                throw $e;
            }
        } catch (\Exception $e) {
            // Capture other exceptions
            ob_end_clean();
            throw $e;
        }
        $output = ob_get_clean();
        
        return json_decode($output, true) ?? ['output' => $output];
    }

    /**
     * Assert response is successful
     */
    protected function assertResponseSuccess(array $response, int $expectedCode = 200)
    {
        $this->assertTrue($response['success'] ?? false, 'Response was not successful: ' . json_encode($response));
        $this->assertEquals($expectedCode, http_response_code());
    }

    /**
     * Assert response is an error
     */
    protected function assertResponseError(array $response, int $expectedCode)
    {
        $this->assertFalse($response['success'] ?? true, 'Response was successful but expected error');
        $this->assertEquals($expectedCode, http_response_code());
    }
}
