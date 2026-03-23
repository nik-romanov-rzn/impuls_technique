<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется авторизация'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$conn = getDBConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к БД'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        
        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Некорректный ID товара'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $stmt = $conn->prepare("INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Добавлено в избранное'], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }
    
    if ($action === 'remove') {
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        
        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Некорректный ID товара'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }
    
    if ($action === 'get') {
        $sql = "SELECT f.id, f.product_id, p.name, p.price, p.old_price, p.image, p.rating, p.reviews, p.badge, p.badge_text 
                FROM favorites f 
                JOIN products p ON f.product_id = p.id 
                WHERE f.user_id = ? 
                ORDER BY f.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => (int)$row['id'],
                'product_id' => (int)$row['product_id'],
                'name' => (string)$row['name'],
                'price' => (float)$row['price'],
                'old_price' => $row['old_price'] !== null ? (float)$row['old_price'] : null,
                'image' => (string)$row['image'],
                'rating' => (float)($row['rating'] ?? 5.0),
                'reviews' => (int)($row['reviews'] ?? 0),
                'badge' => $row['badge'],
                'badge_text' => $row['badge_text']
            ];
        }
        
        $stmt->close();
        $conn->close();
        
        echo json_encode(['items' => $items, 'count' => count($items)], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit;
    }
    
    if ($action === 'check') {
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        
        if ($productId <= 0) {
            echo json_encode(['in_favorites' => false], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $inFavorites = $result->num_rows > 0;
        $stmt->close();
        $conn->close();
        
        echo json_encode(['in_favorites' => $inFavorites], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action: ' . $action], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    if ($conn) $conn->close();
}
?>