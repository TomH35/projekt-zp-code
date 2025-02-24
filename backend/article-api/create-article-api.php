<?php
require '../vendor/autoload.php';
require_once '../Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secretKey = 'M07gGoLVPCMAPuFvV2PLgFBFYH3lPb0Ov22jlxxcliX3PkBYXnXfFmXm76y5twn7';

$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}
$token = str_replace('Bearer ', '', $headers['Authorization']);
try {
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
    if (!isset($decoded->data->admin) || $decoded->data->admin != true) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied: Not an admin']);
        exit;
    }
    $user_id = $decoded->data->user_id;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token: ' . $e->getMessage()]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);
if (!isset($data['title'], $data['slug'], $data['category_id'], $data['content'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}
$title = $data['title'];
$slug = $data['slug'];
$category_id = $data['category_id'];
$content = $data['content'];

$db = connect_to_database();
$stmt = $db->prepare("INSERT INTO articles (user_id, title, slug, content, category_id) VALUES (?, ?, ?, ?, ?)");
$result = $stmt->execute([$user_id, $title, $slug, $content, $category_id]);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Article created successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
