<?php

namespace Tests\Feature;

use App\Mail\ActionMail;
use App\Models\Customer;
use App\Models\OneTimePassword;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_admin_can_signup_login_and_manage_customers_and_sites(): void
    {
        $signup = $this->postJson('/api/admin/auth/signup', [
            'name' => 'Asta Admin',
            'email' => 'admin@example.com',
            'phone' => '+919876543210',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertCreated()->assertJsonPath('success', true);

        $token = $signup->json('data.token');
        $headers = ['Authorization' => 'Bearer '.$token];

        Mail::assertSent(ActionMail::class, function (ActionMail $mail): bool {
            return $mail->hasTo('admin@example.com')
                && $mail->subjectLine === 'Welcome to ASTA Engineering';
        });

        $customer = $this->withHeaders($headers)->postJson('/api/admin/customers', [
            'company_name' => 'A R Engineering',
            'contact_person_name' => 'Mr. Sharma',
            'email' => 'info@arengineering.com',
            'mobile_number' => '9876543210',
            'gst_number' => '27ABCDE1234F1Z5',
            'billing_address' => 'Pune, Maharashtra',
            'pincode' => '411001',
            'status' => 'active',
        ])->assertCreated();

        $customerId = $customer->json('data.id');
        $this->assertNull($customer->json('data.password'));
        $this->assertTrue(Hash::check('info@123', Customer::findOrFail($customerId)->password));

        Mail::assertSent(ActionMail::class, function (ActionMail $mail): bool {
            return $mail->hasTo('info@arengineering.com')
                && $mail->subjectLine === 'Your ASTA Engineering Customer Account'
                && str_contains($mail->body, 'Temporary password: info@123');
        });

        $site = $this->withHeaders($headers)->postJson('/api/admin/sites', [
            'customer_id' => $customerId,
            'site_name' => 'MIDC Plant',
            'building_premises' => 'Warehouse 1',
            'area_locality' => 'Pimpri MIDC',
            'pincode' => '411018',
            'email' => 'plant@example.com',
            'mobile_number' => '9988776655',
            'status' => 'active',
        ])->assertCreated();

        $siteId = $site->json('data.id');

        Mail::assertSent(ActionMail::class, function (ActionMail $mail): bool {
            return $mail->hasTo('plant@example.com')
                && $mail->subjectLine === 'New Site Added to Your Account'
                && str_contains($mail->body, 'MIDC Plant');
        });

        $this->withHeaders($headers)->getJson('/api/admin/customers')
            ->assertOk()->assertJsonPath('data.data.0.sites.0.id', $siteId);
        $this->withHeaders($headers)->getJson('/api/admin/customers/search?q=Engineering')->assertOk();
        $this->withHeaders($headers)->getJson('/api/admin/customers/'.$customerId)->assertOk();
        $this->withHeaders($headers)->putJson('/api/admin/customers/'.$customerId, ['company_name' => 'AR Engineering Updated'])
            ->assertOk()->assertJsonPath('data.company_name', 'AR Engineering Updated');

        $this->withHeaders($headers)->getJson('/api/admin/sites')
            ->assertOk()->assertJsonPath('data.data.0.equipments_count', 0);
        $this->withHeaders($headers)->getJson('/api/admin/sites/search?q=MIDC')->assertOk();
        $this->withHeaders($headers)->getJson('/api/admin/sites/'.$siteId)->assertOk();
        $this->withHeaders($headers)->putJson('/api/admin/sites/'.$siteId, ['site_name' => 'MIDC Plant Updated'])
            ->assertOk()->assertJsonPath('data.site_name', 'MIDC Plant Updated');
        $this->withHeaders($headers)->deleteJson('/api/admin/sites/'.$siteId)->assertOk();
        $this->withHeaders($headers)->deleteJson('/api/admin/customers/'.$customerId)->assertOk();

        $this->postJson('/api/admin/auth/login', [
            'login' => '+919876543210',
            'password' => 'Password123',
        ])->assertOk()->assertJsonPath('success', true);

        Mail::assertSent(ActionMail::class, function (ActionMail $mail): bool {
            return $mail->hasTo('admin@example.com')
                && $mail->subjectLine === 'Welcome Back to ASTA Engineering';
        });
    }

    public function test_password_reset_and_email_otp_login_flows(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'phone' => '9876543210',
            'password' => 'OldPassword123',
        ]);

        $this->postJson('/api/admin/auth/forgot-password', ['email' => 'admin@example.com'])->assertOk();
        $passwordOtp = OneTimePassword::where('purpose', 'password_reset')->latest('id')->firstOrFail();
        $passwordOtp->update(['code_hash' => Hash::make('1234')]);

        $verify = $this->postJson('/api/admin/auth/password/verify-otp', [
            'email' => 'admin@example.com',
            'otp' => '1234',
        ])->assertOk();

        $this->postJson('/api/admin/auth/password/reset', [
            'email' => 'admin@example.com',
            'reset_token' => $verify->json('data.reset_token'),
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertOk();

        Mail::assertSent(ActionMail::class, function (ActionMail $mail): bool {
            return $mail->hasTo('admin@example.com')
                && $mail->subjectLine === 'Password Reset Successful';
        });

        $this->postJson('/api/admin/auth/login', [
            'login' => 'admin@example.com',
            'password' => 'NewPassword123',
        ])->assertOk();

        $this->postJson('/api/admin/auth/email-login', ['email' => 'admin@example.com'])->assertOk();
        $loginOtp = OneTimePassword::where('purpose', 'email_login')->latest('id')->firstOrFail();
        $loginOtp->update(['code_hash' => Hash::make('5678')]);

        $this->postJson('/api/admin/auth/email-login/verify-otp', [
            'email' => 'admin@example.com',
            'otp' => '5678',
        ])->assertOk()->assertJsonStructure(['data' => ['token', 'admin']]);

        Mail::assertSent(ActionMail::class, function (ActionMail $mail): bool {
            return $mail->hasTo('admin@example.com')
                && $mail->subjectLine === 'Welcome Back to ASTA Engineering'
                && str_contains($mail->body, 'using email verification');
        });
    }

    public function test_an_admin_cannot_access_another_admins_records(): void
    {
        $first = $this->postJson('/api/admin/auth/signup', [
            'name' => 'First', 'email' => 'first@example.com', 'phone' => '9000000001',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);
        $second = $this->postJson('/api/admin/auth/signup', [
            'name' => 'Second', 'email' => 'second@example.com', 'phone' => '9000000002',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);

        $customer = $this->withToken($first->json('data.token'))->postJson('/api/admin/customers', [
            'company_name' => 'Private Customer', 'contact_person_name' => 'Owner',
            'email' => 'private@example.com', 'mobile_number' => '9000000003',
            'billing_address' => 'Private address',
        ]);

        $this->withToken($second->json('data.token'))
            ->getJson('/api/admin/customers/'.$customer->json('data.id'))
            ->assertNotFound();
    }

    public function test_admin_can_manage_equipment_certificates_staff_dashboard_and_notifications(): void
    {
        Storage::fake('public');
        $signup = $this->postJson('/api/admin/auth/signup', [
            'name' => 'Admin', 'email' => 'admin@example.com', 'phone' => '9000000010',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);
        $token = $signup->json('data.token');

        $customer = $this->withToken($token)->postJson('/api/admin/customers', [
            'company_name' => 'Equipment Owner', 'contact_person_name' => 'Owner',
            'email' => 'owner@example.com', 'mobile_number' => '9000000011',
            'billing_address' => 'Pune',
        ]);
        $site = $this->withToken($token)->postJson('/api/admin/sites', [
            'customer_id' => $customer->json('data.id'), 'site_name' => 'MIDC Plant',
            'email' => 'site@example.com',
        ]);

        $equipment = $this->withToken($token)->postJson('/api/admin/equipment', [
            'site_id' => $site->json('data.id'), 'name' => 'Chain Pulley Block',
            'short_name' => 'CPB', 'manufacturer' => 'Demag', 'model' => 'DM-5',
            'serial_number' => 'SN-12345', 'equipment_code' => 'EQ-001',
            'rated_capacity' => '5 Ton', 'swl_capacity' => '4.5 Ton',
            'certificate_issued_at' => '2026-11-19', 'certificate_expires_at' => '2027-11-19',
            'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('data.certificates.0.certificate_number', 'ASTA-202611-000001')
            ->assertJsonPath('data.certificate_url', fn (string $url): bool => str_ends_with($url, '.pdf'))
            ->assertJsonStructure(['data' => ['certificates' => [['file_url']]]]);
        $equipmentId = $equipment->json('data.id');
        Storage::disk('public')->assertExists($equipment->json('data.certificates.0.file_path'));

        Mail::assertSent(ActionMail::class, fn (ActionMail $mail): bool => $mail->hasTo('site@example.com') && $mail->subjectLine === 'Equipment Certificate Generated' && $mail->attachmentPath !== null);

        $this->withToken($token)->getJson('/api/admin/sites/'.$site->json('data.id'))
            ->assertOk()->assertJsonPath('data.equipments.0.id', $equipmentId);
        $this->withToken($token)->getJson('/api/admin/equipment/'.$equipmentId)
            ->assertOk()->assertJsonPath('data.certificates.0.file_url', $equipment->json('data.certificates.0.file_url'));
        $this->withToken($token)->getJson('/api/admin/equipment/'.$equipmentId.'/certificates')->assertOk()->assertJsonCount(1, 'data.data');
        $this->withToken($token)->patchJson('/api/admin/equipment/'.$equipmentId, ['remarks' => 'Updated'])->assertOk()->assertJsonPath('data.remarks', 'Updated');

        $staff = $this->withToken($token)->postJson('/api/admin/staff', [
            'full_name' => 'Tushar Patil', 'email' => 'tushar@example.com',
            'mobile_number' => '9000000012', 'employee_id' => 'INS-001',
            'role' => 'inspector', 'status' => 'active',
        ])->assertCreated();
        $staffId = $staff->json('data.id');
        $this->assertTrue(Hash::check('tusharpatil@123', Staff::findOrFail($staffId)->password));
        Mail::assertSent(ActionMail::class, fn (ActionMail $mail): bool => $mail->hasTo('tushar@example.com') && str_contains($mail->body, 'tusharpatil@123'));

        $this->withToken($token)->getJson('/api/admin/staff?role=inspector&status=active')->assertOk()->assertJsonPath('data.data.0.id', $staffId);
        $this->withToken($token)->getJson('/api/admin/staff/search?q=INS-001')->assertOk()->assertJsonPath('data.data.0.id', $staffId);
        $this->withToken($token)->getJson('/api/admin/staff/'.$staffId)->assertOk();
        $this->withToken($token)->patchJson('/api/admin/staff/'.$staffId, ['mobile_number' => '9000000013'])->assertOk();

        $this->withToken($token)->getJson('/api/admin/dashboard/counts')->assertOk()
            ->assertJsonPath('data.counts.total_customers', 1)
            ->assertJsonPath('data.counts.active_sites', 1)
            ->assertJsonPath('data.counts.total_equipment', 1)
            ->assertJsonPath('data.counts.total_certificates', 1)
            ->assertJsonPath('data.counts.field_engineers', 1);

        $notifications = $this->withToken($token)->getJson('/api/admin/notifications')->assertOk();
        $notificationId = $notifications->json('data.data.0.id');
        $this->withToken($token)->getJson('/api/admin/notifications/'.$notificationId)->assertOk();
        $this->withToken($token)->patchJson('/api/admin/notifications/'.$notificationId.'/read')->assertOk()->assertJsonPath('data.is_read', true);
        $this->withToken($token)->patchJson('/api/admin/notifications/read-all')->assertOk();
        $this->withToken($token)->getJson('/api/admin/notifications?unread_only=1')->assertOk()->assertJsonCount(0, 'data.data');

        $this->withToken($token)->deleteJson('/api/admin/staff/'.$staffId)->assertOk();
        $this->withToken($token)->deleteJson('/api/admin/equipment/'.$equipmentId)->assertOk();
    }
}
