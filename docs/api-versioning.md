# API Versioning Documentation

## Overview
The Colame API now supports versioning to ensure backward compatibility while allowing for API evolution. All new API development should use versioned endpoints.

## Current Versions
- **v1**: Current stable version (introduced 2025-09-19)

## Authentication Endpoints

### Version 1 (Recommended)
All v1 authentication endpoints are prefixed with `/api/v1/auth/`

| Method | Endpoint | Description | Authentication |
|--------|----------|-------------|----------------|
| POST | `/api/v1/auth/login` | User login | Public |
| POST | `/api/v1/auth/register` | User registration | Public |
| GET | `/api/v1/auth/user` | Get current user | Required |
| POST | `/api/v1/auth/logout` | Logout current session | Required |
| POST | `/api/v1/auth/refresh-token` | Refresh access token | Required |
| POST | `/api/v1/auth/revoke-all` | Revoke all user tokens | Required |


## Request Examples

### Login
```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password",
    "device_name": "mobile-app"
  }'
```

### Get User (Authenticated)
```bash
curl -X GET http://localhost/api/v1/auth/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Refresh Token
```bash
curl -X POST http://localhost/api/v1/auth/refresh-token \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "device_name": "mobile-app"
  }'
```

### Revoke All Tokens
```bash
curl -X POST http://localhost/api/v1/auth/revoke-all \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

## Response Format

All API responses follow a consistent JSON structure:

### Successful Authentication
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com"
  },
  "token": "1|laravel_sanctum_token_here"
}
```

### Error Response
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

## API Structure

All API endpoints now follow a consistent versioned structure:
- Base URL: `/api/v1/`
- Authentication endpoints: `/api/v1/auth/`
- Module endpoints: `/api/v1/{module}/`

## Module APIs

All module APIs are also available under the v1 namespace:

- `/api/v1/orders/` - Order management
- `/api/v1/items/` - Item management
- `/api/v1/menu/` - Menu operations
- `/api/v1/locations/` - Location management
- `/api/v1/settings/` - Settings
- `/api/v1/offers/` - Offers and promotions
- `/api/v1/onboarding/` - Onboarding flow

## Best Practices

1. **Always use versioned endpoints** for new development
2. **Include Accept header** with `application/json`
3. **Handle token expiration** gracefully with refresh token flow
4. **Store tokens securely** in mobile apps
5. **Use device_name** to identify different client sessions

## Support

For API support or migration assistance, please refer to the main project documentation or contact the development team.