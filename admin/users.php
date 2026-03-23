<?php

session_start();
require_once '../config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id = $id AND role = 'user'");
    header('Location: users.php');
    exit;
}

$users = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи - Админка</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f7; padding: 2rem; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .header-left img {
            width: 40px;
            height: 40px;
        }
        .header h1 { font-size: 1.5rem; color: #1d1d1f; }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #5568d3;
            transform: translateX(-2px);
        }
        table { width: 100%; background: white; border-radius: 1rem; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #f9f9f9; font-weight: 600; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; }
        .btn-danger { background: #f5576c; color: white; }
        @media (max-width: 48rem) { body { padding: 1rem; } table { font-size: 0.875rem; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <img src="https://cdn-icons-png.flaticon.com/512/1077/1077114.png" alt="Users">
                <h1>Пользователи</h1>
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> На главную
            </a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Дата регистрации</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></td>
                    <td>
                        <a href="?delete=<?= $u['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить пользователя?')">
                            <i class="fas fa-trash"></i> Удалить
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($users)): ?>
            <div style="text-align:center;padding:4rem;color:#86868b;margin-top:2rem;">
                <img src="control.png" alt="Empty" style="width:80px;height:80px;opacity:0.3;margin-bottom:1rem;">
                <p>Пользователей пока нет</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>