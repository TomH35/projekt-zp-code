<?php
require '../vendor/autoload.php';
require_once '../Database.php';

if (!isset($_GET['category_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing category_id parameter']);
    exit;
}
$category_id = $_GET['category_id'];

$db = connect_to_database();
$stmt = $db->prepare("SELECT a.id, a.title, a.slug, a.created_at
                      FROM articles a
                      WHERE a.category_id = ?
                      ORDER BY a.created_at DESC");
$stmt->execute([$category_id]);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'articles' => $articles]);
?>
