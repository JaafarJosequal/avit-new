<?php
namespace Josequal\DataImportExport\Block\Adminhtml\Products;

use Magento\Backend\Block\Template;

class Index extends Template
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getImportUrl()
    {
        return $this->getUrl('josequal_dataimportexport/products/import');
    }

    public function getExportUrl()
    {
        return $this->getUrl('josequal_dataimportexport/products/export');
    }

    public function getSampleCsvUrl()
    {
        return $this->getUrl('josequal_dataimportexport/products/downloadSample');
    }
}
