<?php
namespace Josequal\APIMobile\Api\V1;

use Josequal\APIMobile\Api\Data\AuthResponseInterface;

interface AuthenticationInterface
{
    /**
     * Register a new customer account
     *
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param string $password
     * @param string $mobileNumber
     * @param string $dialCode
     * @param string $countryCode
     * @return \Josequal\APIMobile\Api\Data\AuthResponseInterface
     */
    public function signUp(
        string $firstname,
        string $lastname,
        string $email,
        string $password,
        string $mobileNumber,
        string $dialCode,
        string $countryCode
    ): AuthResponseInterface;

    /**
     * Authenticate customer and return access token
     *
     * @param string $email
     * @param string $password
     * @return \Josequal\APIMobile\Api\Data\AuthResponseInterface
     */
    public function signIn(string $email, string $password): AuthResponseInterface;

    /**
     * Logout the current customer session
     *
     * @return \Josequal\APIMobile\Api\Data\AuthResponseInterface
     */
    public function logOut(): AuthResponseInterface;
}
