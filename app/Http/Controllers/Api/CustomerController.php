<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ActionMail;
use App\Models\Customer;
use App\Services\AdminNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:100']]);
        $customers = $this->baseQuery($request)
            ->with(['sites' => fn ($query) => $query->withCount('equipments')])
            ->withCount('sites')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $customers]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $term = $validated['q'];
        $customers = $this->baseQuery($request)
            ->where(function (Builder $query) use ($term): void {
                $query->where('company_name', 'like', "%{$term}%")
                    ->orWhere('contact_person_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('mobile_number', 'like', "%{$term}%")
                    ->orWhere('gst_number', 'like', "%{$term}%");
            })
            ->with('sites')
            ->withCount('sites')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $customers]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateCustomer($request);
        $validated['admin_id'] = $request->user()->id;
        $validated['is_active'] = $this->activeValue($request);
        $temporaryPassword = explode('@', $validated['email'], 2)[0].'@123';
        $validated['password'] = Hash::make($temporaryPassword);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('customer-logos', 'public');
        }

        $customer = Customer::create($validated)->load('sites');

        Mail::to($customer->email)->send(new ActionMail(
            'Your ASTA Engineering Customer Account',
            "Hello {$customer->contact_person_name},\n\nYour customer account for {$customer->company_name} has been created.\n\nEmail: {$customer->email}\nTemporary password: {$temporaryPassword}\n\nPlease keep these credentials secure.",
        ));

        AdminNotificationService::create($request->user()->id, 'customer_created', 'Customer Added', "Customer {$customer->company_name} was added.", 'customer', $customer->id);

        return response()->json(['success' => true, 'message' => 'Customer created successfully.', 'data' => $customer], 201);
    }

    public function show(Request $request, int $customer): JsonResponse
    {
        $record = $this->baseQuery($request)
            ->with(['sites' => fn ($query) => $query->with('equipments')])
            ->withCount('sites')
            ->findOrFail($customer);

        return response()->json(['success' => true, 'data' => $record]);
    }

    public function update(Request $request, int $customer): JsonResponse
    {
        $record = $this->baseQuery($request)->findOrFail($customer);
        $validated = $this->validateCustomer($request, $record, true);

        if ($request->hasAny(['is_active', 'status'])) {
            $validated['is_active'] = $this->activeValue($request);
        }
        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('customer-logos', 'public');
        }

        $record->update($validated);

        AdminNotificationService::create($request->user()->id, 'customer_updated', 'Customer Updated', "Customer {$record->company_name} was updated.", 'customer', $record->id);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'data' => $record->fresh()->load('sites'),
        ]);
    }

    public function destroy(Request $request, int $customer): JsonResponse
    {
        $record = $this->baseQuery($request)->findOrFail($customer);
        $name = $record->company_name;
        $id = $record->id;
        $record->delete();
        AdminNotificationService::create($request->user()->id, 'customer_deleted', 'Customer Deleted', "Customer {$name} was deleted.", 'customer', $id);

        return response()->json(['success' => true, 'message' => 'Customer deleted successfully.']);
    }

    private function baseQuery(Request $request): Builder
    {
        return Customer::query()->where('admin_id', $request->user()->id);
    }

    private function validateCustomer(Request $request, ?Customer $customer = null, bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';
        $adminId = $request->user()->id;

        return $request->validate([
            'company_name' => [$presence, 'string', 'max:150'],
            'contact_person_name' => [$presence, 'string', 'max:100'],
            'email' => [$presence, 'email:rfc', 'max:255', Rule::unique('customers')->where('admin_id', $adminId)->ignore($customer?->id)],
            'mobile_number' => [$presence, 'string', 'regex:/^\+?[0-9]{8,15}$/', Rule::unique('customers')->where('admin_id', $adminId)->ignore($customer?->id)],
            'gst_number' => ['nullable', 'string', 'max:30'],
            'billing_address' => [$presence, 'string', 'max:1000'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function activeValue(Request $request): bool
    {
        if ($request->has('is_active')) {
            return $request->boolean('is_active');
        }

        return $request->input('status', 'active') === 'active';
    }
}
