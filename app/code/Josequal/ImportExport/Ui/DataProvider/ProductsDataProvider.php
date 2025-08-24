<?php
namespace Josequal\ImportExport\Ui\DataProvider;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as UiDataProvider;

class ProductsDataProvider extends UiDataProvider
{
    public function getData()
    {
        // هنا يمكن إضافة منطق جلب البيانات الفعلية
        $data = [
            'totalRecords' => 0,
            'items' => []
        ];

        return $data;
    }
}
