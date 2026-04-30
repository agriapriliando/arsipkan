@extends('layouts.platform')

@php
    $title = 'Leaderboard '.$tenant->name;
@endphp

@section('content')
    <style>
        :root {
            --leaderboard-primary: #6d28d9;
            --leaderboard-primary-hover: #5b21b6;
            --leaderboard-light: #f5f3ff;
            --leaderboard-soft: #ede9fe;
            --leaderboard-bg: #f8fafc;
        }

        .public-leaderboard-shell {
            margin: -2rem -2rem 0;
            background-color: var(--leaderboard-bg);
            min-height: 100vh;
        }

        .public-leaderboard-nav {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 0;
        }

        .public-leaderboard-nav .navbar-brand {
            color: #0f172a;
            text-decoration: none;
        }

        .public-leaderboard-logo {
            width: 32px;
            height: 32px;
            background: var(--leaderboard-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .public-leaderboard-hero {
            background: linear-gradient(135deg, #6d28d9 0%, #4c1d95 100%);
            padding: 4rem 0;
            color: #fff;
            text-align: center;
            margin-bottom: -3rem;
        }

        .public-podium-container {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 1rem;
            margin-bottom: 4rem;
            position: relative;
            z-index: 10;
            flex-wrap: wrap;
        }

        .public-podium-item {
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.1);
            width: 200px;
        }

        .public-podium-item.rank-1 {
            min-height: 280px;
            border: 3px solid #fbbf24;
        }

        .public-podium-item.rank-2 {
            min-height: 240px;
        }

        .public-podium-item.rank-3 {
            min-height: 220px;
        }

        .public-rank-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -3.5rem auto 1rem;
            font-weight: 800;
            font-size: 1.25rem;
            color: #fff;
        }

        .rank-1 .public-rank-badge {
            background: #fbbf24;
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
        }

        .rank-2 .public-rank-badge {
            background: #94a3b8;
        }

        .rank-3 .public-rank-badge {
            background: #b45309;
        }

        .public-leaderboard-table {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(15, 23, 42, 0.02);
        }

        .public-leaderboard-table .table thead th {
            background: #f8fafc;
            border: 0;
            padding: 1.25rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #64748b;
        }

        .public-leaderboard-table .table tbody td {
            padding: 1.25rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .public-avatar-sm {
            width: 32px;
            height: 32px;
            background: var(--leaderboard-soft);
            color: var(--leaderboard-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .public-period-link {
            color: #fff;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;
            padding: 0.55rem 1rem;
            font-weight: 600;
            font-size: 0.875rem;
            background: rgba(255, 255, 255, 0.08);
        }

        .public-period-link.active {
            background: #fff;
            color: var(--leaderboard-primary);
            border-color: #fff;
        }

        @media (max-width: 991.98px) {
            .public-leaderboard-shell {
                margin: -1rem -1rem 0;
            }
        }
    </style>

    @php
        $initials = static function (?string $name): string {
            $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
            $letters = collect($parts)->filter()->take(2)->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))->implode('');

            return $letters !== '' ? $letters : 'UP';
        };
    @endphp

    <div class="public-leaderboard-shell">
        <x-tenant.public-header
            :tenant="$tenant"
            subtitle="Peringkat pengunggah publik"
            active="leaderboard"
            nav-class="public-leaderboard-nav"
            logo-class="public-leaderboard-logo"
        />

        <section class="public-leaderboard-hero">
            <div class="container">
                <h1 class="fw-bold mb-2">Peringkat Pengunggah</h1>
                <p class="opacity-75 mb-3">Apresiasi bagi kontributor arsip paling aktif {{ $periodHeading }} di {{ $tenant->name }}.</p>
                <div class="d-flex justify-content-center flex-wrap gap-2">
                    <a href="{{ route('tenant.leaderboard', ['tenant_slug' => $tenant->slug, 'period' => 'monthly']) }}" class="public-period-link {{ $selectedPeriod === 'monthly' ? 'active' : '' }}">Bulanan</a>
                    <a href="{{ route('tenant.leaderboard', ['tenant_slug' => $tenant->slug, 'period' => 'weekly']) }}" class="public-period-link {{ $selectedPeriod === 'weekly' ? 'active' : '' }}">Mingguan</a>
                </div>
            </div>
        </section>

        <div class="container pb-5">
            @if($leaderboard->isNotEmpty())
                <div class="public-podium-container">
                    @foreach([2, 1, 3] as $rank)
                        @php($uploader = $podium->get($rank))
                        @if($uploader)
                            <div class="public-podium-item rank-{{ $rank }}">
                                <div class="public-rank-badge">{{ $rank }}</div>
                                <div class="public-avatar-sm mx-auto mb-2" style="width: {{ $rank === 1 ? '80px' : '60px' }}; height: {{ $rank === 1 ? '80px' : '60px' }}; font-size: {{ $rank === 1 ? '2rem' : '1.5rem' }}; {{ $rank === 1 ? 'background: #fef3c7; color: #92400e;' : '' }}">
                                    {{ $initials($uploader->name) }}
                                </div>
                                <h2 class="h6 fw-bold mb-1">{{ $uploader->name }}</h2>
                                <p class="text-muted small mb-0">{{ number_format((int) $uploader->valid_upload_count) }} Berkas Valid</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="public-leaderboard-table">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">Posisi</th>
                            <th>Uploader</th>
                            <th>Total Unggahan</th>
                            <th>Valid</th>
                            <th>Poin Kontribusi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaderboard as $index => $uploader)
                            <tr>
                                <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="public-avatar-sm">{{ $initials($uploader->name) }}</div>
                                        <span class="fw-semibold small">{{ $uploader->name }}</span>
                                    </div>
                                </td>
                                <td><span class="small">{{ number_format((int) $uploader->total_upload_count) }}</span></td>
                                <td><span class="text-success fw-bold small">{{ number_format((int) $uploader->valid_upload_count) }}</span></td>
                                <td><span class="badge bg-light text-primary rounded-pill px-3">{{ number_format((float) $uploader->period_score, 0, ',', '.') }} XP</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-5">Belum ada data leaderboard untuk periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
@endsection
