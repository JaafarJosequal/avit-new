# اختبار API حذف الحساب

## اختبار طلب حذف الحساب

### 1. طلب حذف الحساب
```bash
curl -X POST "https://your-domain.com/rest/V1/user/request-account-deletion" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "أريد حذف حسابي لأسباب شخصية"
  }'
```

**النتيجة المتوقعة:**
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

### 2. التحقق من حالة الحذف
```bash
curl -X GET "https://your-domain.com/rest/V1/user/account-deletion-status" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**النتيجة المتوقعة:**
```json
{
    "success": true,
    "message": "Deletion status retrieved successfully",
    "data": {
        "status": "1",
        "deletion_requested": true,
        "deletion_requested_at": "2024-01-15 10:30:00",
        "scheduled_deletion_at": "2024-04-15 10:30:00",
        "days_remaining": 90,
        "reason": "أريد حذف حسابي لأسباب شخصية"
    },
    "statusCode": 200
}
```

### 3. إلغاء طلب الحذف
```bash
curl -X POST "https://your-domain.com/rest/V1/user/cancel-account-deletion" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

**النتيجة المتوقعة:**
```json
{
    "success": true,
    "message": "Account deletion cancelled successfully. Account is now active again.",
    "data": null,
    "statusCode": 200
}
```

### 4. التحقق من إلغاء الحذف
```bash
curl -X GET "https://your-domain.com/rest/V1/user/account-deletion-status" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**النتيجة المتوقعة:**
```json
{
    "success": true,
    "message": "Deletion status retrieved successfully",
    "data": {
        "status": "2",
        "deletion_requested": true,
        "deletion_requested_at": "2024-01-15 10:30:00",
        "scheduled_deletion_at": "2024-04-15 10:30:00",
        "days_remaining": 0,
        "reason": "أريد حذف حسابي لأسباب شخصية",
        "cancelled_at": "2024-01-15 11:00:00"
    },
    "statusCode": 200
}
```

## اختبار الأخطاء

### 1. طلب حذف بدون Token
```bash
curl -X POST "https://your-domain.com/rest/V1/user/request-account-deletion" \
  -H "Content-Type: application/json" \
  -d '{"reason": "test"}'
```

**النتيجة المتوقعة:**
```json
{
    "success": false,
    "message": "Invalid or expired token",
    "data": null,
    "statusCode": 401
}
```

### 2. طلب حذف مرتين
```bash
# الطلب الأول
curl -X POST "https://your-domain.com/rest/V1/user/request-account-deletion" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"

# الطلب الثاني (يجب أن يفشل)
curl -X POST "https://your-domain.com/rest/V1/user/request-account-deletion" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

**النتيجة المتوقعة للطلب الثاني:**
```json
{
    "success": false,
    "message": "Account deletion already requested",
    "data": null,
    "statusCode": 400
}
```

### 3. إلغاء حذف بدون طلب
```bash
curl -X POST "https://your-domain.com/rest/V1/user/cancel-account-deletion" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

**النتيجة المتوقعة:**
```json
{
    "success": false,
    "message": "No deletion request found for this account",
    "data": null,
    "statusCode": 400
}
```

## اختبار Cron Job

### 1. تشغيل Cron Job يدوياً
```bash
php bin/magento cron:run
```

### 2. التحقق من السجلات
```bash
tail -f var/log/system.log | grep "account deletion"
```

## ملاحظات الاختبار

1. **تأكد من وجود Token صحيح**: استخدم Token صالح من تسجيل الدخول
2. **اختبار التسلسل**: اتبع التسلسل الصحيح (طلب → فحص → إلغاء → فحص)
3. **التحقق من قاعدة البيانات**: راجع جدول `customer_account_deletion`
4. **اختبار Cron Job**: تأكد من تشغيل Cron Job يومياً
5. **اختبار التعطيل**: تأكد من تعطيل الحساب عند طلب الحذف
6. **اختبار إعادة التفعيل**: تأكد من إعادة تفعيل الحساب عند الإلغاء

## استكشاف الأخطاء

### مشاكل شائعة:
1. **خطأ 401**: تأكد من صحة Token
2. **خطأ 400**: تأكد من عدم وجود طلب حذف مسبق
3. **خطأ 500**: راجع سجلات الخطأ في `var/log/`
4. **عدم عمل Cron Job**: تأكد من إعداد Cron في النظام

### حلول:
1. **إعادة إنشاء Token**: سجل الدخول مرة أخرى
2. **مسح Cache**: `php bin/magento cache:clean`
3. **إعادة تشغيل Cron**: `php bin/magento cron:run`
4. **فحص قاعدة البيانات**: تأكد من إنشاء الجدول
