<?php
namespace Josequal\APIMobile\Api\V1;

use Josequal\APIMobile\Api\Data\ProfileResponseInterface;

interface ProfileInterface
{
    /**
     * Get current profile data
     *
     * @return \Josequal\APIMobile\Api\Data\ProfileResponseInterface
     */
    public function getProfile(): ProfileResponseInterface;

    /**
     * Update profile from request data
     *
     * @param string|null $firstname
     * @param string|null $lastname
     * @param string|null $mobileNumber
     * @param string|null $dialCode
     * @param string|null $countryCode
     * @return \Josequal\APIMobile\Api\Data\ProfileResponseInterface
     */
    public function updateProfile(
        ?string $firstname = null,
        ?string $lastname = null,
        ?string $mobileNumber = null,
        ?string $dialCode = null,
        ?string $countryCode = null
    ): ProfileResponseInterface;
} 