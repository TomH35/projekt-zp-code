<?php
require '../vendor/autoload.php';
require_once '../Database.php';

function UserRegistration() {
    $db = connect_to_database();

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['username'], $data['email'], $data['password'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid input']);
        return;
    }

    $username = $data['username'];
    $email = $data['email'];
    $password = $data['password'];

    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            http_response_code(400);
            echo json_encode(['message' => 'Email is already registered']);
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, email, password, created_at) VALUES (:username, :email, :password, NOW())";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        if ($result) {
            http_response_code(201);
            echo json_encode(['message' => 'User registered successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to register user']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
    } finally {
        close_database_connection($db);
    }
}

UserRegistration();
?>
