# 🧪 اختبار نظام السلة (Cart System Testing)

## 📋 **قائمة الاختبارات**

### **1. اختبار إضافة المنتجات**

#### **1.1 إضافة منتج بسيط**
```bash
# اختبار إضافة منتج بدون خيارات
curl -X POST http://your-store.com/cart/add \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 123,
    "quantity": 2
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": true,
  "message": "Product added successfully",
  "data": {
    "items": [
      {
        "id": "1",
        "product_id": "123",
        "name": "اسم المنتج",
        "qty": 2,
        "has_options": false
      }
    ],
    "cart_qty": 2
  }
}
```

#### **1.2 إضافة منتج مع خيارات**
```bash
# اختبار إضافة منتج مع خيارات
curl -X POST http://your-store.com/cart/add \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 123,
    "quantity": 1,
    "color": "أحمر",
    "size": "L"
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": true,
  "message": "Product added successfully",
  "data": {
    "items": [
      {
        "id": "1",
        "product_id": "123",
        "name": "اسم المنتج",
        "qty": 1,
        "has_options": true,
        "options": [
          {
            "key": "color",
            "label": "Color",
            "value": "أحمر"
          },
          {
            "key": "size",
            "label": "Size",
            "value": "L"
          }
        ]
      }
    ]
  }
}
```

#### **1.3 اختبار دمج العناصر المتشابهة**
```bash
# إضافة نفس المنتج بنفس الخيارات مرة أخرى
curl -X POST http://your-store.com/cart/add \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 123,
    "quantity": 1,
    "color": "أحمر",
    "size": "L"
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": true,
  "message": "Quantity updated for existing item",
  "data": {
    "items": [
      {
        "id": "1",
        "product_id": "123",
        "name": "اسم المنتج",
        "qty": 2,
        "has_options": true
      }
    ],
    "cart_qty": 2
  }
}
```

### **2. اختبار عرض معلومات السلة**

#### **2.1 عرض السلة الفارغة**
```bash
curl -X GET http://your-store.com/cart/info \
  -H "Authorization: Bearer {token}"
```

**النتيجة المتوقعة:**
```json
{
  "status": true,
  "message": "Cart Details",
  "data": {
    "items": [],
    "cart_qty": 0,
    "has_coupon": false,
    "coupon": "",
    "totals": [
      {
        "label": "Subtotal",
        "value": "$0.00"
      },
      {
        "label": "Grand Total",
        "value": "$0.00"
      }
    ]
  }
}
```

#### **2.2 عرض السلة مع المنتجات**
```bash
# بعد إضافة منتجات
curl -X GET http://your-store.com/cart/info \
  -H "Authorization: Bearer {token}"
```

**النتيجة المتوقعة:**
```json
{
  "status": true,
  "message": "Cart Details",
  "data": {
    "items": [
      {
        "id": "1",
        "product_id": "123",
        "name": "قميص كلاسيك",
        "sku": "SHIRT-001",
        "qty": 2,
        "price": "$29.99",
        "row_total": "$59.98",
        "image": "https://store.com/media/catalog/product/shirt.jpg",
        "options": [
          {
            "key": "color",
            "label": "Color",
            "value": "أحمر"
          },
          {
            "key": "size",
            "label": "Size",
            "value": "L"
          }
        ],
        "has_options": true,
        "options_summary": "Color: أحمر, Size: L",
        "is_available": true,
        "stock_status": {
          "is_in_stock": true,
          "qty": 50,
          "min_qty": 1,
          "max_qty": 100
        }
      }
    ],
    "cart_qty": 2,
    "has_coupon": false,
    "coupon": "",
    "totals": [
      {
        "label": "Subtotal",
        "value": "$59.98"
      },
      {
        "label": "Shipping",
        "value": "$5.00"
      },
      {
        "label": "Grand Total",
        "value": "$64.98"
      }
    ],
    "cart_id": "123",
    "store_id": 1
  }
}
```

### **3. اختبار تحديث الكميات**

#### **3.1 تحديث كمية عنصر**
```bash
curl -X POST http://your-store.com/cart/update \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "item_id": 1,
    "qty": 3
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": true,
  "message": "Cart updated successfully",
  "data": {
    "items": [
      {
        "id": "1",
        "product_id": "123",
        "qty": 3,
        "row_total": "$89.97"
      }
    ],
    "cart_qty": 3
  }
}
```

#### **3.2 اختبار كمية صفرية (خطأ)**
```bash
curl -X POST http://your-store.com/cart/update \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "item_id": 1,
    "qty": 0
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": false,
  "message": "Quantity must be greater than 0",
  "data": []
}
```

### **4. اختبار حذف العناصر**

#### **4.1 حذف عنصر محدد**
```bash
curl -X POST http://your-store.com/cart/delete \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "item_id": 1
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": true,
  "message": "Item removed successfully",
  "data": {
    "items": [],
    "cart_qty": 0
  }
}
```

#### **4.2 حذف جميع عناصر منتج معين**
```bash
curl -X POST http://your-store.com/cart/delete \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 123
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": true,
  "message": "All items for product removed successfully",
  "data": {
    "items": [],
    "cart_qty": 0
  }
}
```

### **5. اختبار مسح السلة**

#### **5.1 مسح السلة بالكامل**
```bash
curl -X POST http://your-store.com/cart/clear \
  -H "Authorization: Bearer {token}"
```

**النتيجة المتوقعة:**
```json
{
  "status": true,
  "message": "Cart cleared successfully",
  "data": {
    "items": [],
    "cart_qty": 0
  }
}
```

### **6. اختبار إدارة الكوبونات**

#### **6.1 تطبيق كوبون صحيح**
```bash
curl -X POST http://your-store.com/cart/apply-coupon \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "coupon_code": "SAVE20"
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": true,
  "message": "Coupon applied successfully",
  "data": {
    "totals": [
      {
        "label": "Subtotal",
        "value": "$100.00"
      },
      {
        "label": "Discount",
        "value": "-$20.00"
      },
      {
        "label": "Grand Total",
        "value": "$80.00"
      }
    ]
  }
}
```

#### **6.2 تطبيق كوبون خاطئ**
```bash
curl -X POST http://your-store.com/cart/apply-coupon \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "coupon_code": "INVALID"
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": false,
  "message": "Invalid coupon code",
  "data": []
}
```

#### **6.3 إزالة الكوبون**
```bash
curl -X POST http://your-store.com/cart/remove-coupon \
  -H "Authorization: Bearer {token}"
```

**النتيجة المتوقعة:**
```json
{
  "status": true,
  "message": "Coupon removed successfully",
  "data": {
    "totals": [
      {
        "label": "Subtotal",
        "value": "$100.00"
      },
      {
        "label": "Grand Total",
        "value": "$100.00"
      }
    ]
  }
}
```

### **7. اختبار الأخطاء**

#### **7.1 منتج غير موجود**
```bash
curl -X POST http://your-store.com/cart/add \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 99999,
    "quantity": 1
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": false,
  "message": "Product not found",
  "data": []
}
```

#### **7.2 بيانات ناقصة**
```bash
curl -X POST http://your-store.com/cart/add \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "quantity": 1
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": false,
  "message": "Product ID is required",
  "data": []
}
```

#### **7.3 مصادقة خاطئة**
```bash
curl -X POST http://your-store.com/cart/add \
  -H "Authorization: Bearer invalid_token" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 123,
    "quantity": 1
  }'
```

**النتيجة المتوقعة:**
```json
{
  "status": false,
  "message": "Unauthorized",
  "data": []
}
```

## 🔧 **أدوات الاختبار**

### **1. Postman Collection**
```json
{
  "info": {
    "name": "Cart System API Tests",
    "description": "اختبارات نظام السلة"
  },
  "item": [
    {
      "name": "Add Product",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/cart/add",
        "headers": {
          "Authorization": "Bearer {{token}}",
          "Content-Type": "application/json"
        },
        "body": {
          "mode": "raw",
          "raw": "{\n  \"product_id\": 123,\n  \"quantity\": 1\n}"
        }
      }
    }
  ]
}
```

### **2. cURL Scripts**
```bash
#!/bin/bash

# متغيرات البيئة
BASE_URL="http://your-store.com"
TOKEN="your_token_here"

# اختبار إضافة منتج
echo "Testing Add Product..."
curl -X POST "$BASE_URL/cart/add" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 123, "quantity": 1}' \
  | jq '.'

# اختبار عرض السلة
echo "Testing Get Cart Info..."
curl -X GET "$BASE_URL/cart/info" \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.'
```

## 📊 **تقرير الاختبار**

### **الاختبارات المطلوبة:**
- [ ] إضافة منتج بسيط
- [ ] إضافة منتج مع خيارات
- [ ] دمج العناصر المتشابهة
- [ ] عرض معلومات السلة
- [ ] تحديث الكميات
- [ ] حذف العناصر
- [ ] مسح السلة
- [ ] تطبيق الكوبونات
- [ ] معالجة الأخطاء
- [ ] اختبار المصادقة

### **المقاييس:**
- **زمن الاستجابة:** < 2 ثانية
- **معدل النجاح:** > 95%
- **معالجة الأخطاء:** 100%
- **الأمان:** مصادقة صحيحة

---

**تم إنشاء هذا الملف بواسطة Josequal Team** 🚀
