<?php
namespace Josequal\APIMobile\Model;

use Magento\Framework\Model\AbstractModel;
use Josequal\APIMobile\Model\ResourceModel\Otp as OtpResourceModel;

class Otp extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(OtpResourceModel::class);
    }

    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    public function getOtp()
    {
        return $this->getData('otp');
    }

    public function setOtp($otp)
    {
        return $this->setData('otp', $otp);
    }

    public function getExpiresAt()
    {
        return $this->getData('expires_at');
    }

    public function setExpiresAt($expiresAt)
    {
        return $this->setData('expires_at', $expiresAt);
    }
}
