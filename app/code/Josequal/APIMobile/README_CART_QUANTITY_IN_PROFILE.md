# 🛒 إضافة كمية السلة إلى API الملف الشخصي

## نظرة عامة
تم تحديث API الملف الشخصي (`/V1/user/profile`) ليشمل كمية العناصر الموجودة في سلة التسوق للمستخدم.

## 🔄 التغييرات المطلوبة

### 1. تحديث CustomerLoginDataInterface
تم إضافة حقول جديدة للحصول على وتعيين كمية السلة:

```php
/**
 * Get cart quantity
 *
 * @return int
 */
public function getCartQuantity(): int;

/**
 * Set cart quantity
 *
 * @param int $quantity
 * @return $this
 */
public function setCartQuantity(int $quantity): CustomerLoginDataInterface;
```

**الملف:** `app/code/Josequal/APIMobile/Api/Data/CustomerLoginDataInterface.php`

### 2. تحديث CustomerLoginData
تم إضافة الخاصية والطرق المطلوبة:

```php
private int $cartQuantity = 0;

public function getCartQuantity(): int { return $this->cartQuantity; }
public function setCartQuantity(int $value): CustomerLoginDataInterface { $this->cartQuantity = $value; return $this; }
```

**الملف:** `app/code/Josequal/APIMobile/Model/Data/CustomerLoginData.php`

### 3. تحديث ProfileService
تم إضافة اعتماد Cart إلى ProfileService:

```php
protected Cart $cart;

public function __construct(
    // ... existing parameters ...
    Cart $cart
) {
    // ... existing assignments ...
    $this->cart = $cart;
}
```

**الملف:** `app/code/Josequal/APIMobile/Service/ProfileService.php`

### 4. تحديث buildProfileResponse
تم إضافة منطق للحصول على كمية السلة:

```php
// Get cart quantity
try {
    $cartQuantity = (int) $this->cart->getQuote()->getItemsSummaryQty();
    $customerData->setCartQuantity($cartQuantity);
} catch (\Exception $e) {
    // If cart is not available, set quantity to 0
    $customerData->setCartQuantity(0);
}
```

### 5. تحديث di.xml
تم إضافة اعتماد Cart إلى إعدادات Dependency Injection:

```xml
<type name="Josequal\APIMobile\Service\ProfileService">
    <arguments>
        <!-- ... existing arguments ... -->
        <argument name="cart" xsi:type="object">Magento\Checkout\Model\Cart</argument>
    </arguments>
</type>
```

**الملف:** `app/code/Josequal/APIMobile/etc/di.xml`

## 🧪 اختبار التغييرات

### API Endpoint
```
GET /V1/user/profile
```

### Headers
```
Authorization: Bearer {your_token}
Content-Type: application/json
```

### Response Structure
```json
{
  "status": true,
  "message": "Profile retrieved successfully",
  "data": {
    "firstname": "John",
    "lastname": "Doe",
    "email": "john.doe@example.com",
    "mobile_number": "+1234567890",
    "dial_code": "+1",
    "country_code": "US",
    "token": "your_auth_token",
    "password": null,
    "image": "https://yourdomain.com/media/default_profile.png",
    "address": "123 Main St, City, State, 12345, US",
    "cart_quantity": 3
  },
  "status_code": 200
}
```

## 🔍 كيفية عمل كمية السلة

### الحصول على الكمية
```php
$cartQuantity = (int) $this->cart->getQuote()->getItemsSummaryQty();
```

### معالجة الأخطاء
- إذا كانت السلة غير متاحة، يتم تعيين الكمية إلى 0
- يتم استخدام try-catch لمعالجة أي أخطاء محتملة

### التحديث التلقائي
- كمية السلة تتحدث تلقائياً بناءً على محتويات سلة المستخدم الحالية
- إذا كانت السلة فارغة، ستكون الكمية 0
- إذا كان هناك عناصر في السلة، ستظهر الكمية الإجمالية

## 📱 استخدام في التطبيقات

### تطبيقات الموبايل
يمكن للتطبيقات الآن عرض عدد العناصر في السلة مباشرة في الملف الشخصي:

```json
{
  "cart_quantity": 5,
  "cart_badge": "5"
}
```

### واجهات الويب
يمكن استخدام كمية السلة في:

- شارات الإشعارات
- عرض ملخص السلة
- تحديث عداد العناصر في الوقت الفعلي

## 🚀 المزايا

1. **تكامل أفضل**: معلومات السلة متاحة مباشرة في الملف الشخصي
2. **أداء محسن**: تقليل عدد الطلبات المطلوبة
3. **تجربة مستخدم أفضل**: عرض معلومات شاملة في مكان واحد
4. **سهولة الصيانة**: كود منظم ومنطق واضح

## 🔧 استكشاف الأخطاء

### مشاكل شائعة

1. **Cart dependency not found**
   - تأكد من تحديث `di.xml`
   - امسح cache: `php bin/magento cache:clean`

2. **Cart quantity always 0**
   - تحقق من أن المستخدم مسجل الدخول
   - تأكد من وجود عناصر في السلة

3. **API returns error**
   - تحقق من صحة token المصادقة
   - راجع logs للأخطاء

### Commands مفيدة
```bash
# مسح cache
php bin/magento cache:clean

# إعادة compile
php bin/magento setup:di:compile

# فحص حالة النظام
php bin/magento setup:db:status
```

## 📚 مراجع إضافية

- [Magento Cart API Documentation](https://devdocs.magento.com/guides/v2.4/rest/tutorials/orders/order-create-order.html)
- [Dependency Injection in Magento](https://devdocs.magento.com/guides/v2.4/extension-dev-guide/depend-inj.html)
- [Web API in Magento](https://devdocs.magento.com/guides/v2.4/get-started/rest-web-api.html)
