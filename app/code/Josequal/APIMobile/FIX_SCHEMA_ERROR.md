# حل مشكلة Schema Error

## ❌ المشكلة
```
Auto Increment column do not have index. Column - "entity_id", table - "customer_account_deletion"
```

## ✅ الحل
تم إصلاح المشكلة في `db_schema.xml` بإضافة index منفصل لـ `entity_id`.

## 🔧 ما تم إصلاحه:

### في `etc/db_schema.xml`:
```xml
<index referenceId="CUSTOMER_ACCOUNT_DELETION_ENTITY_ID" indexType="btree">
    <column name="entity_id"/>
</index>
```

### في `etc/db_schema_whitelist.json`:
```json
"CUSTOMER_ACCOUNT_DELETION_ENTITY_ID": true
```

## 🚀 خطوات التحديث:

### 1. تشغيل Setup Upgrade
```bash
php -d memory_limit=-1 bin/magento setup:upgrade
```

### 2. مسح Cache
```bash
php bin/magento cache:clean
php bin/magento cache:flush
```

### 3. اختبار API
```bash
curl -X POST "https://avit.josequal.net/rest/V1/user/request-account-deletion" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"reason": "test"}'
```

## 📋 هيكل الجدول الصحيح:

```sql
customer_account_deletion
├── entity_id (Primary Key + Index)
├── customer_id (Foreign Key + Index)
├── deletion_requested_at (Timestamp)
├── scheduled_deletion_at (Timestamp + Index)
├── status (Index)
├── reason (Text)
├── cancelled_at (Timestamp)
└── deleted_at (Timestamp)
```

## 🎯 الميزات:

- ✅ **Primary Key** على `entity_id`
- ✅ **Index منفصل** على `entity_id` (مطلوب للـ Auto Increment)
- ✅ **Foreign Key** على `customer_id`
- ✅ **Indexes** على الأعمدة المهمة
- ✅ **Cascade Delete** عند حذف العميل

## ⚠️ إذا استمرت مشكلة Composer:

```bash
# 1. تثبيت Composer Dependencies
composer install

# 2. تحديث الوحدة
php bin/magento setup:upgrade

# 3. مسح Cache
php bin/magento cache:clean
```

## 🎉 النتيجة:
بعد إصلاح Schema Error، سيعمل API حذف الحساب بشكل صحيح!
