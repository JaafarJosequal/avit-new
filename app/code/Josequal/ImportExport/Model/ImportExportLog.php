<?php
namespace Josequal\ImportExport\Model;

use Magento\Framework\Model\AbstractModel;

class ImportExportLog extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Josequal\ImportExport\Model\ResourceModel\ImportExportLog::class);
    }
}
