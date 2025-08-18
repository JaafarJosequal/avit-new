# 🏠 Address Management APIs

## نظرة عامة
هذا الدليل يوضح كيفية استخدام APIs إدارة العناوين في النظام. جميع الـ APIs تتطلب مصادقة المستخدم.

## 🔑 المصادقة
جميع الـ APIs تتطلب مصادقة من خلال Bearer Token:

```bash
Authorization: Bearer YOUR_TOKEN_HERE
```

## 📍 APIs المتاحة

### 1. إضافة عنوان جديد
**POST** `/apimobile/address/add`

**المعاملات المطلوبة:**
```json
{
  "firstname": "John",
  "lastname": "Doe",
  "street": ["123 Main St"],
  "city": "New York",
  "region": "NY",
  "postcode": "10001",
  "country_id": "US",
  "telephone": "1234567890"
}
```

**المعاملات الاختيارية:**
```json
{
  "company": "Company Name",
  "fax": "1234567891",
  "vat_id": "VAT123456"
}
```

**مثال على الطلب:**
```bash
curl -X POST "https://your-domain.com/apimobile/address/add" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "firstname": "John",
    "lastname": "Doe",
    "street": ["123 Main St"],
    "city": "New York",
    "region": "NY",
    "postcode": "10001",
    "country_id": "US",
    "telephone": "1234567890",
    "company": "My Company"
  }'
```

**الاستجابة المتوقعة:**
```json
{
  "status": true,
  "message": "Address added successfully",
  "data": {
    "address_id": 123,
    "message": "Address has been added successfully"
  }
}
```

---

### 2. تعديل عنوان موجود
**POST** `/apimobile/address/edit`

**المعاملات المطلوبة:**
```json
{
  "address_id": 123
}
```

**المعاملات الاختيارية (أي منها يمكن تعديله):**
```json
{
  "firstname": "Jane",
  "lastname": "Smith",
  "street": ["456 Oak Ave"],
  "city": "Los Angeles",
  "region": "CA",
  "postcode": "90210",
  "country_id": "US",
  "telephone": "0987654321",
  "company": "New Company",
  "fax": "0987654322",
  "vat_id": "VAT654321"
}
```

**مثال على الطلب:**
```bash
curl -X POST "https://your-domain.com/apimobile/address/edit" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "address_id": 123,
    "firstname": "Jane",
    "city": "Los Angeles",
    "region": "CA"
  }'
```

**الاستجابة المتوقعة:**
```json
{
  "status": true,
  "message": "Address updated successfully",
  "data": {
    "address_id": 123,
    "message": "Address has been updated successfully"
  }
}
```

---

### 3. حذف عنوان
**POST** `/apimobile/address/delete`

**المعاملات المطلوبة:**
```json
{
  "address_id": 123
}
```

**مثال على الطلب:**
```bash
curl -X POST "https://your-domain.com/apimobile/address/delete" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "address_id": 123
  }'
```

**الاستجابة المتوقعة:**
```json
{
  "status": true,
  "message": "Address deleted successfully",
  "data": {
    "message": "Address has been deleted successfully"
  }
}
```

---

### 4. قائمة العناوين
**GET** `/apimobile/address/getlist`

**لا يحتاج معاملات**

**مثال على الطلب:**
```bash
curl -X GET "https://your-domain.com/apimobile/address/getlist" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**الاستجابة المتوقعة:**
```json
{
  "status": true,
  "message": "Addresses retrieved successfully",
  "data": {
    "addresses": [
      {
        "id": 123,
        "type": "billing",
        "firstname": "John",
        "lastname": "Doe",
        "company": "My Company",
        "street": ["123 Main St"],
        "city": "New York",
        "region": "NY",
        "region_id": 43,
        "postcode": "10001",
        "country_id": "US",
        "telephone": "1234567890",
        "fax": "1234567891",
        "vat_id": "VAT123456",
        "is_default_billing": true,
        "is_default_shipping": false
      },
      {
        "id": 124,
        "type": "shipping",
        "firstname": "John",
        "lastname": "Doe",
        "company": "My Company",
        "street": ["456 Oak Ave"],
        "city": "Los Angeles",
        "region": "CA",
        "region_id": 12,
        "postcode": "90210",
        "country_id": "US",
        "telephone": "0987654321",
        "fax": null,
        "vat_id": null,
        "is_default_billing": false,
        "is_default_shipping": true
      }
    ],
    "total_count": 2
  }
}
```

## 📋 أنواع العناوين

### **Billing Address** (عنوان الفواتير)
- العنوان المستخدم للفواتير والمدفوعات
- يتم تحديده كـ `is_default_billing: true`

### **Shipping Address** (عنوان الشحن)
- العنوان المستخدم لإرسال الطلبات
- يتم تحديده كـ `is_default_shipping: true`

### **Other Addresses** (عناوين أخرى)
- عناوين إضافية للمستخدم
- يمكن استخدامها للفواتير أو الشحن

## 🔍 معالجة الأخطاء

### **أخطاء المصادقة:**
```json
{
  "status": false,
  "message": "Unauthorized",
  "data": []
}
```

### **أخطاء التحقق من البيانات:**
```json
{
  "status": false,
  "message": "Field 'firstname' is required",
  "data": []
}
```

### **أخطاء عدم وجود العنوان:**
```json
{
  "status": false,
  "message": "Address not found",
  "data": []
}
```

### **أخطاء الملكية:**
```json
{
  "status": false,
  "message": "Address does not belong to current customer",
  "data": []
}
```

## ⚠️ ملاحظات مهمة

1. **المصادقة مطلوبة** لجميع العمليات
2. **Address ID مطلوب** لعمليات التعديل والحذف
3. **العنوان يجب أن ينتمي للمستخدم الحالي**
4. **Region ID يتم تحديده تلقائياً** من Region name
5. **Street يجب أن يكون array** (يمكن إرسال string واحد)
6. **جميع الحقول المطلوبة يجب أن تكون موجودة** عند الإضافة
7. **Region Handling** - النظام يتعامل مع Region name و Region ID تلقائياً

## 🔧 معالجة Region (مهم!)

### **المشكلة:**
Magento يتوقع `RegionInterface` object وليس string في `setRegion()` method.

### **الحل المطبق:**
- النظام يحاول أولاً تحويل Region name إلى Region ID
- إذا نجح التحويل، يستخدم `setRegionId()`
- إذا فشل التحويل، يستخدم `setRegion()` كـ fallback

### **أمثلة على Region:**
```json
// الولايات المتحدة
"region": "NY"        → region_id: 43
"region": "CA"        → region_id: 12
"region": "TX"        → region_id: 57

// المملكة العربية السعودية
"region": "Riyadh"    → region_id: 123
"region": "Jeddah"    → region_id: 124
```

### **كيفية العمل:**
1. **أرسل Region name** (مثل "NY", "CA")
2. **النظام يحوله تلقائياً** إلى Region ID
3. **يتم حفظ العنوان** بدون أخطاء

## 🚨 الإصلاحات المطبقة

### **1. إصلاح TypeError (Region):**
**المشكلة:** `setRegion()` يتوقع RegionInterface object
**الحل:** استخدام `setRegionId()` مع fallback لـ `setRegion()`

### **2. إصلاح Method Not Found:**
**المشكلة:** `setSaveInAddressBook()` method لا يوجد
**الحل:** إزالة الاستدعاء - العنوان يُحفظ تلقائياً في address book

### **3. معالجة Region ID:**
**المشكلة:** Region name لا يتم تحويله لـ Region ID
**الحل:** إضافة method `getRegionId()` للتحويل التلقائي

## 🧪 أمثلة على الاختبار

### **اختبار إضافة عنوان:**
```bash
# 1. تسجيل الدخول للحصول على token
curl -X POST "https://your-domain.com/V1/mobile/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "password": "password123"
  }'

# 2. إضافة عنوان جديد
curl -X POST "https://your-domain.com/apimobile/address/add" \
  -H "Authorization: Bearer TOKEN_FROM_STEP_1" \
  -H "Content-Type: application/json" \
  -d '{
    "firstname": "Test",
    "lastname": "User",
    "street": ["Test Street"],
    "city": "Test City",
    "region": "Test Region",
    "postcode": "12345",
    "country_id": "US",
    "telephone": "1234567890"
  }'
```

### **اختبار قائمة العناوين:**
```bash
curl -X GET "https://your-domain.com/apimobile/address/getlist" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🔧 استكشاف الأخطاء

### **مشكلة: "Field is required"**
**الحل:** تأكد من إرسال جميع الحقول المطلوبة

### **مشكلة: "Address not found"**
**الحل:** تأكد من صحة Address ID

### **مشكلة: "Unauthorized"**
**الحل:** تأكد من صحة Token وتجديده إذا لزم الأمر

### **مشكلة: "Address does not belong to current customer"**
**الحل:** تأكد من أن العنوان ينتمي للمستخدم الحالي

## 📱 استخدام في التطبيقات

### **Android (Kotlin):**
```kotlin
val addressData = JSONObject().apply {
    put("firstname", "John")
    put("lastname", "Doe")
    put("street", JSONArray().put("123 Main St"))
    put("city", "New York")
    put("region", "NY")
    put("postcode", "10001")
    put("country_id", "US")
    put("telephone", "1234567890")
}

val request = Request.Builder()
    .url("https://your-domain.com/apimobile/address/add")
    .addHeader("Authorization", "Bearer $token")
    .post(addressData.toString().toRequestBody("application/json".toMediaType()))
    .build()
```

### **iOS (Swift):**
```swift
let addressData: [String: Any] = [
    "firstname": "John",
    "lastname": "Doe",
    "street": ["123 Main St"],
    "city": "New York",
    "region": "NY",
    "postcode": "10001",
    "country_id": "US",
    "telephone": "1234567890"
]

let url = URL(string: "https://your-domain.com/apimobile/address/add")!
var request = URLRequest(url: url)
request.httpMethod = "POST"
request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
request.setValue("application/json", forHTTPHeaderField: "Content-Type")
request.httpBody = try? JSONSerialization.data(withJSONObject: addressData)
```

هذه الـ APIs توفر **إدارة شاملة للعناوين** مع **أمان عالي** و**سهولة في الاستخدام**.
