<?php

session_start();
require_once '../config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();
$uploadDir = '../uploads/products/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function uploadImage($file, $uploadDir) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Ошибка загрузки файла (код: ' . $file['error'] . ')'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Разрешены только JPG, PNG, GIF, WEBP'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Файл слишком большой (макс. 5MB)'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = 'product_' . uniqid() . '.' . $extension;
    $uploadPath = $uploadDir . $newName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'path' => 'uploads/products/' . $newName];
    }
    
    return ['success' => false, 'error' => 'Не удалось сохранить файл'];
}

$categories = [
    'iphone' => 'iPhone',
    'ipad' => 'iPad',
    'mac' => 'Mac',
    'watch' => 'Watch',
    'airpods' => 'AirPods',
    'samsung' => 'Samsung',
    'accessories' => 'Аксессуары',
    'other' => 'Другое'
];

if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = (float)$_POST['price'];
    $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $rating = (float)($_POST['rating'] ?: 5.0);
    $reviews = (int)($_POST['reviews'] ?: 0);
    $badge = $_POST['badge'] ?: null;
    $badge_text = trim($_POST['badge_text']);
    $category = $_POST['category'] ?: 'other';
    $description = trim($_POST['description']);
    
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['image'], $uploadDir);
        if ($uploadResult['success']) {
            $imagePath = $uploadResult['path'];
        } else {
            $error = $uploadResult['error'];
        }
    }
    
    if ($imagePath && !isset($error)) {

        $slug = strtolower(preg_replace('/[^a-zа-яё0-9-]/u', '-', $name)) . '-' . uniqid();
        
        $stmt = $conn->prepare("INSERT INTO products (name, slug, price, old_price, image, rating, reviews, badge, badge_text, category, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdssdisss", $name, $slug, $price, $old_price, $imagePath, $rating, $reviews, $badge, $badge_text, $category, $description);
        
        if ($stmt->execute()) {
            $success = '✅ Товар успешно добавлен! ID: ' . $conn->insert_id;
        } else {
            $error = '❌ Ошибка при добавлении: ' . $stmt->error;
        }
        $stmt->close();
    } elseif (!isset($error)) {
        $error = '❌ Не загружено изображение';
    }
}

if (isset($_POST['edit_product'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $price = (float)$_POST['price'];
    $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $rating = (float)($_POST['rating'] ?: 5.0);
    $reviews = (int)($_POST['reviews'] ?: 0);
    $badge = $_POST['badge'] ?: null;
    $badge_text = trim($_POST['badge_text']);
    $category = $_POST['category'] ?: 'other';
    $description = trim($_POST['description']);
    
    $currentImage = $conn->query("SELECT image FROM products WHERE id = $id")->fetch_assoc()['image'];
    $imagePath = $currentImage;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
        $uploadResult = uploadImage($_FILES['image'], $uploadDir);
        if ($uploadResult['success']) {
            if ($currentImage && file_exists('../' . $currentImage)) {
                unlink('../' . $currentImage);
            }
            $imagePath = $uploadResult['path'];
        }
    }
    

    $slug = strtolower(preg_replace('/[^a-zа-яё0-9-]/u', '-', $name)) . '-' . uniqid();
    
    $stmt = $conn->prepare("UPDATE products SET name=?, slug=?, price=?, old_price=?, image=?, rating=?, reviews=?, badge=?, badge_text=?, category=?, description=? WHERE id=?");
    $stmt->bind_param("sssdssdisssi", $name, $slug, $price, $old_price, $imagePath, $rating, $reviews, $badge, $badge_text, $category, $description, $id);
    
    if ($stmt->execute()) {
        $success = '✅ Товар успешно обновлён!';
    } else {
        $error = '❌ Ошибка при обновлении: ' . $stmt->error;
    }
    $stmt->close();
}


if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $product = $conn->query("SELECT image FROM products WHERE id = $id")->fetch_assoc();
    
    if ($product && file_exists('../' . $product['image'])) {
        unlink('../' . $product['image']);
    }
    
    $conn->query("DELETE FROM products WHERE id = $id");
    header('Location: products.php');
    exit;
}

$products = $conn->query("SELECT * FROM products ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Товары - Админка</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f7; padding: 2rem; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: white; padding: 1.5rem 2rem; border-radius: 1rem; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .header-left img { width: 40px; height: 40px; }
        .header h1 { font-size: 1.5rem; color: #1d1d1f; }
        .back-btn { display: inline-flex; align-items: center; gap: 0.5rem; background: #667eea; color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: all 0.3s; }
        .back-btn:hover { background: #5568d3; transform: translateX(-2px); }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 0.5rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-danger { background: #f5576c; color: white; }
        .btn-warning { background: #ffa500; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        table { width: 100%; background: white; border-radius: 1rem; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #f9f9f9; font-weight: 600; }
        img { width: 60px; height: 60px; object-fit: contain; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 1rem; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        input, select, textarea { padding: 0.75rem; border: 2px solid #e1e1e6; border-radius: 0.5rem; width: 100%; box-sizing: border-box; }
        textarea { resize: vertical; min-height: 100px; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .preview-image { max-width: 200px; margin-top: 1rem; border-radius: 0.5rem; }
        .action-buttons { display: flex; gap: 0.5rem; }
        .category-badge { background: #f5f5f7; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8125rem; color: #667eea; font-weight: 600; }
        @media (max-width: 48rem) { body { padding: 1rem; } table { font-size: 0.875rem; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <img src="goods2.png" alt="Products">
                <h1>Управление товарами</h1>
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> На главную
            </a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <button class="btn btn-primary" onclick="openAddModal()" style="margin-bottom:1.5rem;">
            <i class="fas fa-plus"></i> Добавить товар
        </button>
        
        <table>
            <thead>
                <tr>
                    <th>Фото</th>
                    <th>Название</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Старая цена</th>
                    <th>Рейтинг</th>
                    <th>Бейдж</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><img src="../<?= htmlspecialchars($p['image']) ?>" alt=""></td>
                    <td>
                        <?= htmlspecialchars($p['name']) ?><br>
                        <small style="color:#86868b">ID: <?= $p['id'] ?></small>
                    </td>
                    <td><span class="category-badge"><?= htmlspecialchars($p['category'] ?? 'other') ?></span></td>
                    <td><?= number_format($p['price'], 0, '.', ' ') ?> ₽</td>
                    <td><?= $p['old_price'] ? number_format($p['old_price'], 0, '.', ' ') . ' ₽' : '—' ?></td>
                    <td>⭐ <?= $p['rating'] ?> (<?= $p['reviews'] ?>)</td>
                    <td><?= $p['badge_text'] ?: '—' ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="../product.html?id=<?= $p['id'] ?>" target="_blank" class="btn btn-info" title="Открыть страницу">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <button class="btn btn-warning" onclick='openEditModal(<?= json_encode($p, JSON_UNESCAPED_UNICODE) ?>)' title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить товар?')" title="Удалить">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($products)): ?>
            <div style="text-align:center;padding:4rem;color:#86868b;margin-top:2rem;">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486749.png" alt="Empty" style="width:80px;height:80px;opacity:0.3;margin-bottom:1rem;">
                <p>Товаров пока нет</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="modal" id="addModal">
        <div class="modal-content">
            <h2 style="margin-bottom:1.5rem;"><i class="fas fa-plus-circle"></i> Добавить товар</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div style="grid-column: 1 / -1;">
                        <label>Название товара *</label>
                        <input type="text" name="name" required id="addName">
                    </div>
                    
                    <div>
                        <label>Категория *</label>
                        <select name="category" required>
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label>Цена (₽) *</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    
                    <div>
                        <label>Старая цена (₽)</label>
                        <input type="number" name="old_price" step="0.01">
                    </div>
                    
                    <div>
                        <label>Рейтинг (0-5)</label>
                        <input type="number" name="rating" step="0.1" min="0" max="5" value="5">
                    </div>
                    
                    <div>
                        <label>Кол-во отзывов</label>
                        <input type="number" name="reviews" value="0">
                    </div>
                    
                    <div>
                        <label>Бейдж</label>
                        <select name="badge">
                            <option value="">Без бейджа</option>
                            <option value="Хит">Хит</option>
                            <option value="sale">Скидка</option>
                            <option value="new">Новинка</option>
                        </select>
                    </div>
                    
                    <div style="grid-column: 1 / -1;">
                        <label>Текст бейджа</label>
                        <input type="text" name="badge_text" placeholder="Например: -15%">
                    </div>
                    
                    <div style="grid-column: 1 / -1;">
                        <label>Описание</label>
                        <textarea name="description" rows="4" placeholder="Описание товара..."></textarea>
                    </div>
                    
                    <div style="grid-column: 1 / -1;">
                        <label>Изображение товара *</label>
                        <input type="file" name="image" accept="image/*" required onchange="previewImage(this, 'addPreview')">
                        <img id="addPreview" class="preview-image" style="display:none;">
                    </div>
                </div>
                <div style="display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('addModal').classList.remove('active')" class="btn" style="background:#86868b;color:white;">
                        <i class="fas fa-times"></i> Отмена
                    </button>
                    <button type="submit" name="add_product" class="btn btn-primary">
                        <i class="fas fa-save"></i> Добавить
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2 style="margin-bottom:1.5rem;"><i class="fas fa-edit"></i> Редактировать товар</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="current_image" id="editCurrentImage">
                <div class="form-row">
                    <div style="grid-column: 1 / -1;">
                        <label>Название товара *</label>
                        <input type="text" name="name" required id="editName">
                    </div>
                    
                    <div>
                        <label>Категория *</label>
                        <select name="category" id="editCategory" required>
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label>Цена (₽) *</label>
                        <input type="number" name="price" step="0.01" required id="editPrice">
                    </div>
                    
                    <div>
                        <label>Старая цена (₽)</label>
                        <input type="number" name="old_price" step="0.01" id="editOldPrice">
                    </div>
                    
                    <div>
                        <label>Рейтинг (0-5)</label>
                        <input type="number" name="rating" step="0.1" min="0" max="5" id="editRating">
                    </div>
                    
                    <div>
                        <label>Кол-во отзывов</label>
                        <input type="number" name="reviews" id="editReviews">
                    </div>
                    
                    <div>
                        <label>Бейдж</label>
                        <select name="badge" id="editBadge">
                            <option value="">Без бейджа</option>
                            <option value="Хит">Хит</option>
                            <option value="sale">Скидка</option>
                            <option value="new">Новинка</option>
                        </select>
                    </div>
                    
                    <div style="grid-column: 1 / -1;">
                        <label>Текст бейджа</label>
                        <input type="text" name="badge_text" id="editBadgeText">
                    </div>
                    
                    <div style="grid-column: 1 / -1;">
                        <label>Описание</label>
                        <textarea name="description" rows="4" id="editDescription"></textarea>
                    </div>
                    
                    <div style="grid-column: 1 / -1;">
                        <label>Текущее изображение</label>
                        <img id="editImagePreview" class="preview-image">
                        <label style="margin-top:1rem;">Загрузить новое (необязательно):</label>
                        <input type="file" name="image" accept="image/*" onchange="previewImage(this, 'editPreview')">
                        <img id="editPreview" class="preview-image" style="display:none;">
                    </div>
                </div>
                <div style="display:flex;gap:1rem;justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('editModal').classList.remove('active')" class="btn" style="background:#86868b;color:white;">
                        <i class="fas fa-times"></i> Отмена
                    </button>
                    <button type="submit" name="edit_product" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function openEditModal(product) {
            document.getElementById('editId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editCategory').value = product.category || 'other';
            document.getElementById('editPrice').value = product.price;
            document.getElementById('editOldPrice').value = product.old_price || '';
            document.getElementById('editRating').value = product.rating || 5;
            document.getElementById('editReviews').value = product.reviews || 0;
            document.getElementById('editBadge').value = product.badge || '';
            document.getElementById('editBadgeText').value = product.badge_text || '';
            document.getElementById('editDescription').value = product.description || '';
            document.getElementById('editCurrentImage').value = product.image;
            document.getElementById('editImagePreview').src = '../' + product.image;
            document.getElementById('editImagePreview').style.display = 'block';
            document.getElementById('editPreview').style.display = 'none';
            
            document.getElementById('editModal').classList.add('active');
        }
        
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        document.getElementById('addModal')?.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
        
        document.getElementById('editModal')?.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('addModal').classList.remove('active');
                document.getElementById('editModal').classList.remove('active');
            }
        });
    </script>
</body>
</html>