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

$db = connect_to_database();
$stmt = $db->prepare("SELECT id, category_name, created_at FROM categories ORDER BY created_at DESC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'categories' => $categories]);
?>
