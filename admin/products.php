<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

// جلب جميع المنتجات مع التصنيف
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name, c.icon as category_icon 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>المنتجات - لوحة التحكم</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 260px;
            background: rgba(15,23,42,0.95);
            border-left: 1px solid rgba(0,240,255,0.1);
            padding: 24px 16px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        .admin-sidebar .logo { font-size: 1.6rem; font-weight: 800; display: block; margin-bottom: 30px; text-align: center; }
        .admin-sidebar .logo span { color: #00f0ff; }
        .admin-menu { list-style: none; }
        .admin-menu li { margin-bottom: 4px; }
        .admin-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .admin-menu a:hover, .admin-menu a.active {
            background: rgba(0,240,255,0.08);
            color: #00f0ff;
        }
        .admin-main {
            flex: 1;
            margin-right: 260px;
            padding: 30px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .admin-header h1 { font-size: 1.6rem; font-weight: 800; }
        .btn-logout {
            padding: 10px 20px;
            border: 1px solid #ef4444;
            border-radius: 8px;
            background: transparent;
            color: #ef4444;
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            margin-top: 20px;
        }
        .btn-logout:hover { background: rgba(239,68,68,0.1); }
        .btn-add {
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #00f0ff, #0099cc);
            color: #0a0f1d;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,240,255,0.3); }
        .btn-edit {
            padding: 4px 12px;
            border: none;
            border-radius: 6px;
            background: #3b82f6;
            color: #fff;
            cursor: pointer;
            font-size: 0.7rem;
        }
        .btn-delete {
            padding: 4px 12px;
            border: none;
            border-radius: 6px;
            background: #ef4444;
            color: #fff;
            cursor: pointer;
            font-size: 0.7rem;
        }
        .admin-table-container {
            background: rgba(15,23,42,0.65);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            overflow: hidden;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table th {
            padding: 12px 16px;
            text-align: right;
            background: rgba(0,240,255,0.05);
            color: #00f0ff;
            font-weight: 700;
            font-size: 0.8rem;
            border-bottom: 1px solid rgba(0,240,255,0.1);
        }
        .admin-table td {
            padding: 10px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 0.85rem;
        }
        .admin-table tr:hover td { background: rgba(0,240,255,0.02); }
        .btn-edit:hover, .btn-delete:hover { transform: scale(1.05); }
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; }
            .admin-main { margin-right: 200px; padding: 16px; }
        }
        @media (max-width: 480px) {
            .admin-sidebar { width: 100%; height: auto; position: relative; border-left: none; border-bottom: 1px solid rgba(0,240,255,0.1); }
            .admin-main { margin-right: 0; }
            .admin-container { flex-direction: column; }
            .admin-header { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>

    <div class="admin-container">
        <aside class="admin-sidebar">
            <a href="../index.php" class="logo">⚡ <span>الكيلاني</span></a>
            <ul class="admin-menu">
                <li><a href="dashboard.php">📊 لوحة التحكم</a></li>
                <li><a href="products.php" class="active">📦 المنتجات</a></li>
                <li><a href="categories.php">📂 التصنيفات</a></li>
            </ul>
            <button class="btn-logout" onclick="window.location.href='../logout.php'">🚪 تسجيل الخروج</button>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1>📦 إدارة المنتجات</h1>
                <a href="add-product.php" class="btn-add">➕ إضافة منتج</a>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المنتج</th>
                            <th>التصنيف</th>
                            <th>السعر</th>
                            <th>المشاهدات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:30px;">📭 لا توجد منتجات</td></tr>
                        <?php else: ?>
                            <?php foreach ($products as $i => $p): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($p['name']); ?></td>
                                <td><?php echo $p['category_icon'] . ' ' . htmlspecialchars($p['category_name'] ?? 'غير محدد'); ?></td>
                                <td><?php echo number_format($p['price'], 2); ?> ج.م</td>
                                <td><?php echo $p['views']; ?></td>
                                <td>
                                    <button class="btn-edit" onclick="window.location.href='edit-product.php?id=<?php echo $p['id']; ?>'">✏️</button>
                                    <button class="btn-delete" onclick="if(confirm('هل أنت متأكد من حذف هذا المنتج؟')) window.location.href='delete-product.php?id=<?php echo $p['id']; ?>'">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

</body>
</html>