<?php
namespace Josequal\APIMobile\Api\Data;

interface CustomerLoginDataInterface
{
    /**
     * Get customer's first name
     *
     * @return string
     */
    public function getFirstname(): string;

    /**
     * Set customer's first name
     *
     * @param string $value
     * @return $this
     */
    public function setFirstname(string $value): CustomerLoginDataInterface;

    /**
     * Get customer's last name
     *
     * @return string
     */
    public function getLastname(): string;

    /**
     * Set customer's last name
     *
     * @param string $value
     * @return $this
     */
    public function setLastname(string $value): CustomerLoginDataInterface;

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail(): string;

    /**
     * Set email
     *
     * @param string $value
     * @return $this
     */
    public function setEmail(string $value): CustomerLoginDataInterface;

    /**
     * Get mobile number
     *
     * @return string|null
     */
    public function getMobileNumber(): ?string;

    /**
     * Set mobile number
     *
     * @param string|null $value
     * @return $this
     */
    public function setMobileNumber(?string $value): CustomerLoginDataInterface;

    /**
     * Get dial code
     *
     * @return string|null
     */
    public function getDialCode(): ?string;

    /**
     * Set dial code
     *
     * @param string|null $value
     * @return $this
     */
    public function setDialCode(?string $value): CustomerLoginDataInterface;

    /**
     * Get country code
     *
     * @return string|null
     */
    public function getCountryCode(): ?string;

    /**
     * Set country code
     *
     * @param string|null $value
     * @return $this
     */
    public function setCountryCode(?string $value): CustomerLoginDataInterface;

    /**
     * Get token
     *
     * @return string
     */
    public function getToken(): string;

    /**
     * Set token
     *
     * @param string $value
     * @return $this
     */
    public function setToken(string $value): CustomerLoginDataInterface;

    /**
     * Get password
     *
     * @return string|null
     */
    public function getPassword(): ?string;

    /**
     * Set password
     *
     * @param string|null $value
     * @return $this
     */
    public function setPassword(?string $value): CustomerLoginDataInterface;

    /**
     * Get profile image
     *
     * @return string|null
     */
    public function getImage(): ?string;

    /**
     * Set profile image
     *
     * @param string|null $image
     * @return $this
     */
    public function setImage(?string $image): CustomerLoginDataInterface;

    /**
     * Get address
     *
     * @return string|null
     */
    public function getAddress(): ?string;

    /**
     * Set address
     *
     * @param string|null $address
     * @return $this
     */
    public function setAddress(?string $address): CustomerLoginDataInterface;

    /**
     * Serialize to array for JSON
     *
     * @return array
     */
    public function jsonSerialize(): array;
}
