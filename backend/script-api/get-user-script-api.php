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
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

if (!isset($_GET['script_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No script id provided']);
    exit;
}

$script_id = $_GET['script_id'];
$db = connect_to_database();
$stmt = $db->prepare("SELECT id, script_name, code FROM user_scripts WHERE id = ? AND user_id = ?");
$stmt->execute([$script_id, $user_id]);
$script = $stmt->fetch(PDO::FETCH_ASSOC);

if ($script) {
    echo json_encode(['success' => true, 'script' => $script]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Script not found or unauthorized']);
}
?>
