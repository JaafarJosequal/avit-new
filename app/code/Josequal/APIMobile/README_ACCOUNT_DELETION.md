# Account Deletion API

## نظرة عامة
هذا API يسمح للمستخدمين بحذف حساباتهم مع فترة انتظار 90 يوم قبل الحذف النهائي. خلال هذه الفترة، يتم تعطيل الحساب مع إمكانية استرجاعه.

## الميزات
- **طلب حذف الحساب**: المستخدم يمكنه طلب حذف حسابه
- **فترة انتظار 90 يوم**: الحساب لا يتم حذفه فوراً
- **تعطيل الحساب**: خلال فترة الانتظار، الحساب معطل
- **إمكانية الاسترجاع**: يمكن للمستخدم إلغاء طلب الحذف واسترجاع حسابه
- **حذف تلقائي**: بعد 90 يوم، يتم حذف الحساب تلقائياً

## نقاط النهاية API

### 1. طلب حذف الحساب
**POST** `/rest/V1/user/request-account-deletion`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body (اختياري):**
```json
{
    "reason": "سبب الحذف (اختياري)"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Account deletion requested successfully. Account will be deleted in 90 days.",
    "data": {
        "deletion_date": "2024-04-15 01:00:00",
        "days_remaining": 90
    },
    "statusCode": 200
}
```

### 2. إلغاء طلب الحذف
**POST** `/rest/V1/user/cancel-account-deletion`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Response:**
```json
{
    "success": true,
    "message": "Account deletion cancelled successfully. Account is now active again.",
    "data": null,
    "statusCode": 200
}
```

### 3. حالة طلب الحذف
**GET** `/rest/V1/user/account-deletion-status`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Deletion status retrieved successfully",
    "data": {
        "status": "1",
        "deletion_requested": true,
        "deletion_requested_at": "2024-01-15 10:30:00",
        "scheduled_deletion_at": "2024-04-15 10:30:00",
        "days_remaining": 45,
        "reason": "سبب الحذف"
    },
    "statusCode": 200
}
```

## حالات الحذف
- **1**: معلق (Pending) - تم طلب الحذف
- **2**: ملغي (Cancelled) - تم إلغاء طلب الحذف
- **3**: مكتمل (Completed) - تم حذف الحساب

## Cron Job
يتم تشغيل Cron Job يومياً في الساعة 1:00 صباحاً لمعالجة الحسابات التي انتهت فترة انتظارها (90 يوم).

## قاعدة البيانات
يتم إنشاء جدول `customer_account_deletion` لتتبع طلبات الحذف:

```sql
CREATE TABLE customer_account_deletion (
    entity_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    deletion_requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scheduled_deletion_at TIMESTAMP NOT NULL,
    status SMALLINT UNSIGNED DEFAULT 1,
    reason TEXT,
    cancelled_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES customer_entity(entity_id) ON DELETE CASCADE
);
```

## الأمان
- جميع نقاط النهاية تتطلب مصادقة (Bearer Token)
- يتم التحقق من صحة Token قبل تنفيذ أي عملية
- الحسابات المعطلة لا يمكنها الوصول إلى النظام

## الاستخدام
1. المستخدم يطلب حذف حسابه
2. الحساب يتم تعطيله فوراً
3. يتم جدولة الحذف بعد 90 يوم
4. يمكن للمستخدم إلغاء الطلب واسترجاع الحساب
5. بعد 90 يوم، يتم حذف الحساب نهائياً

## ملاحظات
- عند طلب الحذف، يتم تعطيل الحساب فوراً
- يمكن للمستخدم إلغاء الطلب في أي وقت خلال الـ 90 يوم
- بعد إلغاء الطلب، يتم إعادة تفعيل الحساب
- الحذف النهائي يتم تلقائياً عبر Cron Job
