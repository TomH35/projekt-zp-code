<?php
require '../vendor/autoload.php';
require_once '../Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function getUserPreferences() {
    $db = connect_to_database();

    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $secretKey = 'M07gGoLVPCMAPuFvV2PLgFBFYH3lPb0Ov22jlxxcliX3PkBYXnXfFmXm76y5twn7';

    try {
        $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
        $user_id = $decoded->data->user_id;

        $query = "SELECT arena_image, gun_image, hull_image
                  FROM user_preferences
                  WHERE user_id = ?
                  LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');

        if (!$preferences) {
            echo json_encode(new stdClass()); 
            return;
        }

        echo json_encode($preferences);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error retrieving preferences: ' . $e->getMessage()]);
    } finally {
        close_database_connection($db);
    }
}

getUserPreferences();
