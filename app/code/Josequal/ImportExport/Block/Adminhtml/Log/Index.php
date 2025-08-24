<?php
namespace Josequal\ImportExport\Block\Adminhtml\Log;

use Magento\Backend\Block\Template;

class Index extends Template
{
    protected $_template = 'Josequal_ImportExport::log/index.phtml';

    public function getLogCollection()
    {
        // هنا يمكن إضافة منطق جلب البيانات الفعلية
        return [];
    }
}
