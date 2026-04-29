<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('path_prefix')->unique();
            $table->unsignedBigInteger('storage_quota_bytes')->default(config('tenancy.default_storage_quota_bytes'));
            $table->unsignedBigInteger('storage_used_bytes')->default(0);
            $table->unsignedTinyInteger('storage_warning_threshold_percent')->default(config('tenancy.default_storage_warning_threshold_percent'));
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
