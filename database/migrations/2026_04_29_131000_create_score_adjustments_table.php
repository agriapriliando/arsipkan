<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('guest_uploader_id');
            $table->decimal('nilai_sebelum', 14, 2);
            $table->decimal('nilai_sesudah', 14, 2);
            $table->decimal('selisih', 14, 2);
            $table->foreignId('updated_by_admin_id')->constrained('admin_users')->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'guest_uploader_id'])
                ->references(['tenant_id', 'id'])
                ->on('guest_uploaders')
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_adjustments');
    }
};
