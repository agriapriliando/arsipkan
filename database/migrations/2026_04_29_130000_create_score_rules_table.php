<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_rules', function (Blueprint $table): void {
            $table->id();
            $table->integer('upload_valid_point');
            $table->integer('download_point');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_superadmin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_rules');
    }
};
