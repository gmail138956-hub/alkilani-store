<?php
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// التحقق من صلاحيات المدير
if ($_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// جلب الإحصائيات
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
$totalProducts = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM categories WHERE parent_id IS NULL");
$totalCategories = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(views) as total FROM products");
$totalViews = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT name, views FROM products ORDER BY views DESC LIMIT 1");
$topProduct = $stmt->fetch();

$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5");
$recentProducts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>لوحة التحكم - متجر الكيلاني</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css" />
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
        .admin-sidebar .logo { font-size: 1.6rem; font-weight: 800; display: block; margin-bottom: 30px; text-align: center; color: #fff; text-decoration: none; }
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
        .admin-menu a:hover, .admin-menu a.active { background: rgba(0,240,255,0.08); color: #00f0ff; }
        .admin-main { flex: 1; margin-right: 260px; padding: 30px; }
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
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(15,23,42,0.65);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }
        .stat-card .number { font-size: 1.8rem; font-weight: 800; color: #00f0ff; }
        .stat-card .label { color: #94a3b8; font-size: 0.85rem; }
        .admin-table-container {
            background: rgba(15,23,42,0.65);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            overflow: hidden;
            margin-top: 20px;
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
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; }
            .admin-main { margin-right: 200px; padding: 16px; }
            .admin-stats { grid-template-columns: 1fr 1fr; }
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
            <a href="/index.php" class="logo">⚡ <span>الكيلاني</span></a>
            <ul class="admin-menu">
                <li><a class="active" href="dashboard.php">📊 لوحة التحكم</a></li>
                <li><a href="products.php">📦 المنتجات</a></li>
                <li><a href="categories.php">📂 التصنيفات</a></li>
            </ul>
            <button class="btn-logout" onclick="window.location.href='/logout.php'">🚪 تسجيل الخروج</button>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1>📊 لوحة التحكم</h1>
                <span style="color:#94a3b8;font-size:0.85rem;">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>

            <div class="admin-stats">
                <div class="stat-card">
                    <div class="number"><?php echo $totalProducts; ?></div>
                    <div class="label">📦 المنتجات</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $totalCategories; ?></div>
                    <div class="label">📂 التصنيفات</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $totalViews; ?></div>
                    <div class="label">👁️ المشاهدات</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $topProduct ? htmlspecialchars($topProduct['name']) : '-'; ?></div>
                    <div class="label">🏆 الأكثر مشاهدة</div>
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
                <h3 style="font-size:1.2rem;">📦 أحدث المنتجات</h3>
                <a href="add-product.php" class="btn-add">➕ إضافة منتج</a>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المنتج</th>
                            <th>السعر</th>
                            <th>المشاهدات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentProducts)): ?>
                            <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:30px;">📭 لا توجد منتجات</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentProducts as $i => $p): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($p['name']); ?></td>
                                <td><?php echo number_format($p['price'], 2); ?> ج.م</td>
                                <td><?php echo $p['views']; ?></td>
                                <td>
                                    <button class="btn-edit" onclick="window.location.href='edit-product.php?id=<?php echo $p['id']; ?>'">✏️</button>
                                    <button class="btn-delete" onclick="if(confirm('هل أنت متأكد؟')) window.location.href='delete-product.php?id=<?php echo $p['id']; ?>'">🗑️</button>
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