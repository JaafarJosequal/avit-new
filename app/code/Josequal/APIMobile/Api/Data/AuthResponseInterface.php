<?php
namespace Josequal\APIMobile\Api\Data;

interface AuthResponseInterface
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
    public function setStatus(bool $status): AuthResponseInterface;

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
    public function setMessage(string $message): AuthResponseInterface;

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
    public function setData(?CustomerLoginDataInterface $data): AuthResponseInterface;

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
    public function setStatusCode(int $code): AuthResponseInterface;
}
