<?php
namespace Josequal\APIMobile\Api\Data;

interface PasswordResponseInterface
{
    /**
     * Get status
     *
     * @return bool
     */
    public function getStatus(): bool;

    /**
     * Set status
     *
     * @param bool $status
     * @return $this
     */
    public function setStatus(bool $status): PasswordResponseInterface;

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): PasswordResponseInterface;

    /**
     * Get data
     *
     * @return mixed
     */
    public function getData();

    /**
     * Set data
     *
     * @param mixed $data
     * @return $this
     */
    public function setData($data): PasswordResponseInterface;

    /**
     * Get status code
     *
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * Set status code
     *
     * @param int $code
     * @return $this
     */
    public function setStatusCode(int $code): PasswordResponseInterface;
}
