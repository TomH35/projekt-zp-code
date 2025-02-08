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
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success'=> false, 'message'=> 'Invalid token: ' . $e->getMessage()]);
    exit;
}

$db = connect_to_database();
$sql = "SELECT us.id, us.script_name, us.code, us.created_at, u.username
        FROM user_scripts us
        JOIN users u ON us.user_id = u.id
        WHERE us.script_is_public = 1
        ORDER BY us.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$scripts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success'=> true, 'scripts'=> $scripts]);
?>
