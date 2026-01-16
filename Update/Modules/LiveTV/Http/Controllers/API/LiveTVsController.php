<?php

namespace Modules\LiveTV\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Modules\LiveTV\Models\LiveTvCategory;
use Modules\LiveTV\Transformers\LiveTvCategoryResource;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\LiveTV\Transformers\LiveTvCategoryResourceV2;
use Modules\LiveTV\Transformers\LiveTvCategoryResourceV3;
use Modules\LiveTV\Transformers\LiveTvChannelResource;
use Modules\LiveTV\Transformers\LiveTvChannelResourceV3;
use Modules\LiveTV\Transformers\LiveTvChannelDetailsResource;
use Modules\LiveTV\Transformers\LiveTvChannelDetailsResourceV3;
use Modules\Subscriptions\Models\Subscription;

class LiveTVsController extends Controller
{
    public function liveTvCategoryList(Request $request){

        $perPage = $request->input('per_page', 10);
        $category_list = LiveTvCategory::query();

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $category_list->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%");
            });
        }

        $category_list =$category_list->where('status',1);

        $category = $category_list->orderBy('updated_at', 'desc');
        $category = $category->paginate($perPage);

        $responseData = LiveTvCategoryResource::collection($category);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('livetv.livetv_category_list'),
        ], 200);
    }

    public function liveTvDashboard(Request $request){

        $channelData = LiveTvChannel::with('TvCategory','plan','TvChannelStreamContentMappings')->where('status',1)->orderBy('updated_at', 'desc')->take(6)->get();
        $categoryData = LiveTvCategory::with('tvChannels')->where('status',1)->orderBy('updated_at', 'desc')->get();

        $responseData['slider'] = LiveTvChannelResource::collection($channelData);
        $responseData['category_data'] = LiveTvCategoryResource::collection($categoryData);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('livetv.livetv_dashboard'),
        ], 200);
    }

    public function liveTvDetails(Request $request){

        $channelData = LiveTvChannel::where('id', $request->channel_id)->with('TvCategory','plan','TvChannelStreamContentMappings')->first();

        $responseData = new LiveTvChannelDetailsResource($channelData);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('livetv.livetv_details'),
        ], 200);
    }


    public function liveTvDetailsV3(Request $request){

        $device_type = getDeviceType($request);

        $channelId = $request->channel_id;
        $userId = $request->user_id ?? auth()->id();

        $cacheKey = 'livetv_details_v3_'. md5(json_encode([
            'channel_id' => $channelId,
            'user_id' => $userId,
            'device_type' => $device_type
        ]));

        $cachedResult = cacheApiResponse($cacheKey, 300, function () use ($request, $channelId, $userId, $device_type) {
           $channelData = LiveTvChannel::where('id', $channelId)->with('TvCategory','plan','TvChannelStreamContentMappings')->first();
           $channelData['video_qualities'] = $channelData ? [[
                    'url_type' => $channelData['TvChannelStreamContentMappings']['stream_type'],
                    'url'      => $channelData['TvChannelStreamContentMappings']['stream_type'] == 'Embedded' ? $channelData['TvChannelStreamContentMappings']['embedded'] : $channelData['TvChannelStreamContentMappings']['server_url'],
                ]] : [];
            if ($userId) {
                $getDeviceTypeData = Subscription::checkPlanSupportDevice($userId, $device_type);
                $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
                $userLevel = Subscription::select('plan_id')->where(['user_id' => $userId, 'status' => 'active'])->latest()->first();
                $userPlanId = $userLevel->plan_id ?? 0;
                $channelData = setContentAccess($channelData, $userId, $userPlanId);

            } else {
                $deviceTypeResponse = ['isDeviceSupported' => false];
                $userPlanId = 0;
                $channelData = setContentAccess($channelData, null, $userPlanId);
            }

            $channelData['isDeviceSupported'] = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;

            $channelData['poster_image'] =  $device_type == 'tv' ? $channelData->poster_tv_url : $channelData->poster_url ?? null;
            // Get more items and apply setContentAccess to each
            $moreItems = LiveTvChannel::where('category_id', $channelData->category_id)->where('deleted_at', null)->where('status',1)->get()->except($channelData->id);

            // Apply setContentAccess to each item in moreItems
            $moreItems = $moreItems->map(function ($item) use ($userId, $userPlanId, $deviceTypeResponse, $device_type) {
                $itemData = [
                    'id' => $item->id,
                    'access' => $item->access,
                    'plan_id' => $item->plan_id,
                ];
                $itemData = setContentAccess($itemData, $userId, $userPlanId);

                // Add the processed data to the item
                $item->has_content_access = $itemData['has_content_access'];
                $item->required_plan_level = $itemData['required_plan_level'];
                $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url, 'image', 'livetv') : setBaseUrlWithFileName($item->poster_url, 'image', 'livetv') ?? null;

                return $item;
            });

            $channelData['moreItems'] = $moreItems;
            $responseData = new LiveTvChannelDetailsResourceV3($channelData);
            return $responseData;
        });

        return response()->json([
            'status' => true,
            'data' => $cachedResult['data'],
            'message' => __('livetv.livetv_details'),
        ], 200);
    }

    public function channelList(Request $request){
        $userId = !empty($request->user_id) ? $request->user_id : auth()->id();
        $profile_id = getCurrentProfile($userId, $request);
        $userPlanLevel = (int) (auth()->user()?->subscriptionPackage?->level ?? 0);

        $channelData = LiveTvChannel::with('TvCategory','plan','TvChannelStreamContentMappings')->where('status',1)->orderBy('updated_at', 'desc');
        if(!empty($request->category_id)){
            $channelData = $channelData->where('category_id',$request->category_id);
        }
        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $perPage = $request->input('per_page', 12);
            $channel =$channelData->paginate($perPage);
            $html = '';
            $channel->each(function($channelItem) use ($userPlanLevel, $userId) {
                $isPaid       = $channelItem->access == 'paid';
                $planLevel = $channelItem->plan->level ?? 0;
                $showPremiumBadge = $isPaid && ($userPlanLevel < $planLevel);
                $channelItem['show_premium_badge'] =  $showPremiumBadge;
            });
            $channelList = LiveTvChannelResource::collection($channel);

            foreach ($channelList->toArray($request) as $index => $value) {
                $html .= view('frontend::components.card.card_tvchannel', [
                    'value' => $value,
                ])->render();
            }
            $hasMore =  $channel->hasMorePages();

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.search_list'),
                'hasMore' => $hasMore,
            ], 200);
        }else{
            $channelData=  $channelData->get();
            $responseData['channel'] = LiveTvChannelResource::collection($channelData);
            return response()->json([
                'status' => true,
                 'data' => $responseData,
                'message' => __('livetv.channel_list'),
            ], 200);
        }

    }

    public function channelListV3(Request $request){
        $userId = !empty($request->user_id) ? $request->user_id : null;
        $profile_id = getCurrentProfile($userId, $request);
        $device_type = getDeviceType($request);
        $perPage = $request->input('per_page', 10);
        $cacheKey = 'channel_list_v3_'. md5(json_encode([
            'user_id' => $userId,
            'device_type' => $device_type,
            'profile_id' => $profile_id,
            'category_id' => $request->category_id ?? null,
            'is_ajax' => $request->is_ajax ?? 0,
            'page' => $request->page ?? 1,
            'per_page' => $perPage
        ]));

        $cachedResult = cacheApiResponse($cacheKey, 300, function () use ($request, $userId, $device_type, $perPage) {
            $getDeviceTypeData = Subscription::checkPlanSupportDevice($userId, $device_type);
            $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
            $userLevel = Subscription::select('plan_id')->where(['user_id' => $userId, 'status' => 'active'])->latest()->first();
            $userPlanId = $userLevel->plan_id ?? 0;
            $userPlanLevel = $userLevel->plan_level ?? 0;

            $channelData = LiveTvChannel::with('TvCategory','plan','TvChannelStreamContentMappings')->where('status',1)->where('deleted_at',null)->orderBy('id', 'desc');
            if(!empty($request->category_id)){
                $channelData = $channelData->where('category_id',$request->category_id);
            }
            if ($request->has('is_ajax') && $request->is_ajax == 1) {
                $channel =$channelData->paginate($perPage);

                // Process channel data to add device support and plan level info
                $channel->getCollection()->transform(function($channelItem) use ($device_type, $deviceTypeResponse, $userPlanId, $userId, $userPlanLevel) {
                    $channelItem->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $channelItem->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($channelItem->poster_tv_url, 'image', 'livetv') : setBaseUrlWithFileName($channelItem->poster_url, 'image', 'livetv');
                    $channelItem = setContentAccess($channelItem, $userId, $userPlanId);
                    $channelItem->show_premium_badge = !$channelItem->access == 'free' && $channelItem->access == 'paid' && $channelItem->plan_level > $userPlanLevel;
                    return $channelItem;
                });

                $html = '';
                $channelList = LiveTvChannelResourceV3::collection($channel);

                foreach ($channelList->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_tvchannel', [
                        'value' => $value,
                    ])->render();
                }
                $hasMore =  $channel->hasMorePages();

                return [
                    'html' => $html,
                    'hasMore' => $hasMore,
                    'message' => __('movie.search_list')
                ];
            }else{
                $channelData=  $channelData->paginate($perPage);
                // Process channel data to add device support and plan level info
                $channelData->transform(function($channelItem) use ($device_type, $deviceTypeResponse, $userPlanId, $userId) {
                    $channelItem->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $channelItem = setContentAccess($channelItem, $userId, $userPlanId);
                    $channelItem->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($channelItem->poster_tv_url, 'image', 'livetv') : setBaseUrlWithFileName($channelItem->poster_url, 'image', 'livetv');
                    return $channelItem;
                });
                $responseData['channel'] = LiveTvChannelResourceV3::collection($channelData);
                return [
                    'data' => $responseData,
                    'message' => __('livetv.channel_list')
                ];
            }
        });

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            return response()->json([
                'status' => true,
                'html' => $cachedResult['data']['html'],
                'message' => $cachedResult['data']['message'],
                'hasMore' => $cachedResult['data']['hasMore'],
            ], 200);
        } else {
            return response()->json([
                'status' => true,
                'data' => $cachedResult['data']['data'],
                'message' => $cachedResult['data']['message'],
            ], 200);
        }
    }

    public function liveTvDashboardV2(Request $request){

        $channelData =  LiveTvChannel::get_channel();

        $categoryData = LiveTvCategory::where('status',1)->orderBy('updated_at', 'desc')->get();



        $responseData['slider'] = LiveTvChannelResource::collection($channelData);


        $responseData['category_data'] = LiveTvCategoryResourceV2::collection($categoryData);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('livetv.livetv_dashboard'),
        ], 200);
    }

    public function liveTvDashboardV3(Request $request){
        $user_id = !empty($request->user_id) ? $request->user_id : null;

        $device_type = getDeviceType($request);

        $cacheKey = 'livetv_dashboard_v3_'.md5(json_encode([
            'user_id' => $user_id,
            'device_type' => $device_type
        ]));

        $cachedResult = cacheApiResponse($cacheKey, 300, function () use ($request, $user_id, $device_type) {
            // Get 6 channels for slider
            $sliderChannelData = LiveTvChannel::get_channel();

            // Get ALL channels for category grouping (not limited to 6)
            $allChannelData = LiveTvChannel::select([
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
            ->get()
            ->map(function ($item) {
                $item->plan_level = optional($item->plan)->level;
                $item->category = optional($item->TvCategory)->name;
                $item->stream_type = optional($item->TvChannelStreamContentMappings)->stream_type;
                $item->embedded = optional($item->TvChannelStreamContentMappings)->embedded;
                $item->server_url = optional($item->TvChannelStreamContentMappings)->server_url;
                $item->server_url1 = optional($item->TvChannelStreamContentMappings)->server_url1;
                $item->base_url = $item->poster_url;
                return $item;
            });

            $categoryData = LiveTvCategory::where('status',1)->where('deleted_at',null)->orderBy('updated_at', 'desc')->get();

            if ($user_id) {
                $getDeviceTypeData = Subscription::checkPlanSupportDevice($user_id, $device_type);
                $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
                $userLevel = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();
                $userPlanId = $userLevel->plan_id ?? 0;
            } else {
                $deviceTypeResponse = ['isDeviceSupported' => true];
                $userPlanId = 0;
            }

            // Process slider channels (6 channels)
            $sliderChannelData->each(function ($channel) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                $channel->user_id = $user_id;
                $channel->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $channel = setContentAccess($channel, $user_id, $userPlanId);
                $channel->poster_image =  $device_type == 'tv' ? setBaseUrlWithFileName($channel->poster_tv_url, 'image', 'livetv') : setBaseUrlWithFileName($channel->poster_url , 'image', 'livetv');
            });

            // Process ALL channels for category grouping
            $allChannelData->each(function ($channel) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                $channel->user_id = $user_id;
                $channel->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $channel = setContentAccess($channel, $user_id, $userPlanId);
                $channel->poster_image =  $device_type == 'tv' ? setBaseUrlWithFileName($channel->poster_tv_url, 'image', 'livetv') : setBaseUrlWithFileName($channel->poster_url , 'image', 'livetv');
            });

            $responseData['slider'] = LiveTvChannelResourceV3::collection($sliderChannelData);

            // Group ALL channels by category for easy access
            $channelsByCategory = $allChannelData->groupBy('category_id');

            $categoryData->each(function ($category) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $channelsByCategory) {
                    $category->user_id = $user_id;
                    $category->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $category = setContentAccess($category, $user_id, $userPlanId);
                    $category->posterImage =  setBaseUrlWithFileName($category->file_url ?? null) ;
                    // Get all channels for this category from ALL channels (not just 6)
                    $categoryChannels = $channelsByCategory->get($category->id, collect());
                    // Ensure it's a collection and reset keys
                    $category->processed_channels = $categoryChannels->values();
                });

            $responseData['category_data'] = LiveTvCategoryResourceV3::collection($categoryData);

            return $responseData;
        });

        return response()->json([
            'status' => true,
            'data' => $cachedResult['data'],
            'message' => __('livetv.livetv_dashboard'),
        ], 200);
    }
}
