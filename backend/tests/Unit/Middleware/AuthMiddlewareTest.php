<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use AuthMiddleware;
use JWT;
use PDO;
use Response;

class AuthMiddlewareTest extends TestCase
{
    private $authMiddleware;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->authMiddleware = new AuthMiddleware($this->mockPdo);
        
        // Ensure Response class is loaded
        if (!class_exists('Response')) {
            require_once __DIR__ . '/../../../app/utils/Response.php';
        }
    }

    public function testAuthenticateSucceedsWithValidToken(): void
    {
        // 1. Mock valid token
        $userId = 1;
        $token = JWT::generate(['user_id' => $userId]);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";

        // 2. Mock db user query
        $user = [
            'UserID' => 1,
            'Role_Name' => 'Admin',
            'Account_Status' => 'Active',
            'Is_Deleted' => 0
        ];
        $stmt = $this->createMockStatement([$user]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->authMiddleware->authenticate();
        
        $this->assertEquals($user, $result);

        // Cleanup
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testAuthenticateFailsWithoutToken(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        
        ob_start();
        try {
            $this->authMiddleware->authenticate();
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'RESPONSE_EXIT') {
                ob_end_clean();
                throw $e;
            }
        }
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertFalse($response['success']);
        $this->assertEquals(401, http_response_code());
    }

    public function testRequireRoleAllowsAuthorizedUser(): void
    {
        // ... (unchanged)
        $token = JWT::generate(['user_id' => 1]);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";

        $user = [
            'UserID' => 1,
            'Role_Name' => 'Admin',
            'Account_Status' => 'Active',
            'Is_Deleted' => 0
        ];
        $stmt = $this->createMockStatement([$user]);
        $this->mockPdo->method('prepare')->willReturn($stmt);

        // 2. Call authenticate
        $this->authMiddleware->authenticate();

        // 3. Test requireRole
        $result = $this->authMiddleware->requireRole('Admin');
        $this->assertTrue($result);
        
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }
    
    public function testRequireRoleDeniesUnauthorizedUser(): void
    {
        $token = JWT::generate(['user_id' => 2]);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";

        $user = [
            'UserID' => 2,
            'Role_Name' => 'User',
            'Account_Status' => 'Active',
            'Is_Deleted' => 0
        ];
        $stmt = $this->createMockStatement([$user]);
        $this->mockPdo->method('prepare')->willReturn($stmt);

        $this->authMiddleware->authenticate();

        ob_start();
        try {
            $this->authMiddleware->requireRole('Admin');
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'RESPONSE_EXIT') {
                ob_end_clean();
                throw $e;
            }
        }
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertFalse($response['success']);
        $this->assertEquals(403, http_response_code());
        
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }
}
