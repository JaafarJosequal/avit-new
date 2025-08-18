# 🧪 اختبار Address APIs

## 🔑 الحصول على Token

### 1. تسجيل الدخول
```bash
curl -X POST "https://avit.josequal.net/V1/mobile/login" \
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

## 📍 اختبار Address APIs

### **Test 1: إضافة عنوان جديد**

```bash
curl -X POST "https://avit.josequal.net/apimobile/address/add" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "firstname": "John",
    "lastname": "Doe",
    "street": ["123 Main Street"],
    "city": "New York",
    "region": "NY",
    "postcode": "10001",
    "country_id": "US",
    "telephone": "1234567890",
    "company": "My Company"
  }'
```

**Expected Response:**
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

### **Test 2: إضافة عنوان آخر**

```bash
curl -X POST "https://avit.josequal.net/apimobile/address/add" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "firstname": "John",
    "lastname": "Doe",
    "street": ["456 Oak Avenue"],
    "city": "Los Angeles",
    "region": "CA",
    "postcode": "90210",
    "country_id": "US",
    "telephone": "0987654321",
    "company": "My Company"
  }'
```

---

### **Test 3: عرض قائمة العناوين**

```bash
curl -X GET "https://avit.josequal.net/apimobile/address/getlist" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
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
        "street": ["123 Main Street"],
        "city": "New York",
        "region": "NY",
        "region_id": 43,
        "postcode": "10001",
        "country_id": "US",
        "telephone": "1234567890",
        "fax": null,
        "vat_id": null,
        "is_default_billing": true,
        "is_default_shipping": false
      },
      {
        "id": 124,
        "type": "shipping",
        "firstname": "John",
        "lastname": "Doe",
        "company": "My Company",
        "street": ["456 Oak Avenue"],
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

---

### **Test 4: تعديل العنوان الأول**

```bash
curl -X POST "https://avit.josequal.net/apimobile/address/edit" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "address_id": 123,
    "firstname": "Jane",
    "city": "Brooklyn",
    "postcode": "11201"
  }'
```

**Expected Response:**
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

### **Test 5: تعديل العنوان الثاني**

```bash
curl -X POST "https://avit.josequal.net/apimobile/address/edit" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "address_id": 124,
    "company": "New Company Name",
    "fax": "1234567890"
  }'
```

---

### **Test 6: عرض العناوين بعد التعديل**

```bash
curl -X GET "https://avit.josequal.net/apimobile/address/getlist" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

### **Test 7: حذف العنوان الثاني**

```bash
curl -X POST "https://avit.josequal.net/apimobile/address/delete" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "address_id": 124
  }'
```

**Expected Response:**
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

### **Test 8: عرض العناوين بعد الحذف**

```bash
curl -X GET "https://avit.josequal.net/apimobile/address/getlist" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
```json
{
  "status": true,
  "message": "Addresses retrieved successfully",
  "data": {
    "addresses": [
      {
        "id": 123,
        "type": "billing",
        "firstname": "Jane",
        "lastname": "Doe",
        "company": "My Company",
        "street": ["123 Main Street"],
        "city": "Brooklyn",
        "region": "NY",
        "region_id": 43,
        "postcode": "11201",
        "country_id": "US",
        "telephone": "1234567890",
        "fax": null,
        "vat_id": null,
        "is_default_billing": true,
        "is_default_shipping": false
      }
    ],
    "total_count": 1
  }
}
```

## 🧪 اختبار الحالات الاستثنائية

### **Test 9: إضافة عنوان بدون حقول مطلوبة**

```bash
curl -X POST "https://avit.josequal.net/apimobile/address/add" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "firstname": "John",
    "lastname": "Doe"
  }'
```

**Expected Response:**
```json
{
  "status": false,
  "message": "Field 'street' is required",
  "data": []
}
```

---

### **Test 10: تعديل عنوان غير موجود**

```bash
curl -X POST "https://avit.josequal.net/apimobile/address/edit" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "address_id": 999,
    "firstname": "Test"
  }'
```

**Expected Response:**
```json
{
  "status": false,
  "message": "Address not found",
  "data": []
}
```

---

### **Test 11: حذف عنوان بدون ID**

```bash
curl -X POST "https://avit.josequal.net/apimobile/address/delete" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Expected Response:**
```json
{
  "status": false,
  "message": "Address ID is required",
  "data": []
}
```

---

### **Test 12: طلب بدون مصادقة**

```bash
curl -X GET "https://avit.josequal.net/apimobile/address/getlist"
```

**Expected Response:**
```json
{
  "status": false,
  "message": "Unauthorized",
  "data": []
}
```

## 📱 اختبار من التطبيق

### **Android Test:**
```kotlin
// Test adding address
val addressData = JSONObject().apply {
    put("firstname", "Test")
    put("lastname", "User")
    put("street", JSONArray().put("Test Street"))
    put("city", "Test City")
    put("region", "Test Region")
    put("postcode", "12345")
    put("country_id", "US")
    put("telephone", "1234567890")
}

// Make API call
val response = apiService.addAddress("Bearer $token", addressData)
if (response.status) {
    Log.d("Address", "Address added: ${response.data.address_id}")
} else {
    Log.e("Address", "Error: ${response.message}")
}
```

### **iOS Test:**
```swift
// Test adding address
let addressData: [String: Any] = [
    "firstname": "Test",
    "lastname": "User",
    "street": ["Test Street"],
    "city": "Test City",
    "region": "Test Region",
    "postcode": "12345",
    "country_id": "US",
    "telephone": "1234567890"
]

// Make API call
apiService.addAddress(token: token, data: addressData) { result in
    switch result {
    case .success(let response):
        if response.status {
            print("Address added: \(response.data.address_id)")
        } else {
            print("Error: \(response.message)")
        }
    case .failure(let error):
        print("Network error: \(error)")
    }
}
```

## 🔍 مراقبة النتائج

### **التحقق من قاعدة البيانات:**
```sql
-- عرض عناوين العميل
SELECT * FROM customer_address_entity WHERE parent_id = CUSTOMER_ID;

-- عرض معلومات العميل
SELECT * FROM customer_entity WHERE entity_id = CUSTOMER_ID;
```

### **التحقق من Logs:**
```bash
# Magento logs
tail -f var/log/system.log
tail -f var/log/exception.log

# PHP error logs
tail -f /var/log/php_errors.log
```

## ✅ قائمة التحقق

- [ ] إضافة عنوان جديد يعمل
- [ ] تعديل العنوان يعمل
- [ ] حذف العنوان يعمل
- [ ] عرض قائمة العناوين يعمل
- [ ] التحقق من الحقول المطلوبة يعمل
- [ ] المصادقة تعمل بشكل صحيح
- [ ] معالجة الأخطاء تعمل
- [ ] البيانات تُحفظ في قاعدة البيانات
- [ ] Region ID يتم تحديده تلقائياً
- [ ] العناوين تُعرض بالترتيب الصحيح

## 🚀 نصائح للاختبار

1. **ابدأ بإضافة عنوان** قبل اختبار العمليات الأخرى
2. **احفظ Address ID** من الاستجابة الأولى لاستخدامه في الاختبارات اللاحقة
3. **اختبر الحالات الاستثنائية** للتأكد من معالجة الأخطاء
4. **تحقق من قاعدة البيانات** للتأكد من حفظ البيانات
5. **اختبر من التطبيق** للتأكد من عمل API في البيئة الحقيقية

هذه الاختبارات تضمن أن **Address APIs تعمل بشكل صحيح** وتتعامل مع جميع الحالات.
