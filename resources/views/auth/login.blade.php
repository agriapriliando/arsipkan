@extends('layouts.platform')

@section('content')
    <div class="auth-page">
        <main class="auth-card">
            <div class="auth-logo-box">
                <i data-lucide="file-up" style="width: 28px; height: 28px"></i>
            </div>

            <h1 class="h3 text-center fw-bold mb-2">{{ $heading }}</h1>
            <p class="text-center text-secondary mb-4">{{ $description }}</p>

            <form method="POST" action="{{ $action }}">
                @csrf

                <div class="mb-3">
                    <label for="{{ $identifierName }}" class="form-label small fw-bold text-secondary">{{ $identifierLabel }}</label>
                    <input
                        id="{{ $identifierName }}"
                        name="{{ $identifierName }}"
                        type="{{ $identifierType }}"
                        class="form-control @error($identifierName) is-invalid @enderror"
                        value="{{ old($identifierName) }}"
                        placeholder="{{ $identifierPlaceholder }}"
                        required
                        autofocus
                    >
                    @error($identifierName)
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" x-data="{ showPassword: false }">
                    <label for="password" class="form-label small fw-bold text-secondary">Password</label>
                    <div class="input-group">
                        <input
                            id="password"
                            name="password"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Masukkan password"
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
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-4">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        value="1"
                        class="form-check-input"
                        @checked($rememberDefault)
                    >
                    <label for="remember" class="form-check-label small">Ingat saya</label>
                </div>

                <button type="submit" class="btn btn-brand w-100">Masuk</button>
            </form>
        </main>
    </div>
@endsection
