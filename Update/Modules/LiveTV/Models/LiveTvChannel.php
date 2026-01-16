<?php

namespace Modules\LiveTV\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\LiveTV\Models\TvChannelStreamContentMapping;
use Modules\Subscriptions\Models\Plan;

class LiveTvChannel extends BaseModel
{

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'live_tv_channel';
    protected $fillable = [
        'name','slug','category_id','poster_url','thumb_url','access','plan_id','description','status','poster_tv_url'
    ];
    // protected $appends = ['poster_url'];

    public function getBaseUrlAttribute($value)
    {
        return !empty($value) ? setBaseUrlWithFileNameV2() : NULL;
    }

    public function TvChannelStreamContentMappings()
    {
        return $this->hasOne(TvChannelStreamContentMapping::class,'tv_channel_id','id');
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($liveTvChannel) {
            if (empty($liveTvChannel->slug) && !empty($liveTvChannel->name)) {
                $liveTvChannel->slug = \Illuminate\Support\Str::slug(trim($liveTvChannel->name));
            }
        });

        static::updating(function ($liveTvChannel) {
            if ($liveTvChannel->isDirty('name') && !empty($liveTvChannel->name)) {
                $liveTvChannel->slug = \Illuminate\Support\Str::slug(trim($liveTvChannel->name));
            }
        });

        static::deleting(function () {

        });

        static::restoring(function () {

        });
    }

    public function TvCategory()
    {
        return $this->hasOne(LiveTvCategory::class,'id','category_id');
    }

    public function plan()
    {
        return $this->hasOne(Plan::class, 'id', 'plan_id');
    }

    public static function get_top_channel($channelIdsArray)
    {
        $items = LiveTvChannel::select([
            'id','name','slug','plan_id','description','status','access','category_id','poster_url','poster_tv_url',
        ])
        ->with([
            'plan:id,level',
            'TvCategory:id,name',
            'TvChannelStreamContentMappings:id,tv_channel_id,stream_type,embedded,server_url,server_url1'
        ])
        ->whereIn('id', $channelIdsArray)
        ->where('status', 1)
        ->where('deleted_at', null)
        ->get();

        return $items->map(function ($item) {
            $item->plan_level = optional($item->plan)->level;
            $item->category = optional($item->TvCategory)->name;
            $item->stream_type = optional($item->TvChannelStreamContentMappings)->stream_type;
            $item->embedded = optional($item->TvChannelStreamContentMappings)->embedded;
            $item->server_url = optional($item->TvChannelStreamContentMappings)->server_url;
            $item->server_url1 = optional($item->TvChannelStreamContentMappings)->server_url1;
            $item->base_url = $item->poster_url; // preserve alias
            return $item;
        });
    }

    public static function get_channel()
    {
        $items = LiveTvChannel::select([
            'id','category_id','name','plan_id','slug','description','status','access','poster_url','poster_tv_url',
        ])
        ->with([
            'plan:id,level',
            'TvCategory:id,name',
            'TvChannelStreamContentMappings:id,tv_channel_id,stream_type,embedded,server_url,server_url1'
        ])
        ->where('status',1)
        ->where('deleted_at',null)
        ->orderBy('updated_at', 'desc')
        ->take(6)
        ->get();

        return $items->map(function ($item) {
            $item->plan_level = optional($item->plan)->level;
            $item->category = optional($item->TvCategory)->name;
            $item->stream_type = optional($item->TvChannelStreamContentMappings)->stream_type;
            $item->embedded = optional($item->TvChannelStreamContentMappings)->embedded;
            $item->server_url = optional($item->TvChannelStreamContentMappings)->server_url;
            $item->server_url1 = optional($item->TvChannelStreamContentMappings)->server_url1;
            $item->base_url = $item->poster_url;
            return $item;
        });
    }

    public static function get_tvChannels_catgory_wise($category)
    {
        $items = LiveTvChannel::select([
            'id','name','plan_id','description','status','access','category_id','poster_url','poster_tv_url'
        ])
        ->with([
            'plan:id,level',
            'TvCategory:id,name',
            'TvChannelStreamContentMappings:id,tv_channel_id,stream_type,embedded,server_url,server_url1'
        ])
        ->where('category_id',$category)
        ->where('status',1)
        ->orderBy('updated_at', 'desc')
        ->get();

        return $items->map(function ($item) {
            $item->plan_level = optional($item->plan)->level;
            $item->category = optional($item->TvCategory)->name;
            $item->stream_type = optional($item->TvChannelStreamContentMappings)->stream_type;
            $item->embedded = optional($item->TvChannelStreamContentMappings)->embedded;
            $item->server_url = optional($item->TvChannelStreamContentMappings)->server_url;
            $item->server_url1 = optional($item->TvChannelStreamContentMappings)->server_url1;
            $item->base_url = $item->poster_url;
            return $item;
        });
    }
}
