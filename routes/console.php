<?php

use App\Models\AdminUser;
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
