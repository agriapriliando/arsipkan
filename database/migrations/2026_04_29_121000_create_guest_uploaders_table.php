<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_uploaders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_number', 30);
            $table->string('phone_number_normalized', 30);
            $table->string('guest_token', 100)->nullable()->index();
            $table->decimal('last_score', 14, 2)->default(0);
            $table->string('first_ip', 45)->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'phone_number_normalized']);
            $table->unique(['tenant_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_uploaders');
    }
};
