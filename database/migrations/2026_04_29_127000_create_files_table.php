<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('guest_uploader_id');
            $table->foreignId('upload_link_id')->nullable()->constrained('upload_links')->nullOnDelete();
            $table->string('uploaded_via', 30);
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('extension', 20)->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('visibility', 20);
            $table->string('status', 30);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('detected_file_type')->nullable();
            $table->string('final_file_type')->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('deleted_by_user_account_id')->nullable()->constrained('user_accounts')->nullOnDelete();
            $table->foreignId('permanently_deleted_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'guest_uploader_id'])
                ->references(['tenant_id', 'id'])
                ->on('guest_uploaders')
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'visibility', 'status']);
            $table->index(['tenant_id', 'uploaded_at']);
            $table->unique(['tenant_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
