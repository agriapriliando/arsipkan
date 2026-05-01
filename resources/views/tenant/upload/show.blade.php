<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Upload Berkas - {{ $currentTenant->name ?? config('app.name', 'Arsipkan') }}</title>
        <link href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
        <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/lucide/lucide.min.js') }}"></script>
        <style>
            :root {
                --brand-primary: #6d28d9;
                --brand-primary-hover: #5b21b6;
                --brand-light: #f5f3ff;
                --bg-body: #f8fafc;
            }

            body {
                font-family: "Inter", sans-serif;
                background-color: var(--bg-body);
                color: #1e293b;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
                position: relative;
            }

            .upload-container {
                max-width: 500px;
                width: 100%;
                background: white;
                border-radius: 24px;
                padding: 2.5rem;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
                border: 1px solid #e2e8f0;
                position: relative;
                z-index: 1;
            }

            .logo-box {
                width: 48px;
                height: 48px;
                background: var(--brand-primary);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                margin: 0 auto 1.5rem;
            }

            .form-label {
                font-weight: 600;
                font-size: 0.875rem;
                color: #475569;
                margin-bottom: 0.5rem;
            }

            .form-control {
                border-radius: 12px;
                padding: 0.75rem 1rem;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                transition: all 0.2s;
            }

            .form-control:focus {
                background: white;
                border-color: var(--brand-primary);
                box-shadow: 0 0 0 4px var(--brand-light);
            }

            .drop-zone {
                border: 2px dashed #cbd5e1;
                border-radius: 16px;
                padding: 2rem;
                text-align: center;
                cursor: pointer;
                transition: all 0.2s;
                background: #f8fafc;
                margin-top: 1rem;
            }

            .drop-zone:hover,
            .drop-zone.dragover {
                border-color: var(--brand-primary);
                background: var(--brand-light);
            }

            .btn-upload {
                background: var(--brand-primary);
                color: white;
                border: none;
                border-radius: 12px;
                padding: 0.875rem;
                font-weight: 700;
                width: 100%;
                margin-top: 2rem;
                transition: all 0.2s;
            }

            .btn-upload:hover {
                background: var(--brand-primary-hover);
                color: white;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(109, 40, 217, 0.3);
            }

            .visibility-option {
                display: flex;
                gap: 1rem;
                margin-top: 0.5rem;
            }

            .vis-card {
                flex: 1;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 0.75rem;
                cursor: pointer;
                text-align: center;
                transition: all 0.2s;
            }

            .vis-card input {
                display: none;
            }

            .vis-card:hover {
                border-color: var(--brand-primary);
            }

            .vis-card.active {
                background: var(--brand-light);
                border-color: var(--brand-primary);
                color: var(--brand-primary);
                font-weight: 600;
            }

            #fileInput {
                display: none;
            }

            .file-info {
                margin-top: 1rem;
                display: none;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem;
                background: #f1f5f9;
                border-radius: 10px;
                font-size: 0.875rem;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow: hidden;
                box-sizing: border-box;
            }

            .file-info > i,
            .file-info > button {
                flex: 0 0 auto;
            }

            .file-info .flex-grow-1 {
                flex: 1 1 0;
                width: 0;
                min-width: 0;
                overflow: hidden;
            }

            .file-info .file-name-label {
                display: block;
                width: 100%;
                max-width: 100%;
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            @keyframes move {
                100% {
                    transform: translate3d(0, 0, 1px) rotate(360deg);
                }
            }

            .background {
                position: fixed;
                width: 100vw;
                height: 100vh;
                top: 0;
                left: 0;
                background: #6413cd;
                overflow: hidden;
            }

            .background span {
                width: 20vmin;
                height: 20vmin;
                border-radius: 20vmin;
                backface-visibility: hidden;
                position: absolute;
                animation: move;
                animation-duration: 45s;
                animation-timing-function: linear;
                animation-iteration-count: infinite;
            }

            .background span:nth-child(1) {
                color: #E45A84;
                top: 28%;
                left: 70%;
                animation-duration: 24s;
                animation-delay: -35s;
                transform-origin: -22vw 8vh;
                box-shadow: 40vmin 0 5.500647168195497vmin currentColor;
            }

            .background span:nth-child(2) {
                color: #583C87;
                top: 39%;
                left: 24%;
                animation-duration: 12s;
                animation-delay: -17s;
                transform-origin: -14vw 16vh;
                box-shadow: -40vmin 0 5.9272016043247024vmin currentColor;
            }

            .background span:nth-child(3) {
                color: #E45A84;
                top: 22%;
                left: 9%;
                animation-duration: 8s;
                animation-delay: -4s;
                transform-origin: -13vw 19vh;
                box-shadow: 40vmin 0 5.160840092295143vmin currentColor;
            }

            .background span:nth-child(4) {
                color: #583C87;
                top: 70%;
                left: 19%;
                animation-duration: 36s;
                animation-delay: -18s;
                transform-origin: 22vw 21vh;
                box-shadow: -40vmin 0 5.843394622455966vmin currentColor;
            }

            .background span:nth-child(5) {
                color: #E45A84;
                top: 42%;
                left: 76%;
                animation-duration: 44s;
                animation-delay: -33s;
                transform-origin: 8vw -2vh;
                box-shadow: -40vmin 0 5.969680748390754vmin currentColor;
            }

            .background span:nth-child(6) {
                color: #FFACAC;
                top: 51%;
                left: 28%;
                animation-duration: 29s;
                animation-delay: -10s;
                transform-origin: 6vw -10vh;
                box-shadow: -40vmin 0 5.83216311282165vmin currentColor;
            }

            .background span:nth-child(7) {
                color: #583C87;
                top: 89%;
                left: 34%;
                animation-duration: 31s;
                animation-delay: -17s;
                transform-origin: -16vw -8vh;
                box-shadow: 40vmin 0 5.5314238806511vmin currentColor;
            }

            .background span:nth-child(8) {
                color: #FFACAC;
                top: 55%;
                left: 76%;
                animation-duration: 22s;
                animation-delay: -24s;
                transform-origin: 10vw 25vh;
                box-shadow: 40vmin 0 5.460379339923396vmin currentColor;
            }

            .background span:nth-child(9) {
                color: #583C87;
                top: 24%;
                left: 14%;
                animation-duration: 33s;
                animation-delay: -40s;
                transform-origin: 7vw 22vh;
                box-shadow: 40vmin 0 5.782357638572373vmin currentColor;
            }

            .background span:nth-child(10) {
                color: #583C87;
                top: 90%;
                left: 86%;
                animation-duration: 26s;
                animation-delay: -25s;
                transform-origin: -16vw -3vh;
                box-shadow: -40vmin 0 5.175180666598663vmin currentColor;
            }

            .background span:nth-child(11) {
                color: #FFACAC;
                top: 93%;
                left: 21%;
                animation-duration: 41s;
                animation-delay: -21s;
                transform-origin: -1vw 24vh;
                box-shadow: 40vmin 0 5.452355618633854vmin currentColor;
            }

            .background span:nth-child(12) {
                color: #E45A84;
                top: 46%;
                left: 44%;
                animation-duration: 34s;
                animation-delay: -7s;
                transform-origin: 18vw 11vh;
                box-shadow: 40vmin 0 5.336437286455898vmin currentColor;
            }

            .background span:nth-child(13) {
                color: #E45A84;
                top: 78%;
                left: 87%;
                animation-duration: 8s;
                animation-delay: -24s;
                transform-origin: -6vw 9vh;
                box-shadow: 40vmin 0 5.555415185235511vmin currentColor;
            }

            .background span:nth-child(14) {
                color: #583C87;
                top: 78%;
                left: 22%;
                animation-duration: 18s;
                animation-delay: -27s;
                transform-origin: 15vw -21vh;
                box-shadow: -40vmin 0 5.700161659978305vmin currentColor;
            }

            .background span:nth-child(15) {
                color: #E45A84;
                top: 87%;
                left: 51%;
                animation-duration: 12s;
                animation-delay: -12s;
                transform-origin: 19vw -5vh;
                box-shadow: -40vmin 0 5.4353257832754736vmin currentColor;
            }

            .background span:nth-child(16) {
                color: #E45A84;
                top: 39%;
                left: 81%;
                animation-duration: 40s;
                animation-delay: -4s;
                transform-origin: -3vw -8vh;
                box-shadow: -40vmin 0 5.070002725186504vmin currentColor;
            }

            .background span:nth-child(17) {
                color: #E45A84;
                top: 41%;
                left: 37%;
                animation-duration: 27s;
                animation-delay: -22s;
                transform-origin: -22vw -3vh;
                box-shadow: -40vmin 0 5.983575183143129vmin currentColor;
            }

            .background span:nth-child(18) {
                color: #E45A84;
                top: 12%;
                left: 81%;
                animation-duration: 19s;
                animation-delay: -43s;
                transform-origin: -14vw -1vh;
                box-shadow: -40vmin 0 5.282796139249302vmin currentColor;
            }

            .background span:nth-child(19) {
                color: #E45A84;
                top: 37%;
                left: 16%;
                animation-duration: 10s;
                animation-delay: -24s;
                transform-origin: 8vw 1vh;
                box-shadow: -40vmin 0 5.72300292543677vmin currentColor;
            }

            .background span:nth-child(20) {
                color: #E45A84;
                top: 29%;
                left: 87%;
                animation-duration: 28s;
                animation-delay: -19s;
                transform-origin: 15vw -19vh;
                box-shadow: -40vmin 0 5.212065920802415vmin currentColor;
            }

            @media (max-width: 575.98px) {
                .upload-container {
                    padding: 1.5rem;
                }

                .file-info {
                    align-items: flex-start;
                    gap: 0.6rem;
                }

                .file-info .file-name-label {
                    font-size: 0.82rem;
                }

                .file-info button {
                    flex-shrink: 0;
                    margin-top: 0.1rem;
                }
            }
        </style>
        @livewireStyles
    </head>
    <body>
        <div class="background">
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
        <livewire:tenant.guest-upload-form :code="$code" />

        @livewireScripts
    </body>
</html>
