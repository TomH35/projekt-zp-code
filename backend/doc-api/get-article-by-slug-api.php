<?php
require '../vendor/autoload.php';
require_once '../Database.php';

if (!isset($_GET['slug'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing slug parameter']);
    exit;
}
$slug = $_GET['slug'];

$db = connect_to_database();
$stmt = $db->prepare("SELECT a.*, c.category_name 
                      FROM articles a 
                      JOIN categories c ON a.category_id = c.id 
                      WHERE a.slug = ?");
$stmt->execute([$slug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);
if ($article) {
    echo json_encode(['success' => true, 'article' => $article]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Article not found']);
}
?>
