<?php

namespace Modules\Banner\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Entertainment\Models\Entertainment;

class Banner extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'banners';
    protected $fillable = ['title', 'file_url','poster_url','type', 'type_id','type_name','description', 'status', 'created_by','banner_for','poster_tv_url'];
    const CUSTOM_FIELD_MODEL = 'Modules\Banner\Models\Banner';

    public static function get_sliderList($type=null)
    {
        $query = Banner::select([
            'id', 'banner_for', 'title', 'poster_url', 'file_url', 'type', 'type_id'
        ])
        ->with([
            'entertainment' => function($q) {
                $q->select([
                    'id', 'name', 'type', 'plan_id', 'description', 'trailer_url_type',
                    'is_restricted', 'language', 'imdb_rating', 'content_rating',
                    'duration', 'video_upload_type', 'release_date', 'trailer_url',
                    'video_url_input', 'poster_url', 'movie_access', 'download_status',
                    'enable_quality', 'download_url', 'status'
                ])
                ->with([
                    'plan:id,level',
                    'genresdata:id,name'
                ]);

                if (request()->has('is_restricted')) {
                    $q->where('is_restricted', request()->is_restricted);
                }

                if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
                    $q->where('is_restricted', 0);
                }
            },
            'liveTvChannel' => function($q) {
                $q->select([
                    'id', 'name', 'plan_id', 'description', 'status', 'access', 'category_id'
                ])
                ->with([
                    'plan:id,level',
                    'category:id,name',
                    'streamContentMappings:id,tv_channel_id,stream_type,embedded,server_url,server_url1'
                ]);
            }
        ])
        ->where('status', 1);

        if (!empty($type)) {
            $query->where('banner_for', $type);
        }

        return $query->get();
    }

    public function entertainment()
    {
        return $this->belongsTo(Entertainment::class, 'type_id')->where('type', 'entertainment');
    }

    public function liveTvChannel()
    {
        return $this->belongsTo(\Modules\LiveTv\Models\LiveTvChannel::class, 'type_id')->where('type', 'live_tv');
    }
}
