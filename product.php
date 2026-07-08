<?php
require_once 'config/database.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

// جلب المنتج
$product = getProduct($pdo, $product_id);
if (!$product) {
    header('Location: index.php');
    exit;
}

// زيادة المشاهدات
incrementProductViews($pdo, $product_id);

// جلب الصور
$images = getProductImages($pdo, $product_id);

// جلب المنتجات المشابهة
$related = getRelatedProducts($pdo, $product_id, $product['category_id']);

// جلب التصنيف
$category = $product['category_id'] ? getCategories($pdo) : [];
$cat = array_filter($category, function($c) use ($product) {
    return $c['id'] == $product['category_id'];
});
$cat = reset($cat);

// تنسيق البيانات
$specs = json_decode($product['specs'], true);
$features = json_decode($product['features'], true);

$availabilityMap = [
    'in-stock' => ['label' => '✅ متوفر في المخزن', 'class' => 'in-stock'],
    'on-demand' => ['label' => '⏳ حسب الطلب', 'class' => 'on-demand'],
    'out-of-stock' => ['label' => '❌ غير متوفر حالياً', 'class' => 'out-of-stock']
];
$avail = $availabilityMap[$product['availability']] ?? $availabilityMap['in-stock'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($product['name']); ?> - متجر الكيلاني</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css" />
    
    <style>
        .product-detail-container { max-width: 1200px; margin: 30px auto; padding: 20px; }
        .breadcrumb { display: flex; gap: 8px; margin-bottom: 24px; font-size: 0.9rem; color: #94a3b8; flex-wrap: wrap; }
        .breadcrumb a { color: #00f0ff; text-decoration: none; transition: color 0.3s; }
        .breadcrumb a:hover { color: #fff; }
        .product-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .product-images { display: flex; flex-direction: column; gap: 16px; }
        .main-image { width: 100%; aspect-ratio: 1; border-radius: 16px; background: rgba(15,23,42,0.65); border: 1px solid rgba(0,240,255,0.12); overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative; }
        .main-image img { width: 100%; height: 100%; object-fit: contain; }
        .main-image .nav-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.6); color: #fff; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; transition: all 0.3s; display: flex; align-items: center; justify-content: center; }
        .main-image .nav-btn:hover { background: rgba(0,240,255,0.3); }
        .main-image .nav-btn.prev { right: 10px; }
        .main-image .nav-btn.next { left: 10px; }
        .main-image .image-counter { position: absolute; bottom: 10px; left: 50%; transform: translateX(50%); background: rgba(0,0,0,0.6); color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; }
        .image-thumbnails { display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 8px; }
        .thumbnail { aspect-ratio: 1; border-radius: 12px; background: rgba(15,23,42,0.65); border: 2px solid transparent; cursor: pointer; overflow: hidden; transition: all 0.3s; }
        .thumbnail:hover, .thumbnail.active { border-color: #00f0ff; }
        .thumbnail img { width: 100%; height: 100%; object-fit: cover; }
        .product-info { display: flex; flex-direction: column; gap: 20px; }
        .product-header { border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 20px; }
        .product-sku { display: inline-block; background: rgba(255,255,255,0.05); padding: 2px 12px; border-radius: 12px; font-size: 0.75rem; color: #94a3b8; font-family: monospace; margin-bottom: 8px; }
        .product-category { display: inline-block; background: rgba(0,240,255,0.1); color: #00f0ff; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; margin-bottom: 12px; }
        .product-title { font-size: 1.8rem; font-weight: 800; margin-bottom: 12px; }
        .availability-badge { display: inline-block; padding: 4px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; }
        .availability-badge.in-stock { background: rgba(16,185,129,0.15); color: #10b981; }
        .availability-badge.on-demand { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .availability-badge.out-of-stock { background: rgba(239,68,68,0.15); color: #ef4444; }
        .price-section { background: rgba(0,240,255,0.05); border: 1px solid rgba(0,240,255,0.1); border-radius: 12px; padding: 20px; }
        .current-price { font-size: 2rem; font-weight: 800; color: #00f0ff; }
        .original-price { font-size: 1.2rem; color: #64748b; text-decoration: line-through; margin-right: 12px; }
        .share-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .share-btn { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-family: 'Cairo', sans-serif; font-weight: 600; font-size: 0.85rem; transition: all 0.3s; display: flex; align-items: center; gap: 6px; }
        .share-btn:hover { transform: translateY(-2px); }
        .share-btn.whatsapp { background: #25d366; color: #fff; }
        .share-btn.facebook { background: #1877f2; color: #fff; }
        .share-btn.twitter { background: #000; color: #fff; }
        .share-btn.copy { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .share-btn.print { background: #6b7280; color: #fff; }
        .specs-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .specs-table tr { border-bottom: 1px solid rgba(255,255,255,0.05); }
        .specs-table td { padding: 10px 12px; font-size: 0.9rem; }
        .specs-table .spec-label { color: #94a3b8; font-weight: 600; width: 40%; }
        .specs-table .spec-value { color: #f8fafc; }
        .related-products { margin-top: 50px; padding-top: 40px; border-top: 1px solid rgba(255,255,255,0.06); }
        .related-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 24px; border-right: 3px solid #00f0ff; padding-right: 12px; }
        .related-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .related-item { background: rgba(15,23,42,0.65); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 16px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .related-item:hover { border-color: #00f0ff; transform: translateY(-4px); }
        .related-item img { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 8px; }
        .related-item .name { font-weight: 700; font-size: 0.9rem; }
        .related-item .price { color: #00f0ff; font-weight: 700; }
        .btn-back { display: inline-block; padding: 10px 24px; border: 1px solid #00f0ff; border-radius: 12px; color: #00f0ff; text-decoration: none; font-weight: 600; transition: all 0.3s; }
        .btn-back:hover { background: rgba(0,240,255,0.1); }
        .btn-pdf { padding: 10px 20px; border: none; border-radius: 12px; background: #dc2626; color: #fff; font-family: 'Cairo', sans-serif; font-weight: 700; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-pdf:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(220,38,38,0.3); }
        .btn-whatsapp-product { padding: 10px 20px; border: none; border-radius: 12px; background: #25d366; color: #fff; font-family: 'Cairo', sans-serif; font-weight: 700; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; width: 100%; justify-content: center; }
        .btn-whatsapp-product:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(37,211,102,0.3); }
        .features-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .feature-item { background: rgba(0,240,255,0.02); border: 1px solid rgba(0,240,255,0.08); border-radius: 8px; padding: 12px 16px; display: flex; gap: 8px; align-items: flex-start; }
        .feature-item .feature-icon { color: #00f0ff; font-size: 1.2rem; flex-shrink: 0; }
        .feature-item .feature-text { color: #cbd5e1; font-size: 0.9rem; }
        @media (max-width: 768px) {
            .product-detail-grid { grid-template-columns: 1fr; gap: 24px; }
            .product-title { font-size: 1.4rem; }
            .current-price { font-size: 1.6rem; }
            .image-thumbnails { grid-template-columns: repeat(4, 1fr); }
            .share-buttons { justify-content: center; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">⚡ <span>الكيلاني</span> للكهرباء</a>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 ابحث عن منتج..." />
            </div>
            <nav class="nav-links">
                <a href="index.php#categories">التصنيفات</a>
                <a href="index.php#products">المنتجات</a>
                <a href="index.php#contact">اتصل بنا</a>
            </nav>
        </div>
    </header>

    <div class="product-detail-container">
        
        <div class="breadcrumb">
            <a href="index.php">الرئيسية</a>
            <span>/</span>
            <a href="index.php#products">المنتجات</a>
            <span>/</span>
            <span><?php echo $cat ? htmlspecialchars($cat['name']) : 'التصنيف'; ?></span>
            <span>/</span>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <div class="product-detail-grid">
            
            <div class="product-images">
                <div class="main-image" id="mainImageContainer">
                    <?php 
                    $firstImage = !empty($images) ? 'uploads/products/' . $images[0]['image_path'] : 'https://via.placeholder.com/400x400/1a1a2e/00f0ff?text=لا+توجد+صورة';
                    ?>
                    <img id="mainImage" src="<?php echo $firstImage; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                    <?php if (count($images) > 1): ?>
                        <button class="nav-btn prev" onclick="changeImage(-1)">‹</button>
                        <button class="nav-btn next" onclick="changeImage(1)">›</button>
                        <div class="image-counter" id="imageCounter">1 / <?php echo count($images); ?></div>
                    <?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?>
                <div class="image-thumbnails" id="thumbnailsContainer">
                    <?php foreach ($images as $i => $img): ?>
                        <div class="thumbnail <?php echo $i === 0 ? 'active' : ''; ?>" onclick="setImage(<?php echo $i; ?>)">
                            <img src="uploads/products/<?php echo $img['image_path']; ?>" alt="صورة" />
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="product-info">
                <div class="product-header">
                    <div class="product-sku">SKU: <?php echo htmlspecialchars($product['sku'] ?? '---'); ?></div>
                    <span class="product-category"><?php echo $cat ? $cat['icon'] . ' ' . htmlspecialchars($cat['name']) : 'غير محدد'; ?></span>
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <span class="availability-badge <?php echo $avail['class']; ?>"><?php echo $avail['label']; ?></span>
                </div>

                <div class="price-section">
                    <span class="current-price"><?php echo number_format($product['price'], 2); ?> ج.م</span>
                    <span class="original-price"><?php echo number_format($product['price'] * 1.25, 2); ?> ج.م</span>
                </div>

                <div style="background:rgba(0,240,255,0.05);border:1px solid rgba(0,240,255,0.1);border-radius:8px;padding:16px;display:flex;align-items:center;gap:12px;">
                    <span style="color:#94a3b8;">🏷️ الماركة:</span>
                    <span style="font-weight:700;color:#00f0ff;"><?php echo htmlspecialchars($product['brand'] ?? '-'); ?></span>
                </div>

                <button class="btn-whatsapp-product" onclick="window.open('https://wa.me/201234567890?text=أريد%20الاستفسار%20عن%20<?php echo urlencode($product['name']); ?>', '_blank')">
                    💬 استفسار عبر واتساب
                </button>

                <div>
                    <h4 style="margin-bottom:8px;">📤 مشاركة المنتج</h4>
                    <div class="share-buttons">
                        <button class="share-btn whatsapp" onclick="shareProduct('whatsapp')">💬 واتساب</button>
                        <button class="share-btn facebook" onclick="shareProduct('facebook')">📘 فيسبوك</button>
                        <button class="share-btn twitter" onclick="shareProduct('twitter')">🐦 تويتر</button>
                        <button class="share-btn copy" onclick="shareProduct('copy')">📋 نسخ الرابط</button>
                        <button class="share-btn print" onclick="window.print()">🖨️ طباعة</button>
                    </div>
                </div>

                <button class="btn-pdf" onclick="generatePDF()">📄 تحميل PDF</button>
                <a href="index.php#products" class="btn-back">⬅ العودة إلى المنتجات</a>
            </div>
        </div>

        <div style="background:rgba(15,23,42,0.65);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:24px;margin-top:30px;">
            <div class="description-title" style="font-size:1.2rem;font-weight:700;margin-bottom:16px;border-right:3px solid #00f0ff;padding-right:12px;">📝 وصف المنتج</div>
            <div class="description-content" style="color:#cbd5e1;line-height:1.8;margin-bottom:20px;">
                <?php echo nl2br(htmlspecialchars($product['description'] ?? 'لا يوجد وصف')); ?>
            </div>

            <?php if ($specs && is_array($specs) && count($specs) > 0): ?>
            <div class="description-title" style="font-size:1.2rem;font-weight:700;margin-bottom:16px;border-right:3px solid #00f0ff;padding-right:12px;">⚙️ المواصفات التقنية</div>
            <table class="specs-table">
                <tbody>
                    <?php foreach ($specs as $key => $value): ?>
                        <tr>
                            <td class="spec-label"><?php echo htmlspecialchars($key); ?></td>
                            <td class="spec-value"><?php echo htmlspecialchars($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php if ($features && is_array($features) && count($features) > 0): ?>
            <div class="description-title" style="font-size:1.2rem;font-weight:700;margin-bottom:16px;border-right:3px solid #00f0ff;padding-right:12px;margin-top:24px;">✨ المميزات الرئيسية</div>
            <div class="features-list">
                <?php foreach ($features as $feature): ?>
                    <div class="feature-item">
                        <span class="feature-icon">✓</span>
                        <span class="feature-text"><?php echo htmlspecialchars($feature); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($related && count($related) > 0): ?>
        <div class="related-products">
            <div class="related-title">🔄 منتجات مشابهة</div>
            <div class="related-grid">
                <?php foreach ($related as $rel):
                    $relImages = getProductImages($pdo, $rel['id']);
                    $relImage = !empty($relImages) ? 'uploads/products/' . $relImages[0]['image_path'] : 'https://via.placeholder.com/200x120/1a1a2e/00f0ff?text=صورة';
                ?>
                <div class="related-item" onclick="window.location.href='product.php?id=<?php echo $rel['id']; ?>'">
                    <img src="<?php echo $relImage; ?>" alt="<?php echo htmlspecialchars($rel['name']); ?>" />
                    <div class="name"><?php echo htmlspecialchars($rel['name']); ?></div>
                    <div class="price"><?php echo number_format($rel['price'], 2); ?> ج.م</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <footer class="footer">
        <div class="footer-content">
            <div>
                <h4>⚡ متجر الكيلاني</h4>
                <p>جميع قطع الغيار الكهربائية الأصلية</p>
                <p>الإضاءة الحديثة • التبريد والتكييف</p>
            </div>
            <div>
                <h4>روابط سريعة</h4>
                <p><a href="index.php#categories">التصنيفات</a></p>
                <p><a href="index.php#products">المنتجات</a></p>
                <p><a href="index.php#contact">اتصل بنا</a></p>
            </div>
            <div>
                <h4>معلومات</h4>
                <p>© 2026 متجر الكيلاني</p>
                <p>جميع الحقوق محفوظة</p>
            </div>
        </div>
        <div class="footer-bottom">
            تم التطوير بواسطة <strong>Antigravity AI</strong>
        </div>
    </footer>

    <script>
        // ===== بيانات المنتج =====
        const productData = <?php echo json_encode($product); ?>;
        const productImages = <?php echo json_encode($images); ?>;
        let currentImageIndex = 0;

        // ===== التنقل بين الصور =====
        function setImage(index) {
            if (index >= 0 && index < productImages.length) {
                currentImageIndex = index;
                document.getElementById('mainImage').src = 'uploads/products/' + productImages[index].image_path;
                document.getElementById('imageCounter').textContent = (index + 1) + ' / ' + productImages.length;
                document.querySelectorAll('.thumbnail').forEach((el, i) => {
                    el.classList.toggle('active', i === index);
                });
            }
        }

        function changeImage(direction) {
            const newIndex = currentImageIndex + direction;
            if (newIndex >= 0 && newIndex < productImages.length) {
                setImage(newIndex);
            }
        }

        // ===== دعم لوحة المفاتيح =====
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight') changeImage(1);
            if (e.key === 'ArrowLeft') changeImage(-1);
        });

        // ===== مشاركة المنتج =====
        function shareProduct(platform) {
            const url = window.location.href;
            const text = `🛒 ${productData.name}\n💰 ${productData.price} ج.م\n📦 ${productData.brand || ''}\n\n${url}`;
            
            const shareUrls = {
                whatsapp: `https://wa.me/?text=${encodeURIComponent(text)}`,
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
                twitter: `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`,
                copy: () => {
                    navigator.clipboard.writeText(url).then(() => {
                        alert('✅ تم نسخ الرابط!');
                    }).catch(() => {
                        const input = document.createElement('input');
                        input.value = url;
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        document.body.removeChild(input);
                        alert('✅ تم نسخ الرابط!');
                    });
                }
            };

            if (platform === 'copy') {
                shareUrls.copy();
            } else {
                window.open(shareUrls[platform], '_blank', 'width=600,height=500');
            }
        }

        // ===== توليد PDF =====
        function generatePDF() {
            const content = `
================================================
متجر الكيلاني للكهرباء والإضاءة
================================================

📦 ${productData.name}
🏷️ الماركة: ${productData.brand || '-'}
💰 السعر: ${productData.price} ج.م
🔢 SKU: ${productData.sku || '---'}

📝 الوصف:
${productData.description || 'لا يوجد وصف'}

⚙️ المواصفات التقنية:
${productData.specs ? Object.entries(JSON.parse(productData.specs)).map(([k,v]) => `  ${k}: ${v}`).join('\n') : '  لا توجد مواصفات'}

✨ المميزات:
${productData.features ? JSON.parse(productData.features).map(f => `  • ${f}`).join('\n') : '  لا توجد مميزات'}

================================================
للتواصل: 01234567890 | info@alkilani.com
${window.location.href}
================================================
            `;

            const win = window.open('', '_blank', 'width=600,height=800');
            win.document.write(`
                <html>
                <head>
                    <title>${productData.name} - مواصفات المنتج</title>
                    <style>
                        body { font-family: 'Cairo', sans-serif; padding: 30px; direction: rtl; }
                        pre { white-space: pre-wrap; font-size: 14px; line-height: 1.8; }
                        .header { text-align: center; border-bottom: 2px solid #00f0ff; padding-bottom: 10px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>⚡ متجر الكيلاني</h1>
                        <p>01234567890 | info@alkilani.com</p>
                    </div>
                    <pre>${content}</pre>
                    <script>
                        setTimeout(() => { window.print(); }, 500);
                    <\/script>
                </body>
                </html>
            `);
        }

        // ===== البحث =====
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && this.value.trim()) {
                        window.location.href = 'index.php?search=' + encodeURIComponent(this.value.trim());
                    }
                });
            }
        });
    </script>
</body>
</html>