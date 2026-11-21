<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\EquipmentCertificate;
use App\Models\Inspection;
use App\Models\Site;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $adminId = $request->attributes->get('admin_id');
        $customerIds = Customer::where('admin_id', $adminId)->select('id');
        $siteIds = Site::whereIn('customer_id', $customerIds)->select('id');
        $equipmentIds = Equipment::whereIn('site_id', $siteIds)->select('id');
        $dueDate = now()->addDays(15)->toDateString();

        $counts = [
            'total_customers' => Customer::where('admin_id', $adminId)->count(),
            'active_sites' => Site::whereIn('customer_id', clone $customerIds)->where('is_active', true)->count(),
            'total_sites' => Site::whereIn('customer_id', clone $customerIds)->count(),
            'total_equipment' => Equipment::whereIn('site_id', clone $siteIds)->count(),
            'equipment_audited' => EquipmentCertificate::whereIn('equipment_id', clone $equipmentIds)->distinct('equipment_id')->count('equipment_id'),
            'total_certificates' => EquipmentCertificate::whereIn('equipment_id', clone $equipmentIds)->count(),
            'field_engineers' => Staff::where('admin_id', $adminId)->where('role', 'inspector')->where('is_active', true)->count(),
            'renewals_pending' => EquipmentCertificate::whereIn('equipment_id', clone $equipmentIds)->whereDate('expires_at', '>=', now()->toDateString())->whereDate('expires_at', '<=', $dueDate)->count(),
            'total_users' => Staff::where('admin_id', $adminId)->count(),
            'inspections' => Inspection::where('admin_id', $adminId)->count(),
            'unread_notifications' => AdminNotification::where('admin_id', $adminId)->whereNull('staff_id')->whereNull('customer_id')->whereNull('read_at')->count(),
        ];

        $recentFeed = AdminNotification::where('admin_id', $adminId)->whereNull('staff_id')->whereNull('customer_id')->latest()->limit(10)->get();

        return response()->json(['success' => true, 'data' => ['counts' => $counts, 'recent_feed' => $recentFeed]]);
    }
}
