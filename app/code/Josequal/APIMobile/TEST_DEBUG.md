# 🐛 Debug Test for Address GetList API

## المشكلة:
```
{
    "status": true,
    "message": "Addresses retrieved successfully"
}
```
**لا يوجد `data` في الاستجابة!**

## 🔍 الأسباب المحتملة:

### 1. **العميل لا يملك عناوين:**
- العميل جديد ولم يضف عناوين بعد
- العناوين تم حذفها
- مشكلة في قاعدة البيانات

### 2. **مشكلة في Authentication:**
- Token غير صحيح
- العميل غير مسجل دخول
- مشكلة في customer session

### 3. **مشكلة في Code:**
- `successStatus` method لا يقبل `data` parameter
- مشكلة في `formatAddress` method
- مشكلة في `getAddresses` method

## 🧪 اختبارات Debug:

### **Test 1: التحقق من Authentication**
```bash
curl -X GET "https://avit.josequal.net/apimobile/address/getlist" \
  -H "Authorization: Bearer INVALID_TOKEN"
```

**Expected Response:**
```json
{
  "status": false,
  "message": "Customer not logged in",
  "data": []
}
```

### **Test 2: اختبار مع Token صحيح**
```bash
curl -X GET "https://avit.josequal.net/apimobile/address/getlist" \
  -H "Authorization: Bearer YOUR_VALID_TOKEN"
```

**Expected Response (مع Debug):**
```json
{
  "status": true,
  "message": "Addresses retrieved successfully",
  "data": {
    "addresses": [],
    "total_count": 0,
    "debug": {
      "customer_id": 123,
      "default_billing": null,
      "default_shipping": null,
      "total_addresses": 0
    }
  }
}
```

### **Test 3: إضافة عنوان أولاً**
```bash
curl -X POST "https://avit.josequal.net/apimobile/address/add" \
  -H "Authorization: Bearer YOUR_VALID_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "firstname": "Debug",
    "lastname": "Test",
    "street": ["Debug Street"],
    "city": "Debug City",
    "region": "NY",
    "postcode": "10001",
    "country_id": "US",
    "telephone": "1234567890"
  }'
```

### **Test 4: اختبار GetList بعد إضافة عنوان**
```bash
curl -X GET "https://avit.josequal.net/apimobile/address/getlist" \
  -H "Authorization: Bearer YOUR_VALID_TOKEN"
```

**Expected Response:**
```json
{
  "status": true,
  "message": "Addresses retrieved successfully",
  "data": {
    "addresses": [
      {
        "id": 125,
        "type": "other",
        "firstname": "Debug",
        "lastname": "Test",
        "street": ["Debug Street"],
        "city": "Debug City",
        "region": "NY",
        "region_id": 43,
        "postcode": "10001",
        "country_id": "US",
        "telephone": "1234567890",
        "is_default_billing": false,
        "is_default_shipping": false
      }
    ],
    "total_count": 1,
    "debug": {
      "customer_id": 123,
      "default_billing": null,
      "default_shipping": null,
      "total_addresses": 1
    }
  }
}
```

## 🔧 الإصلاحات المطبقة:

### **1. إصلاح successStatus method:**
```php
// قبل الإصلاح:
public function successStatus($success = 'SUCCESS') {
    return [
        'status' => true,
        'message' => __($success),
    ];
}

// بعد الإصلاح:
public function successStatus($success = 'SUCCESS', $data = []) {
    return [
        'status' => true,
        'message' => __($success),
        'data' => $data,  // ✅ تم إضافة data parameter
    ];
}
```

### **2. إضافة Debug Information:**
```php
// في getAddresses method:
$debug = [
    'customer_id' => $customerId,
    'default_billing' => $customer->getDefaultBilling(),
    'default_shipping' => $customer->getDefaultShipping(),
    'total_addresses' => count($customer->getAddresses())
];

return $this->successStatus('Addresses retrieved successfully', [
    'addresses' => $addresses,
    'total_count' => count($addresses),
    'debug' => $debug  // ✅ معلومات debug
]);
```

## 📋 خطوات الاختبار:

1. **اختبر Authentication** - تأكد من أن العميل مسجل دخول
2. **اختبر GetList** - يجب أن ترى debug information
3. **أضف عنوان** - استخدم Add Address API
4. **اختبر GetList مرة أخرى** - يجب أن ترى العنوان المُضاف
5. **تحقق من قاعدة البيانات** - تأكد من حفظ البيانات

## 🎯 النتيجة المتوقعة:

بعد الإصلاحات، يجب أن ترى:
- `status: true`
- `message: "Addresses retrieved successfully"`
- `data` object يحتوي على:
  - `addresses` array
  - `total_count` number
  - `debug` object مع معلومات العميل

**إذا كنت لا تزال لا ترى `data`، فهناك مشكلة أخرى!** 🔍
