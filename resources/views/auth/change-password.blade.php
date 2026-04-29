@extends('layouts.platform')

@section('content')
    <div class="auth-page">
        <main class="auth-card">
            <div class="auth-logo-box">
                <i data-lucide="file-up" style="width: 28px; height: 28px"></i>
            </div>

            <h1 class="h3 text-center fw-bold mb-2">Ubah Password</h1>
            <p class="text-center text-secondary mb-4">Password awal harus diganti sebelum masuk ke portal.</p>

            <form method="POST" action="{{ $action }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="current_password" class="form-label small fw-bold text-secondary">Password Saat Ini</label>
                    <input id="current_password" name="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" required autofocus>
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label small fw-bold text-secondary">Password Baru</label>
                    <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label small fw-bold text-secondary">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-brand w-100">Simpan Password</button>
            </form>
        </main>
    </div>
@endsection
