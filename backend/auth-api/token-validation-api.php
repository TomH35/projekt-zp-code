<?php
require '../vendor/autoload.php';
require_once '../Database.php'; 

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

$secretKey = 'M07gGoLVPCMAPuFvV2PLgFBFYH3lPb0Ov22jlxxcliX3PkBYXnXfFmXm76y5twn7';

$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No token provided']);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);

try {
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
    echo json_encode(['status' => 'valid', 'message' => 'Token is valid']);
} catch (ExpiredException $e) {
    http_response_code(401);
    echo json_encode(['status' => 'expired', 'message' => 'Token is expired']);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'invalid', 'message' => 'Token is invalid']);
}
?>
