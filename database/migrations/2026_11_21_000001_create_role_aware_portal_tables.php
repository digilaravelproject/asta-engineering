<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_tokens', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('staff_id')->nullable()->after('user_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->after('staff_id')->constrained()->cascadeOnDelete();
        });

        Schema::create('inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('inspector_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipments')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('status', 20)->default('pending');
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['inspector_id', 'status', 'scheduled_at']);
            $table->index(['site_id', 'status']);
        });

        Schema::table('admin_notifications', function (Blueprint $table): void {
            $table->foreignId('staff_id')->nullable()->after('admin_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->after('staff_id')->constrained()->cascadeOnDelete();
            $table->index(['staff_id', 'read_at', 'created_at']);
            $table->index(['customer_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('staff_id');
        });
        Schema::dropIfExists('inspections');
        DB::table('api_tokens')->whereNull('user_id')->delete();
        Schema::table('api_tokens', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('staff_id');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
