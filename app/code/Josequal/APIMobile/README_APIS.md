# APIMobile APIs Documentation

## نظرة عامة

هذا الوحدة توفر APIs شاملة للتصنيفات والمنتجات في تطبيق الهاتف المحمول.

## نقاط النهاية (Endpoints)

### التصنيفات (Categories)

#### 1. جميع التصنيفات
- **النقطة النهائية:** `apimobile/category/items`
- **الطريقة:** GET
- **الوصف:** الحصول على قائمة التصنيفات

**المعاملات (Parameters):**
```json
{
    "parent_id": 0  // اختياري - معرف التصنيف الأب (0 للتصنيفات الرئيسية)
}
```

**الاستجابة (Response):**
```json
{
    "status": true,
    "message": "All categories",
    "data": [
        {
            "id": 123,
            "name": "اسم التصنيف",
            "image": "https://example.com/category-image.jpg"
        }
    ]
}
```

#### 2. منتجات التصنيف
- **النقطة النهائية:** `apimobile/category/products`
- **الطريقة:** GET/POST
- **الوصف:** الحصول على منتجات تصنيف محدد

**المعاملات (Parameters):**
```json
{
    "category_id": 123,     // مطلوب - معرف التصنيف
    "page": 1,             // اختياري - رقم الصفحة (افتراضي: 1)
    "limit": 20,           // اختياري - عدد العناصر (افتراضي: 20)
    "sort": "name-a-z",    // اختياري - نوع الترتيب
    "search": "كلمة البحث"  // اختياري - البحث في المنتجات
}
```

**الاستجابة (Response):**
```json
{
    "status": true,
    "message": "Category Products",
    "data": {
        "category_id": "123",
        "category_name": "اسم التصنيف",
        "count": 50,
        "products": [
            {
                "product_id": 456,
                "name": "اسم المنتج",
                "type": "simple",
                "qty": 10,
                "sku": "SKU123",
                "price": "$100.00",
                "special_price": "$80.00",
                "lowest_price": "$80.00",
                "stock_status": true,
                "review_summary": {
                    "count": 5,
                    "summary": 80,
                    "averageRating": 4
                },
                "image": "https://example.com/product-image.jpg",
                "has_discount": true,
                "discount": "20%",
                "is_favorite": false
            }
        ]
    }
}
```

### المنتجات (Products)

#### 1. قائمة المنتجات
- **النقطة النهائية:** `apimobile/product/items`
- **الطريقة:** GET/POST
- **الوصف:** الحصول على قائمة المنتجات

**المعاملات (Parameters):**
```json
{
    "page": 1,              // اختياري - رقم الصفحة (افتراضي: 1)
    "limit": 20,            // اختياري - عدد العناصر (افتراضي: 20)
    "sort": "name-a-z",     // اختياري - نوع الترتيب
    "search": "كلمة البحث",  // اختياري - البحث في المنتجات
    "category_id": 22       // اختياري - معرف التصنيف (افتراضي: 22)
}
```

**الاستجابة (Response):**
```json
{
    "status": true,
    "message": "Products List",
    "data": {
        "count": 150,
        "products": [
            {
                "product_id": 123,
                "name": "اسم المنتج",
                "type": "simple",
                "qty": 10,
                "sku": "SKU123",
                "price": "$100.00",
                "special_price": "$80.00",
                "lowest_price": "$80.00",
                "stock_status": true,
                "review_summary": {
                    "count": 5,
                    "summary": 80,
                    "averageRating": 4
                },
                "image": "https://example.com/product-image.jpg",
                "has_discount": true,
                "discount": "20%",
                "is_favorite": false
            }
        ]
    }
}
```

#### 2. تفاصيل المنتج
- **النقطة النهائية:** `apimobile/product/details`
- **الطريقة:** GET/POST
- **الوصف:** الحصول على تفاصيل منتج محدد

**المعاملات (Parameters):**
```json
{
    "product_id": 123  // مطلوب - معرف المنتج
}
```

**الاستجابة (Response):**
```json
{
    "status": true,
    "message": "Product Details",
    "data": {
        "product_id": 123,
        "name": "اسم المنتج",
        "type": "simple",
        "qty": 10,
        "sku": "SKU123",
        "price": "$100.00",
        "special_price": "$80.00",
        "lowest_price": "$80.00",
        "stock_status": true,
        "review_summary": {
            "count": 5,
            "summary": 80,
            "averageRating": 4
        },
        "image": "https://example.com/product-image.jpg",
        "has_discount": true,
        "discount": "20%",
        "is_favorite": false,
        "images": [
            "https://example.com/image1.jpg",
            "https://example.com/image2.jpg"
        ],
        "description": "وصف المنتج",
        "care_tips": "نصائح العناية",
        "options": [
            {
                "option_id": 1,
                "title": "اللون",
                "type": "drop_down",
                "values": [
                    {
                        "option_type_id": 1,
                        "title": "أحمر",
                        "price": "$10.00"
                    }
                ]
            }
        ],
        "reviews": [
            {
                "product_id": 123,
                "created_at": "2024-01-01",
                "title": "مراجعة رائعة",
                "detail": "منتج ممتاز",
                "nickname": "عميل",
                "averageRating": 4.5
            }
        ],
        "attributes": [
            {
                "label": "الألوان",
                "value": "أحمر، أزرق"
            },
            {
                "label": "أنواع الزهور",
                "value": "ورود، أوركيد"
            }
        ],
        "related": [
            {
                "product_id": 124,
                "name": "منتج مرتبط",
                "price": "$90.00",
                "image": "https://example.com/related.jpg"
            }
        ]
    }
}
```

### المفضلة (Favorites)

#### 1. قائمة المفضلة
- **النقطة النهائية:** `apimobile/favorites/items`
- **الطريقة:** GET/POST
- **المصادقة:** مطلوب
- **الوصف:** الحصول على قائمة المفضلة

**الاستجابة (Response):**
```json
{
    "status": true,
    "message": "Favorite Items",
    "data": [
        {
            "wishlist_id": 1,
            "wishlist_item_id": 1,
            "product_id": 123,
            "name": "اسم المنتج",
            "type": "simple",
            "qty": 10,
            "price": "$100.00",
            "special_price": "$80.00",
            "lowest_price": "$80.00",
            "stock_status": true,
            "review_summary": {
                "count": 5,
                "summary": 80,
                "averageRating": 4
            },
            "image": "https://example.com/product-image.jpg",
            "has_discount": true,
            "discount": "20%",
            "is_favorite": true
        }
    ]
}
```

#### 2. إضافة منتج للمفضلة
- **النقطة النهائية:** `apimobile/favorites/add`
- **الطريقة:** POST
- **المصادقة:** مطلوب
- **الوصف:** إضافة منتج للمفضلة

**المعاملات (Parameters):**
```json
{
    "product_id": 123  // مطلوب - معرف المنتج
}
```

**الاستجابة (Response):**
```json
{
    "status": true,
    "message": "Product added to fav successfully",
    "data": [
        // قائمة المفضلة المحدثة
    ]
}
```

#### 3. إزالة منتج من المفضلة
- **النقطة النهائية:** `apimobile/favorites/remove`
- **الطريقة:** POST
- **المصادقة:** مطلوب
- **الوصف:** إزالة منتج من المفضلة

**المعاملات (Parameters):**
```json
{
    "product_id": 123  // مطلوب - معرف المنتج
}
```

**الاستجابة (Response):**
```json
{
    "status": true,
    "message": "Product removed from favorite successfully",
    "data": [
        // قائمة المفضلة المحدثة
    ]
}
```

### المراجعات (Reviews)

#### 1. إضافة مراجعة
- **النقطة النهائية:** `apimobile/reviews/add`
- **الطريقة:** POST
- **المصادقة:** مطلوب
- **الوصف:** إضافة مراجعة لمنتج

**المعاملات (Parameters):**
```json
{
    "product_id": 123,     // مطلوب - معرف المنتج
    "title": "عنوان المراجعة",
    "detail": "تفاصيل المراجعة",
    "nickname": "اسم المستخدم",
    "ratings": {
        "quality": 5,
        "value": 4,
        "price": 3
    }
}
```

**الاستجابة (Response):**
```json
{
    "status": true,
    "message": "Review submitted successfully"
}
```

## طرق الاستخدام

### 1. استخدام Query Parameters (GET)
```bash
curl -X GET "https://example.com/apimobile/category/products?category_id=123&page=1&limit=20"
```

### 2. استخدام JSON Body (POST)
```bash
curl -X POST "https://example.com/apimobile/category/products" \
  -H "Content-Type: application/json" \
  -d '{
    "category_id": 123,
    "page": 1,
    "limit": 20,
    "sort": "name-a-z",
    "search": "ورود"
  }'
```

### 3. استخدام Form Data (POST)
```bash
curl -X POST "https://example.com/apimobile/category/products" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "category_id=123&page=1&limit=20"
```

## أنواع الترتيب المتاحة

- `name-a-z` - الاسم من أ إلى ي
- `name-z-a` - الاسم من ي إلى أ
- `price-l-h` - السعر من الأقل إلى الأعلى
- `price-h-l` - السعر من الأعلى إلى الأقل
- `rating-h-l` - التقييم من الأعلى إلى الأقل
- `rating-l-h` - التقييم من الأقل إلى الأعلى
- `position` - حسب الموضع
- `newest` - الأحدث
- `oldest` - الأقدم

## رموز الحالة

- `200` - نجح الطلب
- `400` - خطأ في البيانات
- `401` - غير مصرح
- `404` - غير موجود
- `500` - خطأ في الخادم

## أمثلة الاستخدام

### مثال 1: الحصول على التصنيفات
```bash
curl -X GET "https://example.com/apimobile/category/items"
```

### مثال 2: الحصول على منتجات تصنيف
```bash
curl -X POST "https://example.com/apimobile/category/products" \
  -H "Content-Type: application/json" \
  -d '{
    "category_id": 123,
    "page": 1,
    "limit": 20
  }'
```

### مثال 3: البحث في المنتجات
```bash
curl -X POST "https://example.com/apimobile/product/items" \
  -H "Content-Type: application/json" \
  -d '{
    "search": "ورود",
    "sort": "price-l-h"
  }'
```

### مثال 4: تفاصيل المنتج
```bash
curl -X POST "https://example.com/apimobile/product/details" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 123
  }'
```

### مثال 5: إضافة للمفضلة
```bash
curl -X POST "https://example.com/apimobile/favorites/add" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 123
  }'
```

## الميزات المتقدمة

1. **دعم المنتجات القابلة للتكوين (Configurable Products)**
2. **إدارة الخصومات والنسب المئوية**
3. **دعم متعدد المتاجر والمواقع**
4. **استبعاد منتجات محددة**
5. **ترتيب متقدم (اسم، سعر، تاريخ، تقييم)**
6. **دعم الصفحات والحد الأقصى**
7. **تكامل مع نظام المفضلة**
8. **نظام تقييمات شامل**
9. **إدارة الصور المتعددة**
10. **الخصائص المخصصة**

## الأمان والتحقق

- ✅ التحقق من وجود معرف المنتج/التصنيف
- ✅ التحقق من صحة البيانات
- ✅ دعم CSRF
- ✅ معالجة الأخطاء
- ✅ استجابة JSON منظمة
- ✅ دعم المصادقة للميزات الحساسة

## الدعم

للحصول على الدعم أو الإبلاغ عن مشاكل، يرجى التواصل مع فريق التطوير.
