<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$projectRoot = dirname(__DIR__);
$autoload = $projectRoot . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
    if (class_exists(\Dotenv\Dotenv::class) && is_file($projectRoot . '/.env')) {
        try {
            \Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
        } catch (Throwable $e) {
        }
    }
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$providedSecret = $headers['X-MAP-SECRET'] ?? $headers['X-Map-Secret'] ?? ($_POST['secret'] ?? null);
$expectedSecret = getenv('GOLF_MEASUREMENT_PUBLIC_SECRET') ?: null;

if (!$expectedSecret || !is_string($providedSecret) || !hash_equals($expectedSecret, (string) $providedSecret)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$hole = filter_input(INPUT_POST, 'hole', FILTER_VALIDATE_INT);
$type = filter_input(INPUT_POST, 'type', FILTER_UNSAFE_RAW);
$lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);

$allowedTypes = ['green', 'tee', 'fairway'];
if (!$courseId || $courseId < 1) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid course_id']);
    exit;
}
if (!$hole || $hole < 1 || $hole > 18) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid hole']);
    exit;
}
if (!is_string($type) || !in_array($type, $allowedTypes, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
    exit;
}
if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit;
}

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: '';
$dbUser = getenv('DB_USERNAME') ?: '';
$dbPass = getenv('DB_PASSWORD') ?: '';

if ($dbName === '' || $dbUser === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, (int) $dbPort);
if ($conn->connect_errno) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$stmt = $conn->prepare('INSERT INTO golf_coordinates (course_id, hole_number, point_type, latitude, longitude) VALUES (?, ?, ?, ?, ?)');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param('iisdd', $courseId, $hole, $type, $lat, $lng);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Insert failed']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'OK', 'id' => $stmt->insert_id]);
?>
