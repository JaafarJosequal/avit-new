# Phone Data Fix - إصلاح بيانات الهاتف

## المشكلة
في API تسجيل الدخول، البيانات التالية لا تُرجع:
- `mobile_number`: ""
- `dial_code`: ""
- `country_code`: ""

## السبب
البيانات تُحفظ في الخصائص المخصصة (Custom Attributes) ولكن لا يتم جلبها بشكل صحيح في method `login`.

## الحل المطبق

### 1. إضافة Method مساعد
تم إضافة method `getCustomerPhoneData()` في `AuthService.php` لجلب البيانات من مصادر متعددة:

```php
private function getCustomerPhoneData($customer, $customerModel) {
    $mobileNumber = null;
    $dialCode = null;
    $countryCode = null;

    // Try to get from customer model first (direct database access)
    $mobileNumber = $customerModel->getData('mobile_number');
    $dialCode = $customerModel->getData('dial_code');
    $countryCode = $customerModel->getData('country_code');

    // If not found in customer model, try custom attributes
    if (!$mobileNumber) {
        $mobileNumberAttr = $customer->getCustomAttribute('mobile_number');
        if ($mobileNumberAttr && $mobileNumberAttr->getValue()) {
            $mobileNumber = $mobileNumberAttr->getValue();
        }
    }

    if (!$dialCode) {
        $dialCodeAttr = $customer->getCustomAttribute('dial_code');
        if ($dialCodeAttr && $dialCodeAttr->getValue()) {
            $dialCode = $dialCodeAttr->getValue();
        }
    }

    if (!$countryCode) {
        $countryCodeAttr = $customer->getCustomAttribute('country_code');
        if ($countryCodeAttr && $countryCodeAttr->getValue()) {
            $countryCode = $countryCodeAttr->getValue();
        }
    }

    // If still not found, try addresses
    if (!$mobileNumber) {
        $addresses = $customer->getAddresses();
        if ($addresses) {
            foreach ($addresses as $address) {
                if ($address->getTelephone()) {
                    $mobileNumber = $address->getTelephone();
                    break;
                }
            }
        }
    }

    return [
        'mobile_number' => $mobileNumber ?: '',
        'dial_code' => $dialCode ?: '',
        'country_code' => $countryCode ?: ''
    ];
}
```

### 2. تحديث Method Login
تم تحديث method `login()` لاستخدام method المساعد الجديد:

```php
// Load customer model to get all data
$customerModel = $this->customerFactory->create();
$customerModel->load($fullCustomer->getId());

// Get phone data using helper method
$phoneData = $this->getCustomerPhoneData($fullCustomer, $customerModel);

// Set the values
$loginData->setMobileNumber($phoneData['mobile_number']);
$loginData->setDialCode($phoneData['dial_code']);
$loginData->setCountryCode($phoneData['country_code']);
```

### 3. تحديث Method Register
تم تحديث method `register()` أيضاً لاستخدام method المساعد الجديد.

## الخصائص المطلوبة

### 1. mobile_number
- **النوع**: varchar
- **الوصف**: رقم الهاتف المحمول
- **مطلوب**: لا
- **الموقع**: 999

### 2. dial_code
- **النوع**: varchar
- **الوصف**: رمز الاتصال الدولي
- **مطلوب**: لا
- **الموقع**: 1000

### 3. country_code
- **النوع**: varchar
- **الوصف**: رمز الدولة
- **مطلوب**: لا
- **الموقع**: 1001

## Setup Scripts

### InstallSchema.php
يُنشئ الخصائص المخصصة عند تثبيت الوحدة.

### InstallData.php
يُنشئ الخصائص المخصصة مع إعدادات إضافية.

### UpgradeData.php
يُنشئ الخصائص المخصصة عند ترقية الوحدة.

## كيفية التشغيل

### 1. تشغيل Setup Scripts
```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### 2. تشغيل Manual Setup (إذا لزم الأمر)
```bash
php app/code/Josequal/APIMobile/run_setup.php
```

## اختبار الحل

### 1. تسجيل مستخدم جديد
```bash
curl -X POST "https://avit.josequal.net/apimobile/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "firstname": "Test",
    "lastname": "User",
    "email": "test@example.com",
    "password": "password123",
    "mobile_number": "1234567890",
    "dial_code": "+966",
    "country_code": "SA"
  }'
```

### 2. تسجيل الدخول
```bash
curl -X POST "https://avit.josequal.net/apimobile/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

## الاستجابة المتوقعة

بعد الإصلاح، يجب أن تكون الاستجابة كالتالي:

```json
{
    "status": true,
    "message": "Logged in",
    "data": {
        "firstname": "Test",
        "lastname": "User",
        "email": "test@example.com",
        "mobile_number": "1234567890",
        "dial_code": "+966",
        "country_code": "SA",
        "token": "eyJraWQiOiIxIiwiYWxnIjoiSFMyNTYifQ...",
        "image": "https://yourdomain.com/media/default_profile.png",
        "address": "No address available"
    },
    "status_code": 200
}
```

## ملاحظات مهمة

1. **البيانات القديمة**: المستخدمون المسجلون قبل الإصلاح قد لا تظهر لديهم بيانات الهاتف.
2. **إعادة التسجيل**: قد يحتاج المستخدمون لإعادة التسجيل لتظهر بيانات الهاتف.
3. **التحديث**: يمكن إضافة API لتحديث بيانات الهاتف للمستخدمين الحاليين.

## الخطوات التالية

1. تشغيل setup scripts
2. اختبار API تسجيل الدخول
3. إضافة API لتحديث بيانات الهاتف (إذا لزم الأمر)
4. اختبار مع المستخدمين الحاليين
