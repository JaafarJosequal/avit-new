<?php
namespace Josequal\APIMobile\Api\Data;

interface UserDataInterface
{
    /**
     * Get user ID
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Set user ID
     *
     * @param int $id
     * @return $this
     */
    public function setId(int $id): UserDataInterface;

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname(): string;

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return $this
     */
    public function setFirstname(string $firstname): UserDataInterface;

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname(): string;

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return $this
     */
    public function setLastname(string $lastname): UserDataInterface;

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail(): string;

    /**
     * Set email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): UserDataInterface;

    /**
     * Get mobile number
     *
     * @return string
     */
    public function getMobileNumber(): string;

    /**
     * Set mobile number
     *
     * @param string $mobileNumber
     * @return $this
     */
    public function setMobileNumber(string $mobileNumber): UserDataInterface;

    /**
     * Get dial code
     *
     * @return string
     */
    public function getDialCode(): string;

    /**
     * Set dial code
     *
     * @param string $dialCode
     * @return $this
     */
    public function setDialCode(string $dialCode): UserDataInterface;

    /**
     * Get country code
     *
     * @return string
     */
    public function getCountryCode(): string;

    /**
     * Set country code
     *
     * @param string $countryCode
     * @return $this
     */
    public function setCountryCode(string $countryCode): UserDataInterface;

    /**
     * Get image
     *
     * @return string
     */
    public function getImage(): string;

    /**
     * Set image
     *
     * @param string $image
     * @return $this
     */
    public function setImage(string $image): UserDataInterface;

    /**
     * Get birthdate
     *
     * @return string
     */
    public function getBirthdate(): string;

    /**
     * Set birthdate
     *
     * @param string $birthdate
     * @return $this
     */
    public function setBirthdate(string $birthdate): UserDataInterface;

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress(): string;

    /**
     * Set address
     *
     * @param string $address
     * @return $this
     */
    public function setAddress(string $address): UserDataInterface;
} 