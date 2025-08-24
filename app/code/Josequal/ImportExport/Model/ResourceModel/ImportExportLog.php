<?php
namespace Josequal\ImportExport\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ImportExportLog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('josequal_import_export_log', 'entity_id');
    }
}
