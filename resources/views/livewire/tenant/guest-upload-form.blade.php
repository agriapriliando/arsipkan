<div>
    <section class="hero-card p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-12 col-lg-8">
                <span class="eyebrow mb-3">Upload Guest</span>
                <h1 class="display-6 fw-bold mb-3">{{ $uploadLink->title }}</h1>
                <p class="text-secondary fs-5 mb-0">Unggah file ke {{ $currentTenant->name ?? 'tenant aktif' }} tanpa login.</p>
            </div>

            <div class="col-12 col-lg-4">
                <div class="info-card p-4">
                    <div class="muted-label mb-2">Kode Link</div>
                    <div class="fs-4 fw-bold mb-2">{{ $uploadLink->code }}</div>
                    <div class="text-secondary">Terpakai {{ $uploadLink->usage_count }} / {{ $uploadLink->max_usage ?? 'tanpa batas' }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="panel-box p-4">
        @if($successMessage)
            <div class="alert alert-success">{{ $successMessage }}</div>
        @endif

        <form wire:submit="submit">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label for="name" class="form-label small fw-bold text-secondary">Nama</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name" placeholder="Nama pengunggah">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label for="phoneNumber" class="form-label small fw-bold text-secondary">Nomor HP</label>
                    <input id="phoneNumber" type="text" class="form-control @error('phoneNumber') is-invalid @enderror" wire:model="phoneNumber" placeholder="08123456789">
                    @error('phoneNumber')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label for="visibility" class="form-label small fw-bold text-secondary">Visibilitas</label>
                    <select id="visibility" class="form-select form-control @error('visibility') is-invalid @enderror" wire:model="visibility">
                        <option value="private">Private</option>
                        <option value="internal">Internal</option>
                        <option value="public">Publik</option>
                    </select>
                    @error('visibility')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label for="uploadedFile" class="form-label small fw-bold text-secondary">File</label>
                    <input id="uploadedFile" type="file" class="form-control @error('uploadedFile') is-invalid @enderror" wire:model="uploadedFile">
                    @error('uploadedFile')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-brand" wire:loading.attr="disabled">
                    Unggah File
                </button>
            </div>
        </form>
    </section>
</div>
