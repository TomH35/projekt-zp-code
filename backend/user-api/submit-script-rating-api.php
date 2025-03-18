<?php
require '../vendor/autoload.php';
require_once '../Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secretKey = 'YFIKmQ8HQEIbNcKA0jbbscN5c0z3WrwhXV7cqkh7Kro4GVxeIIebNztbphaNKh4r';

$headers = getallheaders();
if (!isset($headers['auth'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}
$token = str_replace('Bearer ', '', $headers['auth']);
try {
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
    $user_id = $decoded->data->user_id;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token: ' . $e->getMessage()]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);
if (!isset($data['script_id'], $data['rating'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}
$script_id = $data['script_id'];
$rating = intval($data['rating']);
if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

$db = connect_to_database();

$checkStmt = $db->prepare("SELECT id FROM script_ratings WHERE script_id = ? AND user_id = ?");
$checkStmt->execute([$script_id, $user_id]);

if ($checkStmt->rowCount() > 0) {
    $updateStmt = $db->prepare("UPDATE script_ratings SET rating = ?, updated_at = CURRENT_TIMESTAMP WHERE script_id = ? AND user_id = ?");
    $result = $updateStmt->execute([$rating, $script_id, $user_id]);
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Rating updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error during update']);
    }
} else {
    $insertStmt = $db->prepare("INSERT INTO script_ratings (script_id, user_id, rating) VALUES (?, ?, ?)");
    $result = $insertStmt->execute([$script_id, $user_id, $rating]);
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Rating submitted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error during insert']);
    }
}
?>
