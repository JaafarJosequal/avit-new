<?php
namespace Josequal\ImportExport\Block\Adminhtml\Products;

use Magento\Backend\Block\Template;

class Index extends Template
{
    protected $_template = 'Josequal_ImportExport::products/index.phtml';

    public function getImportUrl()
    {
        return $this->getUrl('josequal_import_export/products/import');
    }

    public function getExportUrl()
    {
        return $this->getUrl('josequal_import_export/products/export');
    }

    public function getSampleCsvUrl()
    {
        return $this->getUrl('josequal_import_export/products/downloadSample');
    }
}
