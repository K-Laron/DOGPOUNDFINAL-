<?php

namespace Tests\Traits;

use PDO;
use PDOStatement;
use Tests\Fixtures\MockPDO;

trait MockDatabaseTrait
{
    /**
     * Create a mock PDO instance
     */
    protected function createMockPdo(): PDO
    {
        // Create a mock of our MockPDO class which extends PDO
        // We use MockPDO because trying to mock PDO directly can be tricky with constructors
        $mockPdo = $this->createMock(MockPDO::class);
        
        return $mockPdo;
    }

    /**
     * Create a mock PDOStatement with pre-configured results
     *
     * @param mixed $result The result to return from fetch/fetchAll
     * @param int $rowCount The number of rows affected
     * @return PDOStatement
     */
    protected function createMockStatement($result = [], $rowCount = 0): PDOStatement
    {
        $stmt = $this->createMock(PDOStatement::class);
        
        // Configure common method behaviors
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(is_array($result) && !empty($result) ? $result[0] : false);
        $stmt->method('fetchAll')->willReturn($result);
        $stmt->method('rowCount')->willReturn($rowCount);
        $stmt->method('columnCount')->willReturn(count($result));
        
        return $stmt;
    }
}
