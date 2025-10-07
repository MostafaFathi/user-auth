# Laravel User Auth Package

A comprehensive Laravel package for user type management with SSO and OTP authentication support.

## Features

- Multiple user types with permissions
- SSO authentication (SAML, OpenID Connect)
- OTP-based authentication as fallback
- Development-friendly with test email support
- Flexible configuration

## Requirements

- PHP 8.0 or higher
- Laravel 9.x or 10.x

## Installation

You can install the package via Composer:

```bash
composer require mostafafathi/user-auth
```

## Configuration
### Publish the configuration file:

```bash
php artisan vendor:publish --provider="MostafaFathi\\UserAuth\\UserAuthServiceProvider" --tag=user-auth-config
```

### Publish and run migrations:

```bash
php artisan vendor:publish --provider="MostafaFathi\\UserAuth\\UserAuthServiceProvider" --tag=user-auth-migrations
php artisan migrate
```

### Seed user types:
```bash
php artisan db:seed --class=UserTypeSeeder
```

## Usage
## Environment Variables
Add these to your .env file:

```dotenv
USER_AUTH_METHOD=sso
SSO_ENABLED=true
OTP_ENABLED=true
TEST_EMAIL=test@example.com
```

## SSO Configuration
Configure your SSO providers in the "config/user-auth.php" file.

## License
The MIT License (MIT). Please see License File for more information.




