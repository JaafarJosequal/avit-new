<?php
namespace Josequal\APIMobile\Api\V1;

use Josequal\APIMobile\Api\Data\PasswordResponseInterface;

interface PasswordInterface
{
    /**
     * Update user password
     *
     * @param string $oldPassword
     * @param string $newPassword
     * @param string $confirmPassword
     * @return \Josequal\APIMobile\Api\Data\PasswordResponseInterface
     */
     public function updatePassword(string $oldPassword, string $newPassword, string $confirmPassword): PasswordResponseInterface;

    /**
     * Send OTP to email for password reset
     *
     * @param string $email
     * @return \Josequal\APIMobile\Api\Data\PasswordResponseInterface
     */
    // public function forgetPassword(string $email);

	/**
     * Send OTP to email or phone for password reset.
     *
     * This method generates and sends an OTP (One-Time Password) to the customer's email or phone,
     * depending on the provided channel. It validates the customer's existence before sending the OTP.
     *
     * @param string $emailOrPhone The customer's email address or phone number.
     * @param string $channel The channel to send OTP through ('email' or 'phone').
     * @return \Josequal\APIMobile\Api\Data\PasswordResponseInterface Returns a response indicating success or failure, along with OTP details.
     */
    public function forgetPassword(string $emailOrPhone, string $channel): PasswordResponseInterface;

	/**
	 * Verify OTP for password reset
	 *
	 * This method verifies the OTP sent to the customer's email or phone.
	 * If the OTP is valid, it returns success and allows the customer to proceed with password reset.
	 *
	 * @param string $otp The OTP code to verify.
	 * @param string $emailOrPhone The customer's email or phone number for identification.
	 * @return \Josequal\APIMobile\Api\Data\PasswordResponseInterface Returns a response indicating if OTP is valid or not, along with emailOrPhone.
	 */
	public function verifyOtp(string $otp, string $emailOrPhone): PasswordResponseInterface;

	/**
	 * Reset customer password using verified OTP
	 *
	 * This method resets the customer's password using the previously verified OTP.
	 * The customer is identified by emailOrPhone.
	 *
	 * @param string $newPassword
	 * @param string $confirmPassword
	 * @param string $emailOrPhone The customer's email or phone number for identification.
	 * @return \Josequal\APIMobile\Api\Data\PasswordResponseInterface
	 */
	public function resetPassword(string $newPassword, string $confirmPassword, string $emailOrPhone): PasswordResponseInterface;

}
