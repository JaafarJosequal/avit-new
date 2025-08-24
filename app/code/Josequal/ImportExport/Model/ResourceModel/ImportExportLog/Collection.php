<?php
namespace Josequal\ImportExport\Model\ResourceModel\ImportExportLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Josequal\ImportExport\Model\ImportExportLog::class,
            \Josequal\ImportExport\Model\ResourceModel\ImportExportLog::class
        );
    }
}
