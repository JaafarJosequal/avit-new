<?php
namespace Josequal\APIMobile\Model\Data;

use Josequal\APIMobile\Api\Data\SignUpResponseInterface;
use Josequal\APIMobile\Api\Data\CustomerLoginDataInterface;

class SignUpResponse implements SignUpResponseInterface, \JsonSerializable
{
    private bool $status = false;
    private string $message = '';
    private ?CustomerLoginDataInterface $data = null;
    private int $statusCode = 200;

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): SignUpResponseInterface
    {
        $this->status = $status;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): SignUpResponseInterface
    {
        $this->message = $message;
        return $this;
    }

    public function getData(): ?CustomerLoginDataInterface
    {
        return $this->data;
    }

    public function setData(?CustomerLoginDataInterface $data): SignUpResponseInterface
    {
        $this->data = $data;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $code): SignUpResponseInterface
    {
        $this->statusCode = $code;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'status' => $this->getStatus(),
            'message' => $this->getMessage(),
            'data' => $this->getData() ? $this->getData()->jsonSerialize() : null,
            'status_code' => $this->getStatusCode()
        ];
    }
}
