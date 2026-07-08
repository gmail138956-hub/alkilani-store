<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

// ===== الحصول على معرف التصنيف =====
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id <= 0) {
    header('Location: categories.php');
    exit;
}

// ===== جلب بيانات التصنيف =====
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: categories.php');
    exit;
}

// ===== جلب جميع التصنيفات للقائمة المنسدلة =====
$categories = getCategories($pdo);

// ===== معالجة تحديث التصنيف =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    
    // التحقق من الحقول المطلوبة
    if (empty($name) || empty($icon)) {
        $error = '⚠️ اسم التصنيف والرمز مطلوبان';
    } else {
        // التحقق من عدم وجود تصنيف بنفس الاسم (باستثناء التصنيف الحالي)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $category_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = '⚠️ هذا التصنيف موجود بالفعل';
        } else {
            // تحديث التصنيف
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, icon = ?, description = ?, parent_id = ? WHERE id = ?");
            $stmt->execute([$name, $icon, $description, $parent_id, $category_id]);
            header('Location: categories.php?updated=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>تعديل تصنيف - لوحة التحكم</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/style.css" />
    
    <style>
        /* ===== تنسيق لوحة التحكم ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #0a0f1d; color: #f8fafc; min-height: 100vh; }
        
        .admin-container { display: flex; min-height: 100vh; }
        
        /* ===== الشريط الجانبي ===== */
        .admin-sidebar {
            width: 260px;
            background: rgba(15, 23, 42, 0.95);
            border-left: 1px solid rgba(0, 240, 255, 0.1);
            padding: 24px 16px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        .admin-sidebar .logo { 
            font-size: 1.6rem; 
            font-weight: 800; 
            display: block; 
            margin-bottom: 30px; 
            text-align: center;
            color: #fff;
            text-decoration: none;
        }
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
        .admin-menu a:hover,
        .admin-menu a.active {
            background: rgba(0, 240, 255, 0.08);
            color: #00f0ff;
        }
        
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
        .btn-logout:hover { background: rgba(239, 68, 68, 0.1); }
        
        /* ===== المحتوى الرئيسي ===== */
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .admin-header h1 { font-size: 1.6rem; font-weight: 800; }
        
        .btn-back {
            padding: 8px 20px;
            border: 1px solid #00f0ff;
            border-radius: 8px;
            color: #00f0ff;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
            background: transparent;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
        }
        .btn-back:hover { background: rgba(0, 240, 255, 0.1); }
        
        .btn-save {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #00f0ff, #0099cc);
            color: #0a0f1d;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0, 240, 255, 0.3); }
        
        /* ===== النماذج ===== */
        .form-card {
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 24px;
            max-width: 700px;
        }
        
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: #f8fafc;
            font-family: 'Cairo', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #00f0ff;
            background: rgba(255, 255, 255, 0.08);
        }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        
        /* ===== رسائل ===== */
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .category-current {
            display: inline-block;
            padding: 4px 16px;
            background: rgba(0, 240, 255, 0.1);
            border: 1px solid rgba(0, 240, 255, 0.2);
            border-radius: 20px;
            color: #00f0ff;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .hint {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 4px;
        }
        
        /* ===== استجابة ===== */
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; }
            .admin-main { margin-right: 200px; padding: 16px; }
            .form-row { grid-template-columns: 1fr; }
            .form-card { max-width: 100%; }
        }
        @media (max-width: 480px) {
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-left: none;
                border-bottom: 1px solid rgba(0, 240, 255, 0.1);
            }
            .admin-main { margin-right: 0; }
            .admin-container { flex-direction: column; }
            .admin-header { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>

    <div class="admin-container">
        
        <!-- ===== الشريط الجانبي ===== -->
        <aside class="admin-sidebar">
            <a href="../index.php" class="logo">⚡ <span>الكيلاني</span></a>
            <ul class="admin-menu">
                <li><a href="dashboard.php">📊 لوحة التحكم</a></li>
                <li><a href="products.php">📦 المنتجات</a></li>
                <li><a href="categories.php" class="active">📂 التصنيفات</a></li>
            </ul>
            <button class="btn-logout" onclick="window.location.href='../logout.php'">🚪 تسجيل الخروج</button>
        </aside>

        <!-- ===== المحتوى الرئيسي ===== -->
        <main class="admin-main">
            
            <!-- ===== رأس الصفحة ===== -->
            <div class="admin-header">
                <h1>✏️ تعديل التصنيف</h1>
                <a href="categories.php" class="btn-back">⬅ العودة</a>
            </div>

            <!-- ===== رسائل الخطأ ===== -->
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- ===== نموذج تعديل التصنيف ===== -->
            <div class="form-card">
                
                <div style="margin-bottom:20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <span style="color:#94a3b8;">التصنيف الحالي:</span>
                    <span class="category-current"><?php echo $category['icon'] . ' ' . htmlspecialchars($category['name']); ?></span>
                    
                    <?php 
                        $parent = $category['parent_id'] 
                            ? array_filter($categories, function($c) use ($category) { 
                                return $c['id'] == $category['parent_id']; 
                            }) 
                            : [];
                        $parent = reset($parent);
                    ?>
                    <?php if ($parent): ?>
                        <span style="color:#94a3b8;">← فرعي من:</span>
                        <span style="color:#f8fafc;"><?php echo $parent['icon'] . ' ' . htmlspecialchars($parent['name']); ?></span>
                    <?php endif; ?>
                </div>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">اسم التصنيف *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($category['name']); ?>"
                                   placeholder="مثال: أدوات يدوية">
                        </div>
                        <div class="form-group">
                            <label for="icon">الرمز *</label>
                            <input type="text" id="icon" name="icon" maxlength="2" required 
                                   value="<?php echo htmlspecialchars($category['icon']); ?>"
                                   placeholder="مثال: 🔧">
                            <div class="hint">رمز تعبيري (emoji) أو حرفين كحد أقصى</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">الوصف</label>
                        <input type="text" id="description" name="description" 
                               value="<?php echo htmlspecialchars($category['description']); ?>"
                               placeholder="وصف التصنيف">
                    </div>
                    
                    <div class="form-group">
                        <label for="parent_id">التصنيف الأب (اختياري)</label>
                        <select id="parent_id" name="parent_id">
                            <option value="">-- تصنيف رئيسي --</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php if ($cat['id'] != $category_id): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $cat['id'] == $category['parent_id'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="hint">اختر تصنيفاً رئيسياً لجعل هذا التصنيف فرعياً منه</div>
                    </div>
                    
                    <div style="display:flex;gap:12px;margin-top:8px;">
                        <button type="submit" class="btn-save">💾 حفظ التغييرات</button>
                        <a href="categories.php" class="btn-back" style="padding:12px 24px;">إلغاء</a>
                    </div>
                </form>
            </div>

        </main>
    </div>

</body>
</html>