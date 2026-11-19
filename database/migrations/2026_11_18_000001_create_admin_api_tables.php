<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->unique()->after('email');
        });

        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('admin-api');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('one_time_passwords', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->index();
            $table->string('purpose', 30)->index();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_token_hash', 64)->nullable()->unique();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['email', 'purpose']);
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('contact_person_name');
            $table->string('email');
            $table->string('mobile_number', 20);
            $table->string('gst_number', 30)->nullable();
            $table->text('billing_address');
            $table->string('pincode', 10)->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['admin_id', 'email']);
            $table->unique(['admin_id', 'mobile_number']);
            $table->index(['admin_id', 'company_name']);
        });

        Schema::create('sites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('site_name');
            $table->string('flat_door_block')->nullable();
            $table->string('building_premises')->nullable();
            $table->string('road_street_lane')->nullable();
            $table->string('area_locality')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('mobile_number', 20)->nullable();
            $table->text('additional_contact_details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['customer_id', 'site_name']);
        });

        Schema::create('equipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('equipment_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipments');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('one_time_passwords');
        Schema::dropIfExists('api_tokens');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('phone'));
    }
};
