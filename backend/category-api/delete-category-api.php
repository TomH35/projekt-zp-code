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
    if (!isset($decoded->data->admin) || $decoded->data->admin != true) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied: Not an admin']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token: ' . $e->getMessage()]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);
if (!isset($data['category_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing category_id']);
    exit;
}
$category_id = $data['category_id'];

$db = connect_to_database();
$stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
$result = $stmt->execute([$category_id]);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error during delete']);
}
?>
