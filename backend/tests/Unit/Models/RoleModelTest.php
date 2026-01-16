<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Role;
use PDO;

class RoleModelTest extends TestCase
{
    private $roleModel;
    private $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = $this->createMockPdo();
        $this->roleModel = new Role($this->mockPdo);
    }

    public function testFindReturnsRoleById(): void
    {
        $expectedRole = ['RoleID' => 1, 'Role_Name' => 'Admin'];
        $stmt = $this->createMockStatement([$expectedRole]);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->roleModel->find(1);
        
        $this->assertEquals($expectedRole, $result);
    }

    public function testAllReturnsRoles(): void
    {
        $roles = [['RoleID' => 1, 'Role_Name' => 'Admin'], ['RoleID' => 2, 'Role_Name' => 'User']];
        $stmt = $this->createMockStatement($roles);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->roleModel->all();
        
        $this->assertEquals($roles, $result);
    }

    public function testCreateInsertsRole(): void
    {
        $stmt = $this->createMockStatement([], 1);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);
            
        $this->mockPdo->method('lastInsertId')->willReturn('3');

        $result = $this->roleModel->create('NewRole');
        
        $this->assertEquals(3, $result);
    }
}
