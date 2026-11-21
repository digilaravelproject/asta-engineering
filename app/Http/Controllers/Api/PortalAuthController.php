<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PortalAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required_without:login', 'string'],
            'login' => ['required_without:id', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($validated['id'] ?? $validated['login']);
        [$account, $resolvedRole] = $this->findAccount($identifier, $validated['password']);

        if (! $account) {
            return response()->json(['success' => false, 'message' => 'Invalid login credentials.'], 401);
        }
        if (isset($account->is_active) && ! $account->is_active) {
            return response()->json(['success' => false, 'message' => 'This account is inactive.'], 403);
        }

        $plainToken = bin2hex(random_bytes(40));
        ApiToken::create([
            'user_id' => $account instanceof User ? $account->id : null,
            'staff_id' => $account instanceof Staff ? $account->id : null,
            'customer_id' => $account instanceof Customer ? $account->id : null,
            'name' => $resolvedRole.'-api',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
        ]);

        $account->setAttribute('role', $resolvedRole);

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $account,
                'account' => $account,
                'role' => $resolvedRole,
                'token' => $plainToken,
                'token_type' => 'Bearer',
                'expires_in_days' => 30,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('api_token')->delete();

        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        $role = $request->attributes->get('account_type');
        $account = $request->user();
        $account->setAttribute('role', $role);

        return response()->json(['success' => true, 'data' => ['user' => $account, 'role' => $role]]);
    }

    private function findAccount(string $identifier, string $password): array
    {
        $normalized = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? Str::lower($identifier) : $identifier;
        $candidates = collect();

        User::where('email', $normalized)->orWhere('phone', $identifier)->get()
            ->each(fn (User $account) => $candidates->push([$account, 'admin']));
        Staff::where('role', 'inspector')
            ->where(fn ($query) => $query->where('email', $normalized)->orWhere('mobile_number', $identifier)->orWhere('employee_id', $identifier))
            ->get()->each(fn (Staff $account) => $candidates->push([$account, 'inspector']));
        Customer::where(fn ($query) => $query->where('email', $normalized)->orWhere('mobile_number', $identifier))
            ->get()->each(fn (Customer $account) => $candidates->push([$account, 'user']));

        return $candidates->first(fn (array $candidate) => Hash::check($password, $candidate[0]->password)) ?? [null, null];
    }
}
