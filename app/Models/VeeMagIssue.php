<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A single VeeMag issue: an ordered collection of editorial sections
 * released under a publication (spec v1.0, sections 2 and 37).
 */
class VeeMagIssue extends Model
{
    use SoftDeletes;

    protected $table = 'veemag_issues';

    protected $fillable = [
        'publication_id', 'volume', 'issue_number', 'title', 'subtitle', 'slug',
        'description', 'release_date', 'cover_url', 'hero_url', 'hero_type',
        'trailer_url', 'runtime_seconds', 'status', 'visibility', 'print_enabled',
    ];

    protected $casts = [
        'release_date'  => 'date',
        'print_enabled' => 'boolean',
    ];

    public function publication()
    {
        return $this->belongsTo(VeeMagPublication::class, 'publication_id');
    }

    /** Sections in editorial order. */
    public function sections()
    {
        return $this->hasMany(VeeMagSection::class, 'issue_id')
            ->where('status', 1)
            ->orderBy('order_index');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /** "Vol. 02 • Issue 07" style label for cards and the hero. */
    public function getIssueLabelAttribute(): string
    {
        $parts = [];
        if ($this->volume) {
            $parts[] = 'Vol. ' . str_pad((string) $this->volume, 2, '0', STR_PAD_LEFT);
        }
        if ($this->issue_number) {
            $parts[] = 'Issue ' . str_pad((string) $this->issue_number, 2, '0', STR_PAD_LEFT);
        }

        return implode(' • ', $parts);
    }
}
