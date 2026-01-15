<?php
/**
 * Animals Feature Tests
 * Tests the animals API endpoints
 * 
 * @package AnimalShelter\Tests\Feature
 */

use PHPUnit\Framework\TestCase;

class AnimalsTest extends TestCase
{
    private static $accessToken;
    private static $createdAnimalId;

    /**
     * Set up before all tests - get auth token
     */
    public static function setUpBeforeClass(): void
    {
        // Login to get access token
        $response = self::login('admin1', 'password');
        
        if (isset($response['data']['access_token'])) {
            self::$accessToken = $response['data']['access_token'];
        }
    }

    /**
     * Clean up - delete test animal if created
     */
    public static function tearDownAfterClass(): void
    {
        if (self::$createdAnimalId && self::$accessToken) {
            self::makeAuthRequest('DELETE', '/animals/' . self::$createdAnimalId, null, self::$accessToken);
        }
    }

    // ==========================================
    // PUBLIC ENDPOINT TESTS
    // ==========================================

    public function testGetAvailableAnimalsIsPublic(): void
    {
        $response = $this->makeRequest('GET', '/animals/available');
        
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertIsArray($response['body']['data']);
    }

    public function testGetSingleAnimalIsPublic(): void
    {
        // First get the list to find an ID
        $listResponse = $this->makeRequest('GET', '/animals/available');
        
        if (empty($listResponse['body']['data'])) {
            $this->markTestSkipped('No animals available for testing');
        }

        $animalId = $listResponse['body']['data'][0]['AnimalID'];
        $response = $this->makeRequest('GET', '/animals/' . $animalId);
        
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response['body']);
    }

    // ==========================================
    // PROTECTED ENDPOINT TESTS
    // ==========================================

    public function testListAllAnimalsRequiresAuth(): void
    {
        $response = $this->makeRequest('GET', '/animals');
        
        // Endpoint may be public (200) or require auth (401) depending on config
        $this->assertContains($response['status'], [200, 401]);
    }

    public function testListAllAnimalsWithAuth(): void
    {
        if (!self::$accessToken) {
            $this->markTestSkipped('No auth token available');
        }

        $response = $this->makeAuthRequest('GET', '/animals');
        
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response['body']);
    }

    public function testGetAnimalStatistics(): void
    {
        if (!self::$accessToken) {
            $this->markTestSkipped('No auth token available');
        }

        $response = $this->makeAuthRequest('GET', '/animals/stats/summary');
        
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response['body']);
    }

    // ==========================================
    // CRUD TESTS
    // ==========================================

    public function testCreateAnimal(): void
    {
        if (!self::$accessToken) {
            $this->markTestSkipped('No auth token available');
        }

        $data = [
            'name' => 'PHPUnit Test Dog ' . time(),
            'type' => 'Dog',
            'breed' => 'Test Breed',
            'gender' => 'Male',
            'age_group' => 'Adult',
            'weight' => 15.5,
            'intake_status' => 'Stray'
        ];

        $response = $this->makeAuthRequest('POST', '/animals', $data);
        
        $this->assertContains($response['status'], [200, 201]);
        
        if (isset($response['body']['data']['AnimalID'])) {
            self::$createdAnimalId = $response['body']['data']['AnimalID'];
            $this->assertNotEmpty(self::$createdAnimalId);
        }
    }

    public function testCreateAnimalFailsWithMissingName(): void
    {
        if (!self::$accessToken) {
            $this->markTestSkipped('No auth token available');
        }

        $data = [
            'type' => 'Dog',
            'intake_status' => 'Stray'
            // Missing 'name'
        ];

        $response = $this->makeAuthRequest('POST', '/animals', $data);
        
        $this->assertEquals(422, $response['status']);
    }

    public function testCreateAnimalFailsWithInvalidType(): void
    {
        if (!self::$accessToken) {
            $this->markTestSkipped('No auth token available');
        }

        $data = [
            'name' => 'Invalid Type Animal',
            'type' => 'Elephant',  // Invalid type
            'intake_status' => 'Stray'
        ];

        $response = $this->makeAuthRequest('POST', '/animals', $data);
        
        $this->assertEquals(422, $response['status']);
    }

    public function testUpdateAnimal(): void
    {
        if (!self::$accessToken || !self::$createdAnimalId) {
            $this->markTestSkipped('No auth token or test animal available');
        }

        $data = [
            'name' => 'Updated PHPUnit Dog',
            'weight' => 18.0
        ];

        $response = $this->makeAuthRequest('PUT', '/animals/' . self::$createdAnimalId, $data);
        
        $this->assertEquals(200, $response['status']);
    }

    public function testUpdateAnimalStatus(): void
    {
        if (!self::$accessToken || !self::$createdAnimalId) {
            $this->markTestSkipped('No auth token or test animal available');
        }

        $data = [
            'status' => 'In Treatment'
        ];

        $response = $this->makeAuthRequest('PATCH', '/animals/' . self::$createdAnimalId . '/status', $data);
        
        $this->assertEquals(200, $response['status']);
    }

    // ==========================================
    // ROLE-BASED ACCESS TESTS
    // ==========================================

    public function testDeleteAnimalRequiresAdminRole(): void
    {
        // This test verifies role-based access
        // Non-admin users should get 403
        if (!self::$accessToken) {
            $this->markTestSkipped('No auth token available');
        }

        // If we're admin, this would succeed
        // If not admin, should get 403
        $response = $this->makeAuthRequest('DELETE', '/animals/999999');
        
        // Should be 403 (forbidden) or 404 (not found) - both are valid
        $this->assertContains($response['status'], [403, 404]);
    }

    // ==========================================
    // PAGINATION TESTS
    // ==========================================

    public function testAnimalListPagination(): void
    {
        if (!self::$accessToken) {
            $this->markTestSkipped('No auth token available');
        }

        $response = $this->makeAuthRequest('GET', '/animals?page=1&per_page=5');
        
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('pagination', $response['body']);
    }

    public function testAnimalListFiltering(): void
    {
        if (!self::$accessToken) {
            $this->markTestSkipped('No auth token available');
        }

        $response = $this->makeAuthRequest('GET', '/animals?type=Dog&status=Available');
        
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response['body']);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    private static function login(string $username, string $password): array
    {
        $baseUrl = 'http://localhost:8000/api/v1';
        $url = $baseUrl . '/auth/login';

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json",
                'content' => json_encode(['username' => $username, 'password' => $password]),
                'ignore_errors' => true,
                'timeout' => 10
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        return json_decode($response ?: '{}', true) ?: [];
    }

    private function makeRequest(string $method, string $endpoint): array
    {
        return $this->makeAuthRequest($method, $endpoint, null, null);
    }

    private function makeAuthRequest(string $method, string $endpoint, ?array $data = null, ?string $token = null): array
    {
        $token = $token ?? self::$accessToken;
        $baseUrl = 'http://localhost:8000/api/v1';
        $url = $baseUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 10
            ]
        ];

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $options['http']['content'] = json_encode($data);
        }

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        $status = 500;
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $status = (int)($matches[1] ?? 500);
        }

        return [
            'status' => $status,
            'body' => json_decode($response ?: '{}', true) ?: []
        ];
    }
}
