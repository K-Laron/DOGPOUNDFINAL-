<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use User;
use PDO;

class UserModelTest extends TestCase
{
    private $userModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->userModel = new User($this->mockPdo);
    }

    public function testFindReturnsUserById(): void
    {
        $expectedUser = ['UserID' => 1, 'FirstName' => 'John', 'Is_Deleted' => 0];
        $stmt = $this->createMockStatement([$expectedUser]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->userModel->find(1);
        
        $this->assertEquals($expectedUser, $result);
    }

    public function testFindByEmailReturnsUser(): void
    {
        $expectedUser = ['UserID' => 1, 'Email' => 'john@example.com'];
        $stmt = $this->createMockStatement([$expectedUser]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE u.Email = :email'))
            ->willReturn($stmt);

        $result = $this->userModel->findByEmail('john@example.com');
        
        $this->assertEquals($expectedUser, $result);
    }

    public function testCreateInsertsNewUser(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);
            
        $this->mockPdo->method('lastInsertId')
            ->willReturn('50');

        $data = [
            'role_id' => 2,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123'
        ];

        $result = $this->userModel->create($data);
        
        $this->assertEquals(50, $result);
    }

    public function testVerifyPasswordReturnsTrueForCorrectPassword(): void
    {
        // Mock findByEmail to return a user with a known hash
        // hash for 'password' is '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' (standard laravel/faker hash)
        // But since we can't easily rely on a specific hash algorithm implementation details across environments without the exact same underlying lib slightly differing potentially,
        // we'll use password_hash locally.
        $password = 'secret123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $user = ['UserID' => 1, 'Email' => 'test@example.com', 'Password_Hash' => $hash];
        $stmt = $this->createMockStatement([$user]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->userModel->verifyPassword('test@example.com', $password);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['UserID']);
    }

    public function testVerifyPasswordReturnsFalseForWrongPassword(): void
    {
        $password = 'secret123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $user = ['UserID' => 1, 'Email' => 'test@example.com', 'Password_Hash' => $hash];
        $stmt = $this->createMockStatement([$user]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->userModel->verifyPassword('test@example.com', 'wrongpassword');
        
        $this->assertFalse($result);
    }

    public function testEmailExistsReturnsTrueWhenExists(): void
    {
        $stmt = $this->createMockStatement([['count' => 1]]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->userModel->emailExists('existing@example.com');
        
        $this->assertTrue($result);
    }

    public function testDeleteSoftDeletesUser(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SET Is_Deleted = TRUE'))
            ->willReturn($stmt);

        $result = $this->userModel->delete(1);
        
        $this->assertTrue($result);
    }
}
