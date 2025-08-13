<?php
namespace Josequal\APIMobile\Model;

use Josequal\APIMobile\Api\V1\ProfileInterface;
use Josequal\APIMobile\Api\Data\ProfileResponseInterface;
use Josequal\APIMobile\Service\ProfileService;

class Profile implements ProfileInterface
{
    private ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function getProfile(): ProfileResponseInterface
    {
        return $this->profileService->getProfile();
    }

    public function updateProfile(
        ?string $firstname = null,
        ?string $lastname = null,
        ?string $mobileNumber = null,
        ?string $dialCode = null,
        ?string $countryCode = null
    ): ProfileResponseInterface {
        $data = [];
        
        if ($firstname !== null) {
            $data['firstname'] = $firstname;
        }
        if ($lastname !== null) {
            $data['lastname'] = $lastname;
        }
        if ($mobileNumber !== null) {
            $data['mobile_number'] = $mobileNumber;
        }
        if ($dialCode !== null) {
            $data['dial_code'] = $dialCode;
        }
        if ($countryCode !== null) {
            $data['country_code'] = $countryCode;
        }

        return $this->profileService->updateProfile($data);
    }
} 