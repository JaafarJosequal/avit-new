<?php
namespace Josequal\APIMobile\Model\Data;

use Josequal\APIMobile\Api\Data\PasswordResponseInterface;

class PasswordResponse implements PasswordResponseInterface, \JsonSerializable
{
    private bool $status = false;
    private string $message = '';
    private $data = null;
    private int $statusCode = 200;

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): PasswordResponseInterface
    {
        $this->status = $status;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): PasswordResponseInterface
    {
        $this->message = $message;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): PasswordResponseInterface
    {
        $this->data = $data;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $code): PasswordResponseInterface
    {
        $this->statusCode = $code;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'status' => $this->getStatus(),
            'message' => $this->getMessage(),
            'data' => $this->getData(),
            'status_code' => $this->getStatusCode()
        ];
    }
}
