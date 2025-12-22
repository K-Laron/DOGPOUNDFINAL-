<?php
// Function to make CURL request
function request($method, $path, $token = null, $data = null) {
    $url = 'http://localhost:8000' . $path;
    $ch = curl_init($url);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'body' => $response];
}

echo "1. Logging in...\n";
$login = request('POST', '/auth/login', null, [
    'email' => 'admin@catarmandogpound.com',
    'password' => 'password'
]);

echo "Login Status: " . $login['code'] . "\n";
$auth = json_decode($login['body'], true);

if (!isset($auth['data']['access_token'])) {
    echo "❌ Login failed. Response: " . $login['body'] . "\n";
    exit(1);
}

$token = $auth['data']['access_token'];
echo "✅ Got Token: " . substr($token, 0, 20) . "...\n\n";

echo "2. Testing /dashboard/stats...\n";
$stats = request('GET', '/dashboard/stats', $token);
echo "Status: " . $stats['code'] . "\n";
if ($stats['code'] !== 200) {
    echo "❌ Failed! Body:\n" . $stats['body'] . "\n";
} else {
    echo "✅ Success! JSON valid: " . (json_decode($stats['body']) ? 'Yes' : 'No') . "\n";
}

echo "\n3. Testing /animals...\n";
$animals = request('GET', '/animals?per_page=5', $token);
echo "Status: " . $animals['code'] . "\n";
if ($animals['code'] !== 200) {
    echo "❌ Failed! Body:\n" . $animals['body'] . "\n";
} else {
    echo "✅ Success! JSON valid: " . (json_decode($animals['body']) ? 'Yes' : 'No') . "\n";
}

echo "\n4. Testing /dashboard/activity...\n";
$activity = request('GET', '/dashboard/activity?limit=10', $token);
echo "Status: " . $activity['code'] . "\n";
if ($activity['code'] !== 200) {
    echo "❌ Failed! Body:\n" . $activity['body'] . "\n";
} else {
    echo "✅ Success! JSON valid: " . (json_decode($activity['body']) ? 'Yes' : 'No') . "\n";
}
