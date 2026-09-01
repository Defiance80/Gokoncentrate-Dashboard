<?php

namespace Modules\Magazine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MagazineIssue extends Model
{
    use SoftDeletes;

    protected $table = 'magazine_issues';

    protected $guarded = ['id'];

    protected $casts = [
        'release_date' => 'date',
    ];

    public function magazine()
    {
        return $this->belongsTo(Magazine::class, 'magazine_id');
    }

    public function assets()
    {
        return $this->hasMany(MagazineIssueAsset::class, 'issue_id')->orderBy('sort_order');
    }

    public function printConfig()
    {
        return $this->hasOne(MagazineIssuePrint::class, 'issue_id');
    }

    public function printEvents()
    {
        return $this->hasMany(MagazinePrintEvent::class, 'issue_id');
    }
}
