<?php
require '../vendor/autoload.php';
require_once '../Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secretKey = 'YFIKmQ8HQEIbNcKA0jbbscN5c0z3WrwhXV7cqkh7Kro4GVxeIIebNztbphaNKh4r';
$headers = getallheaders();

if (!isset($headers['auth'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

$token = str_replace('Bearer ', '', $headers['auth']);

try {
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
    $jwt_user_id = $decoded->data->user_id;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

function submitCode() {
    $db = connect_to_database();
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['user_id'], $data['script_name'], $data['code'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid input', 'success' => false]);
        return;
    }

    $user_id     = $data['user_id'];
    $script_name = $data['script_name'];
    $code        = $data['code'];

    global $jwt_user_id;

    if ($jwt_user_id !== (int)$user_id) {
        http_response_code(403);
        echo json_encode([
            'message' => 'Forbidden: token user_id does not match request user_id.',
            'success' => false
        ]);
        return;
    }

    $disallowedPatterns = [
        '/eval\s*\(/i',                          
        '/import\s*\(.*http.*\)/i',              
        '/document\.body/i',                     
        '/document\.cookie/i',                   
        '/document\.write\s*\(/i',               
        '/localStorage\.getItem\s*\(/i',         
        '/sessionStorage\.getItem\s*\(/i',       
        '/fetch\s*\(.*attacker-website\.com/i',  
        '/script\.src/i',                        
        '/navigator\.serviceWorker\.register/i', 
        '/window\.location\.href/i',             
        '/while\s*\(\s*true\s*\)/i'              
    ];

    foreach ($disallowedPatterns as $pattern) {
        if (preg_match($pattern, $code)) {
            http_response_code(400);
            echo json_encode([
                'message' => 'Malicious code detected. Disallowed pattern: ' . $pattern,
                'success' => false
            ]);
            close_database_connection($db);
            return;
        }
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO user_scripts (user_id, script_name, code, created_at, updated_at) 
            VALUES (:user_id, :script_name, :code, NOW(), NOW())
        ");

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
