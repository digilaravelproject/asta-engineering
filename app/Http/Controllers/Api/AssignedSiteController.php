<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignedSiteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $query = $this->accessibleSites($request)
            ->when($validated['q'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('site_name', 'like', "%{$term}%")
                        ->orWhere('building_premises', 'like', "%{$term}%")
                        ->orWhere('area_locality', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('company_name', 'like', "%{$term}%"));
                });
            })
            ->with('customer')
            ->withCount('equipments');

        if ($request->attributes->get('account_type') === 'inspector') {
            $inspectorId = $request->user()->id;
            $query->withCount(['inspections as pending_inspections_count' => fn (Builder $q) => $q->where('inspector_id', $inspectorId)->whereIn('status', ['pending', 'in_progress'])])
                ->orderByRaw('(SELECT MIN(scheduled_at) FROM inspections WHERE inspections.site_id = sites.id AND inspections.inspector_id = ? AND inspections.status IN (?, ?))', [$inspectorId, 'pending', 'in_progress']);
        } else {
            $query->withCount(['inspections as pending_inspections_count' => fn (Builder $q) => $q->whereIn('status', ['pending', 'in_progress'])])->latest();
        }

        $sites = $query->paginate($request->integer('per_page', 15));
        $sites->getCollection()->each(fn (Site $site) => $this->appendPresentationFields($site));

        return response()->json(['success' => true, 'data' => $sites]);
    }

    public function show(Request $request, int $site): JsonResponse
    {
        $inspectorId = $request->attributes->get('account_type') === 'inspector' ? $request->user()->id : null;
        $query = $this->accessibleSites($request)->with(['customer', 'equipments.certificates']);
        if ($inspectorId) {
            $query->with(['inspections' => fn ($q) => $q->where('inspector_id', $inspectorId)->with('equipment')->orderBy('scheduled_at')]);
        } else {
            $query->with(['inspections' => fn ($q) => $q->with(['inspector', 'equipment'])->orderBy('scheduled_at')]);
        }
        $record = $query->withCount('equipments')
            ->withCount(['inspections as pending_inspections_count' => fn (Builder $q) => $q->when($inspectorId, fn (Builder $q) => $q->where('inspector_id', $inspectorId))->whereIn('status', ['pending', 'in_progress'])])
            ->findOrFail($site);
        $this->appendPresentationFields($record);

        return response()->json(['success' => true, 'data' => $record]);
    }

    private function accessibleSites(Request $request): Builder
    {
        return match ($request->attributes->get('account_type')) {
            'inspector' => Site::query()->whereHas('inspections', fn (Builder $query) => $query->where('inspector_id', $request->user()->id)),
            'user' => Site::query()->where('customer_id', $request->user()->id),
            default => Site::query()->whereHas('customer', fn (Builder $query) => $query->where('admin_id', $request->attributes->get('admin_id'))),
        };
    }

    private function appendPresentationFields(Site $site): void
    {
        $site->setAttribute('location', collect([$site->flat_door_block, $site->building_premises, $site->road_street_lane, $site->area_locality, $site->pincode])->filter()->implode(', '));
        $site->setAttribute('contact_name', $site->customer?->contact_person_name);
        $site->setAttribute('contact_phone', $site->mobile_number ?: $site->phone_number ?: $site->customer?->mobile_number);
    }
}
