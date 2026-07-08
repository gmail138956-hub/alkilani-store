<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

// جلب التصنيفات للنموذج
$categories = getCategories($pdo);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $brand = trim($_POST['brand'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $sku = trim($_POST['sku'] ?? '');
    $availability = $_POST['availability'] ?? 'in-stock';
    $description = trim($_POST['description'] ?? '');
    $specs = $_POST['specs'] ?? '';
    $features = $_POST['features'] ?? '';
    
    // التحقق
    if (empty($name)) $errors[] = 'اسم المنتج مطلوب';
    if (empty($category_id)) $errors[] = 'التصنيف مطلوب';
    if ($price <= 0) $errors[] = 'السعر مطلوب';
    
    if (empty($errors)) {
        // معالجة المواصفات والمميزات
        $specs_json = null;
        if (!empty($specs)) {
            $specs_array = [];
            $specs_lines = explode("\n", $specs);
            foreach ($specs_lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $specs_array[trim($key)] = trim($value);
                }
            }
            $specs_json = json_encode($specs_array, JSON_UNESCAPED_UNICODE);
        }
        
        $features_array = [];
        if (!empty($features)) {
            $features_array = array_filter(array_map('trim', explode("\n", $features)));
        }
        $features_json = !empty($features_array) ? json_encode($features_array, JSON_UNESCAPED_UNICODE) : null;
        
        // إدخال المنتج
        $stmt = $pdo->prepare("
            INSERT INTO products (name, category_id, brand, price, sku, availability, description, specs, features)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $category_id, $brand, $price, $sku, $availability, $description, $specs_json, $features_json]);
        
        $product_id = $pdo->lastInsertId();
        
        // معالجة الصور
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                    $image_name = time() . '_' . $key . '.' . $ext;
                    move_uploaded_file($tmp_name, $upload_dir . $image_name);
                    
                    $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)");
                    $stmt->execute([$product_id, $image_name, $key]);
                }
            }
        }
        
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>إضافة منتج - لوحة التحكم</title>
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
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,240,255,0.3); }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
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
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group .hint { font-size: 0.8rem; color: #94a3b8; margin-top: 4px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .error-message {
            background: rgba(239,68,68,0.1);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success-message {
            background: rgba(16,185,129,0.1);
            border: 1px solid #10b981;
            color: #86efac;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .image-preview { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .image-preview img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); }
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; }
            .admin-main { margin-right: 200px; padding: 16px; }
            .form-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .admin-sidebar { width: 100%; height: auto; position: relative; border-left: none; border-bottom: 1px solid rgba(0,240,255,0.1); }
            .admin-main { margin-right: 0; }
            .admin-container { flex-direction: column; }
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
                <h1>➕ إضافة منتج جديد</h1>
                <a href="products.php" class="btn-back">⬅ العودة</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message"><?php echo implode('<br>', $errors); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">✅ تم إضافة المنتج بنجاح! <a href="products.php" style="color:#00f0ff;">العودة إلى القائمة</a></div>
            <?php endif; ?>

            <div style="background:rgba(15,23,42,0.65);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:24px;">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">اسم المنتج *</label>
                            <input type="text" id="name" name="name" required placeholder="مثال: قاطع تيار 50 أمبير" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="category_id">التصنيف *</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">-- اختر التصنيف --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo isset($_POST['category_id']) && $_POST['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="brand">الماركة</label>
                            <input type="text" id="brand" name="brand" placeholder="مثال: شنايدر" value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="price">السعر (ج.م) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required placeholder="0" value="<?php echo $_POST['price'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="sku">رقم الصنف (SKU)</label>
                            <input type="text" id="sku" name="sku" placeholder="مثال: SKU-001" value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="availability">حالة التوفر</label>
                            <select id="availability" name="availability">
                                <option value="in-stock" <?php echo isset($_POST['availability']) && $_POST['availability'] == 'in-stock' ? 'selected' : ''; ?>>متوفر</option>
                                <option value="on-demand" <?php echo isset($_POST['availability']) && $_POST['availability'] == 'on-demand' ? 'selected' : ''; ?>>حسب الطلب</option>
                                <option value="out-of-stock" <?php echo isset($_POST['availability']) && $_POST['availability'] == 'out-of-stock' ? 'selected' : ''; ?>>غير متوفر</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">الوصف</label>
                        <textarea id="description" name="description" rows="3" placeholder="وصف المنتج"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="specs">المواصفات التقنية (كل مواصفة في سطر)</label>
                        <textarea id="specs" name="specs" rows="4" placeholder="الجهد: 220 فولت&#10;التيار: 10 أمبير"><?php echo htmlspecialchars($_POST['specs'] ?? ''); ?></textarea>
                        <div class="hint">اكتب كل مواصفة في سطر منفصل: المفتاح: القيمة</div>
                    </div>

                    <div class="form-group">
                        <label for="features">المميزات (كل مميزة في سطر)</label>
                        <textarea id="features" name="features" rows="3" placeholder="حماية قوية&#10;توفير طاقة"><?php echo htmlspecialchars($_POST['features'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="images">صور المنتج</label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" onchange="previewImages(this)">
                        <div class="hint">يمكنك اختيار عدة صور</div>
                        <div class="image-preview" id="imagePreview"></div>
                    </div>

                    <button type="submit" class="btn-save">💾 حفظ المنتج</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        function previewImages(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            for (const file of input.files) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        }
    </script>

</body>
</html>