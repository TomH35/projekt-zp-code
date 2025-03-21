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

    $db = connect_to_database();
    $stmt = $db->prepare("SELECT id, script_name, script_is_public FROM user_scripts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $scripts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'scripts' => $scripts]);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
