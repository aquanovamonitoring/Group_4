<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

// TODO: Add API key / device token authentication here before exposing this endpoint to the internet.
// Example: Validate an X-API-Key header against a whitelist of device tokens.

header('Content-Type: application/json');
header('Allow: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function jsonError(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$deviceId = trim((string)($_POST['device_id'] ?? ''));
$soilMoistureRaw = $_POST['soil_moisture'] ?? null;
$waterLevelRaw = $_POST['water_level'] ?? null;
$pumpStatus = strtoupper(trim((string)($_POST['pump_status'] ?? '')));

if ($deviceId === '' || strlen($deviceId) > 50) {
    jsonError(400, 'Invalid sensor data');
}

$soilMoisture = null;
if ($soilMoistureRaw !== null && $soilMoistureRaw !== '') {
    $soilMoisture = (int)$soilMoistureRaw;
    if ($soilMoisture < 0 || $soilMoisture > 100) {
        jsonError(400, 'Invalid sensor data');
    }
}

if ($waterLevelRaw === null || $waterLevelRaw === '') {
    jsonError(400, 'Invalid sensor data');
}
$waterLevel = (int)$waterLevelRaw;
if ($waterLevel < 0 || $waterLevel > 100) {
    jsonError(400, 'Invalid sensor data');
}

if (!in_array($pumpStatus, ['ON', 'OFF'], true)) {
    jsonError(400, 'Invalid sensor data');
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare(
        'INSERT INTO water_monitoring (device_id, soil_moisture, water_level, pump_status)
         VALUES (:device_id, :soil_moisture, :water_level, :pump_status)'
    );

    $stmt->execute([
        ':device_id'     => $deviceId,
        ':soil_moisture' => $soilMoisture,
        ':water_level'   => $waterLevel,
        ':pump_status'   => $pumpStatus,
    ]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Water monitoring data saved successfully']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
