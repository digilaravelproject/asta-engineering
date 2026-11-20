<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('profile_image_path')->nullable()->after('phone');
            $table->string('language', 10)->default('en-US')->after('profile_image_path');
            $table->boolean('push_notifications')->default(true)->after('language');
            $table->boolean('biometric_lock')->default(false)->after('push_notifications');
        });

        Schema::create('checkpoints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('checkpoint_code', 30)->nullable()->unique();
            $table->string('name');
            $table->string('equipment_category');
            $table->string('input_type', 30);
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('requires_failure_photo')->default(false);
            $table->timestamps();
            $table->index(['admin_id', 'name']);
            $table->index(['admin_id', 'equipment_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoints');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'profile_image_path', 'language', 'push_notifications', 'biometric_lock',
            ]);
        });
    }
};
