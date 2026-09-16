<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A VeeMag publication — the brand that issues are released under
 * (spec v1.0, section 37).
 */
class VeeMagPublication extends Model
{
    use SoftDeletes;

    protected $table = 'veemag_publications';

    protected $fillable = [
        'owner_id', 'title', 'slug', 'logo', 'description',
        'category', 'brand_settings', 'status',
    ];

    protected $casts = [
        'brand_settings' => 'array',
    ];

    public function issues()
    {
        return $this->hasMany(VeeMagIssue::class, 'publication_id');
    }
}
