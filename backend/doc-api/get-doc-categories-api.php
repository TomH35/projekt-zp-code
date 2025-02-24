<?php
require '../vendor/autoload.php';
require_once '../Database.php';

$db = connect_to_database();
$stmt = $db->prepare("SELECT id, category_name FROM categories ORDER BY category_name ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'categories' => $categories]);
?>
