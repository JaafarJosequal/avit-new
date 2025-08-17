# 🧪 اختبار الخيارات في Cart API

## المشكلة
الخيارات (color, size) لا يتم حفظها أو عرضها في Cart API.

## الحل المطبق
تم تحديث الكود لمعالجة الخيارات بشكل صحيح:

### 1. تحديث وظيفة `addToCart`
- استخدام `DataObject` لحفظ الخيارات
- حفظ الخيارات في `buyRequest`

### 2. تحديث وظيفة `getItemOptions`
- استرجاع الخيارات من `buyRequest`
- استرجاع الخيارات من `productOptions` كبديل

### 3. تحديث وظيفة `findExistingCartItem`
- عدم دمج الكميات إذا لم تكن هناك خيارات
- مقارنة الخيارات بشكل صحيح

### 4. تحديث وظيفة `compareOptions`
- مقارنة الخيارات بشكل دقيق
- التعامل مع الحالات الفارغة

## 🧪 اختبار الخيارات

### **Test 1: إضافة منتج بحجم S**
```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 1,
    "size": "S"
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
        "id": "1916",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 1,
        "price": "$119.99",
        "row_total": "$119.99",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "size": "S"
        }
      }
    ],
    "cart_qty": 1,
    "has_coupon": false,
    "coupon": "",
    "totals": [...]
  }
}
```

### **Test 2: إضافة نفس المنتج بحجم M**
```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 1,
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
        "id": "1916",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 1,
        "price": "$119.99",
        "row_total": "$119.99",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "size": "S"
        }
      },
      {
        "id": "1917",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 1,
        "price": "$119.99",
        "row_total": "$119.99",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "size": "M"
        }
      }
    ],
    "cart_qty": 2,
    "has_coupon": false,
    "coupon": "",
    "totals": [...]
  }
}
```

### **Test 3: إضافة نفس المنتج بحجم S مرة أخرى (يجب دمج الكمية)**
```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 2,
    "size": "S"
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
        "id": "1916",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 3,
        "price": "$119.99",
        "row_total": "$359.97",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "size": "S"
        }
      },
      {
        "id": "1917",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 1,
        "price": "$119.99",
        "row_total": "$119.99",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": {
          "size": "M"
        }
      }
    ],
    "cart_qty": 4,
    "has_coupon": false,
    "coupon": "",
    "totals": [...]
  }
}
```

## 🔧 كيفية عمل الخيارات

### **1. حفظ الخيارات:**
```php
// إنشاء buyRequest جديد
$buyRequest = new \Magento\Framework\DataObject();
$buyRequest->setQty($quantity);

// إضافة الخيارات
if (!empty($options)) {
    foreach ($options as $key => $value) {
        $buyRequest->setData($key, $value);
    }
}

// إضافة المنتج مع الخيارات
$this->cart->addProduct($product, $buyRequest);
```

### **2. استرجاع الخيارات:**
```php
// من buyRequest
if ($item->getBuyRequest()) {
    $buyRequest = $item->getBuyRequest();
    if ($buyRequest->getColor()) {
        $options['color'] = $buyRequest->getColor();
    }
    if ($buyRequest->getSize()) {
        $options['size'] = $buyRequest->getSize();
    }
}

// من productOptions (كبديل)
$productOptions = $item->getProductOptions();
if (isset($productOptions['info_buyRequest'])) {
    $infoBuyRequest = $productOptions['info_buyRequest'];
    // استرجاع الخيارات
}
```

### **3. مقارنة الخيارات:**
```php
// مقارنة دقيقة للخيارات
private function compareOptions($options1, $options2) {
    // إذا كانت كلاهما فارغة، فهي متشابهة
    if (empty($options1) && empty($options2)) {
        return true;
    }
    
    // إذا كانت إحداهما فارغة والأخرى لا، فهي مختلفة
    if (empty($options1) || empty($options2)) {
        return false;
    }
    
    // مقارنة جميع الخيارات
    foreach ($options1 as $key => $value) {
        if (!isset($options2[$key]) || $options2[$key] !== $value) {
            return false;
        }
    }
    
    return true;
}
```

## 📱 أمثلة عملية

### **إضافة منتج بلون وحجم:**
```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 1,
    "color": "#FF0000",
    "size": "L"
  }'
```

### **إضافة منتج بحجم فقط:**
```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 1,
    "size": "XL"
  }'
```

### **إضافة منتج بلون فقط:**
```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 1,
    "color": "#00FF00"
  }'
```

## ✅ النتائج المتوقعة

1. **الخيارات تُحفظ بشكل صحيح** في `buyRequest`
2. **الخيارات تُعرض بشكل صحيح** في استجابة API
3. **دمج الكميات** للمنتجات المتشابهة بنفس الخيارات
4. **إنشاء items جديدة** للمنتجات بخيارات مختلفة
5. **عدم دمج الكميات** للمنتجات بدون خيارات

## 🔍 استكشاف الأخطاء

### **إذا لم تظهر الخيارات:**
1. تأكد من أن الخيارات تُرسل في الطلب
2. تحقق من أن `buyRequest` يتم حفظه بشكل صحيح
3. تحقق من وظيفة `getItemOptions`

### **إذا لم يتم دمج الكميات:**
1. تحقق من وظيفة `findExistingCartItem`
2. تحقق من وظيفة `compareOptions`
3. تأكد من أن الخيارات متطابقة تماماً

### **إذا لم يتم إنشاء items جديدة:**
1. تحقق من أن الخيارات مختلفة
2. تحقق من وظيفة `addProduct`
3. تأكد من حفظ `buyRequest`
