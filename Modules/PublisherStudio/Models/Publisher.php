<?php

namespace Modules\PublisherStudio\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Publisher extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    protected $table = 'publishers';

    protected $fillable = [
        'name', 'company', 'email', 'phone', 'password', 'status',
        'bio', 'avatar_url', 'approved_at', 'approved_by',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'password'    => 'hashed',
        'approved_at' => 'datetime',
    ];

    public function submissions()
    {
        return $this->hasMany(PublisherSubmission::class, 'publisher_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
