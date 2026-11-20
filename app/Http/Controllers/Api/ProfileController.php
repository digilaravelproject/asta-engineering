<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->profileData($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile_image' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'language' => ['sometimes', 'string', 'max:10'],
            'push_notifications' => ['sometimes', 'boolean'],
            'biometric_lock' => ['sometimes', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $oldImage = null;

        if ($request->hasFile('profile_image')) {
            $oldImage = $user->profile_image_path;
            $validated['profile_image_path'] = $request->file('profile_image')
                ->store('admin-profile-images', 'public');
        }

        unset($validated['profile_image']);

        $user->update($validated);

        if ($oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile and preferences updated successfully.',
            'data' => $this->profileData($user->refresh()),
        ]);
    }

    private function profileData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_image_path' => $user->profile_image_path,
            'profile_image_url' => $user->profile_image_path
                ? Storage::disk('public')->url($user->profile_image_path)
                : null,
            'preferences' => [
                'language' => $user->language,
                'push_notifications' => $user->push_notifications,
                'biometric_lock' => $user->biometric_lock,
            ],
        ];
    }
}
