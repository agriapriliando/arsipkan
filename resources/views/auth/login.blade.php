@extends('layouts.platform')

@section('content')
    <style>
        .main-content.no-sidebar {
            padding: 0;
        }

        .auth-page {
            position: relative;
            overflow: hidden;
            background: #6413cd;
        }

        .auth-page .auth-card {
            width: min(100%, 31.25rem);
            max-width: 31.25rem;
            position: relative;
            z-index: 1;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .auth-background {
            position: absolute;
            inset: 0;
            overflow: hidden;
        }

        .auth-background span {
            width: 20vmin;
            height: 20vmin;
            border-radius: 20vmin;
            backface-visibility: hidden;
            position: absolute;
            animation: auth-move linear infinite;
        }

        .auth-background span:nth-child(1) {
            color: #E45A84;
            top: 28%;
            left: 70%;
            animation-duration: 24s;
            animation-delay: -35s;
            transform-origin: -22vw 8vh;
            box-shadow: 40vmin 0 5.500647168195497vmin currentColor;
        }

        .auth-background span:nth-child(2) {
            color: #583C87;
            top: 39%;
            left: 24%;
            animation-duration: 12s;
            animation-delay: -17s;
            transform-origin: -14vw 16vh;
            box-shadow: -40vmin 0 5.9272016043247024vmin currentColor;
        }

        .auth-background span:nth-child(3) {
            color: #E45A84;
            top: 22%;
            left: 9%;
            animation-duration: 8s;
            animation-delay: -4s;
            transform-origin: -13vw 19vh;
            box-shadow: 40vmin 0 5.160840092295143vmin currentColor;
        }

        .auth-background span:nth-child(4) {
            color: #583C87;
            top: 70%;
            left: 19%;
            animation-duration: 36s;
            animation-delay: -18s;
            transform-origin: 22vw 21vh;
            box-shadow: -40vmin 0 5.843394622455966vmin currentColor;
        }

        .auth-background span:nth-child(5) {
            color: #E45A84;
            top: 42%;
            left: 76%;
            animation-duration: 44s;
            animation-delay: -33s;
            transform-origin: 8vw -2vh;
            box-shadow: -40vmin 0 5.969680748390754vmin currentColor;
        }

        .auth-background span:nth-child(6) {
            color: #FFACAC;
            top: 51%;
            left: 28%;
            animation-duration: 29s;
            animation-delay: -10s;
            transform-origin: 6vw -10vh;
            box-shadow: -40vmin 0 5.83216311282165vmin currentColor;
        }

        .auth-background span:nth-child(7) {
            color: #583C87;
            top: 89%;
            left: 34%;
            animation-duration: 31s;
            animation-delay: -17s;
            transform-origin: -16vw -8vh;
            box-shadow: 40vmin 0 5.5314238806511vmin currentColor;
        }

        .auth-background span:nth-child(8) {
            color: #FFACAC;
            top: 55%;
            left: 76%;
            animation-duration: 22s;
            animation-delay: -24s;
            transform-origin: 10vw 25vh;
            box-shadow: 40vmin 0 5.460379339923396vmin currentColor;
        }

        .auth-background span:nth-child(9) {
            color: #583C87;
            top: 24%;
            left: 14%;
            animation-duration: 33s;
            animation-delay: -40s;
            transform-origin: 7vw 22vh;
            box-shadow: 40vmin 0 5.782357638572373vmin currentColor;
        }

        .auth-background span:nth-child(10) {
            color: #583C87;
            top: 90%;
            left: 86%;
            animation-duration: 26s;
            animation-delay: -25s;
            transform-origin: -16vw -3vh;
            box-shadow: -40vmin 0 5.175180666598663vmin currentColor;
        }

        .auth-background span:nth-child(11) {
            color: #FFACAC;
            top: 93%;
            left: 21%;
            animation-duration: 41s;
            animation-delay: -21s;
            transform-origin: -1vw 24vh;
            box-shadow: 40vmin 0 5.452355618633854vmin currentColor;
        }

        .auth-background span:nth-child(12) {
            color: #E45A84;
            top: 46%;
            left: 44%;
            animation-duration: 34s;
            animation-delay: -7s;
            transform-origin: 18vw 11vh;
            box-shadow: 40vmin 0 5.336437286455898vmin currentColor;
        }

        .auth-background span:nth-child(13) {
            color: #E45A84;
            top: 78%;
            left: 87%;
            animation-duration: 8s;
            animation-delay: -24s;
            transform-origin: -6vw 9vh;
            box-shadow: 40vmin 0 5.555415185235511vmin currentColor;
        }

        .auth-background span:nth-child(14) {
            color: #583C87;
            top: 78%;
            left: 22%;
            animation-duration: 18s;
            animation-delay: -27s;
            transform-origin: 15vw -21vh;
            box-shadow: -40vmin 0 5.700161659978305vmin currentColor;
        }

        .auth-background span:nth-child(15) {
            color: #E45A84;
            top: 87%;
            left: 51%;
            animation-duration: 12s;
            animation-delay: -12s;
            transform-origin: 19vw -5vh;
            box-shadow: -40vmin 0 5.4353257832754736vmin currentColor;
        }

        .auth-background span:nth-child(16) {
            color: #E45A84;
            top: 39%;
            left: 81%;
            animation-duration: 40s;
            animation-delay: -4s;
            transform-origin: -3vw -8vh;
            box-shadow: -40vmin 0 5.070002725186504vmin currentColor;
        }

        .auth-background span:nth-child(17) {
            color: #E45A84;
            top: 41%;
            left: 37%;
            animation-duration: 27s;
            animation-delay: -22s;
            transform-origin: -22vw -3vh;
            box-shadow: -40vmin 0 5.983575183143129vmin currentColor;
        }

        .auth-background span:nth-child(18) {
            color: #E45A84;
            top: 12%;
            left: 81%;
            animation-duration: 19s;
            animation-delay: -43s;
            transform-origin: -14vw -1vh;
            box-shadow: -40vmin 0 5.282796139249302vmin currentColor;
        }

        .auth-background span:nth-child(19) {
            color: #E45A84;
            top: 37%;
            left: 16%;
            animation-duration: 10s;
            animation-delay: -24s;
            transform-origin: 8vw 1vh;
            box-shadow: -40vmin 0 5.72300292543677vmin currentColor;
        }

        .auth-background span:nth-child(20) {
            color: #E45A84;
            top: 29%;
            left: 87%;
            animation-duration: 28s;
            animation-delay: -19s;
            transform-origin: 15vw -19vh;
            box-shadow: -40vmin 0 5.212065920802415vmin currentColor;
        }

        @keyframes auth-move {
            100% {
                transform: translate3d(0, 0, 1px) rotate(360deg);
            }
        }
    </style>

    <div class="auth-page">
        <div class="auth-background" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>

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
