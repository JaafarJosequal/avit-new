# Password APIs Documentation

This document describes the password management APIs in the APIMobile module.

## Overview

The password APIs provide three main functionalities:
1. **Update Password** - For authenticated users to change their password
2. **Forget Password** - Send OTP to email or phone for password reset
3. **Reset Password** - Reset password using OTP

## API Endpoints

### 1. Update Password

**Endpoint:** `POST /V1/user/update-password`

**Authentication:** Required (Bearer token)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "oldPassword": "current_password",
    "newPassword": "new_password",
    "confirmPassword": "new_password"
}
```

**Response:**
```json
{
    "status": true,
    "message": "Password updated successfully",
    "data": {},
    "status_code": 200
}
```

**Error Responses:**
- `401` - Invalid or expired token
- `401` - Incorrect old password
- `400` - Passwords don't match
- `400` - Password too short (minimum 9 characters)

### 2. Forget Password

**Endpoint:** `POST /V1/user/forget-password`

**Authentication:** Not required

**Request Body:**
```json
{
    "emailOrPhone": "customer@example.com",
    "channel": "email"
}
```

OR

```json
{
    "emailOrPhone": "+1234567890",
    "channel": "phone"
}
```

**Response:**
```json
{
    "status": true,
    "message": "OTP sent successfully.",
    "data": {
        "otp": "123456"
    },
    "status_code": 200
}
```

**Error Responses:**
- `404` - Customer not found
- `400` - Invalid channel (use "email" or "phone")
- `500` - Unable to send OTP

### 3. Reset Password

**Endpoint:** `POST /V1/user/reset-password`

**Authentication:** Not required

**Request Body:**
```json
{
    "email": "customer@example.com",
    "otp": "123456",
    "newPassword": "new_password",
    "confirmPassword": "new_password"
}
```

**Response:**
```json
{
    "status": true,
    "message": "Password reset successfully",
    "data": {},
    "status_code": 200
}
```

**Error Responses:**
- `404` - Customer not found
- `401` - OTP expired or not found
- `400` - Passwords don't match
- `400` - Password too short (minimum 9 characters)

## Implementation Details

### OTP System

The OTP system includes:
- 6-digit numeric OTP generation
- 15-minute expiration time
- Database storage in `customer_otp` table
- Email template support
- SMS support (placeholder for integration)

### Database Schema

The `customer_otp` table has the following structure:
- `otp_id` (Primary Key)
- `customer_id` (Foreign Key to customer_entity)
- `otp` (OTP code)
- `expires_at` (Expiration timestamp)
- `created_at` (Creation timestamp)

### Email Template

The OTP email uses the template `password_reset_otp_template` located at:
`app/code/Josequal/APIMobile/view/frontend/email/password_reset_otp_template.html`

### Security Features

1. **Password Validation:**
   - Minimum 9 characters
   - Password confirmation required
   - Old password verification for updates

2. **OTP Security:**
   - Time-based expiration (15 minutes)
   - Single-use (deleted after use)
   - Customer-specific validation

3. **Authentication:**
   - Token-based authentication for password updates
   - Anonymous access for password reset flow

## Usage Examples

### cURL Examples

**Update Password:**
```bash
curl -X POST "https://your-domain.com/rest/V1/user/update-password" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "oldPassword": "oldpass123",
    "newPassword": "newpass123",
    "confirmPassword": "newpass123"
  }'
```

**Forget Password (Email):**
```bash
curl -X POST "https://your-domain.com/rest/V1/user/forget-password" \
  -H "Content-Type: application/json" \
  -d '{
    "emailOrPhone": "customer@example.com",
    "channel": "email"
  }'
```

**Reset Password:**
```bash
curl -X POST "https://your-domain.com/rest/V1/user/reset-password" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "otp": "123456",
    "newPassword": "newpass123",
    "confirmPassword": "newpass123"
  }'
```

## Configuration

### Email Configuration

The email sender details are configured in Magento admin:
- **General Contact** email settings
- Email template: `password_reset_otp_template`

### SMS Integration

To enable SMS OTP, implement the `sendOtpSms` method in:
`app/code/Josequal/APIMobile/Model/V1/Password.php`

Example SMS service integration:
```php
private function sendOtpSms(string $phoneNumber, string $otp)
{
    // Integrate with your SMS service (Twilio, Nexmo, etc.)
    $smsService = new YourSmsService();
    return $smsService->sendSms($phoneNumber, 'Your OTP is: ' . $otp);
}
```

## Testing

For testing purposes, OTP codes are logged to the Magento log file when SMS is used. Check:
`var/log/system.log` or `var/log/exception.log`

## Troubleshooting

### Common Issues

1. **OTP not found:**
   - Check if customer exists
   - Verify OTP hasn't expired
   - Check database connection

2. **Email not sending:**
   - Verify email template exists
   - Check SMTP configuration
   - Review email logs

3. **Authentication errors:**
   - Verify token is valid and not expired
   - Check token format (Bearer prefix)

### Log Files

Check these log files for debugging:
- `var/log/system.log`
- `var/log/exception.log`
- `var/log/debug.log`

## Dependencies

The password APIs depend on:
- Magento 2.4.x
- Customer module
- Email module
- Database module
- OAuth token system

## Installation

1. Copy the module to `app/code/Josequal/APIMobile/`
2. Run setup commands:
```bash
php bin/magento module:enable Josequal_APIMobile
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

The setup will automatically create the `customer_otp` table.
