<?php

namespace Modules\Video\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Entertainment\Models\Watchlist;
use Modules\Subscriptions\Models\Plan;
use Modules\Entertainment\Models\Entertainment;

class VideoResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $plans = [];
        $plan = $this->plan;
        if($plan){
            $plans = Plan::where('level', '<=', $plan->level)->get();
        }
        $userId = auth()->id();
        if ($userId) {
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $isInWatchList = WatchList::where('entertainment_id', $this->id)
                ->where('user_id', $userId)
                ->where('type', 'video')
                ->where('profile_id', $profile_id)
                ->exists();
        } else {
            $isInWatchList = false;
        }

        $currentUser = auth()->user();
        $currentPlanLevel = optional(optional($currentUser)->subscriptionPackage)->level ?? 0;
        $videoPlanLevel = optional($this->plan)->level ?? 0;
        $isPremium = ($this->access === 'paid') && (($currentUser === null) || ($videoPlanLevel > $currentPlanLevel));
        $showPremiumBadge = ($this->access === 'paid') && (($currentUser === null) || ($videoPlanLevel > $currentPlanLevel));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'trailer_url_type' => $this->trailer_url_type,
            'short_desc'=>$this->short_desc,
            'trailer_url' => $this->trailer_url_type=='Local' ? setBaseUrlWithFileName($this->trailer_url,'video','video') : $this->trailer_url,
            'access' => $this->access,
            'plan_id' => $this->plan_id,
            'description' => $this->description,
            'type'=>'video',
            'is_watch_list' => $isInWatchList,
            'plan_level' => $videoPlanLevel,
            'is_premium' => $isPremium,
            'show_premium_badge' => $showPremiumBadge,
            'is_purchased' => Entertainment::isPurchased($this->id, 'video', $userId),
            'is_pay_per_view' => $this->access === 'pay-per-view',
            'imdb_rating' => $this->IMDb_rating,
            'duration' => $this->duration,
            'poster_image' => setBaseUrlWithFileName($this->poster_url,'image','video'),
        ];
    }
}
