<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ActionMail;
use App\Models\ApiToken;
use App\Models\OneTimePassword;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function signup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $validated['email'] = Str::lower($validated['email']);
        $user = User::create($validated);
        $token = $this->createApiToken($user);

        Mail::to($user->email)->send(new ActionMail(
            'Welcome to ASTA Engineering',
            "Hello {$user->name},\n\nYour admin account has been created successfully. Welcome to ASTA Engineering.",
        ));

        return response()->json([
            'success' => true,
            'message' => 'Admin account created successfully.',
            'data' => ['admin' => $user, 'token' => $token, 'token_type' => 'Bearer'],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($validated['login']);
        $user = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? User::where('email', Str::lower($login))->first()
            : User::where('phone', $login)->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid login credentials.'], 401);
        }

        Mail::to($user->email)->send(new ActionMail(
            'Welcome Back to ASTA Engineering',
            "Hello {$user->name},\n\nYou have successfully logged in to your ASTA Engineering admin account.",
        ));

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'admin' => $user,
                'token' => $this->createApiToken($user),
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('api_token')->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $email = $this->validatedEmail($request);
        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No admin account was found for this email.'], 404);
        }

        return $this->sendOtp($email, 'password_reset', 'Password reset OTP');
    }

    public function resendPasswordOtp(Request $request): JsonResponse
    {
        $email = $this->validatedEmail($request);

        if (! User::where('email', $email)->exists()) {
            return response()->json(['success' => false, 'message' => 'No admin account was found for this email.'], 404);
        }

        return $this->sendOtp($email, 'password_reset', 'Password reset OTP');
    }

    public function verifyPasswordOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
            'otp' => ['required', 'digits:4'],
        ]);

        $otp = $this->findAndCheckOtp(Str::lower($validated['email']), 'password_reset', $validated['otp']);
        if ($otp instanceof JsonResponse) {
            return $otp;
        }

        $plainResetToken = Str::random(64);
        $otp->update([
            'verified_at' => now(),
            'verification_token_hash' => hash('sha256', $plainResetToken),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
            'data' => ['reset_token' => $plainResetToken],
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
            'reset_token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $email = Str::lower($validated['email']);
        $otp = OneTimePassword::where('email', $email)
            ->where('purpose', 'password_reset')
            ->where('verification_token_hash', hash('sha256', $validated['reset_token']))
            ->whereNotNull('verified_at')
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp) {
            return response()->json(['success' => false, 'message' => 'The reset token is invalid or expired.'], 422);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->update(['password' => $validated['password']]);
        $otp->update(['consumed_at' => now()]);
        $user->apiTokens()->delete();

        Mail::to($user->email)->send(new ActionMail(
            'Password Reset Successful',
            "Hello {$user->name},\n\nYour ASTA Engineering admin password was reset successfully. If you did not make this change, contact support immediately.",
        ));

        return response()->json(['success' => true, 'message' => 'Password reset successfully. Please log in again.']);
    }

    public function emailLogin(Request $request): JsonResponse
    {
        $email = $this->validatedEmail($request);

        if (! User::where('email', $email)->exists()) {
            return response()->json(['success' => false, 'message' => 'No admin account was found for this email.'], 404);
        }

        return $this->sendOtp($email, 'email_login', 'Email login OTP');
    }

    public function resendEmailLoginOtp(Request $request): JsonResponse
    {
        return $this->emailLogin($request);
    }

    public function verifyEmailLoginOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
            'otp' => ['required', 'digits:4'],
        ]);
        $email = Str::lower($validated['email']);
        $otp = $this->findAndCheckOtp($email, 'email_login', $validated['otp']);

        if ($otp instanceof JsonResponse) {
            return $otp;
        }

        $otp->update(['verified_at' => now(), 'consumed_at' => now()]);
        $user = User::where('email', $email)->firstOrFail();
        $token = $this->createApiToken($user);

        Mail::to($user->email)->send(new ActionMail(
            'Welcome Back to ASTA Engineering',
            "Hello {$user->name},\n\nYou have successfully logged in using email verification.",
        ));

        return response()->json([
            'success' => true,
            'message' => 'Email verified. Login successful.',
            'data' => [
                'admin' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    private function validatedEmail(Request $request): string
    {
        $validated = $request->validate(['email' => ['required', 'email:rfc']]);

        return Str::lower($validated['email']);
    }

    private function sendOtp(string $email, string $purpose, string $subject): JsonResponse
    {
        $recent = OneTimePassword::where('email', $email)
            ->where('purpose', $purpose)
            ->where('created_at', '>', now()->subSeconds(60))
            ->exists();

        if ($recent) {
            return response()->json(['success' => false, 'message' => 'Please wait 60 seconds before requesting another OTP.'], 429);
        }

        OneTimePassword::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = (string) random_int(1000, 9999);
        OneTimePassword::create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($email)->send(new ActionMail(
            $subject,
            "Your ASTA Engineering verification code is {$code}.\n\nThis code expires in 10 minutes.",
        ));

        $data = ['expires_in_seconds' => 600, 'resend_after_seconds' => 60];
        if (app()->hasDebugModeEnabled()) {
            $data['debug_otp'] = $code;
        }

        return response()->json(['success' => true, 'message' => 'OTP sent successfully.', 'data' => $data]);
    }

    private function findAndCheckOtp(string $email, string $purpose, string $code): OneTimePassword|JsonResponse
    {
        $otp = OneTimePassword::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'The OTP is invalid or expired.'], 422);
        }

        if ($otp->attempts >= 5) {
            $otp->update(['consumed_at' => now()]);

            return response()->json(['success' => false, 'message' => 'Too many failed attempts. Request a new OTP.'], 429);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return response()->json(['success' => false, 'message' => 'The OTP is incorrect.'], 422);
        }

        return $otp;
    }

    private function createApiToken(User $user): string
    {
        $plainToken = bin2hex(random_bytes(40));
        ApiToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
        ]);

        return $plainToken;
    }
}
