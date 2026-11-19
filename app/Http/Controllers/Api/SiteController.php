<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ActionMail;
use App\Models\Customer;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:100']]);
        $sites = $this->baseQuery($request)
            ->with(['customer', 'equipments'])
            ->withCount('equipments')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $sites]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $term = $validated['q'];
        $sites = $this->baseQuery($request)
            ->where(function (Builder $query) use ($term): void {
                $query->where('site_name', 'like', "%{$term}%")
                    ->orWhere('building_premises', 'like', "%{$term}%")
                    ->orWhere('road_street_lane', 'like', "%{$term}%")
                    ->orWhere('area_locality', 'like', "%{$term}%")
                    ->orWhere('pincode', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('company_name', 'like', "%{$term}%"));
            })
            ->with(['customer', 'equipments'])
            ->withCount('equipments')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $sites]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateSite($request);
        $this->ownedCustomer($request, (int) $validated['customer_id']);
        $validated['is_active'] = $this->activeValue($request);
        $site = Site::create($validated)->load(['customer', 'equipments']);

        $recipient = $site->email ?: $site->customer->email;
        Mail::to($recipient)->send(new ActionMail(
            'New Site Added to Your Account',
            "Hello {$site->customer->contact_person_name},\n\nA new site has been added for {$site->customer->company_name}.\n\nSite: {$site->site_name}\nLocation: ".$this->siteLocation($site),
        ));

        return response()->json(['success' => true, 'message' => 'Site created successfully.', 'data' => $site], 201);
    }

    public function show(Request $request, int $site): JsonResponse
    {
        $record = $this->baseQuery($request)
            ->with(['customer', 'equipments'])
            ->withCount('equipments')
            ->findOrFail($site);

        return response()->json(['success' => true, 'data' => $record]);
    }

    public function update(Request $request, int $site): JsonResponse
    {
        $record = $this->baseQuery($request)->findOrFail($site);
        $validated = $this->validateSite($request, true);

        if (isset($validated['customer_id'])) {
            $this->ownedCustomer($request, (int) $validated['customer_id']);
        }
        if ($request->hasAny(['is_active', 'status'])) {
            $validated['is_active'] = $this->activeValue($request);
        }

        $record->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Site updated successfully.',
            'data' => $record->fresh()->load(['customer', 'equipments']),
        ]);
    }

    public function destroy(Request $request, int $site): JsonResponse
    {
        $record = $this->baseQuery($request)->findOrFail($site);
        $record->delete();

        return response()->json(['success' => true, 'message' => 'Site deleted successfully.']);
    }

    private function baseQuery(Request $request): Builder
    {
        return Site::query()->whereHas('customer', fn (Builder $query) => $query->where('admin_id', $request->user()->id));
    }

    private function ownedCustomer(Request $request, int $customerId): Customer
    {
        return Customer::where('admin_id', $request->user()->id)->findOrFail($customerId);
    }

    private function validateSite(Request $request, bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'customer_id' => [$presence, 'integer', 'exists:customers,id'],
            'site_name' => [$presence, 'string', 'max:150'],
            'flat_door_block' => ['nullable', 'string', 'max:150'],
            'building_premises' => ['nullable', 'string', 'max:150'],
            'road_street_lane' => ['nullable', 'string', 'max:150'],
            'area_locality' => ['nullable', 'string', 'max:150'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone_number' => ['nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'mobile_number' => ['nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'additional_contact_details' => ['nullable', 'string', 'max:1000'],
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

    private function siteLocation(Site $site): string
    {
        return collect([
            $site->flat_door_block,
            $site->building_premises,
            $site->road_street_lane,
            $site->area_locality,
            $site->pincode,
        ])->filter()->implode(', ') ?: 'Not provided';
    }
}
