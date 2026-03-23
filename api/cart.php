<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../auth.php';
require_once '../config.php';


if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется авторизация']);
    exit;
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();
$action = $_GET['action'] ?? '';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    
    if ($productId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Некорректный ID товара']);
        exit;
    }
    
    $product = $conn->query("SELECT id, price FROM products WHERE id = $productId")->fetch_assoc();
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Товар не найден']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $newQuantity = $row['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $newQuantity, $row['id']);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $userId, $productId, $quantity);
        $stmt->execute();
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => true, 'message' => 'Товар добавлен в корзину']);
    exit;
}

if ($action === 'get') {
    $cart = $conn->query("
        SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.old_price, p.image 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = $userId
    ")->fetch_all(MYSQLI_ASSOC);
    
    $total = 0;
    foreach ($cart as &$item) {
        $item['subtotal'] = $item['price'] * $item['quantity'];
        $total += $item['subtotal'];
    }
    
    $conn->close();
    
    echo json_encode([
        'items' => $cart,
        'total' => $total,
        'count' => count($cart)
    ]);
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $quantity, $cartId, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'remove') {
    $cartId = (int)($_GET['cart_id'] ?? 0);
    
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cartId, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'clear') {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    echo json_encode(['success' => true]);
    exit;
}

$conn->close();
echo json_encode(['error' => 'Invalid action']);
?>