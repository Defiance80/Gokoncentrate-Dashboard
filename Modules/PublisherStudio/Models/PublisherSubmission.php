<?php

namespace Modules\PublisherStudio\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublisherSubmission extends Model
{
    use SoftDeletes;

    protected $table = 'publisher_submissions';

    protected $fillable = [
        'publisher_id', 'type', 'title', 'slug', 'synopsis', 'cover_image_url',
        'category', 'payload', 'status', 'review_notes', 'reviewed_by',
        'reviewed_at', 'submitted_at', 'published_ref_type', 'published_ref_id',
    ];

    protected $casts = [
        'payload'      => 'array',
        'reviewed_at'  => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public const TYPES = [
        'veemag'     => 'VeeMag',
        'podcast'    => 'Podcast',
        'short_film' => 'Short Film',
    ];

    public const STATUSES = [
        'draft'             => 'Draft',
        'submitted'         => 'Submitted',
        'changes_requested' => 'Changes Requested',
        'approved'          => 'Approved',
        'rejected'          => 'Rejected',
    ];

    public function publisher()
    {
        return $this->belongsTo(Publisher::class, 'publisher_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return [
            'draft'             => 'secondary',
            'submitted'         => 'info',
            'changes_requested' => 'warning',
            'approved'          => 'success',
            'rejected'          => 'danger',
        ][$this->status] ?? 'secondary';
    }

    public function isEditableByPublisher(): bool
    {
        return in_array($this->status, ['draft', 'changes_requested'], true);
    }

    public function scopeQueue($query)
    {
        return $query->whereIn('status', ['submitted', 'changes_requested']);
    }
}
