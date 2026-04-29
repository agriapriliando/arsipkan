<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_downloads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('file_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('downloaded_at');
            $table->boolean('is_counted_for_score')->default(true);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'file_id'])
                ->references(['tenant_id', 'id'])
                ->on('files')
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'downloaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_downloads');
    }
};
