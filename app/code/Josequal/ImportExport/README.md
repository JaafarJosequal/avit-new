# Josequal Import/Export Module

وحدة بسيطة وجميلة لـ Import/Export للمنتجات والتصنيفات في Magento 2 Admin Dashboard.

## المميزات

### 🎨 تصميم جميل وعصري
- تصميم متجاوب مع جميع الأجهزة
- ألوان متدرجة جميلة
- تأثيرات حركية سلسة
- واجهة مستخدم سهلة وبسيطة

### 📊 دعم أنواع البيانات
- **المنتجات (Products)**
  - SKU (مفتاح أساسي إجباري)
  - Name (اسم المنتج)
  - Description / Short Description
  - Price
  - Special Price (اختياري)
  - Quantity (Stock Qty)
  - Status (Enabled/Disabled)
  - Visibility (Catalog / Search / Both)
  - Category IDs
  - Product Type
  - Attributes (اللون، المقاس)
  - Images (image, small_image, thumbnail)
  - Additional Images (كروابط متعددة)

- **التصنيفات (Categories)**
  - Category ID
  - Parent Category
  - Name
  - Description
  - Is Active
  - Position
  - URL Key

### 🚀 وظائف متقدمة
- Import/Export بصيغ CSV و XLSX
- Drag & Drop للملفات
- شريط تقدم متحرك
- نظام إشعارات جميل
- سجل مفصل لجميع العمليات
- تصفية وبحث في السجل
- دعم الصور المتعددة كروابط

## التثبيت

### 1. نسخ الملفات
```bash
cp -r app/code/Josequal/ImportExport /path/to/magento/app/code/Josequal/
```

### 2. تفعيل الوحدة
```bash
php bin/magento module:enable Josequal_ImportExport
```

### 3. تحديث قاعدة البيانات
```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

### 4. تنظيف الكاش
```bash
php bin/magento cache:clean
php bin/magento cache:flush
```

## الاستخدام

### الوصول للوحدة
1. اذهب إلى Admin Dashboard
2. ابحث عن "Catalog" في القائمة الجانبية
3. ستجد "Import/Export" كقائمة فرعية
4. اختر "Products Import/Export" أو "Categories Import/Export"

### استيراد البيانات
1. اضغط على "Import Products" أو "Import Categories"
2. اختر الملف (CSV أو XLSX)
3. اختر خيارات الاستيراد
4. اضغط "Start Import"
5. راقب التقدم في شريط التقدم

### تصدير البيانات
1. اضغط على "Export Products" أو "Export Categories"
2. اختر صيغة التصدير
3. اختر الحقول المطلوبة
4. اضغط "Start Export"
5. سيتم تحميل الملف تلقائياً

### عرض السجل
1. اذهب إلى "Import/Export Log"
2. استخدم الفلاتر للبحث
3. اضغط على "View Errors" لرؤية الأخطاء
4. اضغط على "Download" لتحميل الملفات المصدرة

## تنسيق الملفات

### ملف CSV للمنتجات
```csv
SKU,Name,Description,Price,Quantity,Status,Category IDs,Images
PROD-001,Product Name,Description,99.99,100,1,"1,2,3","image1.jpg,image2.jpg"
PROD-002,Another Product,Description,149.99,50,1,"2,4","image3.jpg"
```

### ملف CSV للتصنيفات
```csv
Category ID,Parent ID,Name,Description,Is Active,Position,URL Key
,0,Root Category,Root category description,1,1,root-category
,1,Sub Category,Sub category description,1,1,sub-category
```

## الأمان والصلاحيات

الوحدة تدعم نظام الصلاحيات في Magento:
- `Josequal_ImportExport::josequal_import_export` - الوصول العام
- `Josequal_ImportExport::import_export_products` - إدارة المنتجات
- `Josequal_ImportExport::import_export_categories` - إدارة التصنيفات
- `Josequal_ImportExport::import_export_log` - عرض السجل

## الدعم الفني

لأي استفسارات أو مشاكل:
- تأكد من تفعيل الوحدة بشكل صحيح
- تحقق من صلاحيات المستخدم
- راجع سجل الأخطاء في Magento
- تأكد من صحة تنسيق الملفات

## التخصيص

يمكن تخصيص الوحدة بسهولة:
- تعديل الألوان في ملف CSS
- إضافة حقول جديدة
- تعديل رسائل الإشعارات
- إضافة صيغ ملفات جديدة

## المتطلبات

- Magento 2.4.x أو أحدث
- PHP 7.4 أو أحدث
- MySQL 5.7 أو أحدث

## الترخيص

هذه الوحدة مفتوحة المصدر ومتاحة تحت رخصة MIT.
