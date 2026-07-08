<?php
// ==========================================
// API المنتجات - متجر الكيلاني
// ==========================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// ===== تحديد نوع الطلب =====
$method = $_SERVER['REQUEST_METHOD'];

// ===== معالجة الطلبات =====
switch ($method) {
    case 'GET':
        handleGet($pdo);
        break;
    case 'POST':
        handlePost($pdo);
        break;
    case 'PUT':
        handlePut($pdo);
        break;
    case 'DELETE':
        handleDelete($pdo);
        break;
    default:
        echo json_encode(['error' => 'طريقة غير مدعومة']);
        break;
}

// ===== GET - جلب المنتجات =====
function handleGet($pdo) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    try {
        $sql = "SELECT p.*, c.name as category_name, c.icon as category_icon 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id ";
        
        $params = [];
        $where = [];
        
        if ($id > 0) {
            $where[] = "p.id = ?";
            $params[] = $id;
        }
        
        if ($category > 0) {
            $where[] = "p.category_id = ?";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $where[] = "(p.name LIKE ? OR p.brand LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($where)) {
            $sql .= "WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY p.id DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        // جلب الصور لكل منتج
        foreach ($products as &$product) {
            $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order");
            $stmt->execute([$product['id']]);
            $images = $stmt->fetchAll();
            $product['images'] = array_column($images, 'image_path');
            
            // تحويل JSON إلى مصفوفة
            $product['specs'] = json_decode($product['specs'], true);
            $product['features'] = json_decode($product['features'], true);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $products,
            'total' => count($products)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// ===== POST - إضافة منتج =====
function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['error' => 'بيانات غير صالحة']);
        return;
    }
    
    $name = $input['name'] ?? '';
    $category_id = $input['category_id'] ?? null;
    $brand = $input['brand'] ?? '';
    $price = $input['price'] ?? 0;
    $sku = $input['sku'] ?? '';
    $availability = $input['availability'] ?? 'in-stock';
    $description = $input['description'] ?? '';
    $specs = $input['specs'] ?? [];
    $features = $input['features'] ?? [];
    
    if (empty($name) || empty($category_id) || $price <= 0) {
        echo json_encode(['error' => 'اسم المنتج والتصنيف والسعر مطلوبة']);
        return;
    }
    
    try {
        $specs_json = !empty($specs) ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null;
        $features_json = !empty($features) ? json_encode($features, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO products (name, category_id, brand, price, sku, availability, description, specs, features)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $category_id, $brand, $price, $sku, $availability, $description, $specs_json, $features_json]);
        
        $product_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إضافة المنتج بنجاح',
            'product_id' => $product_id
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// ===== PUT - تحديث منتج =====
function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['error' => 'بيانات غير صالحة']);
        return;
    }
    
    $id = $input['id'] ?? 0;
    
    if ($id <= 0) {
        echo json_encode(['error' => 'معرف المنتج مطلوب']);
        return;
    }
    
    $name = $input['name'] ?? '';
    $category_id = $input['category_id'] ?? null;
    $brand = $input['brand'] ?? '';
    $price = $input['price'] ?? 0;
    $sku = $input['sku'] ?? '';
    $availability = $input['availability'] ?? 'in-stock';
    $description = $input['description'] ?? '';
    $specs = $input['specs'] ?? [];
    $features = $input['features'] ?? [];
    
    try {
        $specs_json = !empty($specs) ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null;
        $features_json = !empty($features) ? json_encode($features, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt = $pdo->prepare("
            UPDATE products SET 
                name = ?, category_id = ?, brand = ?, price = ?, sku = ?, 
                availability = ?, description = ?, specs = ?, features = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $category_id, $brand, $price, $sku, $availability, $description, $specs_json, $features_json, $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث المنتج بنجاح'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// ===== DELETE - حذف منتج =====
function handleDelete($pdo) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        echo json_encode(['error' => 'معرف المنتج مطلوب']);
        return;
    }
    
    try {
        // جلب صور المنتج لحذفها
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll();
        
        // حذف الصور من السيرفر
        foreach ($images as $img) {
            @unlink('../uploads/products/' . $img['image_path']);
        }
        
        // حذف المنتج (سيتم حذف الصور تلقائياً من قاعدة البيانات)
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف المنتج بنجاح'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>