<div
    x-data="{
        selectedVisibility: @entangle('visibility'),
        isDragOver: false,
        isUploading: false,
        uploadProgress: 0,
        refreshIcons() {
            this.$nextTick(() => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        },
        handleDrop(event) {
            const files = event.dataTransfer?.files;

            this.isDragOver = false;

            if (!files?.length) {
                return;
            }

            this.$refs.fileInput.files = files;
            this.$refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            this.refreshIcons();
        }
    }"
    x-init="refreshIcons()"
    x-on:livewire-upload-finish.window="refreshIcons()"
    x-on:livewire-upload-error.window="refreshIcons()"
    x-on:livewire-upload-start="isUploading = true; uploadProgress = 0"
    x-on:livewire-upload-progress="uploadProgress = $event.detail.progress"
    x-on:livewire-upload-finish="isUploading = false; uploadProgress = 100"
    x-on:livewire-upload-error="isUploading = false"
>
    <div class="upload-container">
        <div class="logo-box">
            <i data-lucide="file-up"></i>
        </div>
        <h3 class="text-center fw-bold mb-1">{{ $uploadLink->title }}</h3>
        <p class="text-center text-muted small mb-1">Silakan isi identitas dan unggah berkas.</p>

        @if($successMessage)
            <div class="alert alert-success">{{ $successMessage }}</div>
        @endif

        <form wire:submit="submit">
            @if($hasStoredIdentity)
                <div class="mb-3 p-3 rounded-4 border bg-light-subtle">
                    <div class="d-flex flex-column gap-2 flex-sm-row justify-content-between align-items-sm-center">
                        <div>
                            <div class="small text-muted mb-1">Identitas tersimpan</div>
                            <div class="fw-semibold">{{ $name ?: 'Nama belum diisi' }}</div>
                            <div class="small text-secondary">{{ $phoneNumber ?: 'Nomor HP belum diisi' }}</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="toggleIdentityFields">
                            {{ $showIdentityFields ? 'Sembunyikan Form Identitas' : 'Ubah Nama / No. HP' }}
                        </button>
                    </div>
                </div>
            @endif

            <div @class(['d-none' => $hasStoredIdentity && ! $showIdentityFields])>
                <div class="mb-3">
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" wire:model.live.debounce.400ms="name" placeholder="Masukkan nama Anda" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="phoneNumber" class="form-label">Nomor HP (WhatsApp)</label>
                    <input id="phoneNumber" type="tel" class="form-control @error('phoneNumber') is-invalid @enderror" wire:model.live.debounce.400ms="phoneNumber" placeholder="Contoh: 08123456789" required>
                    @error('phoneNumber')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <label class="form-label mb-0">Visibilitas Berkas</label>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadVisibilityGuideModal"
                    >
                        Petunjuk
                    </button>
                </div>
                <div class="visibility-option">
                    <label class="vis-card" x-bind:class="{ 'active': selectedVisibility === 'public' }">
                        <input type="radio" wire:model.live="visibility" value="public">
                        <span>Publik</span>
                    </label>
                    <label class="vis-card" x-bind:class="{ 'active': selectedVisibility === 'private' }">
                        <input type="radio" wire:model.live="visibility" value="private">
                        <span>Privat</span>
                    </label>
                    <label class="vis-card" x-bind:class="{ 'active': selectedVisibility === 'internal' }">
                        <input type="radio" wire:model.live="visibility" value="internal">
                        <span>Internal</span>
                    </label>
                </div>
                @error('visibility')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>

            <div
                class="drop-zone"
                x-bind:class="{ 'dragover': isDragOver, 'opacity-50': isUploading }"
                x-show="@js($uploadedFileName === '')"
                x-on:click="if (!isUploading) $refs.fileInput.click()"
                x-on:dragover.prevent="if (!isUploading) isDragOver = true"
                x-on:dragleave.prevent="isDragOver = false"
                x-on:drop.prevent="if (!isUploading) handleDrop($event)"
            >
                <i data-lucide="cloud-upload" class="text-muted mb-2" style="width: 32px; height: 32px"></i>
                <p class="mb-0 text-muted small fw-medium">Klik atau seret file ke sini</p>
                <p class="text-muted mb-1" style="font-size: 0.7rem">Maksimal 20MB per file</p>
                <p class="text-muted mb-0" style="font-size: 0.68rem">Format yang didukung: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, JPEG, PNG, TXT</p>
            </div>

            <input
                id="fileInput"
                x-ref="fileInput"
                type="file"
                wire:model="uploadedFile"
                x-bind:disabled="isUploading"
            >

            <div class="file-info" @style(['display: flex' => $uploadedFileName !== ''])>
                <i data-lucide="file" class="text-primary" style="width: 20px"></i>
                <span class="flex-grow-1">
                    <span class="file-name-label">{{ $uploadedFileName }}</span>
                </span>
                <button type="button" class="btn btn-sm p-0 text-danger" wire:click="clearUploadedFile" x-bind:disabled="isUploading">
                    <i data-lucide="x" style="width: 18px"></i>
                </button>
            </div>

            <div x-cloak x-show="isUploading" class="mt-3">
                <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                    <span>Progress unggah file</span>
                    <span x-text="`${uploadProgress}%`"></span>
                </div>
                <div class="progress" role="progressbar" aria-label="Progress unggah file" aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="uploadProgress" style="height: 10px; border-radius: 999px;">
                    <div
                        class="progress-bar"
                        x-bind:style="`width: ${uploadProgress}%`"
                        style="background: linear-gradient(90deg, #6d28d9 0%, #8b5cf6 100%);"
                    ></div>
                </div>
            </div>
            @error('uploadedFile')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror

            <button type="submit" class="btn-upload" wire:loading.attr="disabled" x-bind:disabled="isUploading">
                <span x-show="!isUploading" wire:loading.remove wire:target="submit,uploadedFile">Unggah File</span>
                <span x-cloak x-show="isUploading" wire:loading wire:target="submit,uploadedFile">Mengunggah...</span>
            </button>
        </form>
    </div>

    <div class="modal fade" id="uploadVisibilityGuideModal" tabindex="-1" aria-labelledby="uploadVisibilityGuideModalLabel" aria-hidden="true" wire:ignore>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="uploadVisibilityGuideModalLabel">Petunjuk Visibilitas Berkas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-muted mb-3" style="font-size: 0.9rem;">
                        Pilih visibilitas sesuai kebutuhan akses berkas Anda.
                    </p>
                    <div class="mb-3">
                        <div class="fw-semibold mb-1">Publik</div>
                        <div class="text-muted small">
                            Berkas akan direview oleh Admin. Setelah dinyatakan <strong>VALID</strong>, berkas akan ditampilkan di halaman katalog publik.
                            <a href="{{ route('tenant.home', ['tenant_slug' => $uploadLink->tenant->slug]) }}" target="_blank" rel="noopener">Lihat katalog publik</a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="fw-semibold mb-1">Privat</div>
                        <div class="text-muted small">Berkas hanya bisa diakses oleh pemilik dengan cara login. Minta akun uploader anda dengan Admin Organisasi.</div>
                    </div>
                    <div class="mb-0">
                        <div class="fw-semibold mb-1">Internal</div>
                        <div class="text-muted small mb-2">Berkas hanya bisa diakses oleh anggota internal dengan cara login akun uploader.</div>
                        <div class="text-muted small">
                            <a href="{{ route('tenant.login', ['tenant_slug' => $uploadLink->tenant->slug]) }}">Klik login</a> untuk user uploader mengakses data private dan data internal organisasi.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
