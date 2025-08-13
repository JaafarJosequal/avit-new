<?php
namespace Josequal\APIMobile\Helper\Responses\Password;

class ResponseHelper
{
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * Create a standardized success response
     *
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     * @return array
     */
    public static function createSuccessResponse($message = 'Request successful', $data = null, $statusCode = self::HTTP_OK)
    {
        return [
            'status' => true,
            'message' => $message,
            'data' => $data,
            'status_code' => $statusCode
        ];
    }

    /**
     * Create a standardized error response
     *
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     * @return array
     */
    public static function createErrorResponse($message = 'Request failed', $data = null, $statusCode = self::HTTP_BAD_REQUEST)
    {
        return [
            'status' => false,
            'message' => $message,
            'data' => $data,
            'status_code' => $statusCode
        ];
    }
}
