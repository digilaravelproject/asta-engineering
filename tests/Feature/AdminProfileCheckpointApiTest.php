<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProfileCheckpointApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_admin_can_view_and_update_profile_image_and_preferences(): void
    {
        Storage::fake('public');
        $token = $this->signup('admin@example.com', '9000000020');

        $this->withToken($token)->getJson('/api/admin/profile')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@example.com')
            ->assertJsonPath('data.preferences.language', 'en-US')
            ->assertJsonPath('data.preferences.push_notifications', true)
            ->assertJsonPath('data.preferences.biometric_lock', false);

        $firstUpdate = $this->withToken($token)->patch('/api/admin/profile', [
            'profile_image' => UploadedFile::fake()->image('first-avatar.jpg'),
            'language' => 'hi-IN',
            'push_notifications' => false,
            'biometric_lock' => true,
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('data.preferences.language', 'hi-IN')
            ->assertJsonPath('data.preferences.push_notifications', false)
            ->assertJsonPath('data.preferences.biometric_lock', true);

        $firstPath = $firstUpdate->json('data.profile_image_path');
        Storage::disk('public')->assertExists($firstPath);

        $secondUpdate = $this->withToken($token)->patch('/api/admin/profile', [
            'profile_image' => UploadedFile::fake()->image('second-avatar.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondUpdate->json('data.profile_image_path'));
    }

    public function test_admin_can_search_list_and_manage_only_their_checkpoints(): void
    {
        $firstToken = $this->signup('first@example.com', '9000000021');
        $secondToken = $this->signup('second@example.com', '9000000022');

        $created = $this->withToken($firstToken)->postJson('/api/admin/checkpoints', [
            'name' => 'Visual Inspection of Wire Ropes',
            'equipment_category' => 'Lifting Tools',
            'input_type' => 'radio',
            'is_mandatory' => true,
            'requires_failure_photo' => true,
        ])->assertCreated()
            ->assertJsonPath('data.checkpoint_code', 'CHK-001')
            ->assertJsonPath('data.is_mandatory', true);
        $checkpointId = $created->json('data.id');

        $this->withToken($firstToken)->getJson('/api/admin/checkpoints')
            ->assertOk()->assertJsonPath('data.data.0.id', $checkpointId);
        $this->withToken($firstToken)->getJson('/api/admin/checkpoints/search?q=CHK-001')
            ->assertOk()->assertJsonPath('data.data.0.id', $checkpointId);
        $this->withToken($firstToken)->getJson('/api/admin/checkpoints/search?q=Wire')
            ->assertOk()->assertJsonPath('data.data.0.id', $checkpointId);
        $this->withToken($firstToken)->getJson('/api/admin/checkpoints/'.$checkpointId)
            ->assertOk()->assertJsonPath('data.equipment_category', 'Lifting Tools');
        $this->withToken($firstToken)->patchJson('/api/admin/checkpoints/'.$checkpointId, [
            'name' => 'Updated Wire Rope Inspection',
            'input_type' => 'text',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Wire Rope Inspection')
            ->assertJsonPath('data.input_type', 'text');

        $this->withToken($secondToken)->getJson('/api/admin/checkpoints/'.$checkpointId)->assertNotFound();
        $this->withToken($secondToken)->patchJson('/api/admin/checkpoints/'.$checkpointId, ['name' => 'Stolen'])->assertNotFound();
        $this->withToken($secondToken)->deleteJson('/api/admin/checkpoints/'.$checkpointId)->assertNotFound();

        $this->withToken($firstToken)->deleteJson('/api/admin/checkpoints/'.$checkpointId)->assertOk();
        $this->assertDatabaseMissing(Checkpoint::class, ['id' => $checkpointId]);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $firstToken = $this->signup('admin@example.com', '9000000023');
        $secondToken = $this->postJson('/api/admin/auth/login', [
            'login' => 'admin@example.com',
            'password' => 'Password123',
        ])->assertOk()->json('data.token');

        $this->withToken($firstToken)->postJson('/api/admin/auth/logout')->assertOk();
        $this->withToken($firstToken)->getJson('/api/admin/profile')->assertUnauthorized();
        $this->withToken($secondToken)->getJson('/api/admin/profile')->assertOk();
    }

    private function signup(string $email, string $phone): string
    {
        return $this->postJson('/api/admin/auth/signup', [
            'name' => 'Asta Admin',
            'email' => $email,
            'phone' => $phone,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertCreated()->json('data.token');
    }
}
