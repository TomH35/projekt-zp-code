<?php
require '../vendor/autoload.php';
require_once '../Database.php';

function submitCode() {
    $db = connect_to_database();

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['user_id'], $data['script_name'], $data['code'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid input', 'success' => false]);
        return;
    }

    $user_id = $data['user_id'];
    $script_name = $data['script_name'];
    $code = $data['code'];

    try {
        $stmt = $db->prepare("INSERT INTO user_scripts (user_id, script_name, code, created_at, updated_at) VALUES (:user_id, :script_name, :code, NOW(), NOW())");
        $stmt->execute([
            ':user_id' => $user_id,
            ':script_name' => $script_name,
            ':code' => $code
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Code submitted successfully', 'success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to submit code', 'success' => false]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $e->getMessage(), 'success' => false]);
    } finally {
        close_database_connection($db);
    }
}

submitCode();
?>
