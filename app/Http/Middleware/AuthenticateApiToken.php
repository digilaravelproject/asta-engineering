<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthenticated. Bearer token is required.'], 401);
        }

        $token = ApiToken::with(['user', 'staff', 'customer'])
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (! $token || ($token->expires_at && $token->expires_at->isPast())) {
            return new JsonResponse(['success' => false, 'message' => 'The API token is invalid or expired.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $account = $token->account();
        if (! $account) {
            return new JsonResponse(['success' => false, 'message' => 'The account for this API token no longer exists.'], 401);
        }

        if (($token->staff && ! $token->staff->is_active) || ($token->customer && ! $token->customer->is_active)) {
            return new JsonResponse(['success' => false, 'message' => 'This account is inactive.'], 403);
        }

        $request->setUserResolver(fn () => $account);
        $request->attributes->set('api_token', $token);
        $request->attributes->set('account_type', $token->user_id ? 'admin' : ($token->staff_id ? $token->staff->role : 'user'));
        $request->attributes->set('admin_id', $token->user_id ?: ($token->staff_id ? $token->staff->admin_id : $token->customer->admin_id));

        return $next($request);
    }
}
