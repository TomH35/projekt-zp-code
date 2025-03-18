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
    $user_id = $decoded->data->user_id;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!isset($data['script_id'], $data['code'], $data['script_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$script_id   = $data['script_id'];
$code        = $data['code'];
$script_name = $data['script_name'];

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
            'success' => false,
            'message' => 'Malicious code detected. Disallowed pattern: ' . $pattern
        ]);
        exit; 
    }
}

$db = connect_to_database();
$stmt = $db->prepare("
    UPDATE user_scripts 
    SET code = ?, script_name = ?, updated_at = CURRENT_TIMESTAMP 
    WHERE id = ? AND user_id = ?
");

$stmt->execute([$code, $script_name, $script_id, $user_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Code updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed or unauthorized']);
}
