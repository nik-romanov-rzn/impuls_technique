<?php

session_start();
require_once '../config.php';

$admin_pass = '123';

if (isset($_POST['login'])) {
    if ($_POST['password'] === $admin_pass) {
        $_SESSION['admin'] = true;
    } else {
        $error = 'Неверный пароль';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['admin'])) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Вход в админку - Импульс</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                display: flex; 
                justify-content: center; 
                align-items: center; 
                min-height: 100vh; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            }
            .login-box { 
                background: white; 
                padding: 3rem; 
                border-radius: 1.5rem; 
                box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
                width: 100%; 
                max-width: 400px; 
            }
            .login-box h2 { 
                text-align: center; 
                margin-bottom: 2rem; 
                color: #1d1d1f; 
            }
            input { 
                padding: 1rem; 
                border: 2px solid #e1e1e6; 
                border-radius: 0.5rem; 
                width: 100%; 
                margin-bottom: 1rem; 
                font-size: 1rem; 
            }
            input:focus { 
                outline: none; 
                border-color: #667eea; 
            }
            button { 
                background: linear-gradient(135deg, #667eea, #764ba2); 
                color: white; 
                border: none; 
                padding: 1rem; 
                border-radius: 0.5rem; 
                width: 100%; 
                cursor: pointer; 
                font-size: 1rem; 
                font-weight: 600; 
            }
            button:hover { 
                transform: translateY(-2px); 
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); 
            }
            .error { 
                background: #f8d7da; 
                color: #721c24; 
                padding: 1rem; 
                border-radius: 0.5rem; 
                margin-bottom: 1rem; 
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Вход в админку</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Пароль" required>
                <button type="submit" name="login">Войти</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$conn = getDBConnection();

$stats = [
    'users' => 0,
    'orders' => 0,
    'products' => 0,
    'revenue' => 0
];

if ($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['users'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    $stats['orders'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $stats['products'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE status != 'cancelled'");
    $stats['revenue'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Импульс</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f7;
            min-height: 100vh;
        }
        
        .header {
            background: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            font-size: 1.5rem;
            color: #1d1d1f;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .header-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: #f5576c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #d63d4f;
        }
        
        .container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 2rem 3rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .stat-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .stat-info h3 {
            font-size: 0.875rem;
            color: #86868b;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1d1d1f;
        }
        
.menu-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    margin-bottom: 2rem;
    width: 100%;
}

.menu-card {
    background: white;
    border-radius: 1.5rem;
    padding: 3rem 2rem;
    text-align: center;
    text-decoration: none;
    color: #1d1d1f;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: all 0.3s;
    cursor: pointer;
    min-height: 280px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.menu-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.15);
}

.menu-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 1.5rem;
    border-radius: 1.5rem;
    overflow: hidden;
    background: #f5f5f7;
    transition: transform 0.3s;
}

.menu-card:hover .menu-icon {
    transform: scale(1.1);
}

.menu-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.menu-label {
    font-weight: 700;
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}

.menu-sublabel {
    font-size: 0.875rem;
    color: #86868b;
}
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .menu-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .container {
                padding: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <i class="fas fa-chart-line" style="color: #667eea;"></i>
            Панель управления
        </h1>
        <div class="header-buttons">
            <a href="../index.html" class="btn btn-primary" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                На сайт
            </a>
            <a href="?logout" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i>
                Выйти
            </a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="people.png" alt="Пользователи">
                </div>
                <div class="stat-info">
                    <h3>Пользователей</h3>
                    <div class="stat-value"><?= number_format($stats['users']) ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="order.png" alt="Заказы">
                </div>
                <div class="stat-info">
                    <h3>Заказов</h3>
                    <div class="stat-value"><?= number_format($stats['orders']) ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="goods.png" alt="Товары">
                </div>
                <div class="stat-info">
                    <h3>Товаров</h3>
                    <div class="stat-value"><?= number_format($stats['products']) ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="growth.png" alt="Прибыль">
                </div>
                <div class="stat-info">
                    <h3>Прибыль</h3>
                    <div class="stat-value"><?= number_format($stats['revenue'], 0, '.', ' ') ?> ₽</div>
                </div>
            </div>
        </div>
        
        <h2 class="menu-title">Управление</h2>
        <div class="menu-grid">
            <a href="products.php" class="menu-card">
                <div class="menu-icon">
                    <img src="goods2.png" alt="Товары">
                </div>
                <div class="menu-label">Товары</div>
                <div class="menu-sublabel">Управление каталогом</div>
            </a>
            
            <a href="orders.php" class="menu-card">
                <div class="menu-icon">
                    <img src="order2.png" alt="Заказы">
                </div>
                <div class="menu-label">Заказы</div>
                <div class="menu-sublabel">Обработка заказов</div>
            </a>
            
            <a href="users.php" class="menu-card">
                <div class="menu-icon">
                    <img src="people2.png" alt="Пользователи">
                </div>
                <div class="menu-label">Пользователи</div>
                <div class="menu-sublabel">Управление клиентами</div>
            </a>
            
            <a href="analytics.php" class="menu-card" onclick="alert('Раздел в разработке')">
                <div class="menu-icon">
                    <img src="control.png" alt="Аналитика">
                </div>
                <div class="menu-label">Аналитика</div>
                <div class="menu-sublabel">Статистика и отчёты</div>
            </a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statValues = document.querySelectorAll('.stat-value');
            
            statValues.forEach(stat => {
                const finalValue = stat.textContent;
                const isMoney = finalValue.includes('₽');
                const numericValue = parseInt(finalValue.replace(/\D/g, '')) || 0;
                
                let currentValue = 0;
                const increment = numericValue / 50;
                const duration = 1000;
                const stepTime = duration / 50;
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= numericValue) {
                        currentValue = numericValue;
                        clearInterval(timer);
                    }
                    
                    let displayValue = Math.floor(currentValue).toLocaleString('ru-RU');
                    if (isMoney) displayValue += ' ₽';
                    
                    stat.textContent = displayValue;
                }, stepTime);
            });
        });
    </script>
</body>
</html>