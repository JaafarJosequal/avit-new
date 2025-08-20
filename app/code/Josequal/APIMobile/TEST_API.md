# 🧪 اختبار API حذف الحساب

## 📋 الخطوات:

### 1. **مسح Cache:**
```bash
php bin/magento cache:clean
php bin/magento cache:flush
```

### 2. **اختبار API:**

#### **أ) طلب حذف الحساب:**
```bash
curl -X POST "https://avit.josequal.net/rest/V1/user/request-account-deletion" \
  -H "Authorization: Bearer eyJraWQiOiIxIiwiYWxnIjoiSFMyNTYifQ.eyJ1aWQiOjMyLCJ1dHlwaWQiOjMsImlhdCI6MTc1NTY3NjM3NSwiZXhwIjoxNzU1Njc5OTc1fQ.L9H7bV40jyCeprKz9HUBUuRVk6qtWVFMJug7u3lfTIo" \
  -H "Content-Type: application/json" \
  -d '{"reason": "أريد حذف حسابي لأسباب شخصية"}'
```

#### **ب) التحقق من حالة الحذف:**
```bash
curl -X GET "https://avit.josequal.net/rest/V1/user/account-deletion-status" \
  -H "Authorization: Bearer eyJraWQiOiIxIiwiYWxnIjoiSFMyNTYifQ.eyJ1aWQiOjMyLCJ1dHlwaWQiOjMsImlhdCI6MTc1NTY3NjM3NSwiZXhwIjoxNzU1Njc5OTc1fQ.L9H7bV40jyCeprKz9HUBUuRVk6qtWVFMJug7u3lfTIo"
```

#### **ج) إلغاء طلب الحذف:**
```bash
curl -X POST "https://avit.josequal.net/rest/V1/user/cancel-account-deletion" \
  -H "Authorization: Bearer eyJraWQiOiIxIiwiYWxnIjoiSFMyNTYifQ.eyJ1aWQiOjMyLCJ1dHlwaWQiOjMsImlhdCI6MTc1NTY3NjM3NSwiZXhwIjoxNzU1Njc5OTc1fQ.L9H7bV40jyCeprKz9HUBUuRVk6qtWVFMJug7u3lfTIo"
```

## 🎯 النتيجة المتوقعة:

### **طلب حذف الحساب:**
```json
{
    "status": true,
    "message": "Account deletion requested successfully. Account will be deleted in 90 days.",
    "data_field": {
        "status": "pending",
        "deletion_requested_at": "2025-01-20 10:00:00",
        "scheduled_deletion_at": "2025-04-20 10:00:00",
        "days_remaining": 90,
        "reason": "أريد حذف حسابي لأسباب شخصية"
    },
    "code": 200
}
```

### **مقارنة مع Login:**
```json
{
    "status": true,
    "message": "Logged in",
    "data": {
        "firstname": "Omar Ali",
        "lastname": "Ahmad",
        "email": "mardddedd221@example.com",
        "token": "..."
    },
    "status_code": 200
}
```

## 🔍 ملاحظات:

1. **نفس النمط:** API حذف الحساب يستخدم نفس نمط Login/Register
2. **JsonSerializable:** البيانات الآن object منظم مع keys واضحة
3. **AccountDeletionData:** Model منفصل لحذف الحساب
4. **هيكل منظم:** `data_field` يحتوي على object بدلاً من array

## ✅ إذا نجح الاختبار:

- **API يعمل بشكل مثالي!** 🎉✨
- **البيانات منظمة** مع keys واضحة
- **نفس نمط Login/Register** ✅
- **جميع المشاكل تم حلها** 🚀
