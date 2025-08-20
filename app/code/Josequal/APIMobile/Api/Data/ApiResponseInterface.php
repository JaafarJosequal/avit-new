<?php
namespace Josequal\APIMobile\Api\Data;

interface ApiResponseInterface
{
    /**
     * @return bool
     */
    public function getStatus();

    /**
     * @param bool $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * @return mixed
     */
    public function getDataField();

    /**
     * @param mixed $data
     * @return $this
     */
    public function setDataField($data);

    /**
     * @return int
     */
    public function getCode();

    /**
     * @param int $code
     * @return $this
     */
    public function setCode($code);
}
