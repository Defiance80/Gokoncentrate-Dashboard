<?php

namespace Modules\Magazine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Magazine extends Model
{
    use SoftDeletes;

    protected $table = 'magazines';

    protected $guarded = ['id'];

    protected $casts = [
        'release_date' => 'date',
    ];

    public function issues()
    {
        return $this->hasMany(MagazineIssue::class, 'magazine_id');
    }

    public function publishedIssues()
    {
        return $this->hasMany(MagazineIssue::class, 'magazine_id')->where('status', 'published');
    }
}
