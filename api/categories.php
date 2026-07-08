<?php
// ==========================================
// API التصنيفات - متجر الكيلاني
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

// ===== GET - جلب التصنيفات =====
function handleGet($pdo) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $parent = isset($_GET['parent']) ? (int)$_GET['parent'] : null;
    
    try {
        $sql = "SELECT * FROM categories ";
        $params = [];
        $where = [];
        
        if ($id > 0) {
            $where[] = "id = ?";
            $params[] = $id;
        }
        
        if ($parent !== null) {
            if ($parent === 0) {
                $where[] = "parent_id IS NULL";
            } else {
                $where[] = "parent_id = ?";
                $params[] = $parent;
            }
        }
        
        if (!empty($where)) {
            $sql .= "WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY parent_id, id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $categories = $stmt->fetchAll();
        
        // حساب عدد المنتجات في كل تصنيف
        foreach ($categories as &$cat) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$cat['id']]);
            $cat['product_count'] = (int)$stmt->fetchColumn();
        }
        
        // إعادة ترتيب التصنيفات في هيكل شجري
        $tree = buildCategoryTree($categories);
        
        echo json_encode([
            'success' => true,
            'data' => $categories,
            'tree' => $tree,
            'total' => count($categories)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// ===== بناء هيكل شجري للتصنيفات =====
function buildCategoryTree($categories, $parent_id = null) {
    $tree = [];
    foreach ($categories as $cat) {
        if ($cat['parent_id'] == $parent_id) {
            $children = buildCategoryTree($categories, $cat['id']);
            if (!empty($children)) {
                $cat['children'] = $children;
            }
            $tree[] = $cat;
        }
    }
    return $tree;
}

// ===== POST - إضافة تصنيف =====
function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['error' => 'بيانات غير صالحة']);
        return;
    }
    
    $name = $input['name'] ?? '';
    $icon = $input['icon'] ?? '';
    $description = $input['description'] ?? '';
    $parent_id = $input['parent_id'] ?? null;
    
    if (empty($name) || empty($icon)) {
        echo json_encode(['error' => 'اسم التصنيف والرمز مطلوبان']);
        return;
    }
    
    try {
        // التحقق من عدم وجود تصنيف بنفس الاسم
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['error' => 'هذا التصنيف موجود بالفعل']);
            return;
        }
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, icon, description, parent_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $icon, $description, $parent_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إضافة التصنيف بنجاح',
            'category_id' => $pdo->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// ===== PUT - تحديث تصنيف =====
function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['error' => 'بيانات غير صالحة']);
        return;
    }
    
    $id = $input['id'] ?? 0;
    
    if ($id <= 0) {
        echo json_encode(['error' => 'معرف التصنيف مطلوب']);
        return;
    }
    
    $name = $input['name'] ?? '';
    $icon = $input['icon'] ?? '';
    $description = $input['description'] ?? '';
    $parent_id = $input['parent_id'] ?? null;
    
    try {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, icon = ?, description = ?, parent_id = ? WHERE id = ?");
        $stmt->execute([$name, $icon, $description, $parent_id, $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث التصنيف بنجاح'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// ===== DELETE - حذف تصنيف =====
function handleDelete($pdo) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        echo json_encode(['error' => 'معرف التصنيف مطلوب']);
        return;
    }
    
    try {
        // التحقق من وجود منتجات في هذا التصنيف
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['error' => 'لا يمكن حذف تصنيف يحتوي على منتجات']);
            return;
        }
        
        // التحقق من وجود تصنيفات فرعية
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['error' => 'لا يمكن حذف تصنيف يحتوي على تصنيفات فرعية']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف التصنيف بنجاح'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>