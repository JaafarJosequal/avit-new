# 🛒 Cart API Documentation

## نظرة عامة
Cart API يوفر واجهات برمجة شاملة لإدارة سلة التسوق في تطبيق الهاتف المحمول. جميع العمليات تتطلب مصادقة العميل.

## 🔐 المصادقة
جميع Cart APIs تتطلب مصادقة العميل عبر:
- **Header:** `Authorization: Bearer {token}`
- أو **Token مشفر** في Header

## 📋 نقاط النهاية (Endpoints)

### 1. 🆕 إضافة منتج إلى السلة

**URL:** `POST /apimobile/cart/add`

**Request:**
```json
{
  "product_id": "261",
  "quantity": 1,
  "color": "#FF0000",
  "size": "M"
}
```

**Response - إذا المنتج بنفس الخيارات موجود (يتم دمج الكمية):**
```json
{
  "status": true,
  "message": "Quantity updated for existing item",
  "data": {
    "items": [
      {
        "id": "1915",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 2,
        "price": "$119.99",
        "row_total": "$239.98",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "color": "#FF0000",
          "size": "M"
        }
      }
    ],
    "cart_qty": 2,
    "has_coupon": false,
    "coupon": "",
    "totals": [
      {
        "label": "Subtotal",
        "value": "$239.98"
      },
      {
        "label": "Shipping",
        "value": "$0.00"
      },
      {
        "label": "Grand Total",
        "value": "$239.98"
      }
    ]
  }
}
```

**Response - إذا المنتج جديد أو خيارات مختلفة:**
```json
{
  "status": true,
  "message": "Product added successfully",
  "data": {
    "items": [
      {
        "id": "1915",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 1,
        "price": "$119.99",
        "row_total": "$119.99",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "color": "#FF0000",
          "size": "M"
        }
      },
      {
        "id": "1916",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 1,
        "price": "$119.99",
        "row_total": "$119.99",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "color": "#00FF00",
          "size": "S"
        }
      }
    ],
    "cart_qty": 2,
    "has_coupon": false,
    "coupon": "",
    "totals": [
      {
        "label": "Subtotal",
        "value": "$239.98"
      },
      {
        "label": "Shipping",
        "value": "$0.00"
      },
      {
        "label": "Grand Total",
        "value": "$239.98"
      }
    ]
  }
}
```

### 2. 📋 الحصول على تفاصيل السلة

**URL:** `GET /apimobile/cart/info`

**Request:** `{}`

**Response:**
```json
{
  "status": true,
  "message": "Cart Details",
  "data": {
    "items": [
      {
        "id": "1915",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 1,
        "price": "$119.99",
        "row_total": "$119.99",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "color": "#FF0000",
          "size": "M"
        }
      },
      {
        "id": "1916",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 1,
        "price": "$119.99",
        "row_total": "$119.99",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "color": "#00FF00",
          "size": "S"
        }
      }
    ],
    "cart_qty": 2,
    "has_coupon": false,
    "coupon": "",
    "totals": [
      {
        "label": "Subtotal",
        "value": "$239.98"
      },
      {
        "label": "Shipping",
        "value": "$0.00"
      },
      {
        "label": "Grand Total",
        "value": "$239.98"
      }
    ]
  }
}
```

### 3. ✏️ تحديث كمية عنصر

**URL:** `POST /apimobile/cart/update`

**Request:**
```json
{
  "item_id": "1915",
  "qty": 3
}
```

**Response:**
```json
{
  "status": true,
  "message": "Cart updated successfully",
  "data": {
    "items": [
      {
        "id": "1915",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 3,
        "price": "$119.99",
        "row_total": "$359.97",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "color": "#FF0000",
          "size": "M"
        }
      }
    ],
    "cart_qty": 3,
    "has_coupon": false,
    "coupon": "",
    "totals": [
      {
        "label": "Subtotal",
        "value": "$359.97"
      },
      {
        "label": "Shipping",
        "value": "$0.00"
      },
      {
        "label": "Grand Total",
        "value": "$359.97"
      }
    ]
  }
}
```

### 4. ❌ حذف عنصر من السلة

**URL:** `POST /apimobile/cart/delete`

**Request - إما بالـ item_id:**
```json
{
  "item_id": "1915"
}
```

**Request - أو بالـ product_id (يحذف كل العناصر لهذا المنتج):**
```json
{
  "product_id": "261"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Item removed successfully",
  "data": {
    "items": [
      {
        "id": "1916",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 1,
        "price": "$119.99",
        "row_total": "$119.99",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "color": "#00FF00",
          "size": "S"
        }
      }
    ],
    "cart_qty": 1,
    "has_coupon": false,
    "coupon": "",
    "totals": [
      {
        "label": "Subtotal",
        "value": "$119.99"
      },
      {
        "label": "Shipping",
        "value": "$0.00"
      },
      {
        "label": "Grand Total",
        "value": "$119.99"
      }
    ]
  }
}
```

## 📌 ملاحظات مهمة

### **إضافة المنتجات:**
- **يضيف أو يدمج الكمية** حسب الخيارات (color, size, etc.)
- إذا كان المنتج موجود بنفس الخيارات → يتم دمج الكمية
- إذا كان المنتج موجود بخيارات مختلفة → يتم إنشاء item جديد

### **معلومات السلة:**
- **يرجع تفاصيل السلة كاملة** (items + totals)
- يتضمن معلومات الخيارات لكل منتج
- يعرض الإجماليات (Subtotal, Shipping, Grand Total)

### **تحديث الكمية:**
- **يغير الكمية فقط** للعنصر المحدد
- يجب أن تكون الكمية أكبر من 0

### **حذف العناصر:**
- **يحذف عنصر واحد** عند استخدام `item_id`
- **يحذف كل العناصر** لمنتج معين عند استخدام `product_id`

## 🔧 المعاملات (Parameters)

### **إضافة منتج:**
- `product_id` (مطلوب) - معرف المنتج
- `quantity` (اختياري) - الكمية (افتراضي: 1)
- `color` (اختياري) - لون المنتج
- `size` (اختياري) - حجم المنتج
- `custom_options` (اختياري) - خيارات مخصصة إضافية

### **تحديث كمية:**
- `item_id` (مطلوب) - معرف عنصر السلة
- `qty` (مطلوب) - الكمية الجديدة

### **حذف عنصر:**
- `item_id` (اختياري) - معرف عنصر السلة
- `product_id` (اختياري) - معرف المنتج

## 🚨 رموز الخطأ

```json
{
  "status": false,
  "message": "Product ID is required",
  "data": []
}
```

```json
{
  "status": false,
  "message": "Product not found",
  "data": []
}
```

```json
{
  "status": false,
  "message": "Item ID and quantity are required",
  "data": []
}
```

```json
{
  "status": false,
  "message": "Quantity must be greater than 0",
  "data": []
}
```

## 📱 أمثلة الاستخدام

### **إضافة منتج بلون وحجم:**
```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 2,
    "color": "#FF0000",
    "size": "L"
  }'
```

### **عرض معلومات السلة:**
```bash
curl -X GET https://avit.josequal.net/apimobile/cart/info \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### **تحديث كمية:**
```bash
curl -X POST https://avit.josequal.net/apimobile/cart/update \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "item_id": "1915",
    "qty": 5
  }'
```

### **حذف عنصر:**
```bash
curl -X POST https://avit.josequal.net/apimobile/cart/delete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "item_id": "1915"
  }'
```

## 🔄 حالات الاستجابة

### **نجح العملية:**
- `status: true`
- `message: رسالة نجاح مناسبة`
- `data: بيانات محدثة للسلة`

### **فشل العملية:**
- `status: false`
- `message: رسالة خطأ واضحة`
- `data: []`

## 📊 بنية البيانات

### **عنصر السلة:**
```json
{
  "id": "string",           // معرف عنصر السلة
  "product_id": "string",   // معرف المنتج
  "name": "string",         // اسم المنتج
  "sku": "string",          // رمز المنتج
  "qty": "integer",         // الكمية
  "price": "string",        // السعر (مُنسق)
  "row_total": "string",    // إجمالي السطر (مُنسق)
  "image": "string",        // رابط الصورة
  "options": {              // الخيارات
    "color": "string",      // اللون
    "size": "string"        // الحجم
  }
}
```

### **الإجماليات:**
```json
{
  "label": "string",        // تسمية الإجمالي
  "value": "string"         // القيمة (مُنسقة)
}
```

## 🎯 أفضل الممارسات

1. **استخدم `item_id`** للتحديث والحذف بدلاً من `product_id`
2. **تحقق من الكمية** قبل التحديث
3. **استخدم الخيارات** لتمييز المنتجات المتشابهة
4. **تعامل مع الأخطاء** بشكل مناسب
5. **احفظ معرفات العناصر** للعمليات اللاحقة
