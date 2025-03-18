<?php
require '../vendor/autoload.php';
require_once '../Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function getUserRobots() {
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

        if (!isset($data['script_names']) || !is_array($data['script_names'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input: script names are required']);
            return;
        }

        $scriptNames = $data['script_names'];
        
        $placeholders = str_repeat('?,', count($scriptNames) - 1) . '?';
        $query = "SELECT script_name, code FROM user_scripts WHERE user_id = ? AND script_name IN ($placeholders)";

        $stmt = $db->prepare($query);
        $stmt->execute(array_merge([$user_id], $scriptNames));

        $robots = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($robots);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error retrieving robots: ' . $e->getMessage()]);
    } finally {
        close_database_connection($db);
    }
}

getUserRobots();
