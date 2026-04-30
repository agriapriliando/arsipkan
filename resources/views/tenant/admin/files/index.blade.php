@extends('layouts.platform')

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">Manajemen File</span>
            <h1 class="h2 fw-bold mb-1">{{ $heading }}</h1>
            <p class="text-secondary mb-0">{{ $description }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('tenant.admin.files.pending', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn {{ $mode === 'pending' ? 'btn-brand' : 'btn-outline-brand' }}">Pending Review</a>
            <a href="{{ route('tenant.admin.files.index', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn {{ $mode === 'all' ? 'btn-brand' : 'btn-outline-brand' }}">Semua Berkas</a>
            <a href="{{ route('tenant.admin.files.deleted', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn {{ $mode === 'deleted' ? 'btn-brand' : 'btn-outline-brand' }}">Berkas Terhapus</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($mode === 'all')
        <section class="panel-box p-4 mb-4">
            <form method="GET" action="{{ route('tenant.admin.files.index', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label fw-semibold">Pencarian</label>
                    <input
                        id="search"
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        class="form-control"
                        placeholder="Cari nama file, uploader, HP, atau kode link"
                    >
                </div>
                <div class="col-md-3">
                    <label for="visibility" class="form-label fw-semibold">Visibilitas</label>
                    <select id="visibility" name="visibility" class="form-select">
                        <option value="">Semua visibilitas</option>
                        <option value="public" @selected(($filters['visibility'] ?? '') === 'public')>public</option>
                        <option value="internal" @selected(($filters['visibility'] ?? '') === 'internal')>internal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="category_id" class="form-label fw-semibold">Kategori</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">Semua kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-brand">Terapkan Filter</button>
                        <a href="{{ route('tenant.admin.files.index', ['tenant_slug' => request()->route('tenant_slug')]) }}" class="btn btn-outline-brand">Reset</a>
                    </div>
                </div>
            </form>
        </section>
    @endif

    <section class="panel-box p-4">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Berkas</th>
                        <th>Uploader</th>
                        <th>Visibilitas</th>
                        <th>Status</th>
                        <th>Metadata</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($files as $file)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $file->title ?: $file->original_name }}</div>
                                <div class="text-secondary small">{{ $file->original_name }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $file->guestUploader?->name ?? 'Uploader tidak diketahui' }}</div>
                                <div class="text-secondary small">{{ $file->guestUploader?->phone_number ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="status-pill {{ $file->visibility === 'public' ? 'status-active' : 'status-inactive' }}">
                                    {{ $file->visibility }}
                                </span>
                            </td>
                            <td>
                                <span class="status-pill {{ $file->status === 'valid' ? 'status-active' : 'status-inactive' }}">
                                    {{ str_replace('_', ' ', $file->status) }}
                                </span>
                            </td>
                            <td class="text-secondary small">
                                <div>{{ $file->category?->name ?? 'Tanpa kategori' }}</div>
                                <div>{{ $file->uploaded_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
                                <div>{{ $file->uploadLink?->code ? 'Link '.$file->uploadLink->code : 'Tanpa link' }}</div>
                                @if($mode === 'deleted')
                                    <div>Dihapus: {{ $file->deleted_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('tenant.admin.files.show', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="btn btn-sm btn-light border fw-semibold">Detail</a>
                                    <a href="{{ route('tenant.admin.files.download', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="btn btn-sm btn-light border fw-semibold">Unduh</a>
                                    @if($mode === 'all' || $mode === 'pending')
                                        <form method="POST" action="{{ route('tenant.admin.files.archive', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold" onclick="return confirm('Pindahkan file ini ke berkas terhapus?')">Hapus</button>
                                        </form>
                                    @endif
                                    @if($mode === 'deleted')
                                        <form method="POST" action="{{ route('tenant.admin.files.restore', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success fw-semibold" onclick="return confirm('Pulihkan file ini ke daftar aktif?')">Pulihkan</button>
                                        </form>
                                        <form method="POST" action="{{ route('tenant.admin.files.destroy', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold" onclick="return confirm('Hapus permanen file ini? Tindakan ini tidak dapat dibatalkan.')">Hapus Permanen</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">Belum ada file untuk ditampilkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($files, 'links'))
            <div class="mt-4">
                {{ $files->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
@endsection
