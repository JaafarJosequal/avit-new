# 🎨 Cart API Options Guide

## نظرة عامة
هذا الدليل يوضح كيفية عمل الخيارات (Options) في Cart API وكيفية إضافة منتجات بخيارات مختلفة.

## 🔧 الخيارات المدعومة

### **الخيارات الأساسية:**
- `color` - لون المنتج
- `size` - حجم المنتج
- `material` - مادة المنتج
- `style` - نمط المنتج
- `pattern` - نمط/تصميم المنتج
- `brand` - العلامة التجارية
- `model` - موديل المنتج

### **خيارات مخصصة:**
- `custom_options` - خيارات مخصصة إضافية

## 📝 أمثلة الاستخدام

### **1. إضافة منتج بلون وحجم:**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 1,
    "color": "#FF0000",
    "size": "M"
  }'
```

**Response:**
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
        "options": [
          {
            "key": "color",
            "label": "Color",
            "value": "#FF0000"
          },
          {
            "key": "size",
            "label": "Size",
            "value": "M"
          }
        ],
        "has_options": true,
        "options_summary": "Color: #FF0000, Size: M"
      }
    ],
    "cart_qty": 1,
    "totals": [...]
  }
}
```

### **2. إضافة نفس المنتج بنفس الخيارات (يتم دمج الكمية):**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 2,
    "color": "#FF0000",
    "size": "M"
  }'
```

**Response:**
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
        "options": [
          {
            "key": "color",
            "label": "Color",
            "value": "#FF0000"
          },
          {
            "key": "size",
            "label": "Size",
            "value": "M"
          }
        ],
        "has_options": true,
        "options_summary": "Color: #FF0000, Size: M"
      }
    ],
    "cart_qty": 3,
    "totals": [...]
  }
}
```

### **3. إضافة نفس المنتج بخيارات مختلفة (يتم إنشاء item جديد):**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 1,
    "color": "#00FF00",
    "size": "L"
  }'
```

**Response:**
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
        "options": [
          {
            "key": "color",
            "label": "Color",
            "value": "#FF0000"
          },
          {
            "key": "size",
            "label": "Size",
            "value": "M"
          }
        ],
        "has_options": true,
        "options_summary": "Color: #FF0000, Size: M"
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
        "options": [
          {
            "key": "color",
            "label": "Color",
            "value": "#00FF00"
          },
          {
            "key": "size",
            "label": "Size",
            "value": "L"
          }
        ],
        "has_options": true,
        "options_summary": "Color: #00FF00, Size: L"
      }
    ],
    "cart_qty": 4,
    "totals": [...]
  }
}
```

### **4. إضافة منتج بخيارات متعددة:**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 1,
    "color": "#0000FF",
    "size": "XL",
    "material": "Premium Leather",
    "style": "Modern",
    "brand": "Apple",
    "model": "2024"
  }'
```

**Response:**
```json
{
  "status": true,
  "message": "Product added successfully",
  "data": {
    "items": [
      {
        "id": "1917",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 1,
        "price": "$119.99",
        "row_total": "$119.99",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": [
          {
            "key": "color",
            "label": "Color",
            "value": "#0000FF"
          },
          {
            "key": "size",
            "label": "Size",
            "value": "XL"
          },
          {
            "key": "material",
            "label": "Material",
            "value": "Premium Leather"
          },
          {
            "key": "style",
            "label": "Style",
            "value": "Modern"
          },
          {
            "key": "brand",
            "label": "Brand",
            "value": "Apple"
          },
          {
            "key": "model",
            "label": "Model",
            "value": "2024"
          }
        ],
        "has_options": true,
        "options_summary": "Color: #0000FF, Size: XL, Material: Premium Leather, Style: Modern, Brand: Apple, Model: 2024"
      }
    ],
    "cart_qty": 5,
    "totals": [...]
  }
}
```

### **5. إضافة منتج بخيارات مخصصة:**

```bash
curl -X POST https://avit.josequal.net/apimobile/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "261",
    "quantity": 1,
    "color": "#FFD700",
    "size": "S",
    "custom_options": {
      "warranty": "2 Years",
      "delivery": "Express",
      "gift_wrap": "Yes"
    }
  }'
```

**Response:**
```json
{
  "status": true,
  "message": "Product added successfully",
  "data": {
    "items": [
      {
        "id": "1918",
        "product_id": "261",
        "name": "3-in-1 Wireless Charger for Apple Devices",
        "sku": "FS-1046",
        "qty": 1,
        "price": "$119.99",
        "row_total": "$119.99",
        "image": "https://avit.josequal.net/media/catalog/product/xyz.jpg",
        "options": [
          {
            "key": "color",
            "label": "Color",
            "value": "#FFD700"
          },
          {
            "key": "size",
            "label": "Size",
            "value": "S"
          },
          {
            "key": "warranty",
            "label": "Warranty",
            "value": "2 Years"
          },
          {
            "key": "delivery",
            "label": "Delivery",
            "value": "Express"
          },
          {
            "key": "gift_wrap",
            "label": "Gift Wrap",
            "value": "Yes"
          }
        ],
        "has_options": true,
        "options_summary": "Color: #FFD700, Size: S, Warranty: 2 Years, Delivery: Express, Gift Wrap: Yes"
      }
    ],
    "cart_qty": 6,
    "totals": [...]
  }
}
```

## 📊 بنية البيانات

### **عنصر السلة مع الخيارات:**
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
  "options": [              // مصفوفة الخيارات
    {
      "key": "string",      // مفتاح الخيار
      "label": "string",    // تسمية الخيار
      "value": "string"     // قيمة الخيار
    }
  ],
  "has_options": "boolean", // هل يحتوي على خيارات
  "options_summary": "string" // ملخص الخيارات كنص
}
```

## 🔄 منطق دمج الكميات

### **قواعد الدمج:**
1. **إذا كان المنتج موجود بنفس الخيارات** → يتم دمج الكمية
2. **إذا كان المنتج موجود بخيارات مختلفة** → يتم إنشاء item جديد
3. **إذا كان المنتج موجود بدون خيارات** → لا يتم الدمج مع المنتجات التي لها خيارات

### **أمثلة:**

#### **سيناريو 1: دمج الكميات**
```
المنتج الأول: product_id=261, color=#FF0000, size=M, qty=1
المنتج الثاني: product_id=261, color=#FF0000, size=M, qty=2
النتيجة: item واحد مع qty=3
```

#### **سيناريو 2: إنشاء item جديد**
```
المنتج الأول: product_id=261, color=#FF0000, size=M, qty=1
المنتج الثاني: product_id=261, color=#00FF00, size=M, qty=1
النتيجة: itemين منفصلين
```

#### **سيناريو 3: عدم الدمج**
```
المنتج الأول: product_id=261, qty=1 (بدون خيارات)
المنتج الثاني: product_id=261, color=#FF0000, size=M, qty=1
النتيجة: itemين منفصلين
```

## 🎯 أفضل الممارسات

### **1. استخدام الخيارات:**
- استخدم خيارات واضحة ومحددة
- تجنب القيم الفارغة أو null
- استخدم تنسيقات متسقة (مثل hex codes للألوان)

### **2. إدارة السلة:**
- استخدم `item_id` للتحديث والحذف
- تحقق من `has_options` قبل عرض الخيارات
- استخدم `options_summary` للعرض السريع

### **3. معالجة الأخطاء:**
- تحقق من وجود `product_id`
- تأكد من صحة قيم الخيارات
- تعامل مع الأخطاء بشكل مناسب

## 🧪 اختبار الخيارات

### **اختبار دمج الكميات:**
```bash
# إضافة المنتج الأول
curl -X POST /apimobile/cart/add -d '{"product_id": "261", "color": "#FF0000", "size": "M"}'

# إضافة نفس المنتج بنفس الخيارات
curl -X POST /apimobile/cart/add -d '{"product_id": "261", "color": "#FF0000", "size": "M"}'

# يجب أن تكون النتيجة: item واحد مع qty=2
```

### **اختبار إنشاء items جديدة:**
```bash
# إضافة المنتج الأول
curl -X POST /apimobile/cart/add -d '{"product_id": "261", "color": "#FF0000", "size": "M"}'

# إضافة نفس المنتج بخيارات مختلفة
curl -X POST /apimobile/cart/add -d '{"product_id": "261", "color": "#00FF00", "size": "L"}'

# يجب أن تكون النتيجة: itemين منفصلين
```

## 📝 ملاحظات مهمة

1. **الخيارات فارغة:** إذا لم يتم تحديد خيارات، سيتم إضافة المنتج بدون خيارات
2. **حساسية الأحرف:** الخيارات حساسة للأحرف الكبيرة والصغيرة
3. **الحد الأقصى:** لا يوجد حد أقصى لعدد الخيارات
4. **الأداء:** دمج الكميات يحسن الأداء ويقلل من عدد items في السلة
5. **التوافق:** جميع الخيارات متوافقة مع Magento 2 القياسي

## 🔧 استكشاف الأخطاء

### **مشكلة: الخيارات لا تظهر**
- تأكد من إرسال الخيارات في الطلب
- تحقق من أن الخيارات ليست فارغة
- تأكد من صحة تنسيق البيانات

### **مشكلة: لا يتم دمج الكميات**
- تحقق من تطابق جميع الخيارات
- تأكد من أن الخيارات ليست فارغة
- تحقق من صحة `product_id`

### **مشكلة: خطأ في الخيارات**
- تأكد من صحة تنسيق JSON
- تحقق من أن الخيارات ليست null
- تأكد من صحة قيم الخيارات
