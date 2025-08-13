<?php
namespace Josequal\APIMobile\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Otp extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('customer_otp', 'otp_id');
    }
}
