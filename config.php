<?php

define('DB_HOST', 'host');
define('DB_USER', 'user');
define('DB_PASS', 'password');
define('DB_NAME', 'database');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("DB Error: " . $conn->connect_error);
        return null;
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}
?>