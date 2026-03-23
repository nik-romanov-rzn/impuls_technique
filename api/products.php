<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

$conn = getDBConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId > 0) {
    $stmt = $conn->prepare("SELECT id, name, slug, price, old_price, image, rating, reviews, badge, badge_text, category, description, created_at FROM products WHERE id = ?");
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        $conn->close();
        exit;
    }
    
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        echo json_encode([
            'id' => (int)$product['id'],
            'name' => (string)$product['name'],
            'slug' => (string)($product['slug'] ?? ''),
            'price' => (float)$product['price'],
            'old_price' => $product['old_price'] !== null ? (float)$product['old_price'] : null,
            'image' => (string)($product['image'] ?? ''),
            'rating' => (float)($product['rating'] ?? 5.0),
            'reviews' => (int)($product['reviews'] ?? 0),
            'badge' => $product['badge'] ?? null,
            'badge_text' => $product['badge_text'] ?? null,
            'category' => (string)($product['category'] ?? 'other'),
            'description' => (string)($product['description'] ?? ''),
            'created_at' => $product['created_at']
        ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}


$sql = "SELECT id, name, slug, price, old_price, image, rating, reviews, badge, badge_text, category, description, created_at FROM products ORDER BY created_at DESC, id DESC LIMIT 50";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    $conn->close();
    exit;
}

$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = [
        'id' => (int)$row['id'],
        'name' => (string)$row['name'],
        'slug' => (string)($row['slug'] ?? ''),
        'price' => (float)$row['price'],
        'old_price' => $row['old_price'] !== null ? (float)$row['old_price'] : null,
        'image' => (string)($row['image'] ?? ''),
        'rating' => (float)($row['rating'] ?? 5.0),
        'reviews' => (int)($row['reviews'] ?? 0),
        'badge' => $row['badge'] ?? null,
        'badge_text' => $row['badge_text'] ?? null,
        'category' => (string)($row['category'] ?? 'other'),
        'description' => (string)($row['description'] ?? ''),
        'created_at' => $row['created_at']
    ];
}

$conn->close();

echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>