<?php

namespace App\Livewire\Tenant;

use App\Models\File as ArchivedFile;
use App\Models\GuestUploader;
use App\Models\Tenant;
use App\Models\UploadLink;
use App\Services\PhoneNumberNormalizer;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public ?string $successMessage = null;

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
    }

    public function render(): View
    {
        return view('livewire.tenant.guest-upload-form');
    }

    public function submit(PhoneNumberNormalizer $phoneNumberNormalizer): void
    {
        $tenant = $this->currentTenant();
        $this->uploadLink->refresh()->load('tenant');

        abort_unless(Gate::forUser(null)->allows('uploadAsGuest', $this->uploadLink), 404);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phoneNumber' => ['required', 'string', 'max:30'],
            'visibility' => [
                'required',
                Rule::in([
                    ArchivedFile::VISIBILITY_PUBLIC,
                    ArchivedFile::VISIBILITY_INTERNAL,
                    ArchivedFile::VISIBILITY_PRIVATE,
                ]),
            ],
            'uploadedFile' => ['required', 'file', 'max:102400'],
        ], [], [
            'phoneNumber' => 'nomor HP',
            'uploadedFile' => 'file',
        ]);

        $fileSize = $this->uploadedFile?->getSize() ?? 0;
        $phoneNumberNormalized = $phoneNumberNormalizer->normalize($validated['phoneNumber']);
        $guestToken = $this->guestToken($tenant);
        $storedPath = null;

        DB::transaction(function () use ($tenant, $validated, $phoneNumberNormalized, $guestToken, $fileSize, &$storedPath): void {
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
                'uploaded_at' => now(),
            ]);

            $lockedTenant->increment('storage_used_bytes', $fileSize);
            $lockedUploadLink->increment('usage_count');
        });

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        Cookie::queue($this->guestTokenCookieName($tenant), $guestToken, 60 * 24 * 365);

        $this->reset(['name', 'phoneNumber', 'uploadedFile']);
        $this->visibility = ArchivedFile::VISIBILITY_PRIVATE;
        $this->successMessage = 'File berhasil diunggah.';
        $this->uploadLink->refresh();
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
}
