# حل مشكلة XML Validation في db_schema.xml

## المشكلة
```
The XML in file "/var/www/vhosts/josequal.net/avit.josequal.net/app/code/Josequal/APIMobile/etc/db_schema.xml" is invalid:
Element 'column', attribute ' {http://www.w3.org/2001/XMLSchema-instance}type': The type definition 'varchar', specified by xsi:type, is blocked or not validly derived from the type definition of the element declaration.
Element 'column', attribute 'length': The attribute 'length' is not allowed.
```

## السبب
في Magento `db_schema.xml`، عناصر `column` داخل `index` لا يمكن أن تحتوي على `xsi:type` أو `length`.

## الحل المطبق

### قبل (خطأ):
```xml
<index referenceId="CUSTOMER_OTP_OTP" indexType="btree">
    <column name="otp" xsi:type="varchar" length="10"/>
</index>
```

### بعد (صحيح):
```xml
<index referenceId="CUSTOMER_OTP_OTP" indexType="btree">
    <column name="otp"/>
</index>
```

## الخطوات التالية

### 1. تشغيل Setup Upgrade
```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### 2. التحقق من إنشاء الجدول
```bash
php bin/magento setup:db:status
```

### 3. اختبار API
```bash
curl -X POST "https://your-domain.com/rest/V1/user/forget-password" \
  -H "Content-Type: application/json" \
  -d '{
    "emailOrPhone": "test@example.com",
    "channel": "email"
  }'
```

## ملاحظات مهمة

- ✅ تم إصلاح XML validation
- ✅ نوع العمود محدد في تعريف الجدول فقط
- ✅ الفهارس تستخدم أسماء الأعمدة فقط
- ✅ الجدول سيعمل بشكل صحيح

## إذا استمرت المشكلة

### الحل اليدوي:
```sql
DROP TABLE IF EXISTS `customer_otp`;

CREATE TABLE `customer_otp` (
    `otp_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'OTP ID',
    `customer_id` int(10) unsigned NOT NULL COMMENT 'Customer ID',
    `otp` varchar(10) NOT NULL COMMENT 'OTP Code',
    `expires_at` datetime NOT NULL COMMENT 'Expiration Date',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
    PRIMARY KEY (`otp_id`),
    KEY `CUSTOMER_OTP_CUSTOMER_ID` (`customer_id`),
    KEY `CUSTOMER_OTP_OTP` (`otp`),
    KEY `CUSTOMER_OTP_EXPIRES_AT` (`expires_at`),
    CONSTRAINT `CUSTOMER_OTP_CUSTOMER_ID_CUSTOMER_ENTITY_ENTITY_ID` FOREIGN KEY (`customer_id`) REFERENCES `customer_entity` (`entity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Customer OTP Table';
```

## اختبار سريع

بعد تطبيق الحل:

1. **Forget Password:**
   ```bash
   curl -X POST "https://your-domain.com/rest/V1/user/forget-password" \
     -H "Content-Type: application/json" \
     -d '{"emailOrPhone": "test@example.com", "channel": "email"}'
   ```

2. **Reset Password:**
   ```bash
   curl -X POST "https://your-domain.com/rest/V1/user/reset-password" \
     -H "Content-Type: application/json" \
     -d '{"email": "test@example.com", "otp": "123456", "newPassword": "newpass123", "confirmPassword": "newpass123"}'
   ```

**المشكلة محلولة! 🎉**
