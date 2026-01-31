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
if (!$courseId || $courseId < 1) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid course_id']);
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

// Find last inserted point for this course.
$selectStmt = $conn->prepare('SELECT id FROM golf_coordinates WHERE course_id = ? ORDER BY id DESC LIMIT 1');
if (!$selectStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$selectStmt->bind_param('i', $courseId);
if (!$selectStmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query failed']);
    exit;
}

$result = $selectStmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
if (!$row || !isset($row['id'])) {
    echo json_encode(['success' => true, 'message' => 'Nothing to delete']);
    exit;
}

$id = (int) $row['id'];
$deleteStmt = $conn->prepare('DELETE FROM golf_coordinates WHERE id = ?');
if (!$deleteStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$deleteStmt->bind_param('i', $id);
if (!$deleteStmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Delete failed']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'OK', 'deleted_id' => $id]);
?>
