<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A short clip attached to a piece of content (video, movie or TV show).
 *
 * Reconstructed to match the existing code that references it: the columns
 * mirror exactly what Clip::create() writes in the content controllers and
 * what ClipResource reads back. Related to content via content_id + content_type.
 */
class Clip extends Model
{
    use HasFactory;

    protected $table = 'clips';

    protected $fillable = [
        'content_id',
        'content_type',
        'type',
        'url',
        'poster_url',
        'tv_poster_url',
        'title',
    ];
}
