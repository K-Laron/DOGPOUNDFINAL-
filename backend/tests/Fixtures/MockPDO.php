<?php

namespace Tests\Fixtures;

use PDO;

/**
 * MockPDO Class
 * 
 * Extends PDO to allow instantiation without a real database connection.
 * Useful for unit testing where we want to mock the database interaction.
 */
class MockPDO extends PDO
{
    public function __construct()
    {
        // Do nothing - prevent connecting to actual database
    }
}
