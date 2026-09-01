<?php

namespace Modules\Magazine\Models;

use Illuminate\Database\Eloquent\Model;

class MagazineIssueAsset extends Model
{
    protected $table = 'magazine_issue_assets';

    protected $guarded = ['id'];

    public $timestamps = true;

    public function issue()
    {
        return $this->belongsTo(MagazineIssue::class, 'issue_id');
    }
}
