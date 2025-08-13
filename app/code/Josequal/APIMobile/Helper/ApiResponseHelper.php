<?php
namespace Josequal\APIMobile\Helper;

class ApiResponseHelper
{
    public function format(bool $status, string $message, $data = null, int $statusCode = 200): array
    {
        return [
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'status_code' => $statusCode
        ];
    }
}
