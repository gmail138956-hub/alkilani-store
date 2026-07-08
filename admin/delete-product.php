<?php
session_start();

// ===== التحقق من تسجيل الدخول =====
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
   exit;
}

require_once '../config/database.php';

// ===== الحصول على معرف المنتج =====
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    $_SESSION['error'] = 'معرف المنتج غير صحيح';
    header('Location: products.php');
    exit;
}

// ===== التحقق من وجود المنتج =====
$stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['error'] = 'المنتج غير موجود';
    header('Location: products.php');
    exit;
}

// ===== التحقق من وجود طلبات مرتبطة بالمنتج (اختياري) =====
// إذا كان لديك جدول orders_items، يمكنك التحقق
// $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
// $stmt->execute([$product_id]);
// if ($stmt->fetchColumn() > 0) {
//     $_SESSION['error'] = 'لا يمكن حذف منتج مرتبط بطلبات سابقة';
//     header('Location: products.php');
//     exit;
// }

// ===== جلب صور المنتج =====
$stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll();

// ===== حذف الصور من السيرفر =====
$deletedFiles = 0;
$failedFiles = 0;

foreach ($images as $img) {
    $filePath = '../uploads/products/' . $img['image_path'];
    if (file_exists($filePath)) {
        if (@unlink($filePath)) {
            $deletedFiles++;
        } else {
            $failedFiles++;
        }
    }
}

// ===== حذف المنتج من قاعدة البيانات =====
try {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    
    // ===== تسجيل في سجل النشاط =====
    // يمكن إضافة سجل في جدول activity_logs
    // $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    // $stmt->execute([$_SESSION['user_id'], 'delete_product', json_encode(['product_id' => $product_id, 'name' => $product['name']])]);
    
    $_SESSION['success'] = '✅ تم حذف المنتج "' . htmlspecialchars($product['name']) . '" بنجاح';
    $_SESSION['deleted_files'] = $deletedFiles;
    $_SESSION['failed_files'] = $failedFiles;
    
} catch (PDOException $e) {
    $_SESSION['error'] = '❌ حدث خطأ أثناء حذف المنتج: ' . $e->getMessage();
}

header('Location: products.php');
exit;
?>