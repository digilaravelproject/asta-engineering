<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->profileData($request)]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile_image' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'language' => ['sometimes', 'string', 'max:10'],
            'push_notifications' => ['sometimes', 'boolean'],
            'biometric_lock' => ['sometimes', 'boolean'],
        ]);
        $account = $request->user();
        $oldImage = null;

        if ($account instanceof User) {
            if ($request->hasFile('profile_image')) {
                $oldImage = $account->profile_image_path;
                $validated['profile_image_path'] = $request->file('profile_image')->store('admin-profile-images', 'public');
            }
            unset($validated['profile_image']);
            $account->update($validated);
        } elseif ($account instanceof Staff) {
            if ($request->hasFile('profile_image')) {
                $oldImage = $account->profile_image_path;
                $account->update(['profile_image_path' => $request->file('profile_image')->store('staff-profile-images', 'public')]);
            }
        } elseif ($account instanceof Customer && $request->hasFile('profile_image')) {
            $oldImage = $account->logo_path;
            $account->update(['logo_path' => $request->file('profile_image')->store('customer-logos', 'public')]);
        }

        if ($oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $this->profileData($request, $account->refresh()),
        ]);
    }

    private function profileData(Request $request, User|Staff|Customer|null $account = null): array
    {
        $account ??= $request->user();
        $role = $request->attributes->get('account_type');
        $imagePath = $account instanceof Customer ? $account->logo_path : $account->profile_image_path;

        return [
            'id' => $account->id,
            'role' => $role,
            'name' => $account instanceof User ? $account->name : ($account instanceof Staff ? $account->full_name : $account->contact_person_name),
            'email' => $account->email,
            'phone' => $account instanceof User ? $account->phone : $account->mobile_number,
            'profile_image_path' => $imagePath,
            'profile_image_url' => $imagePath ? Storage::disk('public')->url($imagePath) : null,
            'account' => $account,
            'preferences' => $account instanceof User ? [
                'language' => $account->language,
                'push_notifications' => $account->push_notifications,
                'biometric_lock' => $account->biometric_lock,
            ] : null,
        ];
    }
}
