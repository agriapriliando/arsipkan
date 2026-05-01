<?php

namespace App\Livewire\Tenant;

use App\Models\File as ArchivedFile;
use App\Models\GuestUploader;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Services\PhoneNumberNormalizer;
use App\Services\Scoring\ScoreService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class GuestUploadForm extends Component
{
    use WithFileUploads;

    public ?int $tenantId = null;

    public UploadLink $uploadLink;

    public string $name = '';

    public string $phoneNumber = '';

    public string $visibility = ArchivedFile::VISIBILITY_PRIVATE;

    public ?TemporaryUploadedFile $uploadedFile = null;

    public string $uploadedFileName = '';

    public ?string $successMessage = null;

    public bool $hasStoredIdentity = false;

    public bool $showIdentityFields = true;

    public function mount(string $code): void
    {
        $tenant = $this->currentTenant();
        $this->tenantId = $tenant->id;

        $this->uploadLink = UploadLink::query()
            ->forTenant($tenant)
            ->with('tenant')
            ->where('code', Str::upper($code))
            ->firstOrFail();

        abort_unless(Gate::forUser(null)->allows('uploadAsGuest', $this->uploadLink), 404);

        $this->hydrateUploaderIdentity($tenant);
    }

    public function render(): View
    {
        return view('livewire.tenant.guest-upload-form');
    }

    public function submit(PhoneNumberNormalizer $phoneNumberNormalizer, ScoreService $scoreService): void
    {
        $tenant = $this->currentTenant();
        $this->uploadLink->refresh()->load('tenant');

        abort_unless(Gate::forUser(null)->allows('uploadAsGuest', $this->uploadLink), 404);

        $rateLimiterKey = Str::transliterate('upload|'.$tenant->slug.'|'.$this->uploadLink->code.'|'.$this->phoneNumber.'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 5)) {
            $this->addError('uploadedFile', 'Terlalu banyak percobaan upload. Coba lagi dalam '.RateLimiter::availableIn($rateLimiterKey).' detik.');

            return;
        }

        try {
            $validated = $this->validate([
                ...$this->identityRules(),
                'visibility' => [
                    'required',
                    Rule::in([
                        ArchivedFile::VISIBILITY_PUBLIC,
                        ArchivedFile::VISIBILITY_INTERNAL,
                        ArchivedFile::VISIBILITY_PRIVATE,
                    ]),
                ],
                'uploadedFile' => [
                    'required',
                    'file',
                    'max:20480',
                    'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt',
                    'mimetypes:text/plain,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,image/jpeg,image/png',
                ],
            ], [
                'uploadedFile.required' => 'Silakan pilih file yang ingin diunggah.',
                'uploadedFile.file' => 'File yang dipilih tidak valid.',
                'uploadedFile.max' => 'Ukuran file terlalu besar. Maksimal 20 MB per file.',
                'uploadedFile.extensions' => 'Format file tidak didukung. Gunakan PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, JPEG, PNG, atau TXT.',
                'uploadedFile.mimetypes' => 'Tipe file tidak didukung. Gunakan file dokumen atau gambar yang diizinkan.',
            ], [
                ...$this->identityAttributes(),
                'uploadedFile' => 'file',
            ]);
        } catch (ValidationException $exception) {
            RateLimiter::hit($rateLimiterKey, 60);

            throw $exception;
        }

        $fileSize = $this->uploadedFile?->getSize() ?? 0;
        $phoneNumberNormalized = $phoneNumberNormalizer->normalize($validated['phoneNumber']);
        $guestToken = $this->guestToken($tenant);
        $storedPath = null;
        $guestUploaderId = null;

        DB::transaction(function () use ($tenant, $validated, $phoneNumberNormalized, $guestToken, $fileSize, &$storedPath, &$guestUploaderId, $rateLimiterKey): void {
            $lockedTenant = Tenant::query()
                ->whereKey($tenant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUploadLink = UploadLink::query()
                ->whereKey($this->uploadLink->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(Gate::forUser(null)->allows('uploadAsGuest', $lockedUploadLink->load('tenant')), 404);

            if ($lockedTenant->storage_used_bytes + $fileSize > $lockedTenant->storage_quota_bytes) {
                RateLimiter::hit($rateLimiterKey, 60);
                $this->addError('uploadedFile', 'Kuota storage tenant sudah penuh.');

                return;
            }

            $guestUploader = GuestUploader::query()->firstOrNew([
                'tenant_id' => $lockedTenant->id,
                'phone_number_normalized' => $phoneNumberNormalized,
            ]);

            if (! $guestUploader->exists) {
                $guestUploader->first_ip = request()->ip();
            }

            $guestUploader->fill([
                'name' => $validated['name'],
                'phone_number' => $validated['phoneNumber'],
                'guest_token' => $guestToken,
                'last_ip' => request()->ip(),
            ])->save();
            $guestUploaderId = $guestUploader->id;

            $storedPath = $this->uploadedFile->store(
                path: 'tenant-'.$lockedTenant->id.'/guest-uploads',
                options: 'local',
            );

            ArchivedFile::query()->create([
                'tenant_id' => $lockedTenant->id,
                'guest_uploader_id' => $guestUploader->id,
                'upload_link_id' => $lockedUploadLink->id,
                'uploaded_via' => ArchivedFile::UPLOADED_VIA_GUEST_LINK,
                'original_name' => $this->uploadedFile->getClientOriginalName(),
                'stored_name' => $storedPath,
                'extension' => $this->uploadedFile->getClientOriginalExtension(),
                'mime_type' => $this->uploadedFile->getMimeType(),
                'file_size' => $fileSize,
                'visibility' => $validated['visibility'],
                'status' => $validated['visibility'] === ArchivedFile::VISIBILITY_PUBLIC
                    ? ArchivedFile::STATUS_PENDING_REVIEW
                    : ArchivedFile::STATUS_VALID,
                'document_year' => now()->year,
                'uploaded_at' => now(),
            ]);

            $lockedTenant->increment('storage_used_bytes', $fileSize);
            $lockedUploadLink->increment('usage_count');
        });

        if ($this->getErrorBag()->isNotEmpty()) {
            RateLimiter::hit($rateLimiterKey, 60);

            return;
        }

        if ($guestUploaderId !== null) {
            $uploader = GuestUploader::query()->find($guestUploaderId);

            if ($uploader instanceof GuestUploader) {
                $scoreService->recalculateUploaderScore($uploader);
            }
        }

        RateLimiter::clear($rateLimiterKey);

        Cookie::queue($this->guestTokenCookieName($tenant), $guestToken, 60 * 24 * 365);
        $this->queueUploaderIdentityCookie($tenant, $validated['name'], $validated['phoneNumber']);
        $this->hasStoredIdentity = true;
        $this->showIdentityFields = false;

        $this->reset(['uploadedFile', 'uploadedFileName']);
        $this->successMessage = 'File berhasil diunggah.';
        $this->uploadLink->refresh();
    }

    public function updatedUploadedFile(): void
    {
        $this->uploadedFileName = $this->uploadedFile?->getClientOriginalName() ?? '';
        $this->resetErrorBag('uploadedFile');
    }

    public function clearUploadedFile(): void
    {
        $this->reset(['uploadedFile', 'uploadedFileName']);
        $this->resetErrorBag('uploadedFile');
    }

    public function updatedName(): void
    {
        $this->persistUploaderIdentity();
    }

    public function updatedPhoneNumber(): void
    {
        $this->persistUploaderIdentity();
    }

    public function updatedVisibility(): void
    {
        $this->persistUploaderIdentity();
    }

    public function toggleIdentityFields(): void
    {
        $this->showIdentityFields = ! $this->showIdentityFields;
    }

    protected function currentTenant(): Tenant
    {
        $tenant = app(TenantContext::class)->tenant();

        if ($tenant instanceof Tenant) {
            $this->tenantId = $tenant->id;

            return $tenant;
        }

        if ($this->tenantId !== null) {
            $tenant = Tenant::query()
                ->whereKey($this->tenantId)
                ->where('is_active', true)
                ->first();

            if ($tenant instanceof Tenant) {
                app(TenantContext::class)->set($tenant);

                return $tenant;
            }
        }

        abort(404);

        return $tenant;
    }

    protected function guestToken(Tenant $tenant): string
    {
        $cookieToken = request()->cookie($this->guestTokenCookieName($tenant));

        return is_string($cookieToken) && $cookieToken !== ''
            ? $cookieToken
            : Str::random(64);
    }

    protected function guestTokenCookieName(Tenant $tenant): string
    {
        return 'arsipkan_guest_token_'.$tenant->id;
    }

    protected function uploaderIdentityCookieName(Tenant $tenant): string
    {
        return 'arsipkan_guest_identity_'.$tenant->id;
    }

    protected function queueUploaderIdentityCookie(Tenant $tenant, string $name, string $phoneNumber): void
    {
        Cookie::queue(
            $this->uploaderIdentityCookieName($tenant),
            json_encode([
                'name' => $name,
                'phoneNumber' => $phoneNumber,
                'visibility' => $this->visibility,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            60 * 24 * 365,
        );
    }

    protected function hydrateUploaderIdentity(Tenant $tenant): void
    {
        $identity = json_decode((string) request()->cookie($this->uploaderIdentityCookieName($tenant)), true);

        if (
            is_array($identity)
        ) {
            $this->name = is_string($identity['name'] ?? null) ? $identity['name'] : '';
            $this->phoneNumber = is_string($identity['phoneNumber'] ?? null) ? $identity['phoneNumber'] : '';
            $this->visibility = in_array($identity['visibility'] ?? null, [
                ArchivedFile::VISIBILITY_PUBLIC,
                ArchivedFile::VISIBILITY_INTERNAL,
                ArchivedFile::VISIBILITY_PRIVATE,
            ], true)
                ? (string) $identity['visibility']
                : ArchivedFile::VISIBILITY_PRIVATE;
            $this->hasStoredIdentity = $this->name !== '' && $this->phoneNumber !== '';
            $this->showIdentityFields = ! $this->hasStoredIdentity;

            return;
        }

        $guestToken = request()->cookie($this->guestTokenCookieName($tenant));

        if (! is_string($guestToken) || $guestToken === '') {
            return;
        }

        $guestUploader = GuestUploader::query()
            ->where('tenant_id', $tenant->id)
            ->where('guest_token', $guestToken)
            ->first();

        if ($guestUploader instanceof GuestUploader) {
            $this->name = $guestUploader->name;
            $this->phoneNumber = $guestUploader->phone_number;
            $this->hasStoredIdentity = $this->name !== '' && $this->phoneNumber !== '';
            $this->showIdentityFields = ! $this->hasStoredIdentity;
        }
    }

    protected function identityRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phoneNumber' => [
                'required',
                'string',
                'max:30',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $normalizer = app(PhoneNumberNormalizer::class);

                    if (! is_string($value) || ! $normalizer->isValid($value)) {
                        $fail('Nomor HP harus berupa nomor Indonesia yang valid.');
                    }
                },
            ],
        ];
    }

    protected function identityAttributes(): array
    {
        return [
            'phoneNumber' => 'nomor HP',
        ];
    }

    protected function persistUploaderIdentity(): void
    {
        $tenant = $this->currentTenant();

        $this->queueUploaderIdentityCookie($tenant, $this->name, $this->phoneNumber);
    }
}
