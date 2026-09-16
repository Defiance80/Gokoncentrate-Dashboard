<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One editorial section inside a VeeMag issue — the streaming equivalent of a
 * magazine page/department (spec v1.0, sections 8 and 10). Video is stored via
 * the platform's shared upload_type/url mechanism.
 */
class VeeMagSection extends Model
{
    use SoftDeletes;

    protected $table = 'veemag_sections';

    protected $fillable = [
        'issue_id', 'type', 'custom_label', 'title', 'subtitle', 'description',
        'order_index', 'runtime_seconds', 'video_upload_type', 'video_url_input',
        'thumbnail_url', 'transition_style', 'status',
    ];

    /** The section types defined in the spec (section 10). */
    public const TYPES = [
        'opening'          => 'Opening',
        'interview'        => 'Interview',
        'video_article'    => 'Video Article',
        'profile'          => 'Profile',
        'day_in_the_life'  => 'Day in the Life',
        'short_documentary'=> 'Short Documentary',
        'conversation'     => 'Conversation',
        'performance'      => 'Performance',
        'short'            => 'Short',
        'editors_note'     => "Editor's Note",
        'last_word'        => 'Last Word',
        'advertisement'    => 'Advertisement',
        'custom'           => 'Custom',
    ];

    public function issue()
    {
        return $this->belongsTo(VeeMagIssue::class, 'issue_id');
    }

    /** Display label for the section's type (respects a custom label). */
    public function getTypeLabelAttribute(): string
    {
        if ($this->type === 'custom' && $this->custom_label) {
            return $this->custom_label;
        }

        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }
}
