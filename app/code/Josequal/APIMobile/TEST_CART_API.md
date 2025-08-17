# 🧪 Cart API Testing Guide

## نظرة عامة
هذا الدليل يوفر أمثلة شاملة لاختبار جميع Cart APIs مع أمثلة عملية.

## 🔑 الحصول على Token للمصادقة

### 1. تسجيل الدخول للحصول على Token
```bash
curl -X POST https://avit.josequal.net/V1/mobile/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "password": "password123"
  }'
```

**Response:**
```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "customer_id": "123"
  }
}
```

## 🧪 اختبار Cart APIs

### **Test 1: إضافة منتج جديد إلى السلة**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 1,
    "color": "#FF0000",
    "size": "M"
  }'
```

**Expected Response:**
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

### **Test 2: إضافة نفس المنتج بنفس الخيارات (يجب دمج الكمية)**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 2,
    "color": "#FF0000",
    "size": "M"
  }'
```

**Expected Response:**
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

### **Test 3: إضافة نفس المنتج بخيارات مختلفة (يجب إنشاء item جديد)**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 1,
    "color": "#00FF00",
    "size": "L"
  }'
```

**Expected Response:**
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
        "qty": 3,
        "price": "$119.99",
        "row_total": "$359.97",
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
          "size": "L"
        }
      }
    ],
    "cart_qty": 4,
    "has_coupon": false,
    "coupon": "",
    "totals": [
      {
        "label": "Subtotal",
        "value": "$479.96"
      },
      {
        "label": "Shipping",
        "value": "$0.00"
      },
      {
        "label": "Grand Total",
        "value": "$479.96"
      }
    ]
  }
}
```

### **Test 4: عرض معلومات السلة**

```bash
curl -X GET https://avit.josequal.net/apimobile/cart/info \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
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
        "qty": 3,
        "price": "$119.99",
        "row_total": "$359.97",
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
          "size": "L"
        }
      }
    ],
    "cart_qty": 4,
    "has_coupon": false,
    "coupon": "",
    "totals": [
      {
        "label": "Subtotal",
        "value": "$479.96"
      },
      {
        "label": "Shipping",
        "value": "$0.00"
      },
      {
        "label": "Grand Total",
        "value": "$479.96"
      }
    ]
  }
}
```

### **Test 5: تحديث كمية عنصر**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/update \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "item_id": "1915",
    "qty": 5
  }'
```

**Expected Response:**
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
        "qty": 5,
        "price": "$119.99",
        "row_total": "$599.95",
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
          "size": "L"
        }
      }
    ],
    "cart_qty": 6,
    "has_coupon": false,
    "coupon": "",
    "totals": [
      {
        "label": "Subtotal",
        "value": "$719.94"
      },
      {
        "label": "Shipping",
        "value": "$0.00"
      },
      {
        "label": "Grand Total",
        "value": "$719.94"
      }
    ]
  }
}
```

### **Test 6: حذف عنصر محدد**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/delete \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "item_id": "1915"
  }'
```

**Expected Response:**
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
          "size": "L"
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

### **Test 7: حذف كل العناصر لمنتج معين**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/delete \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261"
  }'
```

**Expected Response:**
```json
{
  "status": true,
  "message": "All items for product removed successfully",
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
        "label": "Shipping",
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

## 🚨 اختبار حالات الخطأ

### **Test 8: محاولة إضافة منتج بدون product_id**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "quantity": 1,
    "color": "#FF0000"
  }'
```

**Expected Response:**
```json
{
  "status": false,
  "message": "Product ID is required",
  "data": []
}
```

### **Test 9: محاولة إضافة منتج غير موجود**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "999999",
    "quantity": 1
  }'
```

**Expected Response:**
```json
{
  "status": false,
  "message": "Product not found",
  "data": []
}
```

### **Test 10: محاولة تحديث كمية بدون item_id**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/update \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "qty": 5
  }'
```

**Expected Response:**
```json
{
  "status": false,
  "message": "Item ID and quantity are required",
  "data": []
}
```

### **Test 11: محاولة تحديث كمية إلى 0**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/update \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "item_id": "1915",
    "qty": 0
  }'
```

**Expected Response:**
```json
{
  "status": false,
  "message": "Quantity must be greater than 0",
  "data": []
}
```

### **Test 12: محاولة الوصول بدون مصادقة**

```bash
curl -X GET https://avit.josequal.net/apimobile/cart/info
```

**Expected Response:**
```json
{
  "status": false,
  "message": "Unauthorized",
  "data": []
}
```

## 📱 اختبار من Postman

### **1. إنشاء Collection جديد**
- Name: `Cart API Tests`
- Description: `Testing Cart API endpoints`

### **2. إعداد Environment Variables**
```
base_url: https://avit.josequal.net
token: YOUR_ACTUAL_TOKEN
```

### **3. إنشاء Requests**

#### **Add Product Request:**
```
Method: POST
URL: {{base_url}}/apimobile/cart/add
Headers:
  Authorization: Bearer {{token}}
  Content-Type: application/json
Body (raw JSON):
{
  "product_id": "261",
  "quantity": 1,
  "color": "#FF0000",
  "size": "M"
}
```

#### **Get Cart Info Request:**
```
Method: GET
URL: {{base_url}}/apimobile/cart/info
Headers:
  Authorization: Bearer {{token}}
```

#### **Update Cart Item Request:**
```
Method: POST
URL: {{base_url}}/apimobile/cart/update
Headers:
  Authorization: Bearer {{token}}
  Content-Type: application/json
Body (raw JSON):
{
  "item_id": "1915",
  "qty": 3
}
```

#### **Delete Cart Item Request:**
```
Method: POST
URL: {{base_url}}/apimobile/cart/delete
Headers:
  Authorization: Bearer {{token}}
  Content-Type: application/json
Body (raw JSON):
{
  "item_id": "1915"
}
```

## 🔄 سيناريو اختبار كامل

### **الخطوات:**
1. **تسجيل الدخول** للحصول على token
2. **إضافة منتج جديد** إلى السلة
3. **إضافة نفس المنتج** بنفس الخيارات (يجب دمج الكمية)
4. **إضافة نفس المنتج** بخيارات مختلفة (يجب إنشاء item جديد)
5. **عرض معلومات السلة** للتأكد من المحتويات
6. **تحديث كمية** أحد العناصر
7. **حذف عنصر محدد** من السلة
8. **حذف كل العناصر** لمنتج معين
9. **عرض السلة الفارغة**

### **النتائج المتوقعة:**
- ✅ إضافة المنتجات تعمل بشكل صحيح
- ✅ دمج الكميات للمنتجات المتشابهة
- ✅ إنشاء items جديدة للمنتجات بخيارات مختلفة
- ✅ تحديث الكميات يعمل بشكل صحيح
- ✅ حذف العناصر يعمل بشكل صحيح
- ✅ حساب الإجماليات يعمل بشكل صحيح
- ✅ معالجة الأخطاء تعمل بشكل صحيح
- ✅ المصادقة تعمل بشكل صحيح

## 📊 تقرير الاختبار

### **الوظائف المختبرة:**
- [x] إضافة منتج جديد
- [x] دمج الكميات للمنتجات المتشابهة
- [x] إنشاء items جديدة للمنتجات بخيارات مختلفة
- [x] عرض معلومات السلة
- [x] تحديث كمية عنصر
- [x] حذف عنصر محدد
- [x] حذف كل العناصر لمنتج معين
- [x] معالجة الأخطاء
- [x] المصادقة

### **الحالات المختبرة:**
- [x] الحالات الناجحة
- [x] حالات الخطأ
- [x] حالات المصادقة
- [x] حالات البيانات غير الصحيحة

### **الملاحظات:**
- جميع APIs تعمل بشكل صحيح
- معالجة الأخطاء تعمل بشكل مناسب
- المصادقة تعمل بشكل صحيح
- الأداء جيد
- الاستجابات متسقة
