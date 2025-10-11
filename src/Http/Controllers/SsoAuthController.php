<?php

namespace MostafaFathi\UserAuth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use MostafaFathi\UserAuth\Services\SsoAuthService;

class SsoAuthController extends Controller
{
    protected $authService;

    public function __construct(SsoAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function redirectToSso(Request $request)
    {
        $protocol = config('user-auth.sso.default_protocol');

        // Implement protocol-based redirection
        // This would use the appropriate driver
    }

    public function ssoCallback(Request $request)
    {
        // Check if we should use test user in development
        if (config('user-auth.development.bypass_sso') && $testUser = $this->authService->getTestUser()) {
            Auth::login($testUser);
            return redirect()->intended('/');
        }

        $user = $this->authService->handleSsoAuthentication();

        if ($user) {
            Auth::login($user);
            return redirect()->intended('/');
        }

        return redirect()->route('login')->withErrors([
            'sso' => 'SSO authentication failed.',
        ]);
    }

    public function requestOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $token = $this->authService->sendOtp($request->email);

        if (!$token) {
            return response()->json([
                'error' => 'Please wait before requesting another OTP.',
            ], 429);
        }

        return response()->json([
            'message' => 'OTP sent successfully.',
            'token' => $token,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'code' => 'required',
        ]);

        $user = $this->authService->verifyOtp($request->token, $request->code);

        if ($user) {
            Auth::login($user);

            return response()->json([
                'message' => 'Login successful.',
                'user' => $user,
            ]);
        }

        return response()->json([
            'error' => 'Invalid OTP code.',
        ], 401);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
