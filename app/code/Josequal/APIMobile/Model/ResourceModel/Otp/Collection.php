<?php
namespace Josequal\APIMobile\Model\ResourceModel\Otp;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Josequal\APIMobile\Model\V1\Otp::class, // ✅ استخدم الكلاس الصحيح
            \Josequal\APIMobile\Model\ResourceModel\Otp::class
        );
    }
}
