<?php
require '../vendor/autoload.php';
require_once '../Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secretKey = 'M07gGoLVPCMAPuFvV2PLgFBFYH3lPb0Ov22jlxxcliX3PkBYXnXfFmXm76y5twn7';

$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['success'=> false, 'message'=> 'No token provided']);
    exit;
}
$token = str_replace('Bearer ', '', $headers['Authorization']);
try {
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
    if (!isset($decoded->data->admin) || $decoded->data->admin != true) {
        http_response_code(403);
        echo json_encode(['success'=> false, 'message'=> 'Access denied: Not an admin']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success'=> false, 'message'=> 'Invalid token: ' . $e->getMessage()]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);
if (!isset($data['article_id'])) {
    http_response_code(400);
    echo json_encode(['success'=> false, 'message'=> 'Missing article_id']);
    exit;
}
$article_id = $data['article_id'];

$db = connect_to_database();
$stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
$result = $stmt->execute([$article_id]);

if ($result) {
    echo json_encode(['success'=> true, 'message'=> 'Article deleted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success'=> false, 'message'=> 'Database error during delete']);
}
?>
