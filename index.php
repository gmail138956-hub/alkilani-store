<?php
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/config/database.php';

// جلب المنتجات والتصنيفات
$products = getProducts($pdo);
$categories = getCategories($pdo);

// حساب الإحصائيات
$totalProducts = count($products);
$totalCategories = count(array_filter($categories, function($c) { return $c['parent_id'] === null; }));
$totalViews = array_sum(array_column($products, 'views'));

// تحديد التصنيفات الرئيسية والفرعية
$mainCategories = array_filter($categories, function($c) { return $c['parent_id'] === null; });
$subCategories = array_filter($categories, function($c) { return $c['parent_id'] !== null; });
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="متجر الكيلاني للكهرباء والإضاءة - أفضل تشكيلة من قطع الغيار الكهربائية والإضاءة الحديثة وأنظمة التبريد والتكييف"/>
    <title>متجر الكيلاني للكهرباء والإضاءة</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/assets/css/style.css" />
    
    <style>
        .whatsapp-float {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 999;
            background: #25d366;
            color: #fff;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            box-shadow: 0 4px 20px rgba(37, 211, 102, 0.4);
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            border: none;
        }
        .whatsapp-float:hover { transform: scale(1.1); box-shadow: 0 6px 30px rgba(37, 211, 102, 0.6); }
        .whatsapp-float .tooltip {
            position: absolute;
            left: 70px;
            background: rgba(0,0,0,0.8);
            color: #fff;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.8rem;
            white-space: nowrap;
            opacity: 0;
            transition: all 0.3s;
            pointer-events: none;
        }
        .whatsapp-float:hover .tooltip { opacity: 1; }
        @media (max-width: 480px) {
            .whatsapp-float { width: 50px; height: 50px; font-size: 1.5rem; bottom: 20px; left: 20px; }
        }
    </style>
</head>
<body>

    <a href="https://wa.me/201234567890" target="_blank" class="whatsapp-float">
        💬
        <span class="tooltip">تواصل معنا عبر واتساب</span>
    </a>

    <!-- ======== الهيدر ======== -->
    <header class="header">
        <div class="header-content">
            <a href="/index.php" class="logo">⚡ <span>الكيلاني</span> للكهرباء</a>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 ابحث عن منتج..." />
            </div>
            
            <nav class="nav-links">
                <a href="#categories">التصنيفات</a>
                <a href="#products">المنتجات</a>
                <a href="#contact">اتصل بنا</a>
                <a href="/login.php" style="color: #00f0ff; font-weight: 700;">🔐 لوحة التحكم</a>
            </nav>
        </div>
    </header>

    <!-- ======== البانر ======== -->
    <section class="banner">
        <div class="banner-content">
            <h1>مرحباً بكم في <span>متجر الكيلاني</span></h1>
            <p>أفضل تشكيلة من قطع الغيار الكهربائية، الإضاءة الحديثة، وأنظمة التبريد والتكييف</p>
            <div class="banner-stats">
                <div class="stat">
                    <span><?php echo $totalProducts; ?></span>
                    <label>منتج</label>
                </div>
                <div class="stat">
                    <span><?php echo $totalCategories; ?></span>
                    <label>قسم</label>
                </div>
                <div class="stat">
                    <span><?php echo $totalViews; ?></span>
                    <label>زيارة</label>
                </div>
            </div>
        </div>
    </section>

    <!-- ======== التصنيفات ======== -->
    <section id="categories" class="section">
        <div class="section-header">
            <h2 class="section-title">📂 التصنيفات</h2>
            <button class="btn-add" onclick="window.location.href='/login.php'">➕ إدارة التصنيفات</button>
        </div>
        
        <div class="nested-categories" id="nestedCategories">
            <?php foreach ($mainCategories as $mainCat): ?>
            <?php 
                $subs = array_filter($subCategories, function($s) use ($mainCat) { 
                    return $s['parent_id'] === $mainCat['id']; 
                });
                $mainCount = count(array_filter($products, function($p) use ($mainCat) {
                    return $p['category_id'] == $mainCat['id'];
                }));
            ?>
            <div class="main-category">
                <div class="main-category-header" onclick="toggleSubcategories(this)">
                    <div class="toggle-icon <?php echo empty($subs) ? 'no-arrow' : ''; ?>">
                        <?php echo !empty($subs) ? '▼' : ''; ?>
                    </div>
                    <div class="category-icon"><?php echo $mainCat['icon']; ?></div>
                    <div class="category-info">
                        <div class="category-name"><?php echo htmlspecialchars($mainCat['name']); ?></div>
                        <div class="category-description"><?php echo htmlspecialchars($mainCat['description']); ?></div>
                    </div>
                    <span class="category-count"><?php echo $mainCount; ?> منتج</span>
                </div>
                
                <?php if (!empty($subs)): ?>
                <div class="subcategories-list">
                    <?php foreach ($subs as $sub): 
                        $subCount = count(array_filter($products, function($p) use ($sub) {
                            return $p['category_id'] == $sub['id'];
                        }));
                    ?>
                    <div class="subcategory-item" onclick="filterBySubcategory(<?php echo $sub['id']; ?>, event)">
                        <div class="subcategory-icon"><?php echo $sub['icon']; ?></div>
                        <div class="subcategory-name"><?php echo htmlspecialchars($sub['name']); ?></div>
                        <span class="subcategory-count"><?php echo $subCount; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ======== المنتجات ======== -->
    <section id="products" class="section">
        <h2 class="section-title" id="productsTitle">📦 جميع المنتجات</h2>
        <div class="products-grid" id="productsGrid">
            <?php foreach ($products as $product): 
                $cat = array_filter($categories, function($c) use ($product) {
                    return $c['id'] == $product['category_id'];
                });
                $cat = reset($cat);
                $availabilityMap = [
                    'in-stock' => '✅ متوفر',
                    'on-demand' => '⏳ حسب الطلب',
                    'out-of-stock' => '❌ غير متوفر'
                ];
                $availabilityClass = [
                    'in-stock' => 'in-stock',
                    'on-demand' => 'on-demand',
                    'out-of-stock' => 'out-of-stock'
                ];
                $images = getProductImages($pdo, $product['id']);
                $image = !empty($images) ? '/uploads/products/' . $images[0]['image_path'] : 'https://via.placeholder.com/300x300/1a1a2e/00f0ff?text=صورة';
            ?>
            <div class="product-card">
                <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy" />
                <div class="info">
                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                    <p class="brand">🏷️ <?php echo htmlspecialchars($product['brand']); ?></p>
                    <p class="price"><?php echo number_format($product['price'], 2); ?> ج.م</p>
                    <span class="availability-badge <?php echo $availabilityClass[$product['availability']]; ?>">
                        <?php echo $availabilityMap[$product['availability']]; ?>
                    </span>
                    <button class="btn-details" onclick="window.location.href='/product.php?id=<?php echo $product['id']; ?>'">
                        📋 عرض التفاصيل
                    </button>
                    <button class="btn-whatsapp" onclick="window.open('https://wa.me/201234567890?text=أريد%20الاستفسار%20عن%20<?php echo urlencode($product['name']); ?>', '_blank')">
                        💬 استفسار واتساب
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ======== اتصل بنا ======== -->
    <section id="contact" class="section contact-section">
        <h2 class="section-title">📞 اتصل بنا</h2>
        <div class="contact-content">
            <div class="contact-info">
                <p><strong>📱 الهاتف:</strong> 01234567890</p>
                <p><strong>📧 البريد:</strong> info@alkilani.com</p>
                <p><strong>📍 العنوان:</strong> وسط المدينة، بجوار البنك الأهلي</p>
                <p><strong>🕐 ساعات العمل:</strong> 9:00 ص - 10:00 م</p>
                <p><strong>💬 واتساب:</strong> <a href="https://wa.me/201234567890" target="_blank" style="color:#25d366;">اضغط للتواصل</a></p>
            </div>
            <div class="social-links">
                <a href="#">📘 فيسبوك</a>
                <a href="#">📸 إنستغرام</a>
                <a href="#" id="whatsappLink">💬 واتساب</a>
            </div>
        </div>
    </section>

    <!-- ======== الفوتر ======== -->
    <footer class="footer">
        <div class="footer-content">
            <div>
                <h4>⚡ متجر الكيلاني</h4>
                <p>جميع قطع الغيار الكهربائية الأصلية</p>
                <p>الإضاءة الحديثة • التبريد والتكييف</p>
            </div>
            <div>
                <h4>روابط سريعة</h4>
                <p><a href="#categories">التصنيفات</a></p>
                <p><a href="#products">المنتجات</a></p>
                <p><a href="#contact">اتصل بنا</a></p>
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
        const PRODUCTS = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>;
        const CATEGORIES = <?php echo json_encode($categories, JSON_UNESCAPED_UNICODE); ?>;

        function toggleSubcategories(header) {
            const list = header.nextElementSibling;
            if (!list || !list.classList.contains('subcategories-list')) return;
            list.classList.toggle('expanded');
            const icon = header.querySelector('.toggle-icon');
            if (icon) icon.classList.toggle('collapsed');
        }

        function filterBySubcategory(categoryId, event) {
            if (event) event.stopPropagation();
            document.querySelectorAll('.subcategory-item').forEach(el => el.classList.remove('active'));
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active');
            }
            const filtered = PRODUCTS.filter(p => p.category_id == categoryId);
            renderProducts(filtered);
            const cat = CATEGORIES.find(c => c.id == categoryId);
            const title = document.getElementById('productsTitle');
            if (title && cat) {
                title.textContent = `📦 ${cat.icon} ${cat.name}`;
            }
            document.getElementById('products').scrollIntoView({ behavior: 'smooth' });
        }

        function showAllProducts() {
            renderProducts(PRODUCTS);
            document.querySelectorAll('.subcategory-item').forEach(el => el.classList.remove('active'));
            document.getElementById('productsTitle').textContent = '📦 جميع المنتجات';
            document.getElementById('products').scrollIntoView({ behavior: 'smooth' });
        }

        function renderProducts(products) {
            const container = document.getElementById('productsGrid');
            if (!container) return;
            if (products.length === 0) {
                container.innerHTML = `<div style="text-align:center;padding:40px;color:#94a3b8;grid-column:1/-1;">😕 لا توجد منتجات في هذا التصنيف</div>`;
                return;
            }
            const availabilityMap = {
                'in-stock': { label: '✅ متوفر', class: 'in-stock' },
                'on-demand': { label: '⏳ حسب الطلب', class: 'on-demand' },
                'out-of-stock': { label: '❌ غير متوفر', class: 'out-of-stock' }
            };
            container.innerHTML = products.map(p => {
                const avail = availabilityMap[p.availability] || availabilityMap['in-stock'];
                const image = '/uploads/products/' + (p.images && p.images[0] ? p.images[0] : 'placeholder.jpg');
                return `
                    <div class="product-card">
                        <img src="${image}" alt="${p.name}" loading="lazy" />
                        <div class="info">
                            <h4>${p.name}</h4>
                            <p class="brand">🏷️ ${p.brand || '-'}</p>
                            <p class="price">${Number(p.price).toLocaleString()} ج.م</p>
                            <span class="availability-badge ${avail.class}">${avail.label}</span>
                            <button class="btn-details" onclick="window.location.href='/product.php?id=${p.id}'">
                                📋 عرض التفاصيل
                            </button>
                            <button class="btn-whatsapp" onclick="window.open('https://wa.me/201234567890?text=أريد%20الاستفسار%20عن%20${encodeURIComponent(p.name)}', '_blank')">
                                💬 استفسار واتساب
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function searchProducts(query) {
            if (!query.trim()) {
                showAllProducts();
                return;
            }
            const q = query.trim().toLowerCase();
            const filtered = PRODUCTS.filter(p => 
                p.name.toLowerCase().includes(q) ||
                (p.brand && p.brand.toLowerCase().includes(q))
            );
            renderProducts(filtered);
            document.querySelectorAll('.subcategory-item').forEach(el => el.classList.remove('active'));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    searchProducts(e.target.value);
                });
            }
            const urlParams = new URLSearchParams(window.location.search);
            const search = urlParams.get('search');
            if (search) {
                document.getElementById('searchInput').value = search;
                searchProducts(search);
            }
        });

        renderProducts(PRODUCTS);
    </script>

</body>
</html>