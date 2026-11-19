<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipments', function (Blueprint $table): void {
            $table->string('short_name', 50)->nullable()->after('name');
            $table->string('manufacturer')->nullable()->after('short_name');
            $table->string('model')->nullable()->after('manufacturer');
            $table->string('serial_number')->nullable()->after('model');
            $table->string('rated_capacity')->nullable()->after('equipment_code');
            $table->string('swl_capacity')->nullable()->after('rated_capacity');
            $table->text('description')->nullable()->after('swl_capacity');
            $table->text('remarks')->nullable()->after('description');
            $table->date('next_inspection_at')->nullable()->after('remarks');
            $table->unique(['site_id', 'equipment_code']);
        });

        Schema::create('equipment_certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipments')->cascadeOnDelete();
            $table->string('certificate_number');
            $table->string('title')->default('Equipment Certificate');
            $table->string('file_path');
            $table->date('issued_at');
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['equipment_id', 'certificate_number']);
        });

        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('mobile_number', 20);
            $table->string('employee_id')->nullable();
            $table->string('role', 30)->default('inspector');
            $table->string('password');
            $table->string('profile_image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['admin_id', 'email']);
            $table->unique(['admin_id', 'mobile_number']);
            $table->unique(['admin_id', 'employee_id']);
        });

        Schema::create('admin_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title');
            $table->text('message');
            $table->string('entity_type', 50)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['admin_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('equipment_certificates');

        Schema::table('equipments', function (Blueprint $table): void {
            $table->dropUnique(['site_id', 'equipment_code']);
            $table->dropColumn([
                'short_name', 'manufacturer', 'model', 'serial_number', 'rated_capacity',
                'swl_capacity', 'description', 'remarks', 'next_inspection_at',
            ]);
        });
    }
};
