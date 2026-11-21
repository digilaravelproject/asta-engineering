<?php

namespace Tests\Feature;

use App\Mail\ActionMail;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Site;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_api_returns_role_scoped_dashboard_sites_details_and_notifications(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'phone' => '9000000001', 'password' => 'Password123']);
        $customer = Customer::create([
            'admin_id' => $admin->id, 'company_name' => 'Tata Steel Ltd', 'contact_person_name' => 'Rahul Sharma',
            'email' => 'rahul@example.com', 'mobile_number' => '9000000002', 'password' => Hash::make('Customer123'),
            'billing_address' => 'Navi Mumbai', 'is_active' => true,
        ]);
        $otherCustomer = Customer::create([
            'admin_id' => $admin->id, 'company_name' => 'Other Ltd', 'contact_person_name' => 'Other',
            'email' => 'other@example.com', 'mobile_number' => '9000000003', 'password' => Hash::make('Customer123'),
            'billing_address' => 'Pune', 'is_active' => true,
        ]);
        $site = Site::create(['customer_id' => $customer->id, 'site_name' => 'Turbhe MIDC Plant', 'area_locality' => 'Navi Mumbai', 'is_active' => true]);
        $otherSite = Site::create(['customer_id' => $otherCustomer->id, 'site_name' => 'Private Site', 'is_active' => true]);
        $equipment = Equipment::create(['site_id' => $site->id, 'name' => 'Chain Pulley Block - 5 Ton', 'equipment_code' => 'CPB-001', 'is_active' => true]);
        $inspector = Staff::create([
            'admin_id' => $admin->id, 'full_name' => 'Inspector One', 'email' => 'inspector@example.com',
            'mobile_number' => '9000000004', 'employee_id' => 'INS-LOGIN', 'role' => 'inspector', 'password' => 'Inspector123', 'is_active' => true,
        ]);

        $adminLogin = $this->postJson('/api/auth/login', ['id' => 'admin@example.com', 'password' => 'Password123'])
            ->assertOk()->assertJsonPath('data.role', 'admin')->assertJsonPath('data.user.role', 'admin');
        $this->withToken($adminLogin->json('data.token'))->getJson('/api/dashboard')
            ->assertOk()->assertJsonPath('data.counts.total_sites', 2);
        $assignment = $this->withToken($adminLogin->json('data.token'))->postJson('/api/admin/inspections/assign', [
            'inspector_id' => $inspector->id, 'site_id' => $site->id, 'equipment_id' => $equipment->id,
            'scheduled_at' => now()->addHour()->toIso8601String(),
        ])->assertCreated();

        $inspectorLogin = $this->postJson('/api/auth/login', ['id' => 'INS-LOGIN', 'password' => 'Inspector123'])
            ->assertOk()->assertJsonPath('data.role', 'inspector')->assertJsonPath('data.user.role', 'inspector');
        $inspectorToken = $inspectorLogin->json('data.token');
        $this->withToken($inspectorToken)->getJson('/api/me')->assertOk()
            ->assertJsonPath('data.role', 'inspector')->assertJsonPath('data.user.role', 'inspector');
        $this->withToken($inspectorToken)->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('data.counts.pending_inspections', 1)
            ->assertJsonPath('data.counts.assigned_sites', 1);
        $this->withToken($inspectorToken)->getJson('/api/admin/dashboard/counts')->assertOk()
            ->assertJsonPath('data.counts.pending_inspections', 1);
        $this->withToken($inspectorToken)->getJson('/api/admin/customers')->assertOk()->assertJsonCount(2, 'data.data');
        $this->withToken($inspectorToken)->getJson('/api/admin/profile')->assertOk()
            ->assertJsonPath('data.role', 'inspector')->assertJsonPath('data.email', 'inspector@example.com');
        $this->withToken($inspectorToken)->getJson('/api/sites/assigned?q=Turbhe')->assertOk()
            ->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.pending_inspections_count', 1);
        $this->withToken($inspectorToken)->getJson('/api/sites/'.$site->id)->assertOk()
            ->assertJsonPath('data.equipments.0.equipment_code', 'CPB-001');
        $this->withToken($inspectorToken)->getJson('/api/sites/'.$otherSite->id)->assertNotFound();
        $notification = $this->withToken($inspectorToken)->getJson('/api/notifications')->assertOk()
            ->assertJsonCount(1, 'data.data');
        $this->withToken($inspectorToken)->patchJson('/api/notifications/'.$notification->json('data.data.0.id').'/read')->assertOk()
            ->assertJsonPath('data.is_read', true);
        $this->withToken($inspectorToken)->patchJson('/api/inspections/'.$assignment->json('data.id').'/status', ['status' => 'completed'])
            ->assertOk()->assertJsonPath('data.status', 'completed');

        $userLogin = $this->postJson('/api/auth/login', ['id' => '9000000002', 'password' => 'Customer123'])
            ->assertOk()->assertJsonPath('data.role', 'user')->assertJsonPath('data.user.role', 'user');
        $userToken = $userLogin->json('data.token');
        $this->withToken($userToken)->getJson('/api/admin/profile')->assertOk()
            ->assertJsonPath('data.role', 'user')->assertJsonPath('data.email', 'rahul@example.com');
        $this->withToken($userToken)->getJson('/api/admin/customers')->assertOk()->assertJsonCount(2, 'data.data');
        $this->withToken($userToken)->getJson('/api/sites/assigned')->assertOk()->assertJsonCount(1, 'data.data');
        $this->withToken($userToken)->getJson('/api/sites/'.$site->id)->assertOk();
        $this->withToken($userToken)->getJson('/api/sites/'.$otherSite->id)->assertNotFound();
    }

    public function test_transactional_email_uses_the_branded_responsive_template(): void
    {
        $html = (new ActionMail('Certificate Generated', "Hello Rahul,\n\nYour certificate is ready.", 'certificates/test.pdf', 'certificate.pdf'))->render();

        $this->assertStringContainsString('Astha Engineering', $html);
        $this->assertStringContainsString('Inspection &amp; Safety', $html);
        $this->assertStringContainsString('Document attached', $html);
        $this->assertStringContainsString('@media only screen and (max-width: 620px)', $html);
    }
}
