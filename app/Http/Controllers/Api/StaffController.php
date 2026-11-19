<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ActionMail;
use App\Models\Staff;
use App\Services\AdminNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'role' => ['sometimes', Rule::in(['admin', 'inspector'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);
        $staff = $this->filteredQuery($request)->latest()->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $staff]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'max:100'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100']]);
        $term = $validated['q'];
        $staff = $this->filteredQuery($request)
            ->where(function (Builder $query) use ($term): void {
                $query->where('full_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('mobile_number', 'like', "%{$term}%")
                    ->orWhere('employee_id', 'like', "%{$term}%");
            })->latest()->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $staff]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateStaff($request);
        $validated['admin_id'] = $request->user()->id;
        $validated['is_active'] = $this->activeValue($request);
        $plainPassword = Str::lower(preg_replace('/[^A-Za-z0-9]/', '', $validated['full_name'])).'@123';
        $validated['password'] = $plainPassword;
        if ($request->hasFile('profile_image')) {
            $validated['profile_image_path'] = $request->file('profile_image')->store('staff-profile-images', 'public');
        }
        $staff = Staff::create($validated);

        Mail::to($staff->email)->send(new ActionMail(
            'Your ASTA Engineering Staff Account',
            "Hello {$staff->full_name},\n\nYour {$staff->role} account was created.\n\nEmail: {$staff->email}\nPhone: {$staff->mobile_number}\nTemporary password: {$plainPassword}\n\nPlease keep these credentials secure.",
        ));
        AdminNotificationService::create($request->user()->id, 'staff_created', 'Staff Added', "{$staff->full_name} was added as {$staff->role}.", 'staff', $staff->id);

        return response()->json(['success' => true, 'message' => 'Staff created successfully. Credentials email sent.', 'data' => $staff], 201);
    }

    public function show(Request $request, int $staff): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->baseQuery($request)->findOrFail($staff)]);
    }

    public function update(Request $request, int $staff): JsonResponse
    {
        $record = $this->baseQuery($request)->findOrFail($staff);
        $validated = $this->validateStaff($request, $record, true);
        if ($request->hasAny(['is_active', 'status'])) {
            $validated['is_active'] = $this->activeValue($request);
        }
        if ($request->hasFile('profile_image')) {
            $validated['profile_image_path'] = $request->file('profile_image')->store('staff-profile-images', 'public');
        }
        $record->update($validated);
        $record->refresh();

        Mail::to($record->email)->send(new ActionMail(
            'Your ASTA Engineering Staff Profile Was Updated',
            "Hello {$record->full_name},\n\nYour staff profile was updated.\n\nEmail: {$record->email}\nPhone: {$record->mobile_number}\nRole: {$record->role}\nStatus: ".($record->is_active ? 'Active' : 'Inactive'),
        ));
        AdminNotificationService::create($request->user()->id, 'staff_updated', 'Staff Updated', "{$record->full_name}'s staff profile was updated.", 'staff', $record->id);

        return response()->json(['success' => true, 'message' => 'Staff updated successfully. Notification email sent.', 'data' => $record]);
    }

    public function destroy(Request $request, int $staff): JsonResponse
    {
        $record = $this->baseQuery($request)->findOrFail($staff);
        $name = $record->full_name;
        $id = $record->id;
        $record->delete();
        AdminNotificationService::create($request->user()->id, 'staff_deleted', 'Staff Deleted', "Staff member {$name} was deleted.", 'staff', $id);

        return response()->json(['success' => true, 'message' => 'Staff deleted successfully.']);
    }

    private function baseQuery(Request $request): Builder
    {
        return Staff::query()->where('admin_id', $request->user()->id);
    }

    private function filteredQuery(Request $request): Builder
    {
        return $this->baseQuery($request)
            ->when($request->filled('role'), fn (Builder $query) => $query->where('role', $request->input('role')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->input('status') === 'active'));
    }

    private function validateStaff(Request $request, ?Staff $staff = null, bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';
        $adminId = $request->user()->id;

        return $request->validate([
            'full_name' => [$presence, 'string', 'max:150'],
            'email' => [$presence, 'email:rfc', 'max:255', Rule::unique('staff')->where('admin_id', $adminId)->ignore($staff?->id)],
            'mobile_number' => [$presence, 'string', 'regex:/^\+?[0-9]{8,15}$/', Rule::unique('staff')->where('admin_id', $adminId)->ignore($staff?->id)],
            'employee_id' => ['nullable', 'string', 'max:100', Rule::unique('staff')->where('admin_id', $adminId)->ignore($staff?->id)],
            'role' => [$presence, Rule::in(['admin', 'inspector'])],
            'profile_image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function activeValue(Request $request): bool
    {
        return $request->has('is_active') ? $request->boolean('is_active') : $request->input('status', 'active') === 'active';
    }
}
