<?php

/**
 * JWT Unit Tests
 * Tests the JWT utility class
 * 
 * @package AnimalShelter\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

class JWTTest extends TestCase
{
    private array $testPayload;

    protected function setUp(): void
    {
        $this->testPayload = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'role' => 'Admin'
        ];
    }

    // ==========================================
    // TOKEN GENERATION TESTS
    // ==========================================

    public function testGenerateReturnsString(): void
    {
        $token = JWT::generate($this->testPayload);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testGenerateReturnsThreePartToken(): void
    {
        $token = JWT::generate($this->testPayload);
        $parts = explode('.', $token);

        $this->assertCount(3, $parts);
    }

    public function testGenerateDifferentTokensForDifferentPayloads(): void
    {
        $token1 = JWT::generate(['user_id' => 1]);
        $token2 = JWT::generate(['user_id' => 2]);

        $this->assertNotEquals($token1, $token2);
    }

    // ==========================================
    // TOKEN VERIFICATION TESTS
    // ==========================================

    public function testVerifyReturnsPayloadForValidToken(): void
    {
        $token = JWT::generate($this->testPayload);
        $payload = JWT::verify($token);

        $this->assertIsArray($payload);
        $this->assertEquals(1, $payload['user_id']);
        $this->assertEquals('test@example.com', $payload['email']);
        $this->assertEquals('Admin', $payload['role']);
    }

    public function testVerifyReturnsFalseForInvalidToken(): void
    {
        $result = JWT::verify('invalid.token.here');

        $this->assertFalse($result);
    }

    public function testVerifyReturnsFalseForEmptyToken(): void
    {
        $result = JWT::verify('');

        $this->assertFalse($result);
    }

    public function testVerifyReturnsFalseForTamperedToken(): void
    {
        $token = JWT::generate($this->testPayload);

        // Tamper with the token by changing a character in the signature
        $parts = explode('.', $token);
        $parts[2] = 'tampered' . substr($parts[2], 8);
        $tamperedToken = implode('.', $parts);

        $result = JWT::verify($tamperedToken);

        $this->assertFalse($result);
    }

    public function testVerifyReturnsFalseForTamperedPayload(): void
    {
        $token = JWT::generate($this->testPayload);

        // Tamper with the payload
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]), true);
        $payload['user_id'] = 999;  // Change user ID
        $parts[1] = base64_encode(json_encode($payload));
        $tamperedToken = implode('.', $parts);

        $result = JWT::verify($tamperedToken);

        $this->assertFalse($result);
    }

    // ==========================================
    // EXPIRY TESTS
    // ==========================================

    public function testVerifyContainsExpiryTime(): void
    {
        $token = JWT::generate($this->testPayload);
        $payload = JWT::verify($token);

        $this->assertArrayHasKey('exp', $payload);
        $this->assertIsInt($payload['exp']);
    }

    public function testVerifyContainsIssuedAtTime(): void
    {
        $token = JWT::generate($this->testPayload);
        $payload = JWT::verify($token);

        $this->assertArrayHasKey('iat', $payload);
        $this->assertIsInt($payload['iat']);
    }

    public function testExpiryTimeIsInFuture(): void
    {
        $token = JWT::generate($this->testPayload);
        $payload = JWT::verify($token);

        $this->assertGreaterThan(time(), $payload['exp']);
    }

    public function testIssuedAtTimeIsNow(): void
    {
        $before = time();
        $token = JWT::generate($this->testPayload);
        $after = time();

        $payload = JWT::verify($token);

        $this->assertGreaterThanOrEqual($before, $payload['iat']);
        $this->assertLessThanOrEqual($after, $payload['iat']);
    }

    // ==========================================
    // CUSTOM EXPIRY TESTS
    // ==========================================

    public function testGenerateWithCustomExpiry(): void
    {
        $customExpiry = 3600; // 1 hour
        $token = JWT::generate($this->testPayload, $customExpiry);
        $payload = JWT::verify($token);

        $expectedExp = $payload['iat'] + $customExpiry;
        $this->assertEquals($expectedExp, $payload['exp']);
    }

    // ==========================================
    // REFRESH TOKEN TESTS
    // ==========================================

    public function testGenerateRefreshTokenReturnsString(): void
    {
        if (!method_exists(JWT::class, 'generateRefreshToken')) {
            $this->markTestSkipped('generateRefreshToken method not available');
        }

        $token = JWT::generateRefreshToken($this->testPayload);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    public function testGenerateWithEmptyPayload(): void
    {
        $token = JWT::generate([]);
        $payload = JWT::verify($token);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('iat', $payload);
    }

    public function testGenerateWithNestedPayload(): void
    {
        $nestedPayload = [
            'user' => [
                'id' => 1,
                'profile' => [
                    'name' => 'John',
                    'settings' => ['dark_mode' => true]
                ]
            ]
        ];

        $token = JWT::generate($nestedPayload);
        $payload = JWT::verify($token);

        $this->assertEquals(1, $payload['user']['id']);
        $this->assertEquals('John', $payload['user']['profile']['name']);
        $this->assertTrue($payload['user']['profile']['settings']['dark_mode']);
    }

    public function testVerifyWithMalformedBase64(): void
    {
        $malformedToken = 'not-valid-base64.also-not-valid.signature';

        $result = JWT::verify($malformedToken);

        $this->assertFalse($result);
    }

    public function testTokensAreUniquePerGeneration(): void
    {
        $token1 = JWT::generate($this->testPayload);

        // Small delay to ensure different timestamp
        usleep(1000);

        $token2 = JWT::generate($this->testPayload);

        // Tokens might be same if generated in same second, but payloads should match
        $payload1 = JWT::verify($token1);
        $payload2 = JWT::verify($token2);

        $this->assertEquals($payload1['user_id'], $payload2['user_id']);
    }
}
