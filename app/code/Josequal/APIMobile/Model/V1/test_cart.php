<?php
/**
 * ملف اختبار لدالة السلة المحسنة
 * يمكن استخدام هذا الملف لاختبار الوظائف الجديدة
 */

// تحميل Magento
require_once 'app/bootstrap.php';

try {
    $bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
    $objectManager = $bootstrap->getObjectManager();

    // الحصول على دالة السلة
    $cart = $objectManager->get('\Josequal\APIMobile\Model\V1\Cart');

    echo "=== اختبار دالة السلة المحسنة ===\n\n";

    // اختبار 1: إضافة منتج جديد
    echo "1. إضافة منتج جديد (ID: 1, الكمية: 2)\n";
    $result1 = $cart->addToCart([
        'product_id' => 1,
        'quantity' => 2,
        'color' => 'red',
        'size' => 'L'
    ]);

    if ($result1['status']) {
        echo "✓ تم إضافة المنتج بنجاح: " . $result1['message'] . "\n";
    } else {
        echo "✗ فشل في إضافة المنتج: " . implode(', ', $result1['errors']) . "\n";
    }

    echo "\n";

    // اختبار 2: إضافة نفس المنتج مع نفس الخيارات
    echo "2. إضافة نفس المنتج مع نفس الخيارات (الكمية: 1)\n";
    $result2 = $cart->addToCart([
        'product_id' => 1,
        'quantity' => 1,
        'color' => 'red',
        'size' => 'L'
    ]);

    if ($result2['status']) {
        echo "✓ تم تحديث الكمية: " . $result2['message'] . "\n";
    } else {
        echo "✗ فشل في تحديث الكمية: " . implode(', ', $result2['errors']) . "\n";
    }

    echo "\n";

    // اختبار 3: إضافة نفس المنتج مع خيارات مختلفة
    echo "3. إضافة نفس المنتج مع خيارات مختلفة (اللون: blue)\n";
    $result3 = $cart->addToCart([
        'product_id' => 1,
        'quantity' => 1,
        'color' => 'blue',
        'size' => 'L'
    ]);

    if ($result3['status']) {
        echo "✓ تم إضافة منتج جديد: " . $result3['message'] . "\n";
    } else {
        echo "✗ فشل في إضافة المنتج: " . implode(', ', $result3['errors']) . "\n";
    }

    echo "\n";

    // اختبار 4: عرض معلومات السلة
    echo "4. عرض معلومات السلة\n";
    $cartInfo = $cart->getCartInfo();
    if ($cartInfo['status']) {
        $data = $cartInfo['data'];
        echo "عدد العناصر في السلة: " . count($data['items']) . "\n";
        foreach ($data['items'] as $item) {
            echo "- المنتج ID: " . $item['product_id'] .
                 ", الكمية: " . $item['qty'] .
                 ", الخيارات: " . $item['options_summary'] . "\n";
        }
    }

    echo "\n";

    // اختبار 5: استخدام دالة التصحيح
    echo "5. استخدام دالة التصحيح للمنتج ID: 1\n";
    $debugInfo = $cart->debugCartItem(1, ['color' => 'red', 'size' => 'L']);
    if (!isset($debugInfo['error'])) {
        echo "معلومات التصحيح:\n";
        echo "- عدد العناصر المطابقة: " . count($debugInfo['matching_items']) . "\n";
        foreach ($debugInfo['matching_items'] as $match) {
            echo "- العنصر ID: " . $match['item_id'] .
                 ", تطابق الخيارات: " . ($match['options_match'] ? 'نعم' : 'لا') . "\n";
        }
    } else {
        echo "خطأ في التصحيح: " . $debugInfo['error'] . "\n";
    }

    echo "\n=== انتهى الاختبار ===\n";

} catch (\Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
