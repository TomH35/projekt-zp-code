<?php
require '../vendor/autoload.php';
require_once '../Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function getUserRobotNames() {
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

        $stmt = $db->prepare("SELECT script_name FROM user_scripts WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);

        $robots = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($robots);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error processing request: ' . $e->getMessage()]);
    } finally {
        close_database_connection($db);
    }
}

getUserRobotNames();
?>

