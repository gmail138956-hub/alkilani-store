<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$categories = getCategories($pdo);

// معالجة إضافة تصنيف جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $parent_id = $_POST['parent_id'] ?? null;
    
    if (!empty($name) && !empty($icon)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, icon, description, parent_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $icon, $description, $parent_id]);
        header('Location: categories.php?added=1');
        exit;
    }
}

// معالجة حذف تصنيف
if (isset($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    // التحقق من عدم وجود منتجات في هذا التصنيف
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$category_id]);
    if ($stmt->fetchColumn() > 0) {
        header('Location: categories.php?error=has_products');
        exit;
    }
    
    // التحقق من عدم وجود تصنيفات فرعية
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
    $stmt->execute([$category_id]);
    if ($stmt->fetchColumn() > 0) {
        header('Location: categories.php?error=has_subcategories');
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    header('Location: categories.php?deleted=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>التصنيفات - لوحة التحكم</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #0a0f1d; color: #f8fafc; min-height: 100vh; }
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
        .btn-back {
            padding: 8px 20px;
            border: 1px solid #00f0ff;
            border-radius: 8px;
            color: #00f0ff;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn-back:hover { background: rgba(0,240,255,0.1); }
        .btn-add {
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #00f0ff, #0099cc);
            color: #0a0f1d;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            cursor: pointer;
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
        .form-group { margin-bottom: 12px; }
        .form-group label {
            display: block;
            margin-bottom: 4px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: #f8fafc;
            font-family: 'Cairo', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #00f0ff;
            background: rgba(255,255,255,0.08);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .success-message {
            background: rgba(16,185,129,0.1);
            border: 1px solid #10b981;
            color: #86efac;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error-message {
            background: rgba(239,68,68,0.1);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; }
            .admin-main { margin-right: 200px; padding: 16px; }
            .form-row { grid-template-columns: 1fr; }
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
                <li><a href="products.php">📦 المنتجات</a></li>
                <li><a href="categories.php" class="active">📂 التصنيفات</a></li>
            </ul>
            <button class="btn-logout" onclick="window.location.href='../logout.php'">🚪 تسجيل الخروج</button>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1>📂 إدارة التصنيفات</h1>
                <a href="../index.php" class="btn-back">⬅ العودة للمتجر</a>
            </div>

            <?php if (isset($_GET['added'])): ?>
                <div class="success-message">✅ تم إضافة التصنيف بنجاح!</div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="success-message">✅ تم حذف التصنيف بنجاح!</div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'has_products'): ?>
                <div class="error-message">❌ لا يمكن حذف تصنيف يحتوي على منتجات</div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'has_subcategories'): ?>
                <div class="error-message">❌ لا يمكن حذف تصنيف يحتوي على تصنيفات فرعية</div>
            <?php endif; ?>

            <div style="background:rgba(15,23,42,0.65);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:20px;margin-bottom:20px;">
                <h3 style="margin-bottom:12px;">➕ إضافة تصنيف جديد</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">اسم التصنيف *</label>
                            <input type="text" id="name" name="name" placeholder="مثال: أدوات يدوية" required>
                        </div>
                        <div class="form-group">
                            <label for="icon">الرمز *</label>
                            <input type="text" id="icon" name="icon" placeholder="مثال: 🔧" maxlength="2" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="description">الوصف</label>
                            <input type="text" id="description" name="description" placeholder="وصف التصنيف">
                        </div>
                        <div class="form-group">
                            <label for="parent_id">التصنيف الأب (اختياري)</label>
                            <select id="parent_id" name="parent_id">
                                <option value="">-- تصنيف رئيسي --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <?php if ($cat['parent_id'] === null): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_category" class="btn-add" style="margin-top:8px;">💾 إضافة التصنيف</button>
                </form>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>الرمز</th>
                            <th>اسم التصنيف</th>
                            <th>الوصف</th>
                            <th>النوع</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:30px;">📭 لا توجد تصنيفات</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <?php $parent = $cat['parent_id'] ? array_filter($categories, function($c) use ($cat) { return $c['id'] == $cat['parent_id']; }) : []; ?>
                                <?php $parent = reset($parent); ?>
                            <tr>
                                <td><?php echo $cat['icon']; ?></td>
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td><?php echo htmlspecialchars($cat['description']); ?></td>
                                <td><?php echo $parent ? '🔹 فرعي من: ' . htmlspecialchars($parent['name']) : '🔹 رئيسي'; ?></td>
                                <td>
                                    <button class="btn-edit" onclick="window.location.href='edit-category.php?id=<?php echo $cat['id']; ?>'">✏️</button>
                                    <button class="btn-delete" onclick="if(confirm('هل أنت متأكد من حذف هذا التصنيف؟')) window.location.href='categories.php?delete=<?php echo $cat['id']; ?>'">🗑️</button>
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