<?php

namespace MostafaFathi\UserAuth\Services;

use MostafaFathi\UserAuth\Models\User;
use MostafaFathi\UserAuth\Models\UserType;
use MostafaFathi\UserAuth\Models\OtpCode;
use MostafaFathi\UserAuth\Contracts\SsoDriverInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthService
{
    protected $ssoDriver;
    
    public function setSsoDriver(SsoDriverInterface $driver): self
    {
        $this->ssoDriver = $driver;
        return $this;
    }
    
    public function handleSsoAuthentication(): ?User
    {
        if (!$this->ssoDriver) {
            return null;
        }
        
        $ssoUser = $this->ssoDriver->user();
        
        if (!$ssoUser || !$email = $this->ssoDriver->getEmail()) {
            return null;
        }
        
        // Check if user exists by SSO ID or email
        $user = User::where('sso_id', $ssoUser['id'])
            ->orWhere('email', $email)
            ->first();
            
        if (!$user) {
            $user = $this->createUserFromSso($ssoUser);
        } else {
            $user = $this->updateUserFromSso($user, $ssoUser);
        }
        
        return $user;
    }
    
    protected function createUserFromSso(array $ssoUser): User
    {
        $defaultUserType = UserType::where('name', 'user')->first();
        
        return User::create([
            'name' => $this->ssoDriver->getName() ?? $ssoUser['email'],
            'email' => $this->ssoDriver->getEmail(),
            'user_type_id' => $defaultUserType->id,
            'sso_id' => $ssoUser['id'],
            'sso_provider' => config('user-auth.sso.default_protocol'),
            'sso_attributes' => $this->ssoDriver->getAttributes(),
            'email_verified_at' => now(),
        ]);
    }
    
    protected function updateUserFromSso(User $user, array $ssoUser): User
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
        // Check throttle
        $recentOtp = OtpCode::where('email', $email)
            ->where('created_at', '>', now()->subMinutes(config('user-auth.otp.throttle')))
            ->first();
            
        if ($recentOtp) {
            return null;
        }
        
        $code = $this->generateOtpCode();
        $token = Str::random(60);
        
        OtpCode::create([
            'email' => $email,
            'code' => $code,
            'token' => $token,
            'expires_at' => now()->addMinutes(config('user-auth.otp.expiry')),
        ]);
        
        // Here you would send the email with OTP code
        // Mail::to($email)->send(new OtpMail($code));
        
        return $token;
    }
    
    public function verifyOtp(string $token, string $code): ?User
    {
        $otp = OtpCode::where('token', $token)
            ->where('code', $code)
            ->first();
            
        if (!$otp || !$otp->isValid()) {
            return null;
        }
        
        $otp->markAsUsed();
        
        $user = User::where('email', $otp->email)->first();
        
        if (!$user) {
            $defaultUserType = UserType::where('name', 'user')->first();
            
            $user = User::create([
                'name' => explode('@', $otp->email)[0],
                'email' => $otp->email,
                'user_type_id' => $defaultUserType->id,
                'email_verified_at' => now(),
            ]);
        }
        
        return $user;
    }
    
    protected function generateOtpCode(): string
    {
        $length = config('user-auth.otp.length', 6);
        
        return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
    
    public function getTestUser(): ?User
    {
        $testEmail = config('user-auth.development.test_email');
        
        if (!$testEmail || $testEmail === 'test@example.com') {
            return null;
        }
        
        return User::where('email', $testEmail)->first();
    }
}
