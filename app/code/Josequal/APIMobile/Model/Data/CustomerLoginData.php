<?php
namespace Josequal\APIMobile\Model\Data;

use Josequal\APIMobile\Api\Data\CustomerLoginDataInterface;

class CustomerLoginData implements CustomerLoginDataInterface, \JsonSerializable
{
    private string $firstname = '';
    private string $lastname = '';
    private string $email = '';
    private ?string $mobileNumber = null;
    private ?string $dialCode = null;
    private ?string $countryCode = null;
    private string $token = '';
    private ?string $password = null;
    private ?string $image = null;
    private ?string $address = null;
    private int $cartQuantity = 0;

    public function getFirstname(): string { return $this->firstname; }
    public function setFirstname(string $value): CustomerLoginDataInterface { $this->firstname = $value; return $this; }

    public function getLastname(): string { return $this->lastname; }
    public function setLastname(string $value): CustomerLoginDataInterface { $this->lastname = $value; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $value): CustomerLoginDataInterface { $this->email = $value; return $this; }

    public function getMobileNumber(): ?string { return $this->mobileNumber; }
    public function setMobileNumber(?string $value): CustomerLoginDataInterface { $this->mobileNumber = $value; return $this; }

    public function getDialCode(): ?string { return $this->dialCode; }
    public function setDialCode(?string $value): CustomerLoginDataInterface { $this->dialCode = $value; return $this; }

    public function getCountryCode(): ?string { return $this->countryCode; }
    public function setCountryCode(?string $value): CustomerLoginDataInterface { $this->countryCode = $value; return $this; }

    public function getToken(): string { return $this->token; }
    public function setToken(string $value): CustomerLoginDataInterface { $this->token = $value; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $value): CustomerLoginDataInterface { $this->password = $value; return $this; }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): CustomerLoginDataInterface
    {
        $this->image = $image;
        return $this;
    }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $value): CustomerLoginDataInterface { $this->address = $value; return $this; }

    public function getCartQuantity(): int { return $this->cartQuantity; }
    public function setCartQuantity(int $value): CustomerLoginDataInterface { $this->cartQuantity = $value; return $this; }

    public function jsonSerialize(): array
    {
        return [
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'email' => $this->getEmail(),
            'mobile_number' => $this->getMobileNumber(),
            'dial_code' => $this->getDialCode(),
            'country_code' => $this->getCountryCode(),
            'token' => $this->getToken(),
            'password' => $this->getPassword(),
            'image' => $this->getImage(),
            'address' => $this->address,
            'cart_quantity' => $this->getCartQuantity(),
        ];
    }
}
