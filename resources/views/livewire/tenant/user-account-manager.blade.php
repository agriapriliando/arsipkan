<div>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Akun Uploader</span>
            <h1 class="h2 fw-bold mb-1">Manajemen Akun User Uploader</h1>
            <p class="text-secondary mb-0">Buat akun login dari uploader yang sudah pernah mengunggah file di tenant ini.</p>
        </div>

        <div class="tenant-search">
            <input
                type="text"
                class="form-control"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama atau nomor HP..."
            >
        </div>
    </div>

    @if($generatedPassword)
        <div class="alert alert-success d-flex flex-column gap-1">
            <strong>{{ $generatedPasswordLabel }}</strong>
            <div>Password sementara: <code>{{ $generatedPassword }}</code></div>
            <div class="small text-muted">Sampaikan password ini secara manual ke uploader. User akan diminta mengganti password saat login.</div>
        </div>
    @endif

    <section class="panel-box p-4 mb-4" x-data="{ copied: false, async copyLoginUrl(url) { try { if (navigator.clipboard?.writeText) { await navigator.clipboard.writeText(url); } else { const input = document.createElement('input'); input.value = url; document.body.appendChild(input); input.select(); document.execCommand('copy'); input.remove(); } this.copied = true; setTimeout(() => this.copied = false, 2000); } catch (error) { window.prompt('Salin URL login ini:', url); } } }">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <h2 class="h5 fw-bold mb-1">URL Login User Uploader</h2>
                <div><code>{{ $userLoginUrl }}</code></div>
                <div class="small text-muted mt-1">Bagikan URL ini ke uploader tenant agar mereka bisa login dengan nomor HP dan password akun.</div>
            </div>

            <div class="d-flex flex-column align-items-lg-end">
                <button
                    type="button"
                    class="btn btn-outline-brand"
                    x-on:click="copyLoginUrl(@js($userLoginUrl))"
                    x-bind:title="copied ? 'URL tersalin' : 'Copy URL login'"
                    x-bind:aria-label="copied ? 'URL tersalin' : 'Copy URL login'"
                >
                    Copy URL Login
                </button>
                <div class="small text-success mt-2" x-show="copied">URL login tersalin</div>
            </div>
        </div>
    </section>

    <section class="panel-box p-4">
        <h2 class="h5 fw-bold mb-3">Daftar Uploader</h2>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Uploader</th>
                        <th>Aktivitas</th>
                        <th>Status Akun</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guestUploaders as $guestUploader)
                        @php
                            $userAccount = $guestUploader->userAccount;
                        @endphp
                        <tr wire:key="guest-uploader-{{ $guestUploader->id }}">
                            <td>
                                <div class="fw-bold">{{ $guestUploader->name }}</div>
                                <div class="text-secondary small">{{ $guestUploader->phone_number }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $guestUploader->files_count }} file</div>
                                <div class="text-secondary small">Terakhir diupdate {{ $guestUploader->updated_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
                            </td>
                            <td>
                                @if($userAccount)
                                    <div>
                                        <span class="status-pill {{ $userAccount->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $userAccount->is_active ? 'Akun aktif' : 'Akun nonaktif' }}
                                        </span>
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        {{ $userAccount->must_change_password ? 'Harus ganti password' : 'Password sudah diganti' }}
                                    </div>
                                @else
                                    <span class="status-pill status-inactive">Belum punya akun</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($userAccount)
                                    <div class="btn-group">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-light border fw-semibold"
                                            wire:click="resetPassword({{ $userAccount->id }})"
                                        >
                                            Reset Password
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-light border fw-semibold"
                                            wire:click="toggleActive({{ $userAccount->id }})"
                                        >
                                            {{ $userAccount->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </div>
                                @else
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-brand fw-semibold"
                                        wire:click="createAccount({{ $guestUploader->id }})"
                                    >
                                        Buat Akun
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-5">Belum ada uploader yang cocok dengan pencarian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
