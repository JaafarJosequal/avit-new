<?php

namespace Josequal\APIMobile\Api\Data;

/**
 * Interface SignUpResponseInterface
 */
interface SignUpResponseInterface
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
    public function setStatus(bool $status): SignUpResponseInterface;

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
    public function setMessage(string $message): SignUpResponseInterface;

    /**
     * Get data
     *
     * @return \Josequal\APIMobile\Api\Data\CustomerLoginDataInterface|null
     */
    public function getData(): ?CustomerLoginDataInterface;

    /**
     * Set data
     *
     * @param \Josequal\APIMobile\Api\Data\CustomerLoginDataInterface|null $data
     * @return $this
     */
    public function setData(?CustomerLoginDataInterface $data): SignUpResponseInterface;

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
    public function setStatusCode(int $code): SignUpResponseInterface;
}
