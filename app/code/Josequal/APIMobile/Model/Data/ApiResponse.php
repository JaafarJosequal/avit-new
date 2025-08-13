<?php
namespace Josequal\APIMobile\Model\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use Josequal\APIMobile\Api\Data\ApiResponseInterface;

class ApiResponse extends AbstractSimpleObject implements ApiResponseInterface
{
    public function getStatus() { return $this->_get('status'); }

    public function setStatus($status) { return $this->setData('status', $status); }

    public function getMessage() { return $this->_get('message'); }

    public function setMessage($message) { return $this->setData('message', $message); }

    public function getDataField() { return $this->_get('data'); }

    public function setDataField($data) { return $this->setData('data', $data); }

    public function getCode() { return $this->_get('code'); }

    public function setCode($code) { return $this->setData('code', $code); }
}
