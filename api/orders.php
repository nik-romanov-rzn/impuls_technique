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

$conn = getDBConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к БД'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Требуется авторизация'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $userId = (int)$_SESSION['user_id'];
        $customerName = trim($_POST['customer_name'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');
        $pickupDate = trim($_POST['pickup_date'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $comment = trim($_POST['comment'] ?? '');
        
        if (empty($customerName) || empty($customerPhone)) {
            http_response_code(400);
            echo json_encode(['error' => 'Заполните имя и телефон'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $stmt = $conn->prepare("
            SELECT c.product_id, c.quantity, p.price, p.name 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $cartResult = $stmt->get_result();
        
        $cartItems = [];
        $totalPrice = 0;
        
        while ($row = $cartResult->fetch_assoc()) {
            $cartItems[] = $row;
            $totalPrice += $row['price'] * $row['quantity'];
        }
        $stmt->close();
        
        if (empty($cartItems)) {
            http_response_code(400);
            echo json_encode(['error' => 'Корзина пуста'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, total_price, customer_name, customer_phone, pickup_date, payment_method, comment, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'new')
        ");
        $stmt->bind_param("idsssss", $userId, $totalPrice, $customerName, $customerPhone, $pickupDate, $paymentMethod, $comment);
        
        if (!$stmt->execute()) {
            throw new Exception('Ошибка создания заказа: ' . $stmt->error);
        }
        
        $orderId = $stmt->insert_id;
        $stmt->close();
        
        foreach ($cartItems as $item) {
            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, price, quantity) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iisdi", $orderId, $item['product_id'], $item['name'], $item['price'], $item['quantity']);
            $stmt->execute();
            $stmt->close();
        }
        
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        $conn->close();
        
        echo json_encode([
            'success' => true, 
            'order_id' => $orderId,
            'total' => $totalPrice,
            'message' => 'Заказ успешно оформлен!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($action === 'user') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Требуется авторизация'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $userId = (int)$_SESSION['user_id'];
        
        $orders = $conn->query("
            SELECT * FROM orders 
            WHERE user_id = $userId 
            ORDER BY created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        $conn->close();
        echo json_encode(['orders' => $orders], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($action === 'details') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Требуется авторизация'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $userId = (int)$_SESSION['user_id'];
        $orderId = (int)($_GET['order_id'] ?? 0);
        
        $order = $conn->query("
            SELECT * FROM orders 
            WHERE id = $orderId AND user_id = $userId
        ")->fetch_assoc();
        
        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Заказ не найден'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $items = $conn->query("
            SELECT * FROM order_items WHERE order_id = $orderId
        ")->fetch_all(MYSQLI_ASSOC);
        
        $conn->close();
        echo json_encode(['order' => $order, 'items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($action === 'all') {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $orders = $conn->query("
            SELECT o.*, u.username, u.email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        $conn->close();
        echo json_encode(['orders' => $orders], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        $validStatuses = ['new', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Неверный статус'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $orderId);
        $stmt->execute();
        $stmt->close();
        
        $conn->close();
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($action === 'delete') {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit;
        }
        
        $orderId = (int)($_GET['order_id'] ?? 0);
        
        $conn->query("DELETE FROM order_items WHERE order_id = $orderId");
        $conn->query("DELETE FROM orders WHERE id = $orderId");
        
        $conn->close();
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
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