<?php
require '../vendor/autoload.php';
require_once '../Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secretKey = 'M07gGoLVPCMAPuFvV2PLgFBFYH3lPb0Ov22jlxxcliX3PkBYXnXfFmXm76y5twn7';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid input']);
    exit;
}

$email = $data['email'];
$password = $data['password'];

try {
    $db = connect_to_database();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['message' => 'The entered email or password is incorrect']);
        exit;
    }

    if (!isset($user['admin']) || intval($user['admin']) !== 1) {
        http_response_code(403);
        echo json_encode(['message' => 'Access denied: Not an admin']);
        exit;
    }

    $issuedAt = time();
    $expirationTime = $issuedAt + 604800;
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'data' => [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username'],
            'admin' => true
        ]
    ];

    $jwt = JWT::encode($payload, $secretKey, 'HS256');

    echo json_encode([
        'access_token' => $jwt,
        'user_id' => $user['id'],
        'admin' => true
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}
?>
