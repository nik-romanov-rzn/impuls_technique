<?php

session_start();
require_once '../config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();

if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    $stmt->execute();
    $stmt->close();
    $success = 'Статус обновлён!';
}

if (isset($_GET['delete'])) {
    $orderId = (int)$_GET['delete'];
    $conn->query("DELETE FROM order_items WHERE order_id = $orderId");
    $conn->query("DELETE FROM orders WHERE id = $orderId");
    header('Location: orders.php');
    exit;
}

$orders = $conn->query("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$statusNames = [
    'new' => 'Новый',
    'processing' => '⚙В обработке',
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
    <title>Заказы - Админка</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f7; padding: 2rem; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 0.5rem; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-danger { background: #f5576c; color: white; }
        .btn-warning { background: #ffa500; color: white; }
        table { width: 100%; background: white; border-radius: 1rem; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #f9f9f9; font-weight: 600; }
        .status { padding: 0.375rem 0.75rem; border-radius: 2rem; font-size: 0.75rem; font-weight: 600; color: white; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 1rem; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Управление заказами</h1>
            <a href="index.php" class="btn btn-primary">← Товары</a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>№</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td>
                        <?= htmlspecialchars($order['customer_name']) ?><br>
                        <small style="color:#86868b"><?= htmlspecialchars($order['email'] ?? $order['username'] ?? '-') ?></small>
                    </td>
                    <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                    <td><strong><?= number_format($order['total_price'], 0, '.', ' ') ?> ₽</strong></td>
                    <td>
                        <span class="status" style="background:<?= $statusColors[$order['status']] ?>">
                            <?= $statusNames[$order['status']] ?>
                        </span>
                    </td>
                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-warning" onclick="viewOrder(<?= $order['id'] ?>)">👁️</button>
                        <a href="?delete=<?= $order['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($orders)): ?>
            <div style="text-align:center;padding:4rem;color:#86868b">
                <i class="fas fa-box-open" style="font-size:4rem;opacity:0.3;margin-bottom:1rem"></i>
                <p>Заказов пока нет</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="modal" id="orderModal">
        <div class="modal-content">
            <h2>Заказ #<span id="modalOrderId"></span></h2>
            <div id="modalContent"></div>
            <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1.5rem">
                <button class="btn" onclick="document.getElementById('orderModal').classList.remove('active')" style="background:#86868b;color:white">Закрыть</button>
            </div>
        </div>
    </div>
    
    <script>
        async function viewOrder(orderId) {
            var modal = document.getElementById('orderModal');
            var content = document.getElementById('modalContent');
            
            try {
                var response = await fetch('../api/orders.php?action=details&order_id=' + orderId);
                var result = await response.json();
                
                if (result.order) {
                    document.getElementById('modalOrderId').textContent = orderId;
                    
                    var items = result.items.map(function(item) {
                        return '<li>' + item.product_name + ' — ' + item.quantity + ' шт. × ' + item.price + ' ₽</li>';
                    }).join('');
                    
                    content.innerHTML = 
                        '<div style="margin-bottom:1rem">' +
                            '<strong>Клиент:</strong> ' + result.order.customer_name + '<br>' +
                            '<strong>Телефон:</strong> ' + result.order.customer_phone + '<br>' +
                            '<strong>Email:</strong> ' + (result.order.email || '-') +
                        '</div>' +
                        '<div style="margin-bottom:1rem">' +
                            '<strong>Товары:</strong><ul>' + items + '</ul>' +
                        '</div>' +
                        '<div style="margin-bottom:1rem">' +
                            '<strong>Сумма:</strong> ' + result.order.total_price + ' ₽<br>' +
                            '<strong>Оплата:</strong> ' + (result.order.payment_method === 'cash' ? 'Наличными' : 'Картой') + '<br>' +
                            '<strong>Статус:</strong> ' + result.order.status +
                        '</div>' +
                        '<div style="margin-bottom:1rem">' +
                            '<strong>Комментарий:</strong> ' + (result.order.comment || '-') +
                        '</div>' +
                        '<form method="POST" style="margin-top:1rem">' +
                            '<input type="hidden" name="order_id" value="' + orderId + '">' +
                            '<label><strong>Изменить статус:</strong></label>' +
                            '<select name="status" style="width:100%;margin-top:0.5rem">' +
                                '<option value="new"' + (result.order.status === 'new' ? ' selected' : '') + '>Новый</option>' +
                                '<option value="processing"' + (result.order.status === 'processing' ? ' selected' : '') + '> В обработке</option>' +
                                '<option value="shipped"' + (result.order.status === 'shipped' ? ' selected' : '') + '>Отправлен</option>' +
                                '<option value="delivered"' + (result.order.status === 'delivered' ? ' selected' : '') + '>Доставлен</option>' +
                                '<option value="cancelled"' + (result.order.status === 'cancelled' ? ' selected' : '') + '>Отменён</option>' +
                            '</select>' +
                            '<button type="submit" name="update_status" class="btn btn-primary" style="margin-top:1rem;width:100%">Обновить статус</button>' +
                        '</form>';
                    
                    modal.classList.add('active');
                }
            } catch (e) {
                alert('Ошибка загрузки: ' + e.message);
            }
        }
    </script>
</body>
</html>