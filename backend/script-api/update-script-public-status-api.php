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
    $user_id = $decoded->data->user_id;

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    if (!isset($data['script_id']) || !isset($data['script_is_public'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    $script_id = $data['script_id'];
    $script_is_public = $data['script_is_public'];

    $db = connect_to_database();
    $stmt = $db->prepare("UPDATE user_scripts SET script_is_public = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$script_is_public, $script_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Script public status updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Script not found or unauthorized']);
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
