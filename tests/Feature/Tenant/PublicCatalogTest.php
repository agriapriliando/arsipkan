<?php

use App\Models\Category;
use App\Models\File;
use App\Models\GuestUploader;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\UploadLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createTenantForPublicCatalog(string $slug = 'katalog-a', array $overrides = []): Tenant
{
    return Tenant::create(array_merge([
        'code' => strtoupper(str_replace('-', '_', $slug)),
        'name' => 'Tenant '.$slug,
        'slug' => $slug,
        'storage_quota_bytes' => 10 * 1024 * 1024,
        'storage_used_bytes' => 0,
        'storage_warning_threshold_percent' => 80,
        'is_active' => true,
    ], $overrides));
}

function createGuestUploaderForPublicCatalog(Tenant $tenant, string $phone = '08123456789', string $name = 'Uploader Publik'): GuestUploader
{
    return GuestUploader::create([
        'tenant_id' => $tenant->id,
        'name' => $name,
        'phone_number' => $phone,
        'phone_number_normalized' => '62'.ltrim($phone, '0'),
    ]);
}

function createFileForPublicCatalog(Tenant $tenant, GuestUploader $uploader, array $overrides = []): File
{
    $uploadLink = UploadLink::create([
        'tenant_id' => $tenant->id,
        'code' => uniqid('catalog'),
        'title' => 'Link Katalog',
    ]);

    return File::create(array_merge([
        'tenant_id' => $tenant->id,
        'guest_uploader_id' => $uploader->id,
        'upload_link_id' => $uploadLink->id,
        'uploaded_via' => File::UPLOADED_VIA_GUEST_LINK,
        'original_name' => 'arsip-publik.pdf',
        'stored_name' => uniqid('public-catalog-', true).'.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 2048,
        'visibility' => File::VISIBILITY_PUBLIC,
        'status' => File::STATUS_VALID,
        'uploaded_at' => now(),
    ], $overrides));
}

it('shows only valid public files on the tenant public catalog home page', function () {
    $tenant = createTenantForPublicCatalog();
    $uploader = createGuestUploaderForPublicCatalog($tenant);

    createFileForPublicCatalog($tenant, $uploader, [
        'original_name' => 'laporan-publik.pdf',
        'title' => 'Laporan Publik',
        'description' => 'Ringkasan detail yang hanya boleh muncul di halaman detail.',
    ]);
    createFileForPublicCatalog($tenant, $uploader, [
        'original_name' => 'internal.pdf',
        'visibility' => File::VISIBILITY_INTERNAL,
    ]);
    createFileForPublicCatalog($tenant, $uploader, [
        'original_name' => 'pending.pdf',
        'status' => File::STATUS_PENDING_REVIEW,
    ]);

    $this->get('/katalog-a')
        ->assertOk()
        ->assertSee('Tenant katalog-a')
        ->assertSee('Laporan Publik')
        ->assertSee('12/hal')
        ->assertSee('Menampilkan 1-1 dari 1 berkas')
        ->assertDontSee('laporan-publik.pdf')
        ->assertDontSee('Ringkasan detail yang hanya boleh muncul di halaman detail.')
        ->assertDontSee('internal.pdf')
        ->assertDontSee('pending.pdf');
});

it('supports per page filtering and keeps pagination visible for a single page', function () {
    $tenant = createTenantForPublicCatalog();
    $uploader = createGuestUploaderForPublicCatalog($tenant);

    foreach (range(1, 9) as $number) {
        createFileForPublicCatalog($tenant, $uploader, [
            'original_name' => 'arsip-'.$number.'.pdf',
            'title' => 'Arsip '.$number,
        ]);
    }

    $this->get('/katalog-a?per_page=8')
        ->assertOk()
        ->assertSee('8/hal')
        ->assertSee('Menampilkan 1-8 dari 9 berkas')
        ->assertSee('per_page=8&amp;page=2', false);

    $this->get('/katalog-a?per_page=48')
        ->assertOk()
        ->assertSee('48/hal')
        ->assertSee('Menampilkan 1-9 dari 9 berkas');
});

it('filters and searches the tenant public catalog by category tag and file type', function () {
    $tenant = createTenantForPublicCatalog();
    $uploader = createGuestUploaderForPublicCatalog($tenant);
    $finance = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Keuangan',
        'slug' => 'keuangan',
        'is_active' => true,
    ]);
    $hr = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'SDM',
        'slug' => 'sdm',
        'is_active' => true,
    ]);
    $tagAnnual = Tag::create([
        'tenant_id' => $tenant->id,
        'name' => 'tahunan',
    ]);
    $tagMinutes = Tag::create([
        'tenant_id' => $tenant->id,
        'name' => 'rapat',
    ]);

    $financeFile = createFileForPublicCatalog($tenant, $uploader, [
        'original_name' => 'laporan-keuangan-2026.pdf',
        'title' => 'Laporan Keuangan 2026',
        'category_id' => $finance->id,
        'final_file_type' => 'laporan',
    ]);
    $financeFile->tags()->sync([$tagAnnual->id]);

    $minutesFile = createFileForPublicCatalog($tenant, $uploader, [
        'original_name' => 'notulen-sdm.docx',
        'title' => 'Notulen SDM',
        'category_id' => $hr->id,
        'extension' => 'docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'final_file_type' => 'notulen',
    ]);
    $minutesFile->tags()->sync([$tagMinutes->id]);

    $this->get('/katalog-a?search=laporan-keuangan-2026')
        ->assertOk()
        ->assertSee('Laporan Keuangan 2026')
        ->assertDontSee('Notulen SDM')
        ->assertDontSee('laporan-keuangan-2026.pdf')
        ->assertDontSee('notulen-sdm.docx');

    $this->get('/katalog-a?category_id='.$finance->id)
        ->assertOk()
        ->assertSee('Laporan Keuangan 2026')
        ->assertDontSee('Notulen SDM')
        ->assertDontSee('laporan-keuangan-2026.pdf')
        ->assertDontSee('notulen-sdm.docx');

    $this->get('/katalog-a?tag_id='.$tagMinutes->id)
        ->assertOk()
        ->assertSee('Notulen SDM')
        ->assertDontSee('Laporan Keuangan 2026')
        ->assertDontSee('notulen-sdm.docx')
        ->assertDontSee('laporan-keuangan-2026.pdf');

    $this->get('/katalog-a?file_type=laporan')
        ->assertOk()
        ->assertSee('Laporan Keuangan 2026')
        ->assertDontSee('Notulen SDM')
        ->assertDontSee('laporan-keuangan-2026.pdf')
        ->assertDontSee('notulen-sdm.docx');
});

it('shows the public file detail page and blocks non public or non valid files', function () {
    $tenant = createTenantForPublicCatalog();
    $uploader = createGuestUploaderForPublicCatalog($tenant, name: 'Uploader Arsip');
    $tag = Tag::create([
        'tenant_id' => $tenant->id,
        'name' => 'penting',
    ]);

    $publicFile = createFileForPublicCatalog($tenant, $uploader, [
        'original_name' => 'dokumen-terbit.pdf',
        'title' => 'Dokumen Terbit',
        'description' => 'Dokumen resmi untuk publik.',
    ]);
    $publicFile->tags()->sync([$tag->id]);

    $hiddenFile = createFileForPublicCatalog($tenant, $uploader, [
        'original_name' => 'dokumen-internal.pdf',
        'visibility' => File::VISIBILITY_INTERNAL,
    ]);

    $this->get('/katalog-a/catalog/'.$publicFile->id)
        ->assertOk()
        ->assertSee('Dokumen Terbit')
        ->assertSee('dokumen-terbit.pdf')
        ->assertSee('Dokumen resmi untuk publik.')
        ->assertSee('Uploader Arsip')
        ->assertSee('penting');

    $this->get('/katalog-a/catalog/'.$hiddenFile->id)
        ->assertNotFound();
});

it('allows public download only for valid public files', function () {
    Storage::fake('local');

    $tenant = createTenantForPublicCatalog();
    $uploader = createGuestUploaderForPublicCatalog($tenant);

    $publicFile = createFileForPublicCatalog($tenant, $uploader, [
        'stored_name' => 'tenant-'.$tenant->id.'/arsip-publik.pdf',
    ]);
    $pendingFile = createFileForPublicCatalog($tenant, $uploader, [
        'stored_name' => 'tenant-'.$tenant->id.'/arsip-pending.pdf',
        'status' => File::STATUS_PENDING_REVIEW,
    ]);

    Storage::disk('local')->put($publicFile->stored_name, 'public-file');
    Storage::disk('local')->put($pendingFile->stored_name, 'pending-file');

    $this->get('/katalog-a/catalog/'.$publicFile->id.'/download')
        ->assertOk();

    $this->get('/katalog-a/catalog/'.$pendingFile->id.'/download')
        ->assertNotFound();
});

it('renders pdf files inline and non pdf files as downloadable attachments', function () {
    Storage::fake('local');

    $tenant = createTenantForPublicCatalog();
    $uploader = createGuestUploaderForPublicCatalog($tenant);

    $pdfFile = createFileForPublicCatalog($tenant, $uploader, [
        'original_name' => 'preview-file.pdf',
        'stored_name' => 'tenant-'.$tenant->id.'/preview-file.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ]);
    $docxFile = createFileForPublicCatalog($tenant, $uploader, [
        'original_name' => 'download-file.docx',
        'stored_name' => 'tenant-'.$tenant->id.'/download-file.docx',
        'extension' => 'docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);

    Storage::disk('local')->put($pdfFile->stored_name, 'pdf-content');
    Storage::disk('local')->put($docxFile->stored_name, 'docx-content');

    $this->get('/katalog-a/catalog/'.$pdfFile->id.'/download')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename="preview-file.pdf"');

    $this->get('/katalog-a/catalog/'.$docxFile->id.'/download')
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
        ->assertHeader('content-disposition', 'attachment; filename=download-file.docx');
});

it('shows a public tenant leaderboard page', function () {
    $tenant = createTenantForPublicCatalog();
    $budi = createGuestUploaderForPublicCatalog($tenant, '081200000001', 'Budi Santoso');
    $siti = createGuestUploaderForPublicCatalog($tenant, '081200000002', 'Siti Aminah');
    $agus = createGuestUploaderForPublicCatalog($tenant, '081200000003', 'Agus Salim');

    foreach (range(1, 5) as $number) {
        createFileForPublicCatalog($tenant, $budi, [
            'title' => 'Budi '.$number,
            'status' => File::STATUS_VALID,
            'uploaded_at' => now()->subDays(2),
        ]);
    }

    foreach (range(1, 3) as $number) {
        createFileForPublicCatalog($tenant, $siti, [
            'title' => 'Siti '.$number,
            'status' => File::STATUS_VALID,
            'uploaded_at' => now()->subDays(2),
        ]);
    }

    createFileForPublicCatalog($tenant, $agus, [
        'title' => 'Agus 1',
        'status' => File::STATUS_VALID,
        'uploaded_at' => now()->subDays(2),
    ]);

    $this->get('/katalog-a/leaderboard')
        ->assertOk()
        ->assertSee('Peringkat Pengunggah')
        ->assertSee('Budi Santoso')
        ->assertSee('Siti Aminah')
        ->assertSee('Agus Salim')
        ->assertSee('Bulanan')
        ->assertSee('Mingguan');

    $this->get('/katalog-a/leaderboard?period=weekly')
        ->assertOk()
        ->assertSee('Peringkat Pengunggah')
        ->assertSee('Budi Santoso');
});
