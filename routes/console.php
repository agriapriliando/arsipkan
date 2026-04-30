<?php

use App\Models\AdminUser;
use App\Models\File;
use App\Models\Tenant;
use App\Services\Scoring\ScoreService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('superadmin:reset-password {email} {--password=}', function (string $email): int {
    $superadmin = AdminUser::query()
        ->superadmin()
        ->where('email', $email)
        ->first();

    if ($superadmin === null) {
        $this->error('Akun superadmin tidak ditemukan.');

        return Command::FAILURE;
    }

    $password = $this->option('password') ?: Str::password(16);

    $superadmin->forceFill([
        'password' => Hash::make($password),
    ])->save();

    $this->info('Password superadmin berhasil direset.');

    if (! $this->option('password')) {
        $this->line('Password baru: '.$password);
    }

    return Command::SUCCESS;
})->purpose('Reset password akun superadmin melalui Artisan.');

Artisan::command('tenant:recalculate-storage {tenant_slug?}', function (?string $tenantSlug = null): int {
    $query = Tenant::query()
        ->when($tenantSlug !== null, fn ($query) => $query->where('slug', $tenantSlug));

    $tenants = $query->get();

    if ($tenants->isEmpty()) {
        $this->error('Tenant tidak ditemukan.');

        return Command::FAILURE;
    }

    foreach ($tenants as $tenant) {
        $usedBytes = (int) File::query()
            ->withTrashed()
            ->where('tenant_id', $tenant->id)
            ->sum('file_size');

        $tenant->forceFill([
            'storage_used_bytes' => $usedBytes,
        ])->save();

        $this->info(sprintf(
            'Tenant %s: storage_used_bytes diperbarui menjadi %d byte.',
            $tenant->slug,
            $usedBytes,
        ));
    }

    return Command::SUCCESS;
})->purpose('Rekalkulasi storage_used_bytes tenant berdasarkan seluruh file yang masih tersimpan di database.');

Artisan::command('tenant:rebuild-leaderboard {tenant_slug?}', function (ScoreService $scoreService, ?string $tenantSlug = null): int {
    $query = Tenant::query()
        ->when($tenantSlug !== null, fn ($query) => $query->where('slug', $tenantSlug));

    $tenants = $query->get();

    if ($tenants->isEmpty()) {
        $this->error('Tenant tidak ditemukan.');

        return Command::FAILURE;
    }

    foreach ($tenants as $tenant) {
        $affected = 0;

        foreach ($tenant->guestUploaders()->get() as $uploader) {
            $scoreService->recalculateUploaderScore($uploader);
            $affected++;
        }

        $this->info(sprintf(
            'Tenant %s: leaderboard rebuilt untuk %d uploader.',
            $tenant->slug,
            $affected,
        ));
    }

    return Command::SUCCESS;
})->purpose('Rebuild skor dan leaderboard tenant dengan menghitung ulang last_score seluruh uploader.');
