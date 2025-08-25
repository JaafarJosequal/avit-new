<?php
namespace Josequal\DataImportExport\Block\Adminhtml\Categories;

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
        return $this->getUrl('josequal_dataimportexport/categories/import');
    }

    public function getExportUrl()
    {
        return $this->getUrl('josequal_dataimportexport/categories/export');
    }

    public function getSampleCsvUrl()
    {
        return $this->getUrl('josequal_dataimportexport/categories/downloadSample');
    }
}
