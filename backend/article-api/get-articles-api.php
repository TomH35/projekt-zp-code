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

$db = connect_to_database();
$sql = "SELECT a.*, c.category_name 
        FROM articles a 
        JOIN categories c ON a.category_id = c.id 
        ORDER BY a.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success'=> true, 'articles'=> $articles]);
?>
