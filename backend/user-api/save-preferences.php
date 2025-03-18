<?php
require '../vendor/autoload.php';
require_once '../Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function saveUserPreferences() {
    $db = connect_to_database();

    $headers = getallheaders();
    if (!isset($headers['auth'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    $token = str_replace('Bearer ', '', $headers['auth']);
    $secretKey = 'YFIKmQ8HQEIbNcKA0jbbscN5c0z3WrwhXV7cqkh7Kro4GVxeIIebNztbphaNKh4r';

    try {
        $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
        $user_id = $decoded->data->user_id;

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!isset($data['arena_image'], $data['gun_image'], $data['hull_image'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        $arena_image = $data['arena_image'];
        $gun_image = $data['gun_image'];
        $hull_image = $data['hull_image'];

        $checkQuery = "SELECT id FROM user_preferences WHERE user_id = ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->execute([$user_id]);
        $existingPreference = $stmt->fetch();

        if ($existingPreference) {
            $updateQuery = "UPDATE user_preferences 
                            SET arena_image = ?, gun_image = ?, hull_image = ?
                            WHERE user_id = ?";
            $stmt = $db->prepare($updateQuery);
            $stmt->execute([$arena_image, $gun_image, $hull_image, $user_id]);
        } else {
            $insertQuery = "INSERT INTO user_preferences (user_id, arena_image, gun_image, hull_image) 
                            VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($insertQuery);
            $stmt->execute([$user_id, $arena_image, $gun_image, $hull_image]);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Preferences saved successfully']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error saving preferences: ' . $e->getMessage()]);
    } finally {
        close_database_connection($db);
    }
}

saveUserPreferences();
