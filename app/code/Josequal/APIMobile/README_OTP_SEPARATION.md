# OTP API Separation - APIMobile Module

## Overview

This document describes the separation of OTP verification from the password reset process in the APIMobile module. The OTP verification is now a standalone API that must be called before resetting the password.

## New API Structure

### 1. Send OTP
**Endpoint:** `POST /V1/user/forget-password`
**Purpose:** Send OTP to customer's email or phone
**Parameters:**
- `emailOrPhone` (string, required) - Customer's email or phone number
- `channel` (string, required) - "email" or "phone"

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

### 2. Verify OTP (NEW)
**Endpoint:** `POST /V1/user/verify-otp`
**Purpose:** Verify OTP before allowing password reset
**Authentication:** Required (Bearer token or session)
**Parameters:**
- `otp` (string, required) - OTP code received

**Response:**
```json
{
    "status": true,
    "message": "OTP verified successfully",
    "data": {
        "verified": true
    },
    "status_code": 200
}
```

### 3. Reset Password
**Endpoint:** `POST /V1/user/reset-password`
**Purpose:** Reset password using verified OTP
**Authentication:** Required (Bearer token or session)
**Parameters:**
- `newPassword` (string, required) - New password
- `confirmPassword` (string, required) - Confirm new password

**Response:**
```json
{
    "status": true,
    "message": "Password reset successfully",
    "data": [],
    "status_code": 200
}
```

## Workflow

### Before (Old Flow)
1. Customer requests password reset → `forget-password`
2. Customer receives OTP
3. Customer submits OTP + new password → `reset-password`

### After (New Flow)
1. Customer requests password reset → `forget-password`
2. Customer receives OTP
3. Customer verifies OTP → `verify-otp` (NEW STEP)
4. If OTP is valid, customer can proceed to reset password → `reset-password`

## Authentication

Both `verify-otp` and `reset-password` APIs require authentication. The system supports:

1. **Bearer Token Authentication**: Include `Authorization: Bearer {token}` header
2. **Session Authentication**: Customer must be logged in via session

## Benefits

1. **Better Security**: OTP verification is separated from password reset
2. **Improved UX**: Customer gets immediate feedback on OTP validity
3. **Cleaner Logic**: Each API has a single responsibility
4. **Better Error Handling**: Specific error messages for each step
5. **Flexibility**: Can add additional validation steps between OTP verification and password reset
6. **Simplified Parameters**: No need to pass email/OTP in reset-password API

## Implementation Details

### New Method: `verifyOtp()`
- Validates customer from session or token
- Checks OTP exists and is not expired
- Returns success/failure response
- Does NOT delete OTP (only `resetPassword` does that)

### Updated Method: `resetPassword()`
- Validates customer from session or token
- Checks for valid OTP before resetting password
- Deletes OTP after successful password reset
- Improved error handling

## Error Responses

### Authentication Errors
```json
{
    "status": false,
    "message": "Unauthorized - Please login or provide valid token",
    "data": [],
    "status_code": 401
}
```

### OTP Verification Errors
```json
{
    "status": false,
    "message": "OTP expired or not found",
    "data": [],
    "status_code": 401
}
```

### Password Reset Errors
```json
{
    "status": false,
    "message": "No valid OTP found. Please request a new OTP first.",
    "data": [],
    "status_code": 401
}
```

```json
{
    "status": false,
    "message": "New password and confirm password do not match",
    "data": [],
    "status_code": 400
}
```

```json
{
    "status": false,
    "message": "Password must be at least 9 characters long",
    "data": [],
    "status_code": 400
}
```

## Usage Examples

### Step 1: Request OTP
```bash
curl -X POST "https://your-domain.com/rest/V1/user/forget-password" \
  -H "Content-Type: application/json" \
  -d '{
    "emailOrPhone": "customer@example.com",
    "channel": "email"
  }'
```

### Step 2: Verify OTP
```bash
curl -X POST "https://your-domain.com/rest/V1/user/verify-otp" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "otp": "123456"
  }'
```

### Step 3: Reset Password
```bash
curl -X POST "https://your-domain.com/rest/V1/user/reset-password" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "newPassword": "newpassword123",
    "confirmPassword": "newpassword123"
  }'
```

## Migration Notes

- Existing `forget-password` API remains unchanged
- `verify-otp` and `reset-password` now require authentication
- `verify-otp` only needs `otp` parameter (no email)
- `reset-password` only needs `newPassword` and `confirmPassword` (no email/otp)
- All APIs maintain backward compatibility where possible

## Security Considerations

1. OTP expires after 15 minutes
2. OTP is deleted after successful password reset
3. Each OTP can only be used once
4. Customer must exist and be active
5. All APIs are rate-limited to prevent abuse
6. Authentication required for sensitive operations

