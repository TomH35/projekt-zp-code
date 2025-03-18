<?php
require '../vendor/autoload.php';
require_once '../Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secretKey = 'YFIKmQ8HQEIbNcKA0jbbscN5c0z3WrwhXV7cqkh7Kro4GVxeIIebNztbphaNKh4r';

$headers = getallheaders();
if (!isset($headers['auth'])) {
    http_response_code(401);
    echo json_encode(['success'=> false, 'message'=> 'No token provided']);
    exit;
}
$token = str_replace('Bearer ', '', $headers['auth']);
try {
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
    $currentUserId = $decoded->data->user_id;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success'=> false, 'message'=> 'Invalid token: ' . $e->getMessage()]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);
if (!isset($data['script_id'])) {
    http_response_code(400);
    echo json_encode(['success'=> false, 'message'=> 'Missing script_id parameter']);
    exit;
}
$script_id = $data['script_id'];

$db = connect_to_database();
$stmt = $db->prepare("SELECT script_name, code FROM user_scripts WHERE id = ? AND script_is_public = 1");
$stmt->execute([$script_id]);
$publicScript = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$publicScript) {
    echo json_encode(['success'=> false, 'message'=> 'Script not found or not public']);
    exit;
}

$stmt = $db->prepare("INSERT INTO user_scripts (user_id, script_name, code, script_is_public) VALUES (?, ?, ?, 0)");
$result = $stmt->execute([$currentUserId, $publicScript['script_name'], $publicScript['code']]);
if ($result) {
    echo json_encode(['success'=> true, 'message'=> 'Script saved successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success'=> false, 'message'=> 'Database error']);
}
?>
