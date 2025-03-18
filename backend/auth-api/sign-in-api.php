<?php
require '../vendor/autoload.php';
require_once '../Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function UserSignIn() {
    $db = connect_to_database();

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['email'], $data['password'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid input']);
        return;
    }

    $email = $data['email'];
    $password = $data['password'];

    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['message' => 'The entered email or password is incorrect']);
            return;
        }

        $secretKey = 'YFIKmQ8HQEIbNcKA0jbbscN5c0z3WrwhXV7cqkh7Kro4GVxeIIebNztbphaNKh4r';
        $issuedAt = time();
        $expirationTime = $issuedAt + 604800;//604800
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'username' => $user['username']
            ]
        ];

        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        echo json_encode([
            'access_token' => $jwt,
            'user_id' => $user['id']
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
    } finally {
        close_database_connection($db);
    }
}

UserSignIn();
?>
