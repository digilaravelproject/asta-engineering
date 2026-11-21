<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Equipment;
use App\Models\Inspection;
use App\Models\Site;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InspectionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $adminId = (int) $request->attributes->get('admin_id');
        $validated = $request->validate([
            'inspector_id' => ['required', 'integer', 'exists:staff,id'],
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'equipment_id' => ['nullable', 'integer', 'exists:equipments,id'],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $inspector = Staff::where('admin_id', $adminId)->where('role', 'inspector')->where('is_active', true)->findOrFail($validated['inspector_id']);
        $site = Site::whereHas('customer', fn ($query) => $query->where('admin_id', $adminId))->findOrFail($validated['site_id']);
        if (! empty($validated['equipment_id'])) {
            Equipment::where('site_id', $site->id)->findOrFail($validated['equipment_id']);
        }

        $inspection = Inspection::create($validated + ['admin_id' => $adminId, 'status' => 'pending']);
        AdminNotification::create([
            'admin_id' => $adminId,
            'staff_id' => $inspector->id,
            'type' => 'inspection_assigned',
            'title' => 'Inspection Assigned',
            'message' => "You have an inspection at {$site->site_name} due ".$inspection->scheduled_at->format('d M Y, h:i A').'.',
            'entity_type' => 'inspection',
            'entity_id' => $inspection->id,
            'data' => ['site_id' => $site->id, 'equipment_id' => $inspection->equipment_id],
        ]);

        return response()->json(['success' => true, 'message' => 'Inspection assigned successfully.', 'data' => $inspection->load(['inspector', 'site.customer', 'equipment'])], 201);
    }

    public function updateStatus(Request $request, int $inspection): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['in_progress', 'completed'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);
        $record = Inspection::where('admin_id', $request->attributes->get('admin_id'))->findOrFail($inspection);
        $record->update($validated + ['completed_at' => $validated['status'] === 'completed' ? now() : null]);

        return response()->json(['success' => true, 'message' => 'Inspection status updated.', 'data' => $record->fresh()->load(['site.customer', 'equipment'])]);
    }
}
