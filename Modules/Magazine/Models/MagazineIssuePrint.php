<?php

namespace Modules\Magazine\Models;

use Illuminate\Database\Eloquent\Model;

class MagazineIssuePrint extends Model
{
    protected $table = 'magazine_issue_print';

    protected $guarded = ['id'];

    protected $casts = [
        'print_enabled' => 'boolean',
    ];

    public function issue()
    {
        return $this->belongsTo(MagazineIssue::class, 'issue_id');
    }
}
