<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    public const UPLOADED_VIA_GUEST_LINK = 'guest_link';

    public const UPLOADED_VIA_USER_PORTAL = 'user_portal';

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_INTERNAL = 'internal';

    public const VISIBILITY_PRIVATE = 'private';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_VALID = 'valid';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'tenant_id',
        'guest_uploader_id',
        'upload_link_id',
        'uploaded_via',
        'original_name',
        'stored_name',
        'extension',
        'mime_type',
        'file_size',
        'visibility',
        'status',
        'title',
        'description',
        'category_id',
        'detected_file_type',
        'final_file_type',
        'document_year',
        'uploaded_at',
        'reviewed_at',
        'reviewed_by_admin_id',
        'deleted_by_user_account_id',
        'permanently_deleted_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'document_year' => 'integer',
            'uploaded_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function guestUploader(): BelongsTo
    {
        return $this->belongsTo(GuestUploader::class);
    }

    public function uploadLink(): BelongsTo
    {
        return $this->belongsTo(UploadLink::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by_admin_id');
    }

    public function deletedByUserAccount(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'deleted_by_user_account_id');
    }

    public function permanentlyDeletedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'permanently_deleted_by_admin_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'file_tag_map');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(FileDownload::class);
    }
}
