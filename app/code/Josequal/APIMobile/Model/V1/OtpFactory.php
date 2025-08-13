<?php
namespace Josequal\APIMobile\Model\V1;

use Magento\Framework\ObjectManagerInterface;

class OtpFactory
{
    protected $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = [])
    {
        return $this->objectManager->create(\Josequal\APIMobile\Model\V1\Otp::class, $data);
    }
}
