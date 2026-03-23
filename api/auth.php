<?php

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';
require_once '../auth.php';

$action = $_GET['action'] ?? '';

try {
    if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($username) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Заполните все обязательные поля']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Некорректный email']);
            exit;
        }
        
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Пароль должен быть не менее 6 символов']);
            exit;
        }
        
        if (strlen($username) < 2) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Имя должно быть не менее 2 символов']);
            exit;
        }
        
        $result = registerUser($username, $email, $password, $phone);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Заполните все поля']);
            exit;
        }
        
        $result = loginUser($email, $password);
        echo json_encode($result);
        exit;
    }
    
    if ($action === 'logout') {
        logoutUser();
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'check') {
        echo json_encode([
            'logged_in' => isLoggedIn(),
            'user' => getCurrentUser()
        ]);
        exit;
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>