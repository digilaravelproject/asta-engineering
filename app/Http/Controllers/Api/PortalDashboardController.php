<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Equipment;
use App\Models\EquipmentCertificate;
use App\Models\Inspection;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalDashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return match ($request->attributes->get('account_type')) {
            'inspector' => $this->inspectorDashboard($request),
            'user' => $this->userDashboard($request),
            default => (new DashboardController)($request),
        };
    }

    private function inspectorDashboard(Request $request): JsonResponse
    {
        $inspectorId = $request->user()->id;
        $assignedSiteIds = Inspection::where('inspector_id', $inspectorId)->select('site_id');
        $equipmentIds = Equipment::whereIn('site_id', $assignedSiteIds)->select('id');
        $counts = [
            'pending_inspections' => Inspection::where('inspector_id', $inspectorId)->whereIn('status', ['pending', 'in_progress'])->count(),
            'assigned_sites' => Inspection::where('inspector_id', $inspectorId)->distinct()->count('site_id'),
            'completed_today' => Inspection::where('inspector_id', $inspectorId)->where('status', 'completed')->whereDate('completed_at', today())->count(),
            'upcoming_renewals' => EquipmentCertificate::whereIn('equipment_id', $equipmentIds)->whereBetween('expires_at', [today(), today()->addDays(15)])->count(),
            'unread_notifications' => AdminNotification::where('staff_id', $inspectorId)->whereNull('read_at')->count(),
        ];
        $recentAssignments = Inspection::where('inspector_id', $inspectorId)
            ->with(['site.customer', 'equipment'])
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderBy('scheduled_at')->limit(10)->get();

        return response()->json(['success' => true, 'data' => ['role' => 'inspector', 'counts' => $counts, 'recent_assignments' => $recentAssignments]]);
    }

    private function userDashboard(Request $request): JsonResponse
    {
        $customerId = $request->user()->id;
        $siteIds = Site::where('customer_id', $customerId)->select('id');
        $equipmentIds = Equipment::whereIn('site_id', $siteIds)->select('id');
        $counts = [
            'active_sites' => Site::where('customer_id', $customerId)->where('is_active', true)->count(),
            'total_sites' => Site::where('customer_id', $customerId)->count(),
            'total_equipment' => Equipment::whereIn('site_id', clone $siteIds)->count(),
            'total_certificates' => EquipmentCertificate::whereIn('equipment_id', clone $equipmentIds)->count(),
            'upcoming_renewals' => EquipmentCertificate::whereIn('equipment_id', clone $equipmentIds)->whereBetween('expires_at', [today(), today()->addDays(15)])->count(),
            'unread_notifications' => AdminNotification::where('customer_id', $customerId)->whereNull('read_at')->count(),
        ];

        return response()->json(['success' => true, 'data' => ['role' => 'user', 'counts' => $counts]]);
    }
}
