<?php

namespace Modules\Entertainment\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\Entertainment;

class CommonContentResourceV3 extends JsonResource
{
    public function toArray($request)
    {

        $genre_data = [];
        if (!empty($this->entertainmentGenerMappings)) {
            foreach ($this->entertainmentGenerMappings as $genre) {
                $genre_data[] = [
                    'id' => $genre->id,
                    'name' => $genre->genre->name ?? null,
                ];
            }
        }

        $userId = $request->input('user_id') ?? auth()->id();
        $user = auth()->user();
        $isInWatchList = false;

        if ($userId) {
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $contentType = $this->type ?? 'movie';
            $isInWatchList = Watchlist::where('entertainment_id', $this->id)
                ->where('user_id', $userId)
                ->where('type', $contentType)
                ->where('profile_id', $profile_id)
                ->exists();
        } elseif (isset($this->is_watch_list)) {
            $isInWatchList = $this->is_watch_list;
        }

        // Premium badge logic
        $userPlanLevel = (int) ($user?->subscriptionPackage?->level ?? 0);
        $movieAccess   = (string) ($this->movie_access ?? '');
        $videoPlanLevel = (int) ($this->plan_level ?? $this->plan?->level ?? 0);

        $isPayPerView = $movieAccess === 'pay-per-view';
        $isPaid       = $movieAccess === 'paid';
        $showPremiumBadge = !$isPayPerView && $isPaid && $videoPlanLevel > $userPlanLevel;

        $isPurchased = $isPayPerView
            ? Entertainment::isPurchased($this->id, $this->type)
            : false;

        if ($this->trailer_url_type == 'Local' && !empty($this->bunny_video_url && env('ACTIVE_STORAGE') == 'bunny')) {
            $this->trailer_url_type = 'HLS';
            $this->trailer_url = $this->bunny_video_url;
        } else {
            $this->trailer_url = $this->trailer_url_type == 'Local'
                ? setBaseUrlWithFileName($this->trailer_url,'video',$this->type)
                : $this->trailer_url;
        }

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'type'            => $this->type,
            'trailer_url_type'=> $this->trailer_url_type,
            'trailer_url'     => $this->trailer_url,
            'movie_access'    => $this->movie_access,
            'imdb_rating'     => $this->IMDb_rating ?? $this->imdb_rating,
            'plan_id'         => $this->plan_id,
            'plan_level'      => $this->plan_level ?? optional($this->plan)->level,
            'language'        => $this->language,
            'duration'        => $this->duration,
            'release_date'    => $this->release_date,
            'poster_image'    => setBaseUrlWithFileName($this->poster_url ?? null, 'image', $this->type),
            'is_watch_list'   => $isInWatchList,
            'genres'          => $genre_data,
            'show_premium_badge' => $showPremiumBadge,
            'is_purchased'    => $isPurchased,
            'is_pay_per_view' => $isPayPerView,
            'is_paid'         => $isPaid,
        ];
    }
}
