<?php
namespace Josequal\APIMobile\Model;

use Josequal\APIMobile\Api\V1\AuthenticationInterface;
use Josequal\APIMobile\Api\Data\AuthResponseInterface;
use Josequal\APIMobile\Service\AuthService;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Phrase;

class Authentication implements AuthenticationInterface
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function signUp(
        string $firstname,
        string $lastname,
        string $email,
        string $password,
        string $mobileNumber,
        string $dialCode,
        string $countryCode
    ): AuthResponseInterface {
        $response = $this->authService->register([
            'firstname'     => $firstname,
            'lastname'      => $lastname,
            'email'         => $email,
            'password'      => $password,
            'mobile_number' => $mobileNumber,
            'dial_code'     => $dialCode,
            'country_code'  => $countryCode
        ]);
        
        // If registration failed, throw WebAPI exception
        if (!$response->getStatus()) {
            throw new WebapiException($response->getMessage(), 400);
        }
        
        return $response;
    }

    public function signIn(string $email, string $password): AuthResponseInterface
    {
        $response = $this->authService->login($email, $password);
        
        // If login failed, throw WebAPI exception
        if (!$response->getStatus()) {
            throw new WebapiException($response->getMessage(), 401);
        }
        
        return $response;
    }

    public function logOut(): AuthResponseInterface
    {
        return $this->authService->logout();
    }
}
