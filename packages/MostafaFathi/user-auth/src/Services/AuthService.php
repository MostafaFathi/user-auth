<?php

namespace MostafaFathi\UserAuth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    protected $ssoDriver;

    public function setSsoDriver($driver): self
    {
        $this->ssoDriver = $driver;
        return $this;
    }

    public function handleSsoAuthentication()
    {
        if (!$this->ssoDriver) {
            return null;
        }

        $ssoUser = $this->ssoDriver->user();

        if (!$ssoUser || !$email = $this->ssoDriver->getEmail()) {
            return null;
        }

        // Use the application's User model
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        // Check if user exists by SSO ID or email
        $user = $userModel::where('sso_id', $ssoUser['id'])
            ->orWhere('email', $email)
            ->first();

        if (!$user) {
            $user = $this->createUserFromSso($ssoUser, $userModel);
        } else {
            $user = $this->updateUserFromSso($user, $ssoUser);
        }

        return $user;
    }

    protected function createUserFromSso(array $ssoUser, string $userModel)
    {
        $defaultUserType = \MostafaFathi\UserAuth\Models\UserType::where('name', 'user')->first();

        return $userModel::create([
            'name' => $this->ssoDriver->getName() ?? $ssoUser['email'] ?? 'User',
            'email' => $this->ssoDriver->getEmail(),
            'user_type_id' => $defaultUserType->id,
            'sso_id' => $ssoUser['id'],
            'sso_provider' => config('user-auth.sso.default_protocol'),
            'sso_attributes' => $this->ssoDriver->getAttributes(),
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(32)), // Random password for SSO users
        ]);
    }

    protected function updateUserFromSso($user, array $ssoUser)
    {
        $user->update([
            'sso_id' => $ssoUser['id'],
            'sso_provider' => config('user-auth.sso.default_protocol'),
            'sso_attributes' => $this->ssoDriver->getAttributes(),
        ]);

        return $user;
    }

    public function sendOtp(string $email): ?string
    {
        // Use the application's User model
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        // Check if user exists or can be created
        $user = $userModel::where('email', $email)->first();

        if (!$user && config('user-auth.otp.allow_new_users', true)) {
            // Allow creating new users via OTP
            $defaultUserType = \MostafaFathi\UserAuth\Models\UserType::where('name', 'user')->first();
            $user = $userModel::create([
                'name' => explode('@', $email)[0],
                'email' => $email,
                'user_type_id' => $defaultUserType->id ?? null,
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        if (!$user) {
            return null;
        }

        // Check throttle
        $recentOtp = \MostafaFathi\UserAuth\Models\OtpCode::where('email', $email)
            ->where('created_at', '>', now()->subMinutes(config('user-auth.otp.throttle')))
            ->first();

        if ($recentOtp) {
            return null;
        }

        $code = $this->generateOtpCode();
        $token = Str::random(60);

        \MostafaFathi\UserAuth\Models\OtpCode::create([
            'email' => $email,
            'code' => $code,
            'token' => $token,
            'expires_at' => now()->addMinutes(config('user-auth.otp.expiry')),
        ]);

        return $token;
    }

    public function verifyOtp(string $token, string $code)
    {
        $otp = \MostafaFathi\UserAuth\Models\OtpCode::where('token', $token)
            ->where('code', $code)
            ->first();

        if (!$otp || !$otp->isValid()) {
            return null;
        }

        $otp->markAsUsed();

        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        return $userModel::where('email', $otp->email)->first();
    }

    protected function generateOtpCode(): string
    {
        $length = config('user-auth.otp.length', 6);
        return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    public function getTestUser()
    {
        $testEmail = config('user-auth.development.test_email');

        if (!$testEmail || $testEmail === 'test@example.com') {
            return null;
        }

        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        return $userModel::where('email', $testEmail)->first();
    }

    /**
     * Migrate existing users to have user types
     */
    public function migrateExistingUsers(): array
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $defaultUserType = \MostafaFathi\UserAuth\Models\UserType::where('name', 'user')->first();

        if (!$defaultUserType) {
            return ['success' => false, 'message' => 'Default user type not found'];
        }

        $usersWithoutType = $userModel::whereNull('user_type_id')->get();
        $migratedCount = 0;

        foreach ($usersWithoutType as $user) {
            $user->update(['user_type_id' => $defaultUserType->id]);
            $migratedCount++;
        }

        return [
            'success' => true,
            'migrated_count' => $migratedCount,
            'total_users' => $usersWithoutType->count()
        ];
    }
}
