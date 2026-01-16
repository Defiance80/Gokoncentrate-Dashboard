<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\MobileSetting;
use Modules\Entertainment\Models\Entertainment;
use Modules\Banner\Models\Banner;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Banner\Transformers\SliderResource;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\LiveTV\Transformers\LiveTvChannelResource;
use Modules\LiveTV\Transformers\LiveTvChannelResourceV3;
use Modules\CastCrew\Models\CastCrew;
use Modules\CastCrew\Transformers\CastCrewListResource;
use Modules\Genres\Transformers\GenresResource;
use Modules\Genres\Models\Genres;
use Modules\Video\Models\Video;
use App\Services\RecommendationService;
use App\Services\RecommendationServiceV2;
use Modules\Entertainment\Transformers\MoviesResource;
use Modules\Entertainment\Transformers\MoviesResourceV3;
use Modules\Entertainment\Transformers\CommanResource;
use Modules\Entertainment\Transformers\CommanResourceV3;
use Modules\Entertainment\Transformers\TvshowResource;
use Modules\Entertainment\Transformers\TvshowResourceV3;
use Modules\Constant\Models\Constant;
use Modules\Video\Transformers\VideoResource;
use Modules\Video\Transformers\VideoResourceV3;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Banner\Transformers\SliderResourceV3;
use Modules\Entertainment\Transformers\ContinueWatchResourceV2;
use Modules\Entertainment\Transformers\ContinueWatchResourceV3;
use Modules\Entertainment\Transformers\SeasonResource;
use Modules\Entertainment\Transformers\EpisodeResource;
use Modules\Entertainment\Transformers\SeasonResourceV3;
use Modules\Entertainment\Transformers\EpisodeResourceV3;
use Modules\Episode\Models\Episode;
use Modules\Season\Models\Season;
use Modules\Ad\Models\CustomAdsSetting;
use Modules\Subscriptions\Models\Subscription;
use Modules\Entertainment\Transformers\Backend\CommonContentResourceV3;



class DashboardController extends Controller
{
    protected $recommendationService,$recommendationServiceV2;
    public function __construct(RecommendationService $recommendationService, RecommendationServiceV2 $recommendationServiceV2)
    {
        $this->recommendationService = $recommendationService;
        $this->recommendationServiceV2 = $recommendationServiceV2;

    }

      public function DashboardDetailDataV2(Request $request)
    {

        $user_id = !empty($request->user_id) ? $request->user_id : null;

        if (!Cache::has('genres')) {
            $genresData = Genres::get(['id','name'])->keyBy('id')->toArray();
            Cache::put('genres', $genresData);
        }


            if($request->has('user_id'))
            {
            //    $continueWatchList = ContinueWatch::where('user_id', $user_id)
            //    ->where('profile_id',$request->profile_id)->get();
            //    $continueWatch = ContinueWatchResource::collection($continueWatchList);

               $user = User::where('id',$request->user_id)->first();
               $profile_id=$request->profile_id;

               if( $user_id !=null)
               {
                   $user = User::where('id',$user_id)->first();

                    $likedMovies = $this->recommendationServiceV2->getLikedMovies($user, $profile_id);
                    $likedMovies->each(function ($movie) use ($user_id) {
                        $movie->user_id = $user_id; // Add the user_id to each movie
                    });
                    $likedMovies = CommanResource::collection($likedMovies);
                    $viewedMovies = $this->recommendationService->getEntertainmentViews($user, $profile_id);
                    $viewedMovies->each(function ($movie) use ($user_id) {
                        $movie->user_id = $user_id; // Add the user_id to each movie
                    });
                    $viewedMovies = CommanResource::collection($viewedMovies);

                    $FavoriteGener = $this->recommendationService->getFavoriteGener($user, $profile_id);
                    $FavoriteGener = GenresResource::collection($FavoriteGener);


                    $favorite_personality = $this->recommendationService->getFavoritePersonality($user, $profile_id);
                     $favorite_personality = CastCrewListResource::collection($favorite_personality);

                    $trendingMovies = $this->recommendationService->getTrendingMoviesByCountry($user, $request);
                    $trendingMovies->each(function ($movie) use ($user_id) {
                        $movie->user_id = $user_id; // Add the user_id to each movie
                    });
                    $trendingMovies = CommanResource::collection($trendingMovies);
               }

            }

           $latestMovieIds = MobileSetting::getCacheValueBySlug('latest-movies');
           $latestMovieIdsArray = json_decode($latestMovieIds, true);


           $latest_movie = (!empty($latestMovieIdsArray)) ? Entertainment::get_latest_movie($latestMovieIdsArray) : collect();
           $latest_movie->each(function ($movie) use ($user_id) {
                $movie->user_id = $user_id; // Add the user_id to each movie
            });


           $latest_movie = MoviesResource::collection($latest_movie)->toArray(request());


           $languageIds = MobileSetting::getCacheValueBySlug('enjoy-in-your-native-tongue');
           $languageIdsArray = json_decode($languageIds, true);
           $popular_language = !empty($languageIdsArray) ? Constant::whereIn('id', $languageIdsArray)->where('status', 1)->where('deleted_at', null)->get() : collect();

           $popularMovieIds = MobileSetting::getCacheValueBySlug('popular-movies');

           $popularMovieIdsArray = json_decode($popularMovieIds, true);
           $popular_movie = (!empty($popularMovieIdsArray)) ? Entertainment::get_popular_movie($popularMovieIdsArray) : collect();
           $popular_movie->each(function ($movie) use ($user_id) {
                $movie->user_id = $user_id; // Add the user_id to each movie
           });
           $popular_movie = MoviesResource::collection($popular_movie)->toArray(request());


           $channelIds = MobileSetting::getValueBySlug('top-channels');
           $channelIdsArray = json_decode($channelIds, true);

           $top_channel = (!empty($channelIdsArray)) ? LiveTvChannel::get_top_channel($channelIdsArray) : collect();
           $top_channel = LiveTvChannelResource::collection($top_channel)->toArray(request());



           $castIds = MobileSetting::getValueBySlug('your-favorite-personality');
           $castIdsArray = json_decode($castIds, true);
           $personality = [];
            if (!empty($castIdsArray)) {
               $casts = CastCrew::whereIn('id', $castIdsArray)->where('status', 1)->where('deleted_at', null)->get();
               foreach ($casts as $value) {
                   $personality[] = [
                       'id' => $value->id,
                       'name' => $value->name,
                       'type' => $value->type,
                       'profile_image' => setBaseUrlWithFileName($value->file_url, 'image', 'castcrew'),
                   ];
               }
            }

           $movieIds = MobileSetting::getValueBySlug('500-free-movies');
           $movieIdsArray = json_decode($movieIds, true);


           $free_movie = !empty($movieIdsArray) ? Entertainment::get_free_movie($movieIdsArray) : collect();
           $free_movie = MoviesResource::collection($free_movie)->toArray(request());


           $popular_tvshowIds = MobileSetting::getValueBySlug('popular-tvshows');
           $popular_tvshowIdsArray = json_decode($popular_tvshowIds, true);

           $popular_tvshow = !empty($popular_tvshowIdsArray) ? Entertainment::get_popular_tvshow($popular_tvshowIdsArray) : collect();
           $popular_tvshow->each(function ($video) use ($user_id) {
                 $video->user_id = $user_id; // Add the user_id to each movie
            });
           $popular_tvshow = TvshowResource::collection($popular_tvshow)->toArray(request());


           $genreIds = MobileSetting::getValueBySlug('genre');
           $genreIdsArray = json_decode($genreIds, true);
           $genres = !empty($genreIdsArray) ? GenresResource::collection(
               Genres::whereIn('id', $genreIdsArray)
                   ->where('status', 1)
                   ->where('deleted_at', null)
                   ->get()
           ) : collect();

            $videoIds = MobileSetting::getValueBySlug('popular-videos');
            $videoIdsArray = json_decode($videoIds, true);

            $popular_videos = !empty($videoIdsArray) ? Video::get_popular_videos($videoIdsArray) : collect();
            $popular_videos->each(function ($video) use ($user_id) {
                $video->user_id = $user_id; // Add the user_id to each movie
            });
            $popular_videos = VideoResource::collection($popular_videos)->toArray(request());

            $tranding_movie = Entertainment::get_entertainment_list();
            $tranding_movie = MoviesResource::collection($tranding_movie)->toArray(request());
            $payPerViewRequest = new Request(['user_id' => $user_id]);

            $payPerViewContent = $this->getPayPerViewUnlockedContent( $payPerViewRequest);
            // Define slugs and their default names
            $slugsWithDefaults = [
                'latest-movies' => 'Latest Movies',
                'enjoy-in-your-native-tongue' => 'Popular Language',
                'popular-movies' => 'Popular Movies',
                'top-channels' => 'Top Channels',
                'your-favorite-personality' => 'Popular Personalities',
                '500-free-movies' => 'Free Movies',
                'popular-tvshows' => 'Popular TV Show',
                'genre' => 'Genres',
                'popular-videos' => 'Popular Videos',
            ];

            // Fetch all required settings in one query
            $settings = MobileSetting::whereIn('slug', array_keys($slugsWithDefaults))->pluck('name', 'slug');

            // Resolve names with fallback to default
            $sectionNames = [];
            foreach ($slugsWithDefaults as $slug => $default) {
                $sectionNames[$slug] = $settings[$slug] ?? $default;
            }
           $responseData = [
               'latest_movie' => [
                    'name' => $sectionNames['latest-movies'],
                    'data' => $latest_movie,
                    ],
                'popular_language' => [
                    'name' => $sectionNames['enjoy-in-your-native-tongue'],
                    'data' => $popular_language,
                ],
                'popular_movie' => [
                    'name' => $sectionNames['popular-movies'],
                    'data' => $popular_movie,
                ],
                'top_channel' => [
                    'name' => $sectionNames['top-channels'],
                    'data' => $top_channel,
                ],
                'personality' => [
                    'name' => $sectionNames['your-favorite-personality'],
                    'data' => $personality,
                ],
                'free_movie' => [
                    'name' => $sectionNames['500-free-movies'],
                    'data' => $free_movie,
                ],
                'popular_tvshow' => [
                    'name' => $sectionNames['popular-tvshows'],
                    'data' => $popular_tvshow,
                ],
                'genres' => [
                    'name' => $sectionNames['genre'],
                    'data' => $genres,
                ],
                'popular_videos' => [
                    'name' => $sectionNames['popular-videos'],
                    'data' => $popular_videos,
                ],
               'likedMovies' => $likedMovies ?? [],
               'viewedMovies' => $viewedMovies ?? [],
               'trendingMovies' => $trendingMovies ?? [],
               'favorite_gener' => $FavoriteGener ?? [],
               'favorite_personality' => $favorite_personality ?? [],
               'base_on_last_watch'=> $Lastwatchrecommendation ?? [],
               'tranding_movie'=>$tranding_movie,
               'pay_per_view' => $payPerViewContent,
           ];

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('messages.dashboard_detail'),
        ], 200);
    }


public function getTrandingData(Request $request){


    if ($request->has('is_ajax') && $request->is_ajax == 1) {

        $popularMovieIds = MobileSetting::getValueBySlug(slug: 'popular-movies');
        $movieList = Entertainment::whereIn('id',json_decode($popularMovieIds));

        isset(request()->is_restricted) && $movieList = $movieList->where('is_restricted', request()->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $movieList = $movieList->where('is_restricted',0);

        $movieList = $movieList->where('status',1)
                        ->where(function($query) {
                            $query->whereNull('release_date')
                                ->orWhere('release_date', '<=', now());
                        })
                    ->get();

        $html = '';
        if($request->has('section')&& $request->section == 'tranding_movie'){
            $movieData = (isenablemodule('movie') == 1) ? CommonContentResourceV3::collection($movieList) : [];
            if(!empty( $movieData)){

                foreach( $movieData->toArray(request()) as $index => $movie){
                    $html .= view('frontend::components.card.card_entertainment',['value' => $movie])->render();
                }
            }
        }


    return response()->json([
            'status' => true,
            'html' => $html,
            'message' => __('movie.tvshow_list'),
        ], 200);
    }



}

       public function DashboardDetailV2(Request $request)
    {
        if (!Cache::has('genres')) {
            $genresData = Genres::get(['id','name'])->keyBy('id')->toArray();
            Cache::put('genres', $genresData);
        }

        $user_id = !empty($request->user_id) ? $request->user_id : null;
        $continueWatch = [];

        if($request->has('user_id')){
            $continueWatchList = ContinueWatch::where('user_id', $user_id)
            ->where('profile_id',$request->profile_id)->get();
            $continueWatch = ContinueWatchResourceV2::collection($continueWatchList);
        }

        $isBanner = MobileSetting::getCacheValueBySlug('banner');
        $sliderList = $isBanner
        ? Banner::where('banner_for','home')->where('status', 1)->get()
        : collect();

        // Filter home banners based on their actual content type (type field)
        $sliderList = $sliderList->filter(function($banner) {
            $bannerType = $banner->type;
            if ($bannerType == 'movie') {
                return isenablemodule('movie') == 1;
            } elseif ($bannerType == 'tvshow' || $bannerType == 'tv_show') {
                return isenablemodule('tvshow') == 1;
            } elseif ($bannerType == 'video') {
                return isenablemodule('video') == 1;
            } elseif ($bannerType == 'livetv') {
                return isenablemodule('livetv') == 1;
            }
            // For promotional or other types, include them
            return true;
        });

        $sliders = SliderResource::collection(
            $sliderList->map(fn($slider) => new SliderResource($slider, $user_id))
       );


        $topMovieIds = MobileSetting::getCacheValueBySlug('top-10');

        $top_10 = !empty($topMovieIds) ? Entertainment::get_top_movie(json_decode($topMovieIds, true)) : collect();



        $top_10 = MoviesResource::collection($top_10)->toArray(request());

        $responseData = [
           'slider' => $sliders,
           'continue_watch' => $continueWatch,
           'top_10' => [
              'name' => MobileSetting::where('slug', 'top-10')->value('name') ?? 'Top 10',
              'data' => $top_10,
          ],
        ];

       // Cache::put($cacheKey,$responseData);

       return response()->json([
           'status' => true,
           'data' => $responseData,
           'message' => __('messages.dashboard_detail'),
       ], 200);
    }


    public function getEntertainmentDataV3(Request $request)
    {
        // $type = $request->query('type', 'movie'); // Default to 'movie'
        $type = $request->query('banner_for'); // Default to 'movie'

        $user_id = $request->user_id ?? null;
        $profile_id = $request->profile_id ?? null;
        $device_type = $request->device_type ?? null;
        $is_restricted = $request->is_restricted ?? null;
        // Create cache key based on request parameters
        $cacheKey = 'entertainment_data_v3_'. $type . '_' . $user_id . '_' . $profile_id . '_' . $device_type . '_' . $is_restricted;
        $ttl = 300; // 5 minutes cache

        $result = cacheApiResponse($cacheKey, $ttl, function() use ($type, $user_id, $profile_id, $device_type, $is_restricted, $request) {
            $isBanner = MobileSetting::getValueBySlug('banner');
            if($type == 'tvshow'){
                $type = 'tv_show';
                $is_restricted = $is_restricted ?? null;
            }

            if ($type == 'home') {
                $sliderList = $isBanner
                    ? Banner::where('status',1)->where('deleted_at',null)->where('banner_for',$type)->get()
                    : collect();
                $sliderList = $sliderList->filter(function($banner) {
                    $bannerType = $banner->type;
                    if ($bannerType == 'movie') {
                        return isenablemodule('movie') == 1;
                    } elseif ($bannerType == 'tvshow' || $bannerType == 'tv_show') {
                        return isenablemodule('tvshow') == 1;
                    } elseif ($bannerType == 'video') {
                        return isenablemodule('video') == 1;
                    } elseif ($bannerType == 'livetv') {
                        return isenablemodule('livetv') == 1;
                    }
                    return true;
                });
            } else {
                $isModuleEnabled = true;
                if ($type == 'movie') {
                    $isModuleEnabled = isenablemodule('movie') == 1;
                } elseif ($type == 'tv_show') {
                    $isModuleEnabled = isenablemodule('tvshow') == 1;
                } elseif ($type == 'video') {
                    $isModuleEnabled = isenablemodule('video') == 1;
                } elseif ($type == 'livetv') {
                    $isModuleEnabled = isenablemodule('livetv') == 1;
                }

                $sliderList = ($isBanner && $isModuleEnabled)
                    ? Banner::where('status',1)->where('deleted_at',null)->where('banner_for',$type)->get()
                    : collect();
            }
            $userLevel = Subscription::select('plan_id')
            ->where(['user_id' => $user_id, 'status' => 'active'])
            ->latest()
            ->first();
            $profile_id = $request->profile_id ?? null;
            $sliderList->each(function ($item) use ($user_id, $userLevel, $is_restricted, $device_type, $profile_id) {
                $item->user_id = $user_id;
                $item->device_type = $device_type;
                $item->userPlanId = $userLevel->plan_id ?? null;
                $item->is_restricted = $is_restricted;
                $item->profile_id = $profile_id;
                $item->poster_url = $device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
            });
            $sliders = SliderResourceV3::collection($sliderList)->toArray($request);
            $sliders = array_filter($sliders, function($item) {
                $details = $item['details'] ?? null;
                if ($details === null || (is_array($details) && empty($details))) {
                    return false;
                }
                return true;
            });
            $sliders = array_values($sliders);
            if($user_id){
                $user = User::where('id',$user_id)->first();
                $all_unread_count = isset($user->unreadNotifications) ? $user->unreadNotifications->count() : 0;
            }

            return [
                'slider' => $sliders,
                'unread_notification_count'=> isset($all_unread_count) ? $all_unread_count : 0,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $result['data'],
            'message' => __('messages.' . $type . '_detail'),
        ], 200);
    }

     public function DashboardDetailDataV3(Request $request)
    {
        $user_id = !empty($request->user_id) ? $request->user_id : null;
        $profile_id = $request->profile_id ?? null;

        $device_type = getDeviceType($request);

        // Create cache key based on request parameters
        $cacheKey = 'dashboard_detail_data_v3_'.md5(json_encode([
        'user_id' => $user_id,
        'profile_id' => $profile_id,
        'device_type' => $device_type,
        'latest-movies' => MobileSetting::getCacheValueBySlug('latest-movies'),
        'popular-movies' => MobileSetting::getCacheValueBySlug('popular-movies'),
        'popular-tvshows' => MobileSetting::getValueBySlug('popular-tvshows'),
        'popular-videos' => MobileSetting::getValueBySlug('popular-videos'),
        'top-channels' => MobileSetting::getValueBySlug('top-channels'),
        'genre' => MobileSetting::getValueBySlug('genre'),
        '500-free-movies' => MobileSetting::getValueBySlug('500-free-movies'),
        'your-favorite-personality' => MobileSetting::getValueBySlug('your-favorite-personality'),
        'enjoy-in-your-native-tongue' => MobileSetting::getCacheValueBySlug('enjoy-in-your-native-tongue'),
    ]));

        // Use cacheApiResponse helper for Redis caching
        $cachedResult = cacheApiResponse($cacheKey, 300, function () use ($request, $user_id, $profile_id, $device_type) {

            if (!Cache::has('genres')) {
                $genresData = Genres::get(['id','name'])->keyBy('id')->toArray();
                Cache::put('genres', $genresData);
            }
            $userPlanId = 0;
            $deviceTypeResponse = ['isDeviceSupported' => false];

            if($user_id)
            {

               $user = User::where('id',$user_id)->first();
               $profile_id=$request->profile_id;



                   $user = User::where('id',$user_id)->first();


                    $getDeviceTypeData = Subscription::checkPlanSupportDevice($user_id, $device_type);
                    $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
                    $userLevel = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();
                    $userPlanId = $userLevel->plan_id ?? 0;



                    $FavoriteGener = $this->recommendationService->getFavoriteGener($user, $profile_id);
                    $FavoriteGener = GenresResource::collection($FavoriteGener);


                    $favorite_personality = $this->recommendationService->getFavoritePersonality($user, $profile_id);
                     $favorite_personality = CastCrewListResource::collection($favorite_personality);

                    $trendingMovies = $this->recommendationService->getTrendingMoviesByCountry($user, $request);
                    $trendingMovies->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type) {
                        $movie->user_id = $user_id; // Add the user_id to each movie
                        $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                        $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                        $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                        $movie->access = $movie->movie_access;
                        $movie = setContentAccess($movie, $user_id, $userPlanId);
                    });
                    $trendingMovies = CommanResourceV3::collection($trendingMovies);


            }


           $languageIds = MobileSetting::getCacheValueBySlug('enjoy-in-your-native-tongue');
           $languageIdsArray = json_decode($languageIds, true);
           $popular_language = !empty($languageIdsArray) ? Constant::whereIn('id', $languageIdsArray)->where('status', 1)->where('deleted_at', null)->select('id', 'name','language_image')->get()->makeHidden(['feature_image', 'media', 'status', 'created_at', 'updated_at', 'deleted_at']) : collect();

            $popular_language->each(function ($language) {
               $language->language_image = setBaseUrlWithFileName($language->language_image,'image','constant');
            });

           $popularMovieIds = MobileSetting::getCacheValueBySlug('popular-movies');

           $popularMovieIdsArray = json_decode($popularMovieIds, true);
           $popular_movie = (!empty($popularMovieIdsArray)) ? Entertainment::get_popular_movie($popularMovieIdsArray) : collect();

            $popular_movie->each(function ($movie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                $movie->user_id = $user_id;
                $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                $movie->access = $movie->movie_access;
                $movie = setContentAccess($movie, $user_id, $userPlanId);
            });
           $popular_movie = MoviesResourceV3::collection($popular_movie)->toArray(request());


           $channelIds = MobileSetting::getValueBySlug('top-channels');
           $channelIdsArray = json_decode($channelIds, true);

            $top_channel = (!empty($channelIdsArray)) ? LiveTvChannel::get_top_channel($channelIdsArray) : collect();
            $top_channel->each(function ($channel) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                $channel->user_id = $user_id;
                $channel->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $channel->poster_image =  $device_type == 'tv' ? setBaseUrlWithFileName($channel->poster_tv_url,'image','livetv')  : setBaseUrlWithFileName($channel->poster_url,'image','livetv');
                $channel->access = $channel->access;
                $channel = setContentAccess($channel, $user_id, $userPlanId);
            });

           $top_channel = LiveTvChannelResourceV3::collection($top_channel)->toArray(request());




           $castIds = MobileSetting::getValueBySlug('your-favorite-personality');
           $castIdsArray = json_decode($castIds, true);
           $personality = [];
            if (!empty($castIdsArray)) {
               $casts = CastCrew::whereIn('id', $castIdsArray)->where('deleted_at',null)->where('status',1)->get();
               foreach ($casts as $value) {
                   $personality[] = [
                       'id' => $value->id,
                       'name' => $value->name,
                       'type' => $value->type,
                       'profile_image' => setBaseUrlWithFileName($value->file_url,'image','castcrew'),
                   ];
               }
            }

           $movieIds = MobileSetting::getValueBySlug('500-free-movies');
           $movieIdsArray = json_decode($movieIds, true);


            $free_movie = !empty($movieIdsArray) ? Entertainment::get_free_movie($movieIdsArray) : collect();
            $free_movie->each(function ($freeMovie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                $freeMovie->user_id = $user_id;
                $freeMovie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $freeMovie->poster_image =  $device_type == 'tv' ? $freeMovie->poster_tv_url : $freeMovie->poster_url ?? null;
                $freeMovie->trailer_url =  $freeMovie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($freeMovie->trailer_url, 'video', $freeMovie->type) : $freeMovie->trailer_url;
                $freeMovie->access = $freeMovie->movie_access;
                $freeMovie = setContentAccess($freeMovie, $user_id, $userPlanId);
            });
           $free_movie = MoviesResourceV3::collection($free_movie)->toArray(request());


           $popular_tvshowIds = MobileSetting::getValueBySlug('popular-tvshows');
           $popular_tvshowIdsArray = json_decode($popular_tvshowIds, true);

            $popular_tvshow = !empty($popular_tvshowIdsArray) ? Entertainment::get_popular_tvshow($popular_tvshowIdsArray) : collect();
            if($user_id){
                    $popular_tvshow->each(function ($tvshow) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                        $tvshow->user_id = $user_id;
                        $tvshow->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                        $tvshow->poster_image =  $device_type == 'tv' ? $tvshow->poster_tv_url : $tvshow->poster_url ?? null;
                        $tvshow->trailer_url =  $tvshow->trailer_url_type == 'Local' ? setBaseUrlWithFileName($tvshow->trailer_url, 'video', $tvshow->type) : $tvshow->trailer_url;
                        $tvshow->access = $tvshow->movie_access;
                        $tvshow = setContentAccess($tvshow, $user_id, $userPlanId);
                    });
             }else{
                $popular_tvshow->each(function ($tvshow) use ($device_type) {
                    $tvshow->isDeviceSupported = 0;
                    $tvshow->poster_image =  $device_type == 'tv' ? $tvshow->poster_tv_url : $tvshow->poster_url ?? null;
                    $tvshow->trailer_url =  $tvshow->trailer_url_type == 'Local' ? setBaseUrlWithFileName($tvshow->trailer_url, 'video', $tvshow->type) : $tvshow->trailer_url;
                    $tvshow->access = $tvshow->movie_access;
                    $tvshow = setContentAccess($tvshow, null, null);
                });
            }
           $popular_tvshow = TvshowResourceV3::collection($popular_tvshow)->toArray(request());


           $genreIds = MobileSetting::getValueBySlug('genre');
           $genreIdsArray = json_decode($genreIds, true);
           $genres = !empty($genreIdsArray) ? GenresResource::collection(
            Genres::whereIn('id', $genreIdsArray)
              ->where('status', 1)
              ->where('deleted_at',null)
              ->get()
                )->map(function ($genre) {
                    return [
                        'id'           => $genre['id'],
                        'name'         => $genre['name'],
                        'poster_image' =>!empty($genre['file_url']) ? setBaseUrlWithFileName($genre['file_url'],'image','genres') : null,
                    ];
                })
                : collect();

            $videoIds = MobileSetting::getValueBySlug('popular-videos');
            $videoIdsArray = json_decode($videoIds, true);

            $popular_videos = !empty($videoIdsArray) ? Video::get_popular_videos($videoIdsArray) : collect();
            $popular_videos->each(function ($video) use ($user_id) {
                $video->user_id = $user_id; // Add the user_id to each movie
                $video->type = 'video';
            });
            if($user_id){
                $popular_videos->each(function ($video) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                    $video->user_id = $user_id;
                    $video->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $video = setContentAccess($video, $user_id, $userPlanId);
                    $video->poster_image =  $device_type == 'tv' ? $video->poster_tv_url : $video->poster_url ?? null;
                });
            }else{
                $popular_videos->each(function ($video) use ($device_type) {
                    $video->isDeviceSupported = 0;
                    $video->poster_image =  $device_type == 'tv' ? $video->poster_tv_url : $video->poster_url ?? null;
                    $video->access = $video->movie_access;
                    $video = setContentAccess($video, null, null);
                });
            }
            $popular_videos = VideoResourceV3::collection($popular_videos)->toArray(request());

            $tranding_movie = Entertainment::get_entertainment_list();
            if($user_id){
                $tranding_movie->each(function ($tranding) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                    $tranding->user_id = $user_id;
                    $tranding->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $tranding->required_plan_level = $userPlanId >= $tranding->plan_id ? 1 : 0;
                    $tranding->trailer_url =  $tranding->trailer_url_type == 'Local' ? setBaseUrlWithFileName($tranding->trailer_url, 'video', $tranding->type) : $tranding->trailer_url;
                    $tranding->poster_image =  $device_type == 'tv' ? $tranding->poster_tv_url : $tranding->poster_url ?? null;
                    $tranding->access = $tranding->movie_access;
                    $tranding = setContentAccess($tranding, $user_id, $userPlanId);
                });
            }else{
                $tranding_movie->each(function ($tranding) use ($device_type) {
                    $tranding->isDeviceSupported = 0;
                    $tranding->trailer_url =  $tranding->trailer_url_type == 'Local' ? setBaseUrlWithFileName($tranding->trailer_url, 'video', $tranding->type) : $tranding->trailer_url;
                    $tranding->poster_image =  $device_type == 'tv' ? $tranding->poster_tv_url : $tranding->poster_url ?? null;
                    $tranding->access = $tranding->movie_access;
                    $tranding = setContentAccess($tranding, null, null);
                });
            }
            $tranding_movie = MoviesResourceV3::collection($tranding_movie)->toArray(request());
            $payPerViewRequest = new Request(['user_id' => $user_id]);

            $payPerViewContent = $this->getPayPerViewUnlockedContentV3( $payPerViewRequest);
            // Define slugs and their default names
            $slugsWithDefaults = [
                'enjoy-in-your-native-tongue' => 'Popular Language',
                'popular-movies' => 'Popular Movies',
                'top-channels' => 'Top Channels',
                'your-favorite-personality' => 'Popular Personalities',
                '500-free-movies' => 'Free Movies',
                'popular-tvshows' => 'Popular TV Show',
                'genre' => 'Genres',
                'popular-videos' => 'Popular Videos',
            ];

            // Fetch all required settings in one query
            $settings = MobileSetting::whereIn('slug', array_keys($slugsWithDefaults))->pluck('name', 'slug');

            // Resolve names with fallback to default
            $sectionNames = [];
            foreach ($slugsWithDefaults as $slug => $default) {
                $sectionNames[$slug] = $settings[$slug] ?? $default;
            }
            $otherSectionData = $this->getOtherSectionData($request);
           $likedMovies = collect();
           $viewedMovies = collect();
           $Lastwatchrecommendation = collect();
           if ($user_id) {
               $likedMovies = $this->recommendationService->getLikedMovies($user, $profile_id);
               $likedMovies->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type ) {
                   $movie->user_id = $user_id;
                   $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                   $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                   $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                   $movie->access = $movie->movie_access;
                   $movie = setContentAccess($movie, $user_id, $userPlanId);
               });
               $likedMovies = CommanResourceV3::collection($likedMovies);

               $viewedMovies = $this->recommendationService->getEntertainmentViews($user, $profile_id);
               $viewedMovies->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type ) {
                   $movie->access = $movie->movie_access;
                   $movie->user_id = $user_id;
                   $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                   $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                   $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                   $movie = setContentAccess($movie, $user_id, $userPlanId);
               });
               $viewedMovies = CommanResourceV3::collection($viewedMovies);

               $based_on_last_watch = collect($this->recommendationService->recommendByLastHistory($user,$profile_id));
                $based_on_last_watch->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type ) {
                    $movie->user_id = $user_id; // Add the user_id to each movie
                    $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                    $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                    $movie->access = $movie->movie_access;
                    $movie = setContentAccess($movie, $user_id, $userPlanId);

                });
                $Lastwatchrecommendation = MoviesResourceV3::collection($based_on_last_watch );
           }

           $responseData = [
               // moved from dashboard-detail to keep section parity
               'based_on_likes' => $likedMovies ?? [],
               'based_on_views' => $viewedMovies ?? [],
               'based_on_last_watch' => $Lastwatchrecommendation ?? [],

               'top_channel' => [
                   'name' => $sectionNames['top-channels'],
                   'data' => $top_channel,
               ],
               'personality' => [
                   'name' => $sectionNames['your-favorite-personality'],
                   'data' => $personality,
               ],
               'free_movie' => [
                   'name' => $sectionNames['500-free-movies'],
                   'data' => $free_movie,
               ],
               'popular_tvshow' => [
                   'name' => $sectionNames['popular-tvshows'],
                   'data' => $popular_tvshow,
               ],
               'genres' => [
                   'name' => $sectionNames['genre'],
                   'data' => $genres,
               ],
               'popular_videos' => [
                   'name' => $sectionNames['popular-videos'],
                   'data' => $popular_videos,
               ],

               'trending_movies' => $trendingMovies ?? [],
               'favorite_genres' => $FavoriteGener ?? [],
               'favorite_personality' => $favorite_personality ?? [],
               'trending_in_country'=>$tranding_movie,
               'other_section' => $otherSectionData,
           ];

            return $responseData;
        });

        return response()->json([
            'status' => true,
            'data' => $cachedResult['data'],
            'message' => __('messages.dashboard_detail'),
        ], 200);
    }

    public function getOtherSectionData(Request $request)
    {
        $user_id = $request->user_id ?? null;
        $profile_id = $request->profile_id ?? null;
        $device_type = getDeviceType($request);

        $userPlanId = 0;
        $deviceTypeResponse = ['isDeviceSupported' => false];

        if ($user_id) {
            $user = User::find($user_id);
            $getDeviceTypeData = Subscription::checkPlanSupportDevice($user_id, $device_type);
            $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
            $userLevel = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();
            $userPlanId = $userLevel->plan_id ?? 0;
        }

        $sections = MobileSetting::whereNotNull('type')->where('type', '!=', '')->get();

        $response = [];

        foreach ($sections as $section) {
            $ids = json_decode($section->value, true);
            if (empty($ids) || !is_array($ids)) {
                continue;
            }

            $data = collect();
            switch ($section->type) {
                case 'movie':
                    $data = Entertainment::whereIn('id', $ids)
                        ->where('type', 'movie')
                        ->released()
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->get();

                    $data->each(function ($movie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                        $movie->user_id = $user_id;
                        $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                        $movie->poster_image = $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                        $movie->trailer_url = $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                        $movie->access = $movie->movie_access;
                        setContentAccess($movie, $user_id, $userPlanId);
                    });

                    $data = MoviesResourceV3::collection($data)->toArray(request());
                    break;

                case 'tvshow':
                    $data = Entertainment::whereIn('id', $ids)
                        ->where('type', 'tvshow')
                        ->released()
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->get();

                    if ($user_id) {
                        $data->each(function ($tvshow) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                            $tvshow->user_id = $user_id;
                            $tvshow->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                            $tvshow->poster_image = $device_type == 'tv' ? $tvshow->poster_tv_url : $tvshow->poster_url ?? null;
                            $tvshow->trailer_url = $tvshow->trailer_url_type == 'Local' ? setBaseUrlWithFileName($tvshow->trailer_url, 'video', $tvshow->type) : $tvshow->trailer_url;
                            $tvshow->access = $tvshow->movie_access;
                            setContentAccess($tvshow, $user_id, $userPlanId);
                        });
                    } else {
                        $data->each(function ($tvshow) use ($device_type) {
                            $tvshow->isDeviceSupported = 0;
                            $tvshow->poster_image = $device_type == 'tv' ? $tvshow->poster_tv_url : $tvshow->poster_url ?? null;
                            $tvshow->trailer_url = $tvshow->trailer_url_type == 'Local' ? setBaseUrlWithFileName($tvshow->trailer_url, 'video', $tvshow->type) : $tvshow->trailer_url;
                            $tvshow->access = $tvshow->movie_access;
                            setContentAccess($tvshow, null, null);
                        });
                    }

                    $data = TvshowResourceV3::collection($data)->toArray(request());
                    break;

                case 'video':
                    $data = Video::whereIn('id', $ids)
                        ->whereDate('release_date', '<=', now())
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->get();

                    if ($user_id) {
                        $data->each(function ($video) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                            $video->user_id = $user_id;
                            $video->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                            setContentAccess($video, $user_id, $userPlanId);
                            $video->poster_image = $device_type == 'tv' ? $video->poster_tv_url : $video->poster_url ?? null;
                        });
                    } else {
                        $data->each(function ($video) use ($device_type) {
                            $video->isDeviceSupported = 0;
                            $video->poster_image = $device_type == 'tv' ? $video->poster_tv_url : $video->poster_url ?? null;
                            $video->access = $video->movie_access;
                            setContentAccess($video, null, null);
                        });
                    }

                    $data = VideoResourceV3::collection($data)->toArray(request());
                    break;

                case 'channel':
                    $data = LiveTvChannel::whereIn('id', $ids)
                        ->where('status', 1)
                        ->get();

                    $data->each(function ($channel) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                        $channel->user_id = $user_id;
                        $channel->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                        $channel->poster_image = $device_type == 'tv'
                            ? setBaseUrlWithFileName($channel->poster_tv_url, 'image', 'livetv')
                            : setBaseUrlWithFileName($channel->poster_url, 'image', 'livetv');
                        $channel->access = $channel->access;
                        setContentAccess($channel, $user_id, $userPlanId);
                    });

                    $data = LiveTvChannelResourceV3::collection($data)->toArray(request());
                    break;

                default:
                    continue 2;
            }

            if (!empty($data)) {
                $response[] = [
                    'slug' => $section->slug,
                    'name' => $section->name,
                    'type' => $section->type,
                    'data' => $data,
                ];
            }
        }

        return $response;
    }


    public function DashboardDetailV3(Request $request)
    {
        $device_type = getDeviceType($request);

        if (!Cache::has('genres')) {
            $genresData = Genres::get(['id','name'])->keyBy('id')->toArray();
            Cache::put('genres', $genresData);
        }

        $user_id = !empty($request->user_id) ? $request->user_id : null;
        $profile_id = $request->profile_id ?? null;

        $cacheKey = 'dashboard_detail_v3_'.time().md5(json_encode([
            'user_id' => $user_id,
            'profile_id' => $profile_id,
            'latest-movies' => MobileSetting::getCacheValueBySlug('latest-movies'),
            'banner' => MobileSetting::getCacheValueBySlug('banner'),
            'top_10' => MobileSetting::getCacheValueBySlug('top-10'),
            'device_type' => $device_type
        ]));

        $cachedResult = cacheApiResponse($cacheKey, 300, function () use ($request, $user_id, $profile_id, $device_type) {
            $continueWatch = [];
            $deviceTypeResponse = ['isDeviceSupported' => false];
            $userPlanId = 0;

            if($request->has('user_id')){

                $user = User::where('id',$user_id)->first();

                $continuewatch = ContinueWatch::query()
                    ->whereNotNull('watched_time')
                    ->whereNotNull('total_watched_time')
                    ->where(function ($query) {
                        // For movie and tvshow, check entertainment relationship
                        $query->where(function ($q) {
                            $q->where('entertainment_type', 'movie')
                            ->whereHas('entertainment', function ($subQuery) {
                                $subQuery->where('status', 1)
                                            ->whereNull('deleted_at');
                            });
                        })
                        // For episode, check episode relationship
                        ->orWhere(function ($q) {
                            $q->where('entertainment_type', 'tvshow')
                            ->whereNotNull('episode_id')
                            ->whereHas('episode', function ($subQuery) {
                                $subQuery->where('status', 1)
                                            ->whereNull('deleted_at');
                            });
                        })
                        // For video, check video relationship
                        ->orWhere(function ($q) {
                            $q->where('entertainment_type', 'video')
                            ->whereHas('video', function ($subQuery) {
                                $subQuery->where('status', 1)
                                            ->whereNull('deleted_at');
                            });
                        });
                    })
                    ->with(['entertainment', 'episode.seasondata', 'video']);

                $continueWatchList = $continuewatch->where('user_id', $user_id)
                ->where('profile_id', $profile_id)->orderBy('updated_at', 'desc')->get();


                 $continueWatchList->each(function ($continueWatchItem){
                    // If it's a TV show with an episode, use episode's poster image instead of TV show's
                    if ($continueWatchItem->entertainment_type == 'tvshow' && $continueWatchItem->episode) {
                        // Use episode's poster image
                        $continueWatchItem->thumbnail_url = $continueWatchItem->episode->poster_url ?? null;
                        $continueWatchItem->trailer_url_type = $continueWatchItem->episode->trailer_url_type ?? $continueWatchItem->entertainment->trailer_url_type ?? null;
                        $continueWatchItem->trailer_url = $continueWatchItem->episode->trailer_url_type == 'Local' 
                            ? setBaseUrlWithFileName($continueWatchItem->episode->trailer_url, 'video', 'episode') 
                            : ($continueWatchItem->episode->trailer_url ?? ($continueWatchItem->entertainment->trailer_url_type == 'Local' 
                                ? setBaseUrlWithFileName($continueWatchItem->entertainment->trailer_url, 'video', $continueWatchItem->entertainment->type) 
                                : $continueWatchItem->entertainment->trailer_url));
                        
                        if ($continueWatchItem->episode->seasondata) {
                            $continueWatchItem->tv_show_data = [
                                'id' => $continueWatchItem->episode->seasondata->entertainment_id ?? $continueWatchItem->episode->seasondata->id,
                                'episode_name' => $continueWatchItem->episode->name,
                                'season_name' => $continueWatchItem->episode->seasondata->name,
                                'season_id' => $continueWatchItem->episode->seasondata->id,
                            ];
                        } else {
                            $continueWatchItem->tv_show_data = null;
                        }
                    } else {
                        // For movies, videos, or TV shows without episodes, use entertainment poster
                        $continueWatchItem->thumbnail_url = $continueWatchItem->entertainment->thumbnail_url ?? $continueWatchItem->entertainment->poster_url ?? null;
                        $continueWatchItem->trailer_url_type = $continueWatchItem->entertainment->trailer_url_type ?? null;
                        $continueWatchItem->trailer_url =  $continueWatchItem->entertainment->trailer_url_type == 'Local' ? setBaseUrlWithFileName($continueWatchItem->entertainment->trailer_url, 'video', $continueWatchItem->entertainment->type) : $continueWatchItem->entertainment->trailer_url;
                        $continueWatchItem->tv_show_data = null;
                    }
                });
                $continueWatch = ContinueWatchResourceV3::collection($continueWatchList);

                $likedMovies = $this->recommendationService->getLikedMovies($user, $profile_id);


                $getDeviceTypeData = Subscription::checkPlanSupportDevice($user_id, $device_type);
                $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
                $userLevel = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();
                $userPlanId = $userLevel->plan_id ?? 0;




                $likedMovies->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type ) {
                    $movie->user_id = $user_id; // Add the user_id to each movie
                    $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                    $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                    $movie->access = $movie->movie_access;
                    $movie = setContentAccess($movie, $user_id, $userPlanId);
                });
                $likedMovies = CommanResourceV3::collection($likedMovies);
                $viewedMovies = $this->recommendationService->getEntertainmentViews($user, $profile_id);
                $viewedMovies->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type ) {
                    $movie->access = $movie->movie_access;
                    $movie->user_id = $user_id; // Add the user_id to each movie
                    $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                    $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                    $movie = setContentAccess($movie, $user_id, $userPlanId);

                });
                $viewedMovies = CommanResourceV3::collection($viewedMovies);

                $based_on_last_watch = collect($this->recommendationService->recommendByLastHistory($user,$profile_id));
                $based_on_last_watch->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type ) {
                    $movie->user_id = $user_id; // Add the user_id to each movie
                    $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                    $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                    $movie->access = $movie->movie_access;
                    $movie = setContentAccess($movie, $user_id, $userPlanId);

                });
                $Lastwatchrecommendation = MoviesResourceV3::collection($based_on_last_watch );

            }

            $latestMovieIds = MobileSetting::getCacheValueBySlug('latest-movies');
            $latestMovieIdsArray = json_decode($latestMovieIds, true);

            $latest_movie = (!empty($latestMovieIdsArray)) ? Entertainment::get_latest_movie($latestMovieIdsArray) : collect();
            if($request->has('user_id')){
                 $latest_movie->each(function ($movie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                 $movie->user_id = $user_id;
                 $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                 $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                 $movie->access = $movie->movie_access;
                 $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                 $movie = setContentAccess($movie, $user_id, $userPlanId);
             });
            }else{
                $latest_movie->each(function ($movie) use ($device_type) {
                    $movie->isDeviceSupported = 0;
                    $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                    $movie->access = $movie->movie_access;
                    $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                    $movie = setContentAccess($movie, null, null);
                });
            }
            $latest_movie = MoviesResourceV3::collection($latest_movie)->toArray(request());

            $isBanner = MobileSetting::getCacheValueBySlug('banner');
            $sliderList = $isBanner
            ? Banner::where('banner_for','home')->where('status', 1)->where('deleted_at', null)->get()
            : collect();

            $sliderList = $sliderList->filter(function($banner) {
                $bannerType = $banner->type;
                if ($bannerType == 'movie') {
                    return isenablemodule('movie') == 1;
                } elseif ($bannerType == 'tvshow' || $bannerType == 'tv_show') {
                    return isenablemodule('tvshow') == 1;
                } elseif ($bannerType == 'video') {
                    return isenablemodule('video') == 1;
                } elseif ($bannerType == 'livetv') {
                    return isenablemodule('livetv') == 1;
                }
                return true;
            });

            $sliders = SliderResource::collection(
                $sliderList->map(fn($slider) => new SliderResource($slider, $user_id))
           );

            $topMovieIds = MobileSetting::getCacheValueBySlug('top-10');

            $top_10 = !empty($topMovieIds) ? Entertainment::get_top_movie(json_decode($topMovieIds, true)) : collect();
             if($request->has('user_id')){
                $top_10->each(function ($top10) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {

                $top10->user_id = $user_id;
                $top10->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;

                $top10->poster_image =  $device_type == 'tv' ? $top10->poster_tv_url : $top10->poster_url ?? null;
                $top10->access = $top10->movie_access;
                $top10->trailer_url =  $top10->trailer_url_type == 'Local' ? setBaseUrlWithFileName($top10->trailer_url, 'video', $top10->type) : $top10->trailer_url;
                $top10 = setContentAccess($top10, $user_id, $userPlanId);
            });
        }else{
                $top_10->each(function ($top10) use ($user_id, $device_type) {
                $top10->user_id = $user_id;
                $top10->poster_image =  $device_type == 'tv' ? $top10->poster_tv_url : $top10->poster_url ?? null;
                $top10->trailer_url =  $top10->trailer_url_type == 'Local' ? setBaseUrlWithFileName($top10->trailer_url, 'video', $top10->type) : $top10->trailer_url;
                $top10->access = $top10->movie_access;
                $top10 = setContentAccess($top10, null, null);
            });
        }
            $top_10 = MoviesResourceV3::collection($top_10)->toArray(request());

            $languageIds = MobileSetting::getCacheValueBySlug('enjoy-in-your-native-tongue');
            $languageIdsArray = json_decode($languageIds, true);
            $popular_language = !empty($languageIdsArray) ? Constant::whereIn('id', $languageIdsArray)->where('status', 1)->where('deleted_at', null)->select('id', 'name','language_image')->get()->makeHidden(['feature_image', 'media', 'status', 'created_at', 'updated_at', 'deleted_at']) : collect();

            $popular_language->each(function ($language) {
               $language->language_image = setBaseUrlWithFileName($language->language_image,'image','constant');
            });

            $popularMovieIds = MobileSetting::getCacheValueBySlug('popular-movies');
            $popularMovieIdsArray = json_decode($popularMovieIds, true);
            $popular_movie = (!empty($popularMovieIdsArray)) ? Entertainment::get_popular_movie($popularMovieIdsArray) : collect();

            $popular_movie->each(function ($movie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                $movie->user_id = $user_id;
                $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                $movie->access = $movie->movie_access;
                $movie = setContentAccess($movie, $user_id, $userPlanId);
            });
            $popular_movie = MoviesResourceV3::collection($popular_movie)->toArray(request());

            $payPerViewRequest = new Request(['user_id' => $user_id]);
            $payPerViewContent = $this->getPayPerViewUnlockedContentV3($payPerViewRequest);

            $today = Carbon::now()->toDateString();
            // $is_advertisement_enabled = MobileSetting::where('slug', 'advertisement')->first();
            $customAds = CustomAdsSetting::
                        where('status', 1)
                        ->where('placement', 'home_page')
                        ->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today)
                        ->get(['type','media','redirect_url'])->map(function($ad) {
                            return [
                                'type' => $ad->type,
                                'url' => $ad->media ? setBaseUrlWithFileName($ad->media,$ad->type,'ads') : null,
                                'redirect_url' => $ad->redirect_url,
                            ];
                        });

            $slugsWithDefaultsAdditional = [
                'enjoy-in-your-native-tongue' => 'Popular Language',
                'popular-movies' => 'Popular Movies',
            ];
            $settingsAdditional = MobileSetting::whereIn('slug', array_keys($slugsWithDefaultsAdditional))->pluck('name', 'slug');
            $sectionNamesAdditional = [];
            foreach ($slugsWithDefaultsAdditional as $slug => $default) {
                $sectionNamesAdditional[$slug] = $settingsAdditional[$slug] ?? $default;
            }

                        $slugsWithDefaults = [
                            'latest-movies' => 'Latest Movies',
                            'top-10' => 'Top 10',
                        ];

                        $settings = MobileSetting::whereIn('slug', array_keys($slugsWithDefaults))->pluck('name', 'slug');

                        $sectionNames = [];
                        foreach ($slugsWithDefaults as $slug => $default) {
                            $sectionNames[$slug] = $settings[$slug] ?? $default;
                        }

                    return [
                        'continue_watch' => $continueWatch,
                        'latest_movie' => [
                                'name' => $sectionNames['latest-movies'],
                                'data' => $latest_movie,
                            ],
                        'top_10' => [
                            'name' => $sectionNames['top-10'],
                            'data' => $top_10,
                        ],
                        'popular_language' => [
                            'name' => $sectionNamesAdditional['enjoy-in-your-native-tongue'] ?? 'Popular Language',
                            'data' => $popular_language,
                        ],
                        'popular_movie' => [
                            'name' => $sectionNamesAdditional['popular-movies'] ?? 'Popular Movies',
                            'data' => $popular_movie,
                        ],
                        'pay_per_view' => $payPerViewContent,
                        'custom_ads' => $customAds,
                    ];
        });

        $responseData = $cachedResult['data'];

       return response()->json([
           'status' => true,
           'data' => $responseData,
           'message' => __('messages.dashboard_detail'),
       ], 200);
    }
    public function getPayPerViewUnlockedContent(Request $request)
    {
        $payPerViewContent = [];
        $user_id = $request->query('user_id');


        // Movies
       $movies = MoviesResource::collection(
          Entertainment::where('movie_access', 'pay-per-view')
              ->where('type', 'movie')
              ->where('status', 1)
              ->where('deleted_at', null)
              ->when(request()->has('is_restricted'), function ($query) {
                  $query->where('is_restricted', request()->is_restricted);
              })
              ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                  $query->where('is_restricted', 0);
              })
              ->get()
      )->map(function ($item) use ($user_id) {
          $item->user_id = $user_id;
          return $item;
      })->toArray(request());

      $payPerViewContent = array_merge($payPerViewContent, $movies);

        // TV Shows
        $tvshows = TvshowResource::collection(
            Entertainment::where('movie_access', 'pay-per-view')
                ->where('type', 'tvshow')
                ->where('status', 1)
                ->where('deleted_at', null)
                ->get()
        )->map(function ($item) use ($user_id) {
            $item->user_id = $user_id;
            return $item;
        })->toArray(request());
        $payPerViewContent = array_merge($payPerViewContent, $tvshows);

        // Videos
        $videos = VideoResource::collection(
            Video::where('access', 'pay-per-view')
                ->where('status', 1)
                ->where('deleted_at', null)
                  ->when(request()->has('is_restricted'), function ($query) {
                  $query->where('is_restricted', request()->is_restricted);
              })
              ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                  $query->where('is_restricted', 0);
              })
                ->get()
        )->map(function ($item) use ($user_id) {
            $item->user_id = $user_id;
            return $item;
        })->toArray(request());
        $payPerViewContent = array_merge($payPerViewContent, $videos);

        // Seasons
        $seasons = SeasonResource::collection(
            Season::where('access', 'pay-per-view')
                ->where('status', 1)
                ->where('deleted_at', null)
                ->get()
        )->map(function ($item) use ($user_id) {
            $item->user_id = $user_id;
            return $item;
        })->toArray(request());
        $payPerViewContent = array_merge($payPerViewContent, $seasons);

        // Episodes
        $episodes = EpisodeResource::collection(
            Episode::where('access', 'pay-per-view')
                ->where('status', 1)
                ->where('deleted_at', null)
                  ->when(request()->has('is_restricted'), function ($query) {
                  $query->where('is_restricted', request()->is_restricted);
              })
              ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                  $query->where('is_restricted', 0);
              })
                ->get()
        )->map(function ($item) use ($user_id) {
            $item->user_id = $user_id;
            return $item;
        })->toArray(request());
        $payPerViewContent = array_merge($payPerViewContent, $episodes);

        if ($request->is('api/*')) {
            return response()->json([
                'status' => true,
                'data' => $payPerViewContent
            ]);
        }

        return $payPerViewContent;
    }

    public function getPayPerViewUnlockedContentV3(Request $request)
    {
        $device_type = getDeviceType($request);
        $user_id = $request->query('user_id');
        $page = $request->query('page', 1);
        $per_page = $request->query('per_page', null);
        $is_restricted = $request->query('is_restricted');

        if($per_page == null){
            $per_page = 0;
        }
        // Create unique cache key based on request parameters
        $cacheKey = 'pay_per_view_content_v3_' . md5(json_encode([
            'user_id' => $user_id,
            'device_type' => $device_type,
            'page' => $page,
            'per_page' => $per_page,
            'is_restricted' => $is_restricted,
            'is_child_profile' => getCurrentProfileSession('is_child_profile')
        ]));

        // Use cacheApiResponse helper for Redis caching
        $cachedResult = cacheApiResponse($cacheKey, 300, function () use ($request, $user_id, $device_type, $page, $per_page) {
            $userPlanId = Subscription::select('plan_id')
            ->where(['user_id' => $user_id, 'status' => 'active'])
            ->latest()
            ->first();
            $userPlanId = optional($userPlanId)->plan_id ?? 0;
            $deviceTypeResponse = Subscription::checkPlanSupportDevice($user_id, $device_type);
            $deviceTypeResponse = json_decode($deviceTypeResponse->getContent(), true);
            $payPerViewContent = [];

        $isMovieModuleEnabled = isenablemodule('movie') == 1;
        $isTVShowModuleEnabled = isenablemodule('tvshow') == 1;
        $isVideoModuleEnabled = isenablemodule('video') == 1;

        if ($isMovieModuleEnabled) {
            $movies = Entertainment::where('movie_access', 'pay-per-view')
                ->where('type', 'movie')
                ->where('status', 1)
                ->when(request()->has('is_restricted'), function ($query) {
                    $query->where('is_restricted', request()->is_restricted);
                })
                ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                    $query->where('is_restricted', 0);
                })
                ->get();

            $movies->each(function ($movie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type){
                $movie->movie_access = 'pay-per-view';
                $movie->user_id = $user_id;
                $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $movie->access = 'pay-per-view';
                $movie = setContentAccess($movie, $user_id, $userPlanId);

                $movie->poster_image = $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
            });

            $movies = MoviesResourceV3::collection($movies)->toArray(request());
            $payPerViewContent = array_merge($payPerViewContent, $movies);
        }

        if ($isTVShowModuleEnabled) {
            $tvshows = Entertainment::where('movie_access', 'pay-per-view')
                ->where('type', 'tvshow')
                ->where('status', 1)
                ->get();


            $tvshows->each(function ($tvshows) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type){
                $tvshows->tvshows_access = 'pay-per-view';
                $tvshows->user_id = $user_id;
                $tvshows->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $tvshows->access = 'pay-per-view';
                $tvshows = setContentAccess($tvshows, $user_id, $userPlanId);
                $tvshows->poster_image = $device_type == 'tv' ? $tvshows->poster_tv_url : $tvshows->poster_url ?? null;
            });
            $tvshows = TvshowResourceV3::collection($tvshows)->toArray(request());
            $payPerViewContent = array_merge($payPerViewContent, $tvshows);
        }

        if ($isVideoModuleEnabled) {
        $videos = Video::where('access', 'pay-per-view')
            ->where('status', 1)
            ->when(request()->has('is_restricted'), function ($query) {
                $query->where('is_restricted', request()->is_restricted);
            })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            })
            ->get();

        $videos->each(function ($video) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
            $video->access = 'pay-per-view';
            $video->user_id = $user_id;
            $video->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
            $video = setContentAccess($video, $user_id, $userPlanId);
            $video->poster_image = $device_type == 'tv' ? $video->poster_tv_url : $video->poster_url ?? null;
        });




        $videos = VideoResourceV3::collection($videos)->toArray(request());
        $payPerViewContent = array_merge($payPerViewContent, $videos);
    }

        if ($isTVShowModuleEnabled) {
            $seasons = Season::where('access', 'pay-per-view')
                ->where('status', 1)
                ->get();

            $seasons->each(function ($season) use ($user_id,  $userPlanId, $deviceTypeResponse, $device_type) {
                $season->access = 'pay-per-view';
                $season->user_id = $user_id;
                $season->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $season = setContentAccess($season, $user_id, $userPlanId);
                $season->poster_image = $device_type == 'tv' ? $season->poster_tv_url : $season->poster_url ?? null;
            });

            $seasons = SeasonResourceV3::collection($seasons)->toArray(request());
            $payPerViewContent = array_merge($payPerViewContent, $seasons);
        }

        if ($isTVShowModuleEnabled) {
            $episodes = Episode::where('access', 'pay-per-view')
                ->where('status', 1)
                ->with('seasondata')
                ->when(request()->has('is_restricted'), function ($query) {
                    $query->where('is_restricted', request()->is_restricted);
                })
                ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                    $query->where('is_restricted', 0);
                })
                ->get();

            $episodes->each(function ($episode) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type) {
                $episode->access = 'pay-per-view';
                $episode->user_id = $user_id;
                $episode->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $episode->poster_image =  $device_type == 'tv' ? $episode->poster_tv_url : $episode->poster_url ?? null;
                $episode->download_data = [];
                $episode->tv_show_data = [
                    'id' => $episode->seasondata ? $episode->seasondata->id : null,
                    'season_id' => $episode->seasondata ? $episode->seasondata->id : null,
                ];
                $episode = setContentAccess($episode, $user_id, $userPlanId);
            });

            $episodes = EpisodeResourceV3::collection($episodes)->toArray(request());
            $payPerViewContent = array_merge($payPerViewContent, $episodes);
        }
        if ($per_page > 0) {
            $offset = ($page - 1) * $per_page;
            $paginatedContent = array_slice($payPerViewContent, $offset, $per_page);
        } else {
            $paginatedContent = $payPerViewContent;
        }

        if ($request->is('api/*')) {
            return [
                'status' => true,
                'data' => $paginatedContent,
            ];
        }

        return $paginatedContent;
        });

         $result = $cachedResult;
        if (isset($cachedResult['data']) && is_array($cachedResult['data'])) {
            $result = $cachedResult['data'];
        }

        if ($request->is('api/*')) {
            return response()->json($result);
        }

        return $result;
    }

    /**
     * Get slider data based on type and type_id
     */
    public function getSliderData($type, $typeId, $userId = null, $request = null)
    {
        $data = null;

        switch ($type) {
            case 'movie':
            case 'tvshow':
                $entertainment = Entertainment::with('plan')->select('*');

                isset($request->is_restricted) && $entertainment = $entertainment->where('is_restricted', $request->is_restricted);
                (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                    $entertainment = $entertainment->where('is_restricted',0);

                $entertainment = $entertainment->where('id',$typeId)->first();

                if ($entertainment) {
                    $entertainment['is_watch_list'] = \Modules\Entertainment\Models\Watchlist::where('entertainment_id', $typeId)
                        ->where('user_id', $userId)
                        ->where('profile_id',$request->profile_id ?? null)
                        ->exists();

                    $entertainment->user_id = $userId ?? null;
                    $data = $type === 'movie' ? new MoviesResource($entertainment) : new TvshowResource($entertainment);
                }
                break;

            case 'livetv':
                $livetv = LiveTvChannel::find($typeId);
                if ($livetv) {
                    $data = new LiveTvChannelResource($livetv);
                }
                break;

            case 'video':
                $video = Video::select('*');

                isset($request->is_restricted) && $video = $video->where('is_restricted', $request->is_restricted);
                (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                    $video = $video->where('is_restricted',0);

                $video = $video->where('id',$typeId)->first();
                if ($video) {
                    $video->user_id = $userId ?? null;
                    $data = new VideoResource($video);
                }
                break;
        }

        return $data;
    }
}
