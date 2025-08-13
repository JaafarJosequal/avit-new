<?php
namespace Josequal\APIMobile\Model\Data;

use Josequal\APIMobile\Api\Data\UserDataInterface;

class UserData implements UserDataInterface, \JsonSerializable
{
    private int $id = 0;
    private string $firstname = '';
    private string $lastname = '';
    private string $email = '';
    private string $mobileNumber = '';
    private string $dialCode = '';
    private string $countryCode = '';
    private string $image = '';
    private string $birthdate = '';
    private string $address = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): UserDataInterface
    {
        $this->id = $id;
        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): UserDataInterface
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): UserDataInterface
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): UserDataInterface
    {
        $this->email = $email;
        return $this;
    }

    public function getMobileNumber(): string
    {
        return $this->mobileNumber;
    }

    public function setMobileNumber(string $mobileNumber): UserDataInterface
    {
        $this->mobileNumber = $mobileNumber;
        return $this;
    }

    public function getDialCode(): string
    {
        return $this->dialCode;
    }

    public function setDialCode(string $dialCode): UserDataInterface
    {
        $this->dialCode = $dialCode;
        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): UserDataInterface
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): UserDataInterface
    {
        $this->image = $image;
        return $this;
    }

    public function getBirthdate(): string
    {
        return $this->birthdate;
    }

    public function setBirthdate(string $birthdate): UserDataInterface
    {
        $this->birthdate = $birthdate;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): UserDataInterface
    {
        $this->address = $address;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'email' => $this->getEmail(),
            'mobile_number' => $this->getMobileNumber(),
            'dial_code' => $this->getDialCode(),
            'country_code' => $this->getCountryCode(),
            'image' => $this->getImage(),
            'birthdate' => $this->getBirthdate(),
            'address' => $this->getAddress()
        ];
    }
} 