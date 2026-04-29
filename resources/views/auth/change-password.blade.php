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

                @if($errors->any())
                    <div class="alert alert-danger">
                        <div class="fw-semibold mb-1">Periksa kembali form berikut:</div>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3" x-data="{ showPassword: false }">
                    <label for="current_password" class="form-label small fw-bold text-secondary">Password Saat Ini</label>
                    <div class="input-group">
                        <input
                            id="current_password"
                            name="current_password"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            class="form-control @error('current_password') is-invalid @enderror"
                            required
                            autofocus
                        >
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            x-on:click="showPassword = !showPassword"
                            x-bind:aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            x-bind:aria-pressed="showPassword ? 'true' : 'false'"
                            x-bind:title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                        >
                            <span x-show="!showPassword" x-cloak>
                                <i data-lucide="eye" style="width: 18px; height: 18px"></i>
                            </span>
                            <span x-show="showPassword" x-cloak>
                                <i data-lucide="eye-off" style="width: 18px; height: 18px"></i>
                            </span>
                        </button>
                    </div>
                    <div class="form-text">Masukkan password yang sedang Anda gunakan saat ini.</div>
                    @error('current_password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" x-data="{ showPassword: false }">
                    <label for="password" class="form-label small fw-bold text-secondary">Password Baru</label>
                    <div class="input-group">
                        <input
                            id="password"
                            name="password"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            class="form-control @error('password') is-invalid @enderror"
                            required
                        >
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            x-on:click="showPassword = !showPassword"
                            x-bind:aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            x-bind:aria-pressed="showPassword ? 'true' : 'false'"
                            x-bind:title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                        >
                            <span x-show="!showPassword" x-cloak>
                                <i data-lucide="eye" style="width: 18px; height: 18px"></i>
                            </span>
                            <span x-show="showPassword" x-cloak>
                                <i data-lucide="eye-off" style="width: 18px; height: 18px"></i>
                            </span>
                        </button>
                    </div>
                    <div class="form-text">Password baru minimal 8 karakter.</div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4" x-data="{ showPassword: false }">
                    <label for="password_confirmation" class="form-label small fw-bold text-secondary">Konfirmasi Password Baru</label>
                    <div class="input-group">
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            class="form-control"
                            required
                        >
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            x-on:click="showPassword = !showPassword"
                            x-bind:aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            x-bind:aria-pressed="showPassword ? 'true' : 'false'"
                            x-bind:title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                        >
                            <span x-show="!showPassword" x-cloak>
                                <i data-lucide="eye" style="width: 18px; height: 18px"></i>
                            </span>
                            <span x-show="showPassword" x-cloak>
                                <i data-lucide="eye-off" style="width: 18px; height: 18px"></i>
                            </span>
                        </button>
                    </div>
                    <div class="form-text">Isi sama persis dengan password baru.</div>
                    @error('password_confirmation')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-brand w-100">Simpan Password</button>
            </form>
        </main>
    </div>
@endsection
