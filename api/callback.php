<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
$logFile = __DIR__ . '/../callback_errors.log';
ini_set('error_log', $logFile);

function sendJson($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendJson(['success' => true]);
}

if (isset($_GET['action']) && $_GET['action'] === 'create' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'Method not allowed'], 405);
}

$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    error_log("Config not found: $configPath");
    sendJson(['error' => 'Configuration error'], 500);
}
require_once $configPath;

$conn = getDBConnection();
if (!$conn) {
    $err = mysqli_connect_error();
    error_log("DB connection failed: $err");
    sendJson(['error' => 'Database connection failed'], 500);
}

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $productName = trim($_POST['product_name'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $type = $_POST['type'] ?? 'callback';
    $agreement = isset($_POST['agreement']) ? 1 : 0;
    
    if (empty($phone)) {
        sendJson(['error' => 'Введите номер телефона'], 400);
    }
    
    $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
    if (strlen($cleanPhone) < 10) {
        sendJson(['error' => 'Некорректный номер телефона'], 400);
    }
    
    if (!$agreement) {
        sendJson(['error' => 'Требуется согласие на обработку данных'], 400);
    }
    
    $stmt = $conn->prepare("INSERT INTO callbacks (name, phone, product_id, product_name, comment, type, agreement, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'new')");
    
    if (!$stmt) {
        $err = $conn->error;
        error_log("Prepare failed: $err");
        sendJson(['error' => 'Database error: ' . $err], 500);
    }
    
    $stmt->bind_param("ssissii", $name, $phone, $productId, $productName, $comment, $type, $agreement);
    
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        sendJson(['success' => true, 'message' => 'Заявка принята! Мы перезвоним.', 'id' => $id]);
    } else {
        $err = $stmt->error;
        error_log("Execute failed: $err");
        $stmt->close();
        $conn->close();
        sendJson(['error' => 'Ошибка сохранения: ' . $err], 500);
    }
    exit;
}

if ($action === 'all') {
    session_start();
    if (!isset($_SESSION['admin'])) {
        sendJson(['error' => 'Access denied'], 403);
    }
    
    $result = $conn->query("SELECT * FROM callbacks ORDER BY created_at DESC");
    if (!$result) {
        sendJson(['error' => 'Query failed: ' . $conn->error], 500);
    }
    
    $callbacks = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    sendJson(['callbacks' => $callbacks]);
    exit;
}

if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    if (!isset($_SESSION['admin'])) {
        sendJson(['error' => 'Access denied'], 403);
    }
    
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if (!in_array($status, ['new', 'called', 'cancelled'])) {
        sendJson(['error' => 'Invalid status'], 400);
    }
    
    $stmt = $conn->prepare("UPDATE callbacks SET status = ? WHERE id = ?");
    if (!$stmt) {
        sendJson(['error' => 'Prepare failed'], 500);
    }
    
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    sendJson(['success' => true]);
    exit;
}

if ($action === 'delete') {
    session_start();
    if (!isset($_SESSION['admin'])) {
        sendJson(['error' => 'Access denied'], 403);
    }
    
    $id = (int)($_GET['id'] ?? 0);
    $conn->query("DELETE FROM callbacks WHERE id = $id");
    $conn->close();
    sendJson(['success' => true]);
    exit;
}

$conn->close();
sendJson(['error' => 'Unknown action: ' . $action], 400);
?>