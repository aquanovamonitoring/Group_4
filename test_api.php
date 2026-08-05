<?php
declare(strict_types=1);

$apiUrl = 'http://localhost/api/esp8266.php';

$testCases = [
    [
        'name' => 'Valid data',
        'data' => [
            'device_id' => 'ESP01_01',
            'soil_moisture' => '42',
            'water_level' => '78',
            'pump_status' => 'OFF',
        ],
        'expectedStatus' => 200,
    ],
    [
        'name' => 'Missing device_id',
        'data' => [
            'soil_moisture' => '42',
            'water_level' => '78',
            'pump_status' => 'OFF',
        ],
        'expectedStatus' => 400,
    ],
    [
        'name' => 'Invalid water_level',
        'data' => [
            'device_id' => 'ESP01_01',
            'soil_moisture' => '42',
            'water_level' => '150',
            'pump_status' => 'OFF',
        ],
        'expectedStatus' => 400,
    ],
    [
        'name' => 'Invalid pump_status',
        'data' => [
            'device_id' => 'ESP01_01',
            'soil_moisture' => '42',
            'water_level' => '78',
            'pump_status' => 'INVALID',
        ],
        'expectedStatus' => 400,
    ],
    [
        'name' => 'Invalid method (GET)',
        'data' => [],
        'expectedStatus' => 405,
        'method' => 'GET',
    ],
];

foreach ($testCases as $case) {
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($case['data']));

    if (isset($case['method']) && $case['method'] === 'GET') {
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    $pass = $httpCode === $case['expectedStatus'] ? 'PASS' : 'FAIL';

    echo "[{$pass}] {$case['name']} — HTTP {$httpCode} (expected {$case['expectedStatus']})\n";
    echo "       Response: " . substr($response, 0, 120) . "\n\n";
}
