# حل سريع لمشكلة API حذف الحساب

## المشكلة
API يعطي خطأ "There has been an error processing your request"

## الحل السريع

### 1. إنشاء جدول قاعدة البيانات
قم بتشغيل هذا الأمر SQL في قاعدة البيانات:

```sql
CREATE TABLE IF NOT EXISTS `customer_account_deletion` (
    `entity_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Entity ID',
    `customer_id` int(11) unsigned NOT NULL COMMENT 'Customer ID',
    `deletion_requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Deletion Requested At',
    `scheduled_deletion_at` timestamp NOT NULL COMMENT 'Scheduled Deletion At',
    `status` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT 'Status: 1=Pending, 2=Cancelled, 3=Completed',
    `reason` text COMMENT 'Deletion Reason',
    `cancelled_at` timestamp NULL DEFAULT NULL COMMENT 'Cancelled At',
    `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Actually Deleted At',
    PRIMARY KEY (`entity_id`),
    KEY `CUSTOMER_ACCOUNT_DELETION_CUSTOMER_ID` (`customer_id`),
    KEY `CUSTOMER_ACCOUNT_DELETION_STATUS` (`status`),
    KEY `CUSTOMER_ACCOUNT_DELETION_SCHEDULED_DELETION_AT` (`scheduled_deletion_at`),
    CONSTRAINT `CUSTOMER_ACCOUNT_DELETION_CUSTOMER_ID_CUSTOMER_ENTITY_ENTITY_ID` 
        FOREIGN KEY (`customer_id`) REFERENCES `customer_entity` (`entity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Customer Account Deletion Requests';
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

## ملاحظات
- تأكد من وجود Token صحيح
- إذا استمرت المشكلة، راجع سجلات الخطأ
- جميع doc blocks تم إصلاحها ✅
