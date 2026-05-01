<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table): void {
            $table->unsignedSmallInteger('document_year')->nullable()->after('final_file_type');
            $table->index(['tenant_id', 'document_year']);
        });

        DB::table('files')
            ->select(['id', 'uploaded_at'])
            ->orderBy('id')
            ->get()
            ->each(function (object $file): void {
                $uploadedAt = $file->uploaded_at;

                if ($uploadedAt === null) {
                    return;
                }

                DB::table('files')
                    ->where('id', $file->id)
                    ->update([
                        'document_year' => CarbonImmutable::parse($uploadedAt)->year,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'document_year']);
            $table->dropColumn('document_year');
        });
    }
};
