<?php

namespace Tests\Traits;

trait ApiTestTrait
{
    protected static $accessToken;
    protected static $baseUrl = 'http://localhost:8000/api/v1';

    /**
     * Helper to perform login and get token
     */
    protected static function login(string $username, string $password): array
    {
        $url = self::$baseUrl . '/auth/login';

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

    /**
     * Make a request to the API
     */
    protected function makeRequest(string $method, string $endpoint, ?array $data = null, ?string $token = null): array
    {
        return $this->makeAuthRequest($method, $endpoint, $data, $token);
    }

    /**
     * Make an authenticated request to the API
     */
    protected function makeAuthRequest(string $method, string $endpoint, ?array $data = null, ?string $token = null): array
    {
        $token = $token ?? self::$accessToken;
        $url = self::$baseUrl . $endpoint;

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
