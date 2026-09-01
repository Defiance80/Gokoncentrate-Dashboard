<?php

namespace Modules\Magazine\Models;

use Illuminate\Database\Eloquent\Model;

class MagazinePrintEvent extends Model
{
    protected $table = 'magazine_print_events';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'issue_id',
        'event',
        'session_id',
        'device',
        'referrer',
    ];

    public function issue()
    {
        return $this->belongsTo(MagazineIssue::class, 'issue_id');
    }
}
