<div
    x-data="{
        selectedVisibility: @entangle('visibility'),
        isDragOver: false,
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
>
    <div class="upload-container">
        <div class="logo-box">
            <i data-lucide="file-up"></i>
        </div>
        <h3 class="text-center fw-bold mb-1">{{ $uploadLink->title }}</h3>
        <p class="text-center text-muted small mb-1">Silakan isi identitas dan pilih berkas Anda.</p>

        @if($successMessage)
            <div class="alert alert-success">{{ $successMessage }}</div>
        @endif

        <form wire:submit="submit">
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

            <div class="mb-3">
                <label class="form-label">Visibilitas Berkas</label>
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
                <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem">Berkas publik menunggu moderasi admin. Berkas privat dan internal langsung aktif.</p>
                @error('visibility')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>

            <div
                class="drop-zone"
                x-bind:class="{ 'dragover': isDragOver }"
                x-show="@js($uploadedFileName === '')"
                x-on:click="$refs.fileInput.click()"
                x-on:dragover.prevent="isDragOver = true"
                x-on:dragleave.prevent="isDragOver = false"
                x-on:drop.prevent="handleDrop($event)"
            >
                <i data-lucide="cloud-upload" class="text-muted mb-2" style="width: 32px; height: 32px"></i>
                <p class="mb-0 text-muted small fw-medium">Klik atau seret file ke sini</p>
                <p class="text-muted" style="font-size: 0.7rem">Maksimal 100MB</p>
            </div>

            <input
                id="fileInput"
                x-ref="fileInput"
                type="file"
                wire:model="uploadedFile"
            >

            <div class="file-info" @style(['display: flex' => $uploadedFileName !== ''])>
                <i data-lucide="file" class="text-primary" style="width: 20px"></i>
                <span class="text-truncate flex-grow-1">{{ $uploadedFileName }}</span>
                <button type="button" class="btn btn-sm p-0 text-danger" wire:click="clearUploadedFile">
                    <i data-lucide="x" style="width: 18px"></i>
                </button>
            </div>
            @error('uploadedFile')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror

            <button type="submit" class="btn-upload" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit,uploadedFile">Unggah File</span>
                <span wire:loading wire:target="submit,uploadedFile">Mengunggah...</span>
            </button>
        </form>
    </div>
</div>
