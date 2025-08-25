# Josequal Data Import/Export Extension

## نظرة عامة
هذا الـ extension يوفر وظائف Import/Export متقدمة للمنتجات والتصنيفات في Magento 2. يدعم ملفات CSV و Excel (XLSX, XLS).

## الميزات
- **استيراد المنتجات**: دعم ملفات CSV, XLSX, XLS
- **تصدير المنتجات**: تصدير بصيغة Excel أو CSV
- **استيراد التصنيفات**: دعم ملفات CSV, XLSX, XLS
- **تصدير التصنيفات**: تصدير بصيغة Excel أو CSV
- **واجهة مستخدم سهلة**: في admin panel
- **فلترة البيانات**: تصفية حسب التصنيف والحالة
- **معالجة الأخطاء**: تقارير مفصلة عن الأخطاء

## متطلبات النظام
- Magento 2.4.x
- PHP 7.4+
- مكتبة PhpSpreadsheet

## التثبيت

### 1. تثبيت المكتبات المطلوبة
```bash
composer require phpoffice/phpspreadsheet
```

### 2. نسخ الملفات
انسخ مجلد `Josequal/DataImportExport` إلى `app/code/`

### 3. تفعيل الـ Module
```bash
php bin/magento module:enable Josequal_DataImportExport
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

## الاستخدام

### الوصول للـ Extension
1. اذهب إلى Admin Panel
2. Tools > Josequal Data Import/Export
3. اختر Products Import/Export أو Categories Import/Export

### استيراد المنتجات
1. اضغط على "Import Products"
2. اختر ملف CSV, XLSX, أو XLS
3. تأكد من أن الملف يحتوي على الأعمدة المطلوبة
4. اضغط "Start Import"

### تصدير المنتجات
1. اضغط على "Export Products"
2. اختر صيغة التصدير (Excel أو CSV)
3. اختر الفلاتر المطلوبة
4. اضغط "Start Export"

## تنسيق الملفات

### المنتجات - الأعمدة المطلوبة
- **sku** (مطلوب): رمز المنتج الفريد
- **name**: اسم المنتج
- **description**: وصف المنتج
- **short_description**: وصف مختصر
- **price**: السعر
- **special_price**: السعر الخاص (اختياري)
- **quantity**: الكمية المتوفرة
- **status**: الحالة (1=مفعل، 2=معطل)
- **visibility**: الرؤية (1=غير مرئي، 2=فهرس، 3=بحث، 4=كلاهما)
- **category_ids**: معرفات التصنيفات (مفصولة بفواصل)
- **product_type**: نوع المنتج
- **image**: الصورة الرئيسية
- **small_image**: الصورة الصغيرة
- **thumbnail**: الصورة المصغرة

### التصنيفات - الأعمدة المطلوبة
- **name** (مطلوب): اسم التصنيف
- **category_id**: معرف التصنيف (اختياري للمنتجات الجديدة)
- **code**: رمز التصنيف (اختياري)
- **parent_id**: معرف التصنيف الأب
- **description**: وصف التصنيف
- **is_active**: الحالة النشطة (1=نشط، 0=غير نشط)
- **position**: الموقع
- **url_key**: مفتاح URL للـ SEO

## صيغ الملفات المدعومة

### الاستيراد
- **CSV**: ملفات قيم مفصولة بفواصل
- **XLSX**: ملفات Excel 2007+
- **XLS**: ملفات Excel 97-2003

### التصدير
- **XLSX**: ملفات Excel مع أعمدة ذاتية التعديل
- **CSV**: ملفات قيم مفصولة بفواصل

## إعدادات الأمان
- التحقق من نوع الملف
- معالجة آمنة للملفات
- صلاحيات محدودة للمستخدمين

## استكشاف الأخطاء
- تأكد من تثبيت مكتبة PhpSpreadsheet
- تحقق من صلاحيات الملفات
- تأكد من تنسيق البيانات في الملف

## الدعم
للحصول على الدعم، يرجى التواصل مع فريق Josequal.

## الترخيص
OSL-3.0
