<?php
// ==========================================
// اتصال قاعدة البيانات - متجر الكيلاني
// ==========================================

header('Content-Type: text/html; charset=utf-8');

// ===== قراءة متغيرات البيئة =====
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'alkilani_store';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
} catch(PDOException $e) {
    die("❌ خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// ===== دوال مساعدة =====
function getCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY parent_id, id");
    return $stmt->fetchAll();
}

function getProducts($pdo, $category_id = null) {
    $sql = "SELECT p.*, c.name as category_name, c.icon as category_icon 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id ";
    if ($category_id) {
        $sql .= "WHERE p.category_id = :category_id ";
    }
    $sql .= "ORDER BY p.id DESC";
    $stmt = $pdo->prepare($sql);
    if ($category_id) {
        $stmt->execute(['category_id' => $category_id]);
    } else {
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

function getProduct($pdo, $id) {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.icon as category_icon 
                           FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getProductImages($pdo, $product_id) {
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll();
}

function incrementProductViews($pdo, $product_id) {
    $stmt = $pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?");
    $stmt->execute([$product_id]);
}

function getRelatedProducts($pdo, $product_id, $category_id, $limit = 4) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT " . (int)$limit);
    $stmt->execute([$category_id, $product_id]);
    return $stmt->fetchAll();
}
?>