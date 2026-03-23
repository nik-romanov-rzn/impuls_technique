<?php

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_lifetime', 86400);
    session_start();
}

require_once 'config.php';

function registerUser($username, $email, $password, $phone = null) {
    try {
        $conn = getDBConnection();
        
        if (!$conn) {
            return ['success' => false, 'error' => 'Ошибка подключения к базе данных'];
        }
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt) {
            $conn->close();
            return ['success' => false, 'error' => 'Ошибка подготовки запроса'];
        }
        
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'error' => 'Пользователь с таким именем или email уже существует'];
        }
        $stmt->close();
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $conn->close();
            return ['success' => false, 'error' => 'Ошибка подготовки запроса'];
        }
        
        $stmt->bind_param("ssss", $username, $email, $passwordHash, $phone);
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt->close();
            $conn->close();
            
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'user';
            
            return ['success' => true, 'user_id' => $userId];
        }
        
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        
        return ['success' => false, 'error' => 'Ошибка при регистрации: ' . $error];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Исключение: ' . $e->getMessage()];
    }
}

function loginUser($email, $password) {
    try {
        $conn = getDBConnection();
        
        if (!$conn) {
            return ['success' => false, 'error' => 'Ошибка подключения к базе данных'];
        }
        
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
        if (!$stmt) {
            $conn->close();
            return ['success' => false, 'error' => 'Ошибка подготовки запроса'];
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'error' => 'Неверный email или пароль'];
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!password_verify($password, $user['password'])) {
            $conn->close();
            return ['success' => false, 'error' => 'Неверный email или пароль'];
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        $conn->close();
        return ['success' => true, 'user' => $user];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Исключение: ' . $e->getMessage()];
    }
}

function logoutUser() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
    
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'] ?? 'user'
    ];
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
?>