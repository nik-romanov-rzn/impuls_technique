<?php

session_start();
require_once '../config.php';

if (empty($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();

$analytics = [
    'total_revenue' => 0,
    'total_orders' => 0,
    'total_users' => 0,
    'total_products' => 0,
    'avg_order_value' => 0,
    'top_products' => [],
    'orders_by_status' => [],
    'revenue_by_month' => [],
    'recent_orders' => []
];

if ($conn) {
    $result = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE status != 'cancelled'");
    $analytics['total_revenue'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    $analytics['total_orders'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $analytics['total_users'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $analytics['total_products'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    if ($analytics['total_orders'] > 0) {
        $analytics['avg_order_value'] = $analytics['total_revenue'] / $analytics['total_orders'];
    }
    
    $result = $conn->query("
        SELECT oi.product_name, SUM(oi.quantity) as total_sold, SUM(oi.price * oi.quantity) as revenue
        FROM order_items oi
        GROUP BY oi.product_id, oi.product_name
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $analytics['top_products'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    
    $result = $conn->query("
        SELECT status, COUNT(*) as count 
        FROM orders 
        GROUP BY status
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $analytics['orders_by_status'][$row['status']] = $row['count'];
        }
    }
    
    $result = $conn->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
               SUM(total_price) as revenue,
               COUNT(*) as orders
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $analytics['revenue_by_month'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    
    $result = $conn->query("
        SELECT o.*, u.username, u.email 
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $analytics['recent_orders'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$statusNames = [
    'new' => 'Новый',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'delivered' => 'Доставлен',
    'cancelled' => 'Отменён'
];

$statusColors = [
    'new' => '#1976d2',
    'processing' => '#f57c00',
    'shipped' => '#388e3c',
    'delivered' => '#1976d2',
    'cancelled' => '#d32f2f'
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика - Админ-панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f7;
            min-height: 100vh;
        }
        
        .header {
            background: white;
            padding: 1.5rem 3rem;
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
        
        .btn-secondary {
            background: #f5f5f7;
            color: #1d1d1f;
            border: 2px solid #e1e1e6;
        }
        
        .container {
            width: 100%;
            padding: 2rem 3rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #86868b;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1d1d1f;
            margin-bottom: 0.5rem;
        }
        
        .stat-change {
            font-size: 0.8125rem;
            color: #28a745;
            font-weight: 600;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        
        .chart-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #1d1d1f;
        }
        
        .table-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: #f9f9f9;
            font-weight: 600;
            color: #1d1d1f;
        }
        
        tr:hover {
            background: #fafafa;
        }
        
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            display: inline-block;
        }
        
        .two-columns {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        @media (max-width: 1200px) {
            .stats-grid,
            .charts-grid,
            .two-columns {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 1.5rem;
            }
            
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <i class="fas fa-chart-line" style="color: #667eea;"></i>
            Аналитика и статистика
        </h1>
        <div class="header-buttons">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                На главную
            </a>
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
                <div class="stat-label">Общая прибыль</div>
                <div class="stat-value"><?= number_format($analytics['total_revenue'], 0, '.', ' ') ?> ₽</div>
                <div class="stat-change">За всё время</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Всего заказов</div>
                <div class="stat-value"><?= number_format($analytics['total_orders']) ?></div>
                <div class="stat-change">За всё время</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Пользователей</div>
                <div class="stat-value"><?= number_format($analytics['total_users']) ?></div>
                <div class="stat-change">Зарегистрировано</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Товаров</div>
                <div class="stat-value"><?= number_format($analytics['total_products']) ?></div>
                <div class="stat-change">В каталоге</div>
            </div>
        </div>
        
        <div class="two-columns">
            <div class="table-card">
                <h3 class="chart-title">Топ товаров</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Товар</th>
                            <th>Продано</th>
                            <th>Выручка</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['top_products'] as $product): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($product['product_name']) ?></strong></td>
                            <td><?= $product['total_sold'] ?> шт.</td>
                            <td><?= number_format($product['revenue'], 0, '.', ' ') ?> ₽</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($analytics['top_products'])): ?>
                        <tr>
                            <td colspan="3" style="text-align:center;color:#86868b;">Нет данных</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="table-card">
                <h3 class="chart-title">Последние заказы</h3>
                <table>
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Клиент</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['recent_orders'] as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td><?= number_format($order['total_price'], 0, '.', ' ') ?> ₽</td>
                            <td>
                                <span class="status-badge" style="background: <?= $statusColors[$order['status']] ?>">
                                    <?= $statusNames[$order['status']] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($analytics['recent_orders'])): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;color:#86868b;">Нет заказов</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        const revenueData = <?= json_encode($analytics['revenue_by_month']) ?>;
        const ordersByStatus = <?= json_encode($analytics['orders_by_status']) ?>;
        
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(d => {
                    const [year, month] = d.month.split('-');
                    return `${month}.${year}`;
                }),
                datasets: [{
                    label: 'Прибыль (₽)',
                    data: revenueData.map(d => d.revenue),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('ru-RU') + ' ₽';
                            }
                        }
                    }
                }
            }
        });
        
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map(fn($s) => $statusNames[$s], array_keys($analytics['orders_by_status']))) ?>,
                datasets: [{
                    data: Object.values(ordersByStatus),
                    backgroundColor: Object.keys(ordersByStatus).map(status => <?= json_encode($statusColors) ?>[status])
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>