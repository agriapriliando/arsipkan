@extends('layouts.platform')

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <span class="eyebrow mb-3">{{ $mode === 'mine' ? 'Berkas Saya' : $heading }}</span>
            <h1 class="h2 fw-bold mb-1">{{ $heading }}</h1>
            <p class="text-secondary mb-0">{{ $description }}</p>
        </div>

        @if($mode === 'mine')
            <span class="text-secondary small">Upload file dilakukan melalui link upload organisasi.</span>
        @endif
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <section class="panel-box p-4">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Berkas</th>
                        <th>Metadata</th>
                        <th>Status</th>
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
                                @if($mode === 'mine')
                                    <form method="POST" action="{{ route('tenant.user.files.visibility', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="mb-1">
                                        @csrf
                                        @method('PATCH')
                                        <select
                                            name="visibility"
                                            class="form-select form-select-sm"
                                            onchange="if(this.value === 'public' && !confirm('Ubah ke public? File akan masuk antrean review admin.')) { this.value = '{{ $file->visibility }}'; return; } this.form.submit();"
                                        >
                                            <option value="private" @selected($file->visibility === 'private')>private</option>
                                            <option value="internal" @selected($file->visibility === 'internal')>internal</option>
                                            <option value="public" @selected($file->visibility === 'public')>public</option>
                                        </select>
                                    </form>
                                    <div class="text-secondary small">Jika diubah ke public, file akan direview ulang admin.</div>
                                @else
                                    <div class="fw-semibold text-capitalize">{{ $file->visibility }}</div>
                                @endif
                                <div class="text-secondary small">
                                    {{ $file->category?->name ?? 'Tanpa kategori' }} | {{ $file->uploaded_at?->translatedFormat('d M Y H:i') ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <span class="status-pill {{ $file->status === 'valid' ? 'status-active' : 'status-inactive' }}">
                                    {{ str_replace('_', ' ', $file->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('tenant.user.files.show', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="btn btn-sm btn-light border fw-semibold">Detail</a>
                                    <a href="{{ route('tenant.user.files.download', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}" class="btn btn-sm btn-light border fw-semibold">Unduh</a>
                                    @if($mode === 'mine')
                                        <form method="POST" action="{{ route('tenant.user.files.destroy', ['tenant_slug' => request()->route('tenant_slug'), 'file' => $file->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold" onclick="return confirm('Pindahkan file ini ke arsip terhapus?')">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-5">Belum ada berkas untuk ditampilkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
