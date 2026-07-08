// ==========================================
// كود JavaScript - متجر الكيلاني
// ==========================================

// ===== عرض التصنيفات الفرعية =====
function toggleSubcategories(header) {
    const list = header.nextElementSibling;
    if (!list || !list.classList.contains('subcategories-list')) return;
    list.classList.toggle('expanded');
    const icon = header.querySelector('.toggle-icon');
    if (icon) icon.classList.toggle('collapsed');
}

// ===== تصفية حسب التصنيف الفرعي =====
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

// ===== عرض جميع المنتجات =====
function showAllProducts() {
    renderProducts(PRODUCTS);
    document.querySelectorAll('.subcategory-item').forEach(el => el.classList.remove('active'));
    document.getElementById('productsTitle').textContent = '📦 جميع المنتجات';
    document.getElementById('products').scrollIntoView({ behavior: 'smooth' });
}

// ===== عرض المنتجات =====
function renderProducts(products) {
    const container = document.getElementById('productsGrid');
    if (!container) return;
    
    if (products.length === 0) {
        container.innerHTML = `
            <div style="text-align:center;padding:40px;color:#94a3b8;grid-column:1/-1;">
                😕 لا توجد منتجات في هذا التصنيف
            </div>
        `;
        return;
    }
    
    const availabilityMap = {
        'in-stock': { label: '✅ متوفر', class: 'in-stock' },
        'on-demand': { label: '⏳ حسب الطلب', class: 'on-demand' },
        'out-of-stock': { label: '❌ غير متوفر', class: 'out-of-stock' }
    };
    
    container.innerHTML = products.map(p => {
        const avail = availabilityMap[p.availability] || availabilityMap['in-stock'];
        const image = p.image || 'https://via.placeholder.com/300x300/1a1a2e/00f0ff?text=صورة';
        return `
            <div class="product-card">
                <img src="${image}" alt="${p.name}" loading="lazy" />
                <div class="info">
                    <h4>${p.name}</h4>
                    <p class="brand">🏷️ ${p.brand || '-'}</p>
                    <p class="price">${Number(p.price).toLocaleString()} ج.م</p>
                    <span class="availability-badge ${avail.class}">${avail.label}</span>
                    <button class="btn-details" onclick="window.location.href='product.php?id=${p.id}'">
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

// ===== البحث =====
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

// ===== تهيئة الصفحة =====
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            searchProducts(e.target.value);
        });
    }
    
    // البحث من الـ URL (عند العودة من صفحة المنتج)
    const urlParams = new URLSearchParams(window.location.search);
    const search = urlParams.get('search');
    if (search) {
        document.getElementById('searchInput').value = search;
        searchProducts(search);
    }
});

// ===== عرض المنتجات بعد التحميل =====
if (typeof PRODUCTS !== 'undefined') {
    renderProducts(PRODUCTS);
}