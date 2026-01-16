<?php

namespace Modules\Entertainment\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Entertainment\Models\Entertainment;
use Modules\Entertainment\Transformers\SeasonResourceV3;
use Modules\Entertainment\Transformers\EpisodeResourceV3;
use Modules\Entertainment\Transformers\MovieDetailDataResource;;
use Modules\Entertainment\Transformers\TvshowDetailResource;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\Like;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Episode\Models\Episode;
use Modules\Entertainment\Transformers\EpisodeResource;
use Modules\Entertainment\Transformers\EpisodeDetailResource;
use Modules\Entertainment\Transformers\SearchResource;
use Modules\Entertainment\Transformers\ComingSoonResource;
use Carbon\Carbon;
use Modules\Entertainment\Models\UserReminder;
use Modules\Entertainment\Models\EntertainmentView;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Genres\Models\Genres;
use Modules\Video\Models\Video;
use Modules\Video\Transformers\VideoResource;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSearchHistory;
use Modules\Season\Models\Season;
use Modules\Entertainment\Transformers\SeasonResource;
use Modules\CastCrew\Models\CastCrew;
use Modules\CastCrew\Transformers\CastCrewListResource;
use Modules\Entertainment\Transformers\EpisodeDetailResourceV2;
use Modules\Entertainment\Transformers\MovieDetailDataResourceV2;
use Modules\Entertainment\Transformers\MoviesResourceV2;
use Modules\Entertainment\Transformers\TvshowDetailResourceV2;
use Modules\Entertainment\Transformers\TvshowResourceV2;
use DB;
use Modules\Entertainment\Models\Subtitle;
use Modules\Entertainment\Transformers\CommonContentDetails;
use Modules\Subscriptions\Models\Subscription;
use Modules\Entertainment\Models\EntertainmentStreamContentMapping;
use Modules\Episode\Models\EpisodeStreamContentMapping;
use Modules\Entertainment\Models\EntertainmnetDownloadMapping;
use Modules\Episode\Models\EpisodeDownloadMapping;
use Modules\Ad\Models\CustomAdsSetting;
use Modules\Ad\Models\VastAdsSetting;
use Modules\Entertainment\Models\EntertainmentGenerMapping;
use Modules\Video\Models\VideoStreamContentMapping;
use Modules\Video\Models\VideoDownloadMapping;
use Modules\Entertainment\Transformers\CommonContentList;
use Modules\Frontend\Models\PayPerView;
use Modules\Entertainment\Transformers\MoviesResourceV3;
use Modules\Entertainment\Transformers\TvshowResourceV3;
use Modules\Video\Transformers\VideoResourceV3;
use Modules\Entertainment\Transformers\Backend\CommonContentResourceV3;
use Modules\Entertainment\Transformers\Backend\ComingSoonResourceV3;
use Illuminate\Support\Facades\Cache;
use Modules\Entertainment\Transformers\ContentDetailsCastCrewV3;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\LiveTV\Transformers\LiveTvChannelResourceV3;
class EntertainmentsController extends Controller
{

    public function movieListV3(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $movieList = Entertainment::query()
            ->where('deleted_at', null)
            ->where('status', 1)
            ->released();

        if (empty($request->language) && empty($request->genre_id) && empty($request->actor_id)) {
            $movieList->where('type', 'movie');
        }

        if ($request->has('is_restricted')) {
            $movieList->where('is_restricted', $request->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile'))) {
            $movieList->where('is_restricted', 0);
        }

        $movieList->with([
            'entertainmentGenerMappings',
            'plan',
            'entertainmentReviews',
            'entertainmentTalentMappings',
            'entertainmentStreamContentMappings',
            'entertainmentDownloadMappings'
        ]);


        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $movieList->where('name', 'like', "%{$searchTerm}%");
        }


        if ($request->filled('genre_id')) {
            $genreId = $request->genre_id;
            $movieList->whereHas('entertainmentGenerMappings', function ($q) use ($genreId) {
                $q->where('genre_id', $genreId);
            });
        }


        if ($request->filled('actor_id')) {
            $actorId = $request->actor_id;

            $movieList->whereHas('entertainmentTalentMappings', function ($q) use ($actorId) {
                $q->where('talent_id', $actorId);
            });


            $allowedTypes = [];
            if (isenablemodule('movie')) $allowedTypes[] = 'movie';
            if (isenablemodule('tvshow')) $allowedTypes[] = 'tvshow';

            if (!empty($allowedTypes)) {
                $movieList->whereIn('type', $allowedTypes);
            }
        }


        if ($request->filled('language')) {
            $movieList->where('language', $request->language);
        }


        $movies = $movieList->orderByDesc('id')->paginate($perPage);
        $responseData = CommonContentResourceV3::collection($movies);


        if ($request->boolean('is_ajax')) {

            $html = '';

            if (!empty($responseData)) {
                $html .= view('frontend::components.card.card_movie', ['values' => $responseData->toArray($request)])->render();
            }

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.movie_list'),
                'hasMore' => $movies->hasMorePages(),
            ], 200);
        }

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.movie_list'),
        ], 200);
    }

    public function tvshowListV3(Request $request)
{
    $perPage = $request->input('per_page', 10);

    // Base query
    $tvshowList = Entertainment::query()
        ->select([
            'entertainments.id',
            'entertainments.name',
            'entertainments.slug',
            'entertainments.description',
            'entertainments.type',
            'entertainments.plan_id',
            'plan.level as plan_level',
            'entertainments.language',
            'entertainments.imdb_rating',
            'entertainments.content_rating',
            'entertainments.release_date',
            'entertainments.is_restricted',
            'entertainments.status',
            'entertainments.poster_url as poster_url',
            'entertainments.poster_tv_url as poster_tv_url',
            'entertainments.thumbnail_url as thumbnail_url',
            'entertainments.trailer_url',
            'entertainments.trailer_url_type',
            'entertainments.movie_access',
        ])
        ->join('entertainment_gener_mapping as egm', 'egm.entertainment_id', '=', 'entertainments.id')
        ->leftJoin('plan', 'plan.id', '=', 'entertainments.plan_id')
        ->with('episodeV2')
        ->where('entertainments.type', 'tvshow')
        ->where('entertainments.release_date', '<=', now()->format('Y-m-d'))
        ->whereHas('episodeV2')
        ->where('entertainments.status', 1)
        ->groupBy('entertainments.id');

    // Search filter
    if ($request->filled('search')) {
        $searchTerm = $request->search;
        $tvshowList->where(function($query) use ($searchTerm) {
            $query->where('entertainments.name', 'like', "%{$searchTerm}%")
                ->orWhereHas('entertainmentGenerMappings.genre', function ($genreQuery) use ($searchTerm) {
                    $genreQuery->where('name', 'like', "%{$searchTerm}%");
                });
        });
    }

    if (isset($request->is_restricted)) {
        $tvshowList->where('is_restricted', $request->is_restricted);
    }

    if (!empty(getCurrentProfileSession('is_child_profile'))) {
        $tvshowList->where('is_restricted', 0);
    }


    if ($request->boolean('is_ajax')) {
        $tvshows = $tvshowList->orderByDesc('entertainments.id')->paginate($perPage);
        $responseData = CommonContentResourceV3::collection($tvshows)->toArray($request);

        $html = '';
        if (!empty($responseData)) {
            $html .= view('frontend::components.card.card_tvshow', ['values' => $responseData])->render();
        }

        return response()->json([
            'status' => true,
            'html' => $html,
            'message' => __('movie.tvshow_list'),
            'hasMore' => $tvshows->hasMorePages(),
        ], 200);
    }


    $tvshows = $tvshowList->orderByDesc('entertainments.id')->paginate($perPage);
    $responseData = CommonContentResourceV3::collection($tvshows)->toArray($request);


    return response()->json([
        'status' => true,
        'data' => $responseData,
        'message' => __('movie.tvshow_list'),
    ], 200);
}





    public function movieDetails(Request $request)
    {


        $movieId = $request->movie_id;

        $cacheKey = 'movie_' . $movieId . '_'.$request->profile_id;


            $movie = Entertainment::where('id', $movieId)->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews', 'entertainmentTalentMappings', 'entertainmentStreamContentMappings', 'entertainmentDownloadMappings', 'entertainmentSubtitleMappings')->first();
            $movie['reviews'] = $movie->entertainmentReviews ?? null;

            if ($request->has('user_id')) {

                $user_id = $request->user_id;
                $movie['is_watch_list'] = (int) WatchList::where('entertainment_id', $movieId)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->exists();
                $movie['is_likes'] = Like::where('entertainment_id', $movieId)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->where('is_like', 1)->exists();
                $movie['is_download'] = EntertainmentDownload::where('entertainment_id', $movieId)->where('device_id',$request->device_id)->where('user_id', $user_id)
                ->where('entertainment_type', 'movie')->where('is_download', 1)->exists();
                $movie['your_review'] = $movie->entertainmentReviews ? optional($movie->entertainmentReviews)->where('user_id', $user_id)->first() : null;

                if ($movie['your_review']) {
                    $movie['reviews'] = $movie['reviews']->where('user_id', '!=', $user_id);
                }

                $continueWatch = ContinueWatch::where('entertainment_id', $movie->id)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->where('entertainment_type', 'movie')->first();
                $movie['continue_watch'] = $continueWatch;
            }
            $responseData = new MovieDetailDataResource($movie);


        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.movie_details'),
        ], 200);
    }



    public function tvshowDetails(Request $request)
    {

        $tvshow_id = $request->tvshow_id;


            $tvshow = Entertainment::where('id', $tvshow_id)->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews', 'entertainmentTalentMappings', 'season', 'episode')->first();
            $tvshow['reviews'] = $tvshow->entertainmentReviews ?? null;

            if ($request->has('user_id')) {
                $user_id = $request->user_id;
                $tvshow['user_id'] = $user_id;
                $tvshow['is_watch_list'] = (int) WatchList::where('entertainment_id', $request->tvshow_id)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->exists();
                $tvshow['is_likes'] = Like::where('entertainment_id', $request->tvshow_id)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->where('is_like', 1)->exists();
                $tvshow['your_review'] =  $tvshow->entertainmentReviews ? $tvshow->entertainmentReviews->where('user_id', $user_id)->first() :null;

                if ($tvshow['your_review']) {
                    $tvshow['reviews'] = $tvshow['reviews']->where('user_id', '!=', $user_id);
                }
            }

            $responseData = new TvshowDetailResource($tvshow);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.tvshow_details'),
        ], 200);
    }

    public function saveDownload(Request $request)
    {
        $user = auth()->user();
        $download_data = $request->all();
        $download_data['user_id'] = $user->id;

        $download = EntertainmentDownload::where('entertainment_id', $request->entertainment_id)->where('user_id', $user->id)->where('entertainment_type', $request->entertainment_type)->first();

        if (!$download) {
            $result = EntertainmentDownload::create($download_data);

            if ($request->entertainment_type == 'movie') {

                Cache::flush();

            } else if ($request->entertainment_type == 'episode') {
                Cache::flush();

            }

            return response()->json(['status' => true, 'message' => __('movie.movie_download')]);
        } else {
            return response()->json(['status' => true, 'message' => __('movie.already_download')]);
        }
    }

    public function episodeList(Request $request)
    {

        $perPage = $request->input('per_page', 10);
        $user_id = $request->user_id;
        $episodeList = Episode::where('status', 1)->with('entertainmentdata', 'plan', 'EpisodeStreamContentMapping', 'episodeDownloadMappings');

        if ($request->has('tvshow_id')) {
            $episodeList = $episodeList->where('entertainment_id', $request->tvshow_id);
        }
        if ($request->has('season_id')) {
            $episodeList = $episodeList->where('season_id', $request->season_id);
        }

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $episodeList->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%");
            });
        }

        $shouldSortByEpisodeNumber = false;
        if ($request->has('season_id')) {
            $allEpisodesInSeason = Episode::where('season_id', $request->season_id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->get();

            $shouldSortByEpisodeNumber = $allEpisodesInSeason->count() > 0 &&
                $allEpisodesInSeason->every(function ($episode) {
                    return !is_null($episode->episode_number);
                });
        }

        if ($shouldSortByEpisodeNumber) {
            $episodes = $episodeList
                        ->orderByRaw('CAST(episode_number AS UNSIGNED) ASC')
                        ->paginate($perPage);
        } else {
            $episodes = $episodeList->orderBy('id', 'asc')->paginate($perPage);
        }

        $responseData = EpisodeResource::collection(
            $episodes->map(function ($episode) use ($user_id) {
                return new EpisodeResource($episode, $user_id);
            })
        );

        if ($request->has('is_ajax') && $request->is_ajax == 1) {

            $html = '';

            foreach ($responseData->toArray($request) as $index => $value) {
                $html .= '<div class="col">';
                $html .= view('frontend::components.card.card_episode', [
                    'data' => $value,
                    'index' => $index
                ])->render();
                $html .= '</div>';
            }

            $hasMore = $episodes->hasMorePages();

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.episode_list'),
                'hasMore' => $hasMore,
            ], 200);
        }


        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.episode_list'),
        ], 200);
    }

    public function episodeListV3(Request $request)
    {
        $device_type = getDeviceType($request);
        $perPage = $request->input('per_page', 10);
        $user_id = $request->user_id;
        $tvshow_id = $request->input('tv_show_id');

        // Create unique cache key based on all relevant parameters
        $cacheKey = 'episode_list_v3_'. md5(json_encode([
            'user_id' => $user_id,
            'device_type' => $device_type,
            'per_page' => $perPage,
            'tvshow_id' => $request->input('tvshow_id'),
            'season_id' => $request->input('season_id'),
            'search' => $request->input('search'),
            'download_quality' => $request->input('download_quality'),
            'page' => $request->input('page', 1),
            'is_ajax' => $request->input('is_ajax', 0)
        ]));

        // Use cacheApiResponse helper with 5 minutes TTL
        $cachedResult = cacheApiResponse($cacheKey, 300, function() use ($request, $device_type, $perPage, $user_id) {
            $episodeList = Episode::where('status', 1)
                ->with('entertainmentdata', 'plan', 'EpisodeStreamContentMapping', 'episodeDownloadMappings');

            if ($request->has('tvshow_id')) {
                $episodeList->where('entertainment_id', $request->tvshow_id);
            }
            if ($request->has('season_id')) {
                $episodeList->where('season_id', $request->season_id);
            }
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $episodeList->where('name', 'like', "%{$searchTerm}%");
            }
            if (isset($request->is_restricted)) {
                $episodeList->where('is_restricted', $request->is_restricted);
            }

            // Check if all episodes have episode_number (only when season_id is provided)
            $shouldSortByEpisodeNumber = false;
            if ($request->has('season_id')) {
                $allEpisodesInSeason = Episode::where('season_id', $request->season_id)
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->get();

                $shouldSortByEpisodeNumber = $allEpisodesInSeason->count() > 0 &&
                    $allEpisodesInSeason->every(function ($episode) {
                        return !is_null($episode->episode_number);
                    });
            }

            // Sort by episode_number if all episodes have it, otherwise by id
            if ($shouldSortByEpisodeNumber) {
                $episodes = $episodeList
                ->orderByRaw('CAST(episode_number AS UNSIGNED) ASC')
                ->paginate($perPage);
            } else {
                $episodes = $episodeList->orderBy('id', 'asc')->paginate($perPage);
            }
            // Get device support info
            $getDeviceTypeData = Subscription::checkPlanSupportDevice($user_id, $device_type);
            $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);

            // Get user's active plan
            $userLevel = Subscription::select('plan_id')
                ->where(['user_id' => $user_id, 'status' => 'active'])
                ->latest()
                ->first();
            $userPlanId = $userLevel->plan_id ?? null;
            $profile_id = getCurrentProfile($user_id, $request);

            // Map over paginator collection safely
            $episodes->getCollection()->transform(function ($episode) use ($device_type, $deviceTypeResponse, $userPlanId, $user_id, $profile_id, $request) {
                // Poster image
                $episode->poster_image = $device_type == 'tv'
                    ? setBaseUrlWithFileName($episode->poster_tv_url, 'image', 'episode')
                    : setBaseUrlWithFileName($episode->poster_url, 'image', 'episode');

                // Device supported
                $episode->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] ? 1 : 0;

                // Required plan level
                if (!is_null($episode->plan_id) && !is_null($userPlanId)) {
                    $episode->required_plan_level = $userPlanId >= $episode->plan_id ? 1 : 0;
                } else {
                    $episode->required_plan_level = 0;
                }
                $episode->type = 'episode';
                $episode = setContentAccess($episode, $user_id, $userPlanId);

                if(isset($episode->access) && $episode->access == 'pay-per-view'){
                    $rental = [
                        'price' => (float)$episode->price,
                        'discount' => (int)$episode->discount,
                        'access_duration' => $episode->access_duration,
                        'availability_days' => $episode->available_for,
                        'access' => $episode->purchase_type,
                    ];
                    if ($rental['price'] > 0 && $rental['discount'] > 0) {
                        $rental['discounted_price'] = round(
                            $rental['price'] - ($rental['price'] * $rental['discount'] / 100),
                            2
                        );
                    } else {
                        $rental['discounted_price'] = $rental['price'];
                    }
                    $episode->rental = $rental;
                } else {
                    $episode->rental = [];
                }

                $continuewatch = ContinueWatch::where('user_id', $user_id)
                    ->where('profile_id', $profile_id)
                    ->where('entertainment_type', 'episode')
                    ->where('entertainment_id', $episode->id)
                    ->first();


                $episode->watched_time = $continuewatch->watched_time ?? '00:00:01';
                $episode->total_watched_time = $continuewatch->total_watched_time ?? '00:00:01';

                $downloadMappingsQuery = EpisodeDownloadMapping::where('episode_id', $episode->id);

                if ($request->download_quality) {
                    $downloadMappingsQuery->where('quality', $request->download_quality);
                }

                $downloadMappings = $downloadMappingsQuery->get();

                $defaultDownload = [];

                if (!empty($episode->download_type) || !empty($episode->download_url)) {
                    $defaultDownload[] = [
                        'id'        => $episode->id,
                        'url_type'  => $episode->download_type ?? null,
                        'url'       => ($episode->download_type === 'Local')
                                        ? setBaseUrlWithFileName($episode->download_url ?? null, 'video', 'episode')
                                        : ($episode->download_url ?? null),
                        'quality'   => 'default_quality',
                    ];
                }

                $mappingDownloads = [];

                if (!empty($downloadMappings)) {
                    foreach ($downloadMappings as $mapping) {
                        $mappingDownloads[] = [
                            'id'        => $mapping->id,
                            'url_type'  => $mapping->type,
                            'url'       => ($mapping->type === 'Local')
                                            ? setBaseUrlWithFileName($mapping->url, 'video', 'episode')
                                            : $mapping->url,
                            'quality'   => $mapping->quality,
                        ];
                    }
                }

                $mergedDownloads = array_merge($defaultDownload, $mappingDownloads);

                $episode->download_data = [
                    'download_enable' => $episode->download_status ?? 0,
                    'download_quality' => $mergedDownloads
                ];
                $episode->tv_show_data = [
                    'id' => $episode->seasondata->id,
                    'season_id' => $episode->seasondata->id,
                ];

                $episode->season_data = $episode->entertainmentdata?->season?->map(function ($season) {
                    return [
                        'id' => $season->id,
                        'name' => $season->name,
                        'season_id' => $season->id,
                        'total_episode' => $season->episodes()->count(),
                    ];
                }) ?? collect();

                return $episode;
            });

            // Wrap in resource
            $responseData = EpisodeResourceV3::collection($episodes);

            // Handle AJAX rendering
            if ($request->has('is_ajax') && $request->is_ajax == 1) {
                $html = '';
                foreach ($responseData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_episode', [
                        'data' => $value,
                        'index' => $index
                    ])->render();
                }

                return [
                    'status' => true,
                    'html' => $html,
                    'message' => __('movie.episode_list'),
                    'hasMore' => $episodes->hasMorePages(),
                ];
            }

            return [
                'status' => true,
                'data' => $responseData,
                'message' => __('movie.episode_list'),
            ];
        });

        // Return cached response
        return response()->json($cachedResult['data'], 200);
    }




    public function episodeDetails(Request $request)
    {
        $user_id = $request->user_id;
        $episode_id = $request->episode_id;


            $episode = Episode::where('id', $episode_id)->with('entertainmentdata', 'plan', 'EpisodeStreamContentMapping', 'episodeDownloadMappings','subtitles')->first();

            if ($request->has('user_id')) {
                $continueWatch = ContinueWatch::where('entertainment_id', $episode->id)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->where('entertainment_type', 'episode')->first();
                $episode['continue_watch'] = $continueWatch;

                $episode['is_download'] = EntertainmentDownload::where('entertainment_id', $episode->id)->where('user_id',  $user_id)->where('entertainment_type', 'episode')->where('is_download', 1)->exists();

                $genre_ids = $episode->entertainmentData->entertainmentGenerMappings->pluck('genre_id');

                $moreItems = Entertainment::where('type', 'tvshow')
                    ->whereHas('entertainmentGenerMappings', function ($query) use ($genre_ids) {
                        $query->whereIn('genre_id', $genre_ids);
                    });

                isset($request->is_restricted) && $moreItems = $moreItems->where('is_restricted', $request->is_restricted);
                (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $moreItems = $moreItems->where('is_restricted',0);

                $episode['moreItems'] = $moreItems->where('id', '!=', $episode->id)
                    ->orderBy('id', 'desc')
                    ->get();

                $episode['genre_data'] = Genres::whereIn('id', $genre_ids)->get();
            }


            $genre_ids = $episode->entertainmentData->entertainmentGenerMappings->pluck('genre_id');

            $episode['moreItems'] = Entertainment::where('type', 'tvshow')
                ->whereHas('entertainmentGenerMappings', function ($query) use ($genre_ids) {
                    $query->whereIn('genre_id', $genre_ids);
                })
                ->where('id', '!=', $episode->id)
                ->orderBy('id', 'desc')
                ->get();

            $episode['genre_data'] = Genres::whereIn('id', $genre_ids)->get();

            $responseData = new EpisodeDetailResource($episode);




        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.episode_details'),
        ], 200);
    }

    public function searchList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $movieList = Entertainment::query()
        ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews',
         'entertainmentTalentMappings', 'entertainmentStreamContentMappings')
         ->where('type', 'movie');

        $movieList = $movieList->where('status', 1);

        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $movieList = $movieList->where('is_restricted',0);

        isset($request->is_restricted) && $movieList = $movieList->where('is_restricted', $request->is_restricted);

        $movies = $movieList->orderBy('updated_at', 'desc');
        $movies = $movies->paginate($perPage);

        $responseData = new SearchResource($movies);
        if(isenablemodule('movie') == 1){
            $responseData = $responseData;

        }else{
            $responseData = [];
        }

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.search_list'),
        ], 200);
    }

    public function getSearch(Request $request)
    {

        $movieList = Entertainment::query()->whereDate('release_date', '<=', Carbon::now())
            ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews', 'entertainmentTalentMappings', 'entertainmentStreamContentMappings')
            ->where('type', 'movie')->where('status', 1)->where('deleted_at', null);


        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;

            if (strtolower($searchTerm) == 'movie' || strtolower($searchTerm) == 'movies') {
                $movieList->where('type', 'movie');
            } else {
                $movieList->where(function($movieList) use($searchTerm) {
                    $movieList->where('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('entertainmentGenerMappings.genre', function ($genreQuery) use ($searchTerm) {
                        $genreQuery->where('name', 'like', "%{$searchTerm}%");
                    });
                });
            }

        }

        isset($request->is_restricted) && $movieList = $movieList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $movieList = $movieList->where('is_restricted',0);

        $movieList = $movieList->orderBy('updated_at', 'desc')->get();


        $movieData = (isenablemodule('movie') == 1) ? CommonContentResourceV3::collection($movieList) : [];
        $tvshowList = Entertainment::where('status', 1)->where('type', 'tvshow')
            ->whereDate('release_date', '<=', Carbon::now())
            ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews',
            'entertainmentTalentMappings', 'season', 'episode')->whereHas('episode')->where('deleted_at', null);

        isset($request->is_restricted) && $tvshowList = $tvshowList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $tvshowList = $tvshowList->where('is_restricted',0);

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $tvshowList->where('name', 'like', "%{$searchTerm}%")
            ->orWhereHas('entertainmentGenerMappings.genre', function ($query) use ($searchTerm) {
                $query->where('name', '=', "%{$searchTerm}%");
            });
        }

        $tvshowList = $tvshowList->orderBy('updated_at', 'desc')->where('type', 'tvshow')->get();
        $tvshowData = (isenablemodule('tvshow') == 1) ? CommonContentResourceV3::collection($tvshowList) : [];


        $videoList = Video::query()->whereDate('release_date', '<=', Carbon::now())->with('VideoStreamContentMappings', 'plan');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $videoList->where('name', 'like', "%{$searchTerm}%");
        }

        $videoList = $videoList->where('status', 1)->orderBy('updated_at', 'desc')->take(6)->get();
        $videoData = (isenablemodule('video') == 1) ? VideoResourceV3::collection($videoList) : [];


        $seasonList = Season::query()->with('episodes');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $seasonList->where('name', 'like', "%{$searchTerm}%");
        }

        $seasonList = $seasonList->where('status', 1)->orderBy('updated_at', 'desc')->get();
        $seasonData = (isenablemodule('tvshow') == 1) ? SeasonResourceV3::collection($seasonList) : [];


        $episodeList = Episode::query()->whereDate('release_date', '<=', Carbon::now())->with('seasondata');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $episodeList->where('name', 'like', "%{$searchTerm}%");
        }

        $episodeList = $episodeList->where('status', 1)->orderBy('updated_at', 'desc')->get();
        $episodeData = (isenablemodule('tvshow') == 1) ? EpisodeResourceV3::collection($episodeList) : [];


        $actorList = CastCrew::query()->where('type', 'actor')->with('entertainmentTalentMappings');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $actorList->where('name', 'like', "%{$searchTerm}%");
        }

        $actorList = $actorList->orderBy('updated_at', 'desc')->get();
        $actorData = CastCrewListResource::collection($actorList);


        $directorList = CastCrew::query()->where('type', 'director')->with('entertainmentTalentMappings');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $directorList->where('name', 'like', "%{$searchTerm}%");
        }

        $directorList = $directorList->orderBy('updated_at', 'desc')->take(6)->get();
        $directorData = CastCrewListResource::collection($directorList);



        if ($request->has('is_ajax') && $request->is_ajax == 1) {

            $html = '';

            if($movieData && $movieData->isNotEmpty()) {

                foreach ($movieData->toArray($request) as $index => $value) {

                    $html .= view('frontend::components.card.card_entertainment', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($tvshowData && $tvshowData->isNotEmpty()) {

                foreach ($tvshowData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_entertainment', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($videoData && $videoData->isNotEmpty()) {

                foreach ($videoData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_video', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($seasonData && $seasonData->isNotEmpty()) {

                foreach ($seasonData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_season', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($episodeData && $episodeData->isNotEmpty()) {

                foreach ($episodeData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_season', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($actorData && $actorData->isNotEmpty()) {

                foreach ($actorData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_castcrew', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($directorData && $directorData->isNotEmpty()) {

                foreach ($directorData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_castcrew', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }

            if (empty($movieData) && empty($tvshowData) && empty($videoData) && empty($seasonData) && empty($episodeData) && empty($actorData) && empty($directorData)) {
                $html .= '';
            }


            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.search_list'),

            ], 200);
        }

        return response()->json([
            'status' => true,
            'movieList' => $movieData,
            'tvshowList' => $tvshowData,
            'videoList' => $videoData,
            'seasonList' => $seasonData,
            'message' => __('movie.search_list'),
        ], 200);
    }


    public function getSearchV3(Request $request)
    {
        $device_type = getDeviceType($request);

        // Create cache key based on request parameters
        $cacheKey = 'search_v3_' . md5(json_encode($request->all())) . '_' . $device_type;

        // Check if search term is provided
        if (
            (empty($request->search) || trim($request->search) === '') &&
            empty($request->genre_id) &&
            empty($request->actor_id) &&
            empty($request->director_id) &&
            empty($request->access) &&
            empty($request->language)
        ){
            return response()->json([
                'status' => true,
                'movieList' => [],
                'tvshowList' => [],
                'videoList' => [],
                'seasonList' => [],
                'episodeList' => [],
                'channelList' => [],
                'message' => __('movie.search_list'),
            ], 200);
        }

        // Use cacheApiResponse helper with 5 minutes TTL
        $cachedResult = cacheApiResponse($cacheKey, 300, function() use ($request, $device_type) {
            $user_id = $request->user_id;
            if($user_id){
                $profile_id = getCurrentProfile($user_id, $request);
                $getDeviceTypeData = Subscription::checkPlanSupportDevice($user_id, $device_type);
                $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
                $userLevel = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();
                $userPlanId = $userLevel->plan_id ?? 0;
            }else{
                $deviceTypeResponse = [];
                $userPlanId = 0;
            }

            $defaultIncludedTypes = collect(['movie', 'tvshow', 'video', 'season', 'episode', 'livetv', 'channel']);
            $searchTypes = collect(explode(',', strtolower((string) ($request->search_type ?? ''))))
                ->map(fn ($type) => trim($type))
                ->filter()
                ->values();

            $shouldIncludeType = function (string $type, array $aliases = [], bool $defaultInclude = false) use ($searchTypes, $defaultIncludedTypes) {
                if ($searchTypes->isEmpty()) {
                    return $defaultInclude ?: $defaultIncludedTypes->contains($type);
                }

                $targets = collect(array_merge([$type], $aliases))
                    ->map(fn ($target) => strtolower($target))
                    ->filter();

                return $searchTypes->contains(function ($value) use ($targets) {
                    return $targets->contains($value);
                });
            };
            if ($shouldIncludeType('movie', [], true)) {
            $movieList = Entertainment::query()
                ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews', 'entertainmentTalentMappings', 'entertainmentStreamContentMappings')
                ->where('type', 'movie')->where('status', 1)->where('deleted_at', null);

            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;

                if (strtolower($searchTerm) == 'movie' || strtolower($searchTerm) == 'movies') {
                    $movieList->where('type', 'movie');
                } else {
                    $movieList->where(function($query) use($searchTerm) {
                        $query->where('name', 'like', "%{$searchTerm}%")
                        ->orWhereHas('entertainmentGenerMappings.genre', function ($genreQuery) use ($searchTerm) {
                            $genreQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                    });
                }
            }

            isset($request->is_restricted) && $movieList = $movieList->where('is_restricted', $request->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                    $movieList = $movieList->where('is_restricted',0);

            // Filter by director_id if provided
            if ($request->has('director_id') && !empty($request->director_id)) {

                $movieList->whereHas('entertainmentTalentMappings', function ($query) use ($request) {
                    $query->whereIn('talent_id', explode(',', $request->director_id))
                          ->whereHas('talentprofile', function ($subQuery) {
                              $subQuery->where('type', 'director');
                          });
                });
            }

            if ($request->has('is_released') && !empty($request->is_released)) {
                $movieList->where('release_date', '<=', Carbon::now());
            }

            if ($request->has('actor_id') && !empty($request->actor_id)) {

                $movieList->whereHas('entertainmentTalentMappings', function ($query) use ($request) {
                    $query->whereIn('talent_id', explode(',', $request->actor_id))
                          ->whereHas('talentprofile', function ($subQuery) {
                              $subQuery->where('type', 'actor');
                          });
                });
            }
            if ($request->has('genre_id') && !empty($request->genre_id)) {
                $genreIds = array_filter(array_map('intval', explode(',', $request->genre_id)));
                if (!empty($genreIds)) {
                    $movieList->whereHas('entertainmentGenerMappings', function ($query) use ($genreIds) {
                        $query->whereIn('genre_id', $genreIds);
                    });
                }
            }
            if ($request->has('access') && !empty($request->access)) {
                $movieList->where('movie_access', $request->access);
            }
            if($request->has('language') && !empty($request->language)) {
                $movieList->whereIn('language', explode(',', $request->language));
            }

            $movieList = $movieList->orderBy('updated_at', 'desc')->get();

            if($user_id){


                $movieList = $movieList->map(function($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId) {
                    $item->poster_image = $request->device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item->access = $item->movie_access;
                    $item = setContentAccess($item, $user_id, $userPlanId);
                    $item['isDeviceSupported'] = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    return $item;
                });

            }else{
                $movieList = $movieList->map(function($item) use ($device_type ) {
                    $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item->isDeviceSupported = 0;
                    $item->access = $item->movie_access;
                    $item = setContentAccess($item, null, null);
                    return $item;
                });

            }
            $movieData = (isenablemodule('movie') == 1) ? MoviesResourceV3::collection($movieList) : [];
        }else{
            $movieData = [];
        }

        if ($shouldIncludeType('tvshow', [], true)) {
            $tvshowList = Entertainment::where('status', 1)->where('type', 'tvshow')
            ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews',
            'entertainmentTalentMappings', 'season', 'episode')->whereHas('episode')->where('deleted_at', null);




        isset($request->is_restricted) && $tvshowList = $tvshowList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $tvshowList = $tvshowList->where('is_restricted',0);

        // Filter by director_id if provided
        if ($request->has('director_id') && !empty($request->director_id)) {
            $tvshowList->whereHas('entertainmentTalentMappings', function ($query) use ($request) {
                $query->whereIn('talent_id', explode(',', $request->director_id))
                      ->whereHas('talentprofile', function ($subQuery) {
                          $subQuery->where('type', 'director');
                      });
            });
        }

        if ($request->has('is_released') && !empty($request->is_released)) {
            $tvshowList->where('release_date', '<=', Carbon::now());
        }

        if ($request->has('actor_id') && !empty($request->actor_id)) {
            $tvshowList->whereHas('entertainmentTalentMappings', function ($query) use ($request) {
                $query->whereIn('talent_id', explode(',', $request->actor_id))
                      ->whereHas('talentprofile', function ($subQuery) {
                          $subQuery->where('type', 'actor');
                      });
            });
        }
        if ($request->has('genre_id') && !empty($request->genre_id)) {
            $genreIds = array_filter(array_map('intval', explode(',', $request->genre_id)));
            if (!empty($genreIds)) {
                $tvshowList->whereHas('entertainmentGenerMappings', function ($query) use ($genreIds) {
                    $query->whereIn('genre_id', $genreIds);
                });
            }
        }
        if ($request->has('access') && !empty($request->access)) {
            $tvshowList->where('movie_access', $request->access);
        }
        if($request->has('language') && !empty($request->language)) {
            $tvshowList->whereIn('language', explode(',', $request->language));
        }

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $tvshowList->where(function($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('entertainmentGenerMappings.genre', function ($genreQuery) use ($searchTerm) {
                        $genreQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $tvshowList = $tvshowList->orderBy('updated_at', 'desc')->where('type', 'tvshow')->get();
            if($user_id){
                $tvshowList = $tvshowList->map(function($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId) {
                    $item->poster_image = $request->device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item->access = $item->movie_access;
                    $item = setContentAccess($item, $user_id, $userPlanId);
                    $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $item->season_data = $item->season->map(function($season) {
                        return [
                            'id' => $season->id,
                            'name' => $season->name,
                            'season_id' => $season->id,
                            'total_episode' => $season->episodes()->count(),
                        ];
                    })->values();
                    return $item;
            });
            }else{
                $tvshowList = $tvshowList->map(function($item) use ($device_type ) {
                    $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item->access = $item->movie_access;
                    $item = setContentAccess($item, null, null);
                    $item->has_content_access = 0;
                    $item->required_plan_level = $item->plan_id ?? 0;
                    $item->isDeviceSupported = 0;
                    $item->season_data = $item->season->map(function($season) {
                        return [
                            'id' => $season->id,
                            'name' => $season->name,
                            'season_id' => $season->id,
                            'total_episode' => $season->episodes()->count(),
                        ];
                    })->values();
                    return $item;
                });
            }

        $tvshowData = (isenablemodule('tvshow') == 1) ? TvshowResourceV3::collection($tvshowList) : [];
        }else{
            $tvshowData = [];
        }
        if ($shouldIncludeType('video', [], true)) {
        $videoList = Video::query()->with('VideoStreamContentMappings', 'plan');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $videoList->where('name', 'like', "%{$searchTerm}%");
        }
        if ($request->has('access') && !empty($request->access)) {
            $videoList->where('access', $request->access);
        }

        if ($request->has('is_released') && !empty($request->is_released)) {
            $videoList->where('release_date', '<=', Carbon::now());
        }

        isset($request->is_restricted) && $videoList = $videoList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $videoList = $videoList->where('is_restricted',0);


        $videoList = $videoList->where('status', 1)->orderBy('updated_at', 'desc')->take(6)->get();

        if ($user_id) {
            $videoList = $videoList->map(function($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId) {
                    $item->poster_image = $request->device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
                    $item = setContentAccess($item, $user_id, $userPlanId);
                    $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    return $item;
            });
        }else{
            $videoList = $videoList->map(function($item) use ($device_type ) {
                    $item->poster_image = $device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
                    $item->has_content_access = 0;
                    $item = setContentAccess($item, null, null);
                    $item->required_plan_level = $item->plan_id ?? 0;
                    $item->isDeviceSupported = 0;
                    return $item;
            });
        }
        if($request->has('language') && !empty($request->language) || $request->has('genre_id') && !empty($request->genre_id) || $request->has('actor_id') && !empty($request->actor_id) || $request->has('director_id') && !empty($request->director_id)) {
            $videoList = [];
        }

    $videoData = (isenablemodule('video') == 1) ? VideoResourceV3::collection($videoList) : [];
    }else{
        $videoData = [];
    }

        $channelData = [];
        if (isenablemodule('livetv') == 1 && $shouldIncludeType('livetv', ['channel'], true)) {
            $channelList = LiveTvChannel::query()->where('status', 1)->whereNull('deleted_at');

            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $channelList->where('name', 'like', "%{$searchTerm}%");
            }

            if ($request->has('genre_id') && !empty($request->genre_id)) {
                $categoryIds = array_filter(array_map('intval', explode(',', $request->genre_id)));
                if (!empty($categoryIds)) {
                    $channelList->whereIn('category_id', $categoryIds);
                }
            }

            if ($request->has('access') && !empty($request->access)) {
                $channelList->where('access', $request->access);
            }
            $channelList = $channelList->orderBy('updated_at', 'desc')->get();

            if ($user_id) {
                $channelList = $channelList->map(function ($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId) {
                    $item->poster_image = $request->device_type == 'tv'
                        ? setBaseUrlWithFileName($item->poster_tv_url, 'image', 'livetv')
                        : setBaseUrlWithFileName($item->poster_url, 'image', 'livetv');
                    $item = setContentAccess($item, $user_id, $userPlanId);
                    $item->isDeviceSupported = ($deviceTypeResponse['isDeviceSupported'] ?? false) ? 1 : 0;
                    return $item;
                });
            } else {
                $channelList = $channelList->map(function ($item) use ($device_type) {
                    $item->poster_image = $device_type == 'tv'
                        ? setBaseUrlWithFileName($item->poster_tv_url, 'image', 'livetv')
                        : setBaseUrlWithFileName($item->poster_url, 'image', 'livetv');
                    $item = setContentAccess($item, null, null);
                    $item->isDeviceSupported = 0;
                    return $item;
                });
            }
            if($request->has('language') && !empty($request->language) || $request->has('genre_id') && !empty($request->genre_id) || $request->has('actor_id') && !empty($request->actor_id) || $request->has('director_id') && !empty($request->director_id)) {
                $channelList = [];
            }
            $channelData = LiveTvChannelResourceV3::collection($channelList);
        }

        if ($shouldIncludeType('season', [], true)) {
        $seasonList = Season::query()->with('episodes','entertainmentdata');
        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $seasonList->where('name', 'like', "%{$searchTerm}%");
        }

        // Filter seasons by genre_id based on entertainment_id
        if ($request->has('genre_id') && !empty($request->genre_id)) {
            $genreIds = array_filter(array_map('intval', explode(',', $request->genre_id)));
            if (!empty($genreIds)) {
                $seasonList->whereHas('entertainmentdata', function ($query) use ($genreIds) {
                    $query->whereHas('entertainmentGenerMappings', function ($subQuery) use ($genreIds) {
                        $subQuery->whereIn('genre_id', $genreIds);
                    });
                });
            }
        }

        // Filter seasons by actor_id based on entertainment_id
        if ($request->has('actor_id') && !empty($request->actor_id)) {
            $seasonList->whereHas('entertainmentdata', function ($query) use ($request) {
                $query->whereHas('entertainmentTalentMappings', function ($subQuery) use ($request) {
                    $subQuery->whereIn('talent_id', explode(',', $request->actor_id))
                             ->whereHas('talentprofile', function ($talentQuery) {
                                 $talentQuery->where('type', 'actor');
                             });
                });
            });
        }

        // Filter seasons by director_id based on entertainment_id
        if ($request->has('director_id') && !empty($request->director_id)) {
            $seasonList->whereHas('entertainmentdata', function ($query) use ($request) {
                $query->whereHas('entertainmentTalentMappings', function ($subQuery) use ($request) {
                    $subQuery->whereIn('talent_id', explode(',', $request->director_id))
                             ->whereHas('talentprofile', function ($talentQuery) {
                                 $talentQuery->where('type', 'director');
                             });
                });
            });
        }

        // Filter seasons by language based on entertainment_id
        if ($request->has('language') && !empty($request->language)) {
            $seasonList->whereHas('entertainmentdata', function ($query) use ($request) {
                $query->whereIn('language', explode(',', $request->language));
            });
        }

        $seasonList = $seasonList->where('status', 1)->orderBy('updated_at', 'desc')->get();

        if ($user_id) {
            $seasonList = $seasonList->map(function($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId) {
                $item->poster_image = $request->device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
                $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                $item = setContentAccess($item, $user_id, $userPlanId);
                $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $item->setAttribute('tv_show_data', [
                    'id' => $item->entertainmentdata->id,
                    'name' => $item->entertainmentdata->name,
                ]);
                return $item;
            });
        } else {
            $seasonList = $seasonList->map(function($item) use ($device_type ) {
                $item->poster_image = $device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
                $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                $item = setContentAccess($item, null, null);
                $item->isDeviceSupported = 0;
                $item->setAttribute('tv_show_data', [
                    'id' => $item->entertainmentdata->id,
                    'name' => $item->entertainmentdata->name,
                ]);
                return $item;
             });
        }
        if($request->has('access') && !empty($request->access)) {
            $seasonList->where('access', $request->access);
        }

        $seasonData = (isenablemodule('tvshow') == 1) ? SeasonResourceV3::collection($seasonList) : [];
    }else{
        $seasonData = [];
    }

        if ($shouldIncludeType('episode', [], true)) {
        $episodeList = Episode::query()->with('seasondata','entertainmentdata');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $episodeList->where('name', 'like', "%{$searchTerm}%");
        }

        // Filter episodes by genre_id based on season's entertainment_id
        if ($request->has('genre_id') && !empty($request->genre_id)) {
            $genreIds = array_filter(array_map('intval', explode(',', $request->genre_id)));
            if (!empty($genreIds)) {
                $episodeList->whereHas('seasondata.entertainmentdata', function ($query) use ($genreIds) {
                    $query->whereHas('entertainmentGenerMappings', function ($subQuery) use ($genreIds) {
                        $subQuery->whereIn('genre_id', $genreIds);
                    });
                });
            }
        }

        // Filter episodes by actor_id based on season's entertainment_id
        if ($request->has('actor_id') && !empty($request->actor_id)) {
            $episodeList->whereHas('seasondata.entertainmentdata', function ($query) use ($request) {
                $query->whereHas('entertainmentTalentMappings', function ($subQuery) use ($request) {
                    $subQuery->whereIn('talent_id', explode(',', $request->actor_id))
                             ->whereHas('talentprofile', function ($talentQuery) {
                                 $talentQuery->where('type', 'actor');
                             });
                });
            });
        }

        // Filter episodes by director_id based on season's entertainment_id
        if ($request->has('director_id') && !empty($request->director_id)) {
            $episodeList->whereHas('seasondata.entertainmentdata', function ($query) use ($request) {
                $query->whereHas('entertainmentTalentMappings', function ($subQuery) use ($request) {
                    $subQuery->whereIn('talent_id', explode(',', $request->director_id))
                             ->whereHas('talentprofile', function ($talentQuery) {
                                 $talentQuery->where('type', 'director');
                             });
                });
            });
        }

        // Filter episodes by language based on season's entertainment_id
        if ($request->has('language') && !empty($request->language)) {
            $episodeList->whereHas('seasondata.entertainmentdata', function ($query) use ($request) {
                $query->whereIn('language', explode(',', $request->language));
            });
        }

        // Filter episodes by access
        if ($request->has('access') && !empty($request->access)) {
            $episodeList->where('access', $request->access);
        }

        $episodeList = $episodeList->where('status', 1)->orderBy('updated_at', 'desc')->get();
        if ($user_id) {

            $episodeList = $episodeList->map(function($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId, $profile_id) {

                $continuewatch = ContinueWatch::where('user_id', $user_id)
                ->where('profile_id', $profile_id)
                ->where('entertainment_type', 'episode')
                ->where('entertainment_id', $item->entertainment_id)
                ->first();

                if(isset($continuewatch) && $item->id == $continuewatch->episode_id){
                    $item->watched_time = $continuewatch?->watched_time ?? '00:00:01';
                    $item->total_watched_time = $continuewatch?->total_watched_time ?? '00:00:01';
                }else{
                    $item->watchedtime = '00:00:01';
                    $item->total_watched_time = '00:00:01';
                }
                $item->poster_image = $request->device_type == 'tv' ? $item->poster_tv_url :$item->poster_url;
                $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                $item = setContentAccess($item, $user_id, $userPlanId);
                $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $item->setAttribute('tv_show_data', [
                    'id' => $item->entertainmentdata->id,
                    'name' => $item->entertainmentdata->name,
                    'season_id' => $item->seasondata->id,
                    'total_episode' => $item->seasondata->episodes()->count(),
                ]);
                $item->setAttribute('season_data', [
                    'id' => $item->seasondata->id,
                    'name' => $item->seasondata->name,
                    'season_id' => $item->seasondata->id,
                    'total_episode' => $item->seasondata->episodes()->count(),
                ]);
                return $item;
            });
        } else {
            $episodeList = $episodeList->map(function($item) use ($device_type) {
                $item->poster_image = $device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
                $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                $item = setContentAccess($item, null, null);
                $item->isDeviceSupported = 0;
                $item->setAttribute('tv_show_data', [
                    'id' => $item->entertainmentdata->id,
                    'name' => $item->entertainmentdata->name,
                    'season_id' => $item->seasondata->id,
                    'total_episode' => $item->seasondata->episodes()->count(),
                ]);
                $item->setAttribute('season_data', [
                    'id' => $item->seasondata->id,
                    'name' => $item->seasondata->name,
                    'season_id' => $item->seasondata->id,
                    'total_episode' => $item->seasondata->episodes()->count(),
                ]);
                return $item;
            });
        }
        $episodeData = (isenablemodule('tvshow') == 1) ? EpisodeResourceV3::collection($episodeList) : [];
    }else{
        $episodeData = [];
    }

        $actorsList = [];
        $directorsList = [];
        $actorData = collect([]);
        $directorData = collect([]);

        if ($shouldIncludeType('actor',[], true)) {
            $actorList = CastCrew::query()->where('type', 'actor')->where('status', 1)->where('deleted_at', null)->with('entertainmentTalentMappings');
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $actorList->where('name', 'like', "%{$searchTerm}%");
            }
            $actorData = $actorList->orderBy('updated_at', 'desc')->get();
            foreach ($actorData as $actor) {
                $actorsList[] = [
                    'id' => $actor->id,
                    'name' => $actor->name,
                    'role' => 'actor',
                    'profile_image' => $actor->file_url ? setBaseUrlWithFileName($actor->file_url,'image','castcrew') : null,
                ];
            }
        }

        if ($shouldIncludeType('director',[], true)) {
            $directorList = CastCrew::query()->where('type', 'director')->where('status', 1)->where('deleted_at', null)->with('entertainmentTalentMappings');
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $directorList->where('name', 'like', "%{$searchTerm}%");
            }
            $directorData = $directorList->orderBy('updated_at', 'desc')->get();
            foreach ($directorData as $director) {
                $directorsList[] = [
                    'id' => $director->id,
                    'name' => $director->name,
                    'role' => 'director',
                    'profile_image' => $director->file_url ? setBaseUrlWithFileName($director->file_url,'image','castcrew') : null,
                ];
            }
        }

        if ($request->has('is_ajax') && $request->is_ajax == 1) {

            $html = '';

            if($movieData && $movieData->isNotEmpty()) {

                foreach ($movieData->toArray($request) as $index => $value) {

                    $html .= view('frontend::components.card.card_entertainment', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($tvshowData && $tvshowData->isNotEmpty()) {

                foreach ($tvshowData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_entertainment', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($channelData && $channelData->isNotEmpty()) {
                foreach ($channelData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_tvchannel', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($videoData && $videoData->isNotEmpty()) {

                foreach ($videoData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_video', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($seasonData && $seasonData->isNotEmpty()) {

                foreach ($seasonData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_season', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($episodeData && $episodeData->isNotEmpty()) {

                foreach ($episodeData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_season', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($actorData && $actorData->isNotEmpty()) {

                foreach ($actorData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_castcrew', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($directorData && $directorData->isNotEmpty()) {

                foreach ($directorData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_castcrew', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }

            if (empty($movieData) && empty($tvshowData) && empty($videoData) && empty($channelData) && empty($seasonData) && empty($episodeData) && empty($actorData) && empty($directorData)) {
                $html .= '';
            }

            return [
                'status' => true,
                'html' => $html,
                'message' => __('movie.search_list'),
            ];
        }

        return [
            'status' => true,
            'movieList' => $movieData,
            'tvshowList' => $tvshowData,
            'videoList' => $videoData,
            'channelList' => $channelData,
            'seasonList' => $seasonData,
            'episodeList' => $episodeData,
            'actors_list' => $actorsList,
            'directors_list' => $directorsList,
            'message' => __('movie.search_list'),
        ];

        });

        return response()->json($cachedResult['data'], 200);
    }


    public function comingSoon(Request $request)
    {

        $perPage = $request->input('per_page', 10);
        $todayDate = Carbon::today()->toDateString();
        $device_type = getDeviceType($request);


        $cacheKey = 'coming_soon_'. md5(json_encode($request->all())).($request->is_ajax ? '_html' : '_json');


        $responseData = cache()->remember($cacheKey, 10, function () use ($request, $perPage, $todayDate,$device_type) {

            switch ($request->type) {
                case 'all':
                    // Get Entertainment items (movie, tvshow)
                    $movieList = Entertainment::where('release_date', '>', $todayDate)
                        ->whereIn('type', ['movie', 'tvshow'])
                        ->where('status', 1)
                        ->where('deleted_at',null);

                    // Get Video items
                    $videoList = Video::where('release_date', '>', $todayDate)
                        ->where('status', 1)
                        ->where('deleted_at',null);

                    isset($request->is_restricted) && $movieList = $movieList->where('is_restricted', $request->is_restricted);
                    isset($request->is_restricted) && $videoList = $videoList->where('is_restricted', $request->is_restricted);

                    (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                        $movieList = $movieList->where('is_restricted', 0);
                    (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                        $videoList = $videoList->where('is_restricted', 0);

                    // Apply relationships to both queries
                    $movieList = $movieList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                        'entertainmentGenerMappings',
                        'plan',
                        'entertainmentReviews',
                        'entertainmentTalentMappings',
                        'entertainmentStreamContentMappings',
                        'season'
                    ])->withCount(['WatchList as is_in_watchlist' => function ($query) use ($request) {
                        $query->where('user_id', $request->user_id)
                            ->where('profile_id', $request->profile_id);
                    }]);



                    $videoList = $videoList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                        'plan',
                    ])->withCount(['WatchList as is_in_watchlist' => function ($query) use ($request) {
                        $query->where('user_id', $request->user_id)
                            ->where('profile_id', $request->profile_id);
                    }]);

                    // Get all items and merge them
                    $allEntertainment = $movieList->get();
                    $allVideos = $videoList->get();
                    $entertainmentList = $allEntertainment
                        ->merge($allVideos)
                        ->sortBy('release_date')
                        ->values();

                    // Manual pagination for merged collection
                    $total = $entertainmentList->count();
                    $currentPage = $request->input('page', 1);
                    $offset = ($currentPage - 1) * $perPage;
                    $items = $entertainmentList->slice($offset, $perPage)->values();

                    $entertainment = new \Illuminate\Pagination\LengthAwarePaginator(
                        $items,
                        $total,
                        $perPage,
                        $currentPage,
                        ['path' => $request->url(), 'pageName' => 'page']
                    );
                    break;

                case 'movie':
                case 'tvshow':
                    $entertainmentList = Entertainment::where('release_date', '>', $todayDate)
                        ->where('status', 1)
                        ->where('type', $request->type)
                        ->where('deleted_at',null);
                    $entertainmentList = $entertainmentList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                    ]);
                    $entertainmentList = $entertainmentList->when($request->has('is_restricted'), function($q) use ($request) {
                        $q->where('is_restricted', $request->is_restricted);
                    });
                    break;

                case 'video':
                    $entertainmentList = Video::where('release_date', '>', $todayDate)
                        ->where('status', 1)
                        ->where('deleted_at',null);
                    $entertainmentList = $entertainmentList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                    ]);
                    $entertainmentList = $entertainmentList->when($request->has('is_restricted'), function($q) use ($request) {
                        $q->where('is_restricted', $request->is_restricted);
                    });
                    break;
                default:
                return [
                    'status' => false,
                    'message' => 'Invalid type'
                ];
            }

            // Only apply these filters if we don't already have a paginated result (i.e., not 'all' type)
            if ($request->type !== 'all') {
                // Don't apply type filter for video since we're already querying Video model
                if ($request->filled('type') && $request->type !== 'video') {
                    $entertainmentList->where('type', $request->type);
                }

                isset($request->is_restricted) && $entertainmentList = $entertainmentList->where('is_restricted', $request->is_restricted);
                (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                    $entertainmentList = $entertainmentList->where('is_restricted', 0);

                if($request->type == 'video'){
                    $entertainmentList = $entertainmentList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                        'plan',
                    ])->withCount(['WatchList as is_in_watchlist' => function ($query) use ($request) {
                        $query->where('user_id', $request->user_id)
                            ->where('profile_id', $request->profile_id);
                    }]);
                }else{

                    $entertainmentList = $entertainmentList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                        'entertainmentGenerMappings',
                        'plan',
                        'entertainmentReviews',
                        'entertainmentTalentMappings',
                        'entertainmentStreamContentMappings',
                        'season'
                    ])->withCount(['WatchList as is_in_watchlist' => function ($query) use ($request) {
                        $query->where('user_id', $request->user_id)
                            ->where('profile_id', $request->profile_id);
                    }]);
                }
                $entertainment = $entertainmentList->paginate($perPage);
            }
            $entertainment->setCollection(
                $entertainment->getCollection()->map(function($item) use ($device_type) {
                    $item->posterImage = $device_type == 'tv'
                        ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$item->type)
                        : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                    return $item;
                })
            );
            $html = '';
            if ($request->has('is_ajax') && $request->is_ajax == 1) {

                $responseCollection = ComingSoonResourceV3::collection($entertainment);



                foreach ($responseCollection->toArray($request) as $comingSoonData) {
                    if (isenablemodule($comingSoonData['type']) == 1) {
                        $userId = auth()->id();
                        if ($userId) {
                            $contentType = $comingSoonData['type'] ?? 'movie';
                            $isInWatchList = WatchList::where('entertainment_id', $comingSoonData['id'])
                                ->where('user_id', $userId)
                                ->where('type', $contentType)
                                ->where('profile_id', getCurrentProfile($userId, $request))
                                ->exists();
                            $comingSoonData['is_watch_list'] = $isInWatchList ? true : false;
                        }
                        $html .= view('frontend::components.card.card_comingsoon', ['data' => $comingSoonData])->render();
                    }
                }
            }

            return [
                'data' => ComingSoonResourceV3::collection($entertainment),
                'html' => $html,
                'hasMore' => $entertainment->hasMorePages()
            ];
        });

        // Safe access to keys
        $html = $responseData['html'] ?? '';
        $hasMore = $responseData['hasMore'] ?? false;
        $data = $responseData['data'] ?? [];


        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.coming_soon_list'),
                'hasMore' => $hasMore,
            ], 200);
        }

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => __('movie.coming_soon_list'),
        ], 200);
    }

    public function saveReminder(Request $request)
    {
        $user = auth()->user();
        $reminderData = $request->all();
        $reminderData['user_id'] = $user->id;

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user->id, $request);

        $reminderData['profile_id'] = $profile_id;



        $entertainment = $request->entertainment_id ? Entertainment::where('id', $request->entertainment_id)->first() : null;

        if($entertainment != null){
            $reminderData['release_date'] = $request->release_date ?? $entertainment->release_date;
        }


        $reminders = UserReminder::updateOrCreate(
            ['entertainment_id' => $request->entertainment_id, 'user_id' => $user->id, 'profile_id'=>$profile_id],
            $reminderData
        );

        Cache::flush();

        $message = $reminders->wasRecentlyCreated ? __('movie.reminder_add') : __('movie.reminder_update');
        $result = $reminders;

        return response()->json(['status' => true, 'message' => $message]);
    }

    public function saveEntertainmentViews(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();
        $data['user_id'] = $user->id;
        $viewData = EntertainmentView::where('entertainment_id', $request->entertainment_id)->where('user_id', $user->id)->first();

        Cache::flush();

        if (!$viewData) {
            $views = EntertainmentView::create($data);
            $message = __('movie.view_add');
        } else {
            $message = __('movie.already_added');
        }

        return response()->json(['status' => true, 'message' => $message]);
    }
    public function deleteReminder(Request $request)
    {
        $user = auth()->user();

        $ids = $request->is_ajax == 1 ? $request->id : explode(',', $request->id);

        $entertainment = Entertainment::whereIn('id',$ids)->get();

        $reminders = UserReminder::whereIn('entertainment_id', $ids)->where('user_id', $user->id)->forceDelete();

        Cache::flush();

        if ($reminders == null) {

            $message = __('movie.reminder_add');

            return response()->json(['status' => false, 'message' => $message]);
        }

        $message = __('movie.reminder_remove');


        return response()->json(['status' => true, 'message' => $message]);
    }
    public function deleteDownload(Request $request)
    {
        $user = auth()->user();

        $ids = explode(',', $request->id);

        $download = EntertainmentDownload::whereIn('id', $ids)->forceDelete();

        Cache::flush();

        if ($download == null) {

            $message = __('movie.download');

            return response()->json(['status' => false, 'message' => $message]);
        }

        $message = __('movie.download');


        return response()->json(['status' => true, 'message' => $message]);
    }

    public function episodeDetailsV2(Request $request)
    {
        $user_id = $request->user_id;
        $episode_id = $request->episode_id;

        $cacheKey = 'episode_v2' . $episode_id .'_'.$request->profile_id;
        $responseData = Cache::get($cacheKey);

        if (!$responseData) {
            $episode = Episode::selectRaw('episodes.*,
                    (select id from entertainment_downloads where entertainment_id = episodes.id
                    AND user_id = '.$user_id.'
                    AND entertainment_type = "episode"
                    AND is_download = 1
                    limit 1) download_id,
                    e.language,
                    plan.level as plan_level,
                    GROUP_CONCAT(egm.genre_id) as genre_ids
                ')
                ->leftJoin('entertainments as e','episodes.entertainment_id','=','e.id')
                ->leftJoin('plan','episodes.plan_id','=','plan.id')
                ->join('entertainment_gener_mapping as egm','egm.entertainment_id','=','e.id');

            isset(request()->is_restricted) && $episode = $episode->where('is_restricted', request()->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $episode = $episode->where('is_restricted',0);

            $episode = $episode->where('episodes.id', $episode_id)
                ->with('EpisodeStreamContentMapping')
                ->first();


            if ($request->has('user_id')) {
                $continueWatch = ContinueWatch::where('entertainment_id', $episode->id)
                ->where('user_id', $user_id)->where('profile_id', $request->profile_id)
                ->where('entertainment_type', 'episode')
                ->first();
                $episode['continue_watch'] = $continueWatch;

                $genre_ids = isset($episode->genre_ids) ? explode(",",$episode->genre_ids) : NULL;
                $episode['user_id'] = $user_id;
                $episodeId = isset($episode->id) ? $episode->id : 0;
                $episode['moreItems'] = Entertainment::get_more_items($episodeId,$genre_ids);
                $episode['genre_data'] = Genres::whereIn('id', $genre_ids)->get();
            }

            $genre_ids = isset($episode->genre_ids) ? explode(",",$episode->genre_ids) : NULL;

            $episodeId = isset($episode->id) ? $episode->id : 0;
            $episode['moreItems'] = Entertainment::get_more_items($episodeId,$genre_ids);
            $episode['genre_data'] = Genres::whereIn('id', $genre_ids)->get();
            $episode['genre_data'] = Genres::whereIn('id', $genre_ids)->get();
            $episode['subtitles'] = Subtitle::where('entertainment_id',$episode->id)->where('type','episode')->get();

            $responseData = new EpisodeDetailResourceV2($episode);
            Cache::put($cacheKey, $responseData);
        }

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.episode_details'),
        ], 200);
    }

    public function tvshowDetailsV2(Request $request)
    {

        $tvshow_id = $request->tvshow_id;

        $cacheKey = 'tvshow_v2' . $tvshow_id . '_' . $request->profile_id;

        $responseData = Cache::get($cacheKey);


        if (empty($responseData))
        {
            $user_id = isset($request->user_id) ? $request->user_id : 0;
            $profile_id = isset($request->user_id) ? $request->profile_id : 0;

            $tvshow = Entertainment::get_first_tvshow($tvshow_id,$user_id,$profile_id)->first();

            $tvshow['reviews'] = $tvshow->entertainmentReviews ?? null;

            if ($request->has('user_id')) {
                $user_id = $request->user_id;
                $tvshow['user_id'] = $user_id;
                $tvshow['is_watch_list'] = (int) WatchList::where('entertainment_id', $request->tvshow_id)->where('user_id', $user_id)->where('type', 'tvshow')->where('profile_id', $request->profile_id)->exists();
                $tvshow['your_review'] =  $tvshow->entertainmentReviews ? $tvshow->entertainmentReviews->where('user_id', $user_id)->first() :null;

                if ($tvshow['your_review']) {
                    $tvshow['reviews'] = $tvshow['reviews']->where('user_id', '!=', $user_id);
                }
            }

            $responseData = new TvshowDetailResourceV2($tvshow);
            Cache::put($cacheKey, $responseData);
        }

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.tvshow_details'),
        ], 200);
    }

    public function movieDetailsV2(Request $request)
    {

        $movieId = $request->movie_id;

        $cacheKey = 'movie_v2' . $movieId . '_'.$request->profile_id;

        $responseData = Cache::get($cacheKey);

        if (!$responseData)
        {
            $user_id = isset($request->user_id) ? $request->user_id : 0;
            $profile_id = isset($request->profile_id) ? $request->profile_id : 0;
            $device_id = isset($request->device_id) ? $request->device_id : 0;

            $movie = Entertainment::get_movie($movieId,$user_id,$profile_id,$device_id)->first();

            $movie['reviews'] = $movie->entertainmentReviews ?? null;

            $movie['subtitles'] = $movie->subtitles ?? null;

            if ($request->has('user_id')) {

                $user_id = $request->user_id;

                $movie->user_id = $user_id;
                $movie['is_watch_list'] = (int) WatchList::where('entertainment_id', $request->movie_id)->where('user_id', $user_id)->where('type', 'movie')->where('profile_id', $request->profile_id)->exists();
                if ($movie['your_review_id']) {
                    $movie['reviews'] = $movie['reviews']->where('user_id', '!=', $user_id);
                }



            }

            $responseData = new MovieDetailDataResourceV2($movie);
            Cache::put($cacheKey, $responseData);
        }

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.movie_details'),
        ], 200);
    }

    public function tvshowListV2(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $tvshowList = Entertainment::query()
        ->selectRaw('entertainments.id,entertainments.name,entertainments.description,entertainments.type,entertainments.price,entertainments.purchase_type,entertainments.access_duration,entertainments.discount,entertainments.available_for,entertainments.trailer_url_type,entertainments.plan_id,plan.level as plan_level,entertainments.movie_access,entertainments.language,entertainments.imdb_rating,entertainments.content_rating,entertainments.duration,entertainments.release_date,entertainments.is_restricted,entertainments.video_upload_type,entertainments.video_url_input,entertainments.enable_quality,entertainments.download_url,entertainments.poster_url as poster_image,entertainments.poster_tv_url as poster_tv_image,entertainments.thumbnail_url as thumbnail_image,GROUP_CONCAT(egm.genre_id) as genre_ids,GROUP_CONCAT(egm.genre_id) as genres,entertainments.trailer_url,entertainments.trailer_url as base_url,entertainments.status,entertainments.created_by,entertainments.updated_by,entertainments.deleted_by,entertainments.created_at,entertainments.updated_at,entertainments.deleted_at')
        ->join('entertainment_gener_mapping as egm','egm.entertainment_id','=','entertainments.id')
        ->leftJoin('plan','plan.id','=','entertainments.plan_id')
        ->with('episodeV2')
        ->where('entertainments.type', 'tvshow')
        ->where('entertainments.release_date', '<=', Carbon::now()->format('Y-m-d'))
        ->groupBy('entertainments.id')
        ->whereHas('episodeV2');



        if ($request->has('search')) {
            $searchTerm = $request->search;
            $tvshowList->where(function ($query) use ($searchTerm) {
                $query->where('entertainments.name', 'like', "%{$searchTerm}%");
            });
        }

        isset(request()->is_restricted) && $tvshowList = $tvshowList->where('is_restricted', request()->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $tvshowList = $tvshowList->where('is_restricted',0);

        $tvshowList = $tvshowList->where('entertainments.status', 1);

        $tvshows = $tvshowList->orderBy('entertainments.id', 'desc');
        $tvshows = $tvshows->paginate($perPage);

        $userId = auth()->id() ?? $request->user_id;
        if ($userId) {
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $tvshows->getCollection()->transform(function ($tvshow) use ($userId, $profile_id) {
                $isInWatchList = WatchList::where('entertainment_id', $tvshow->id)
                    ->where('user_id', $userId)
                    ->where('type', 'tvshow')
                    ->where('profile_id', $profile_id)
                    ->exists();
                $tvshow->is_watch_list = (int) $isInWatchList;
                return $tvshow;
            });
        }
        $responseData = TvshowResourceV2::collection($tvshows);


        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';

            foreach($responseData->toArray($request) as $tvShowData) {
                $html .= view('frontend::components.card.card_entertainment', ['value' => $tvShowData])->render();
            }

            $hasMore = $tvshows->hasMorePages();

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.tvshow_list'),
                'hasMore' => $hasMore,
            ], 200);
        }


        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.tvshow_list'),
        ], 200);
    }

    public function movieListV2(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $accessType = $request->input('access_type');

        $movieList = Entertainment::selectRaw('entertainments.id,entertainments.id as e_id,entertainments.name,entertainments.type,entertainments.price,entertainments.purchase_type,entertainments.access_duration,entertainments.discount,entertainments.available_for,entertainments.plan_id,plan.level as plan_level,entertainments.description,entertainments.trailer_url_type,entertainments.is_restricted,entertainments.language,entertainments.imdb_rating,entertainments.content_rating,entertainments.duration,entertainments.video_upload_type,GROUP_CONCAT(egm.genre_id) as genres,entertainments.release_date,entertainments.trailer_url,entertainments.video_url_input, entertainments.poster_url as poster_image, entertainments.poster_tv_url as poster_tv_image, entertainments.thumbnail_url as thumbnail_image,entertainments.trailer_url as base_url,entertainments.movie_access')
        ->join('entertainment_gener_mapping as egm','egm.entertainment_id','=','entertainments.id')
        ->leftJoin('plan','plan.id','=','entertainments.plan_id')

        ->when(in_array($accessType, ['pay-per-view', 'purchased']), function ($query) {
            return $query->where('entertainments.movie_access', 'pay-per-view');
        }, function ($query) use ($request) {
            if ($request->filled('actor_id')) {
                return $query->whereIn('entertainments.type', ['movie', 'tvshow']);
            }
            return $query->where('entertainments.type', 'movie');
        });

        if ($accessType === 'purchased' && auth()->check()) {
            $userId = auth()->id();
            $movieList->whereExists(function ($subQuery) use ($userId) {
                $subQuery->select(DB::raw(1))
                    ->from('pay_per_views')
                    ->whereColumn('pay_per_views.movie_id', 'entertainments.id')
                    ->where('pay_per_views.user_id', $userId);
            });
        }

        isset($request->is_restricted) && $movieList = $movieList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $movieList = $movieList->where('is_restricted',0);

       $movieList = $movieList->where('entertainments.status', 1)
            ->where(function ($query) {
                $query->where('release_date', '<=', Carbon::now()->format('Y-m-d'))
                      ->orWhereNull('release_date');
            });

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $movieList->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%");
            });
        }
        if ($request->filled('genre_id')) {
            $genreId = $request->genre_id;
            $movieList->where('egm.genre_id',$genreId);

        }


        if ($request->filled('actor_id'))
        {
            $actorId = $request->actor_id;

            $isMovieModuleEnabled = isenablemoduleV2('movie');
            $isTVShowModuleEnabled = isenablemoduleV2('tvshow');

            $movies = $movieList->where(function ($query) use ($actorId, $isMovieModuleEnabled, $isTVShowModuleEnabled)
            {
                if ($isMovieModuleEnabled && $isTVShowModuleEnabled)
                {
                    $query->where('entertainments.type', 'movie')
                          ->orWhere('entertainments.type', 'tvshow');
                } elseif ($isMovieModuleEnabled) {
                    $query->where('entertainments.type', 'movie');
                } elseif ($isTVShowModuleEnabled) {
                    $query->where('entertainments.type', 'tvshow');
                }
            })
            ->join('entertainment_talent_mapping as etm', function($q) use ($actorId)
            {
                $q->on('etm.entertainment_id','=','entertainments.id')
                ->where('etm.talent_id', $actorId);
            });
        }
        if ($request->filled('language')) {
            $movieList->where('entertainments.language', $request->language);
        }

        $movies = $movieList->whereNull('entertainments.deleted_at')->groupBy('entertainments.id')->orderBy('entertainments.id', 'desc')->paginate($perPage);

        $userId = auth()->id() ?? $request->user_id;
        if ($userId) {
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $movies->getCollection()->transform(function ($movies) use ($userId, $profile_id) {
                $isInWatchList = WatchList::where('entertainment_id', $movies->id)
                    ->where('user_id', $userId)
                    ->where('type', 'movie')
                    ->where('profile_id', $profile_id)
                    ->exists();
                $movies->is_watch_list = (int) $isInWatchList;
                return $movies;
            });
        }

         $responseData = MoviesResourceV2::collection($movies);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';
            foreach ($responseData->toArray($request) as $movieData)
            {
                if(isenablemoduleV2($movieData['type']) == 1)
                {
                    $html .= view('frontend::components.card.card_entertainment', ['value' => $movieData])->render();

                }
            }

            $hasMore = $movies->hasMorePages();

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.movie_list'),
                'hasMore' => $hasMore,
            ], 200);
        }

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.movie_list'),
        ], 200);
    }

    public function genreContentList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $accessType = $request->input('access_type');
        $genreId = $request->input('genre_id');
        $actorId = $request->input('actor_id');
        $directorId = $request->input('director_id');
        $type = $request->input('type');

        $contentList = Entertainment::select([
            'entertainments.id',
            'entertainments.slug',
            'entertainments.name',
            'entertainments.type',
            'entertainments.price',
            'entertainments.purchase_type',
            'entertainments.access_duration',
            'entertainments.discount',
            'entertainments.available_for',
            'entertainments.plan_id',
            'entertainments.description',
            'entertainments.trailer_url_type',
            'entertainments.is_restricted',
            'entertainments.language',
            'entertainments.imdb_rating',
            'entertainments.content_rating',
            'entertainments.duration',
            'entertainments.video_upload_type',
            'entertainments.release_date',
            'entertainments.trailer_url',
            'entertainments.video_url_input',
            'entertainments.poster_url',
            'entertainments.poster_tv_url',
            'entertainments.thumbnail_url',
            'entertainments.movie_access',
        ])
        ->with([
            'plan:id,level',
            'genresdata:id,name'
        ]);

        // Filter by genre_id (required)
        if ($genreId) {
            $contentList->whereHas('entertainmentGenerMappings', function ($q) use ($genreId) {
                $q->where('genre_id', $genreId);
            });
        }

        // Filter by type (movie, tvshow, or both)
        if ($type) {
            if ($type === 'both') {
                $contentList->whereIn('entertainments.type', ['movie', 'tvshow']);
                // For 'both', only show TV shows that have at least one season with at least one episode
                $contentList->where(function ($query) {
                    $query->where('entertainments.type', 'movie')
                        ->orWhere(function ($q) {
                            $q->where('entertainments.type', 'tvshow')
                                ->whereHas('season', function ($seasonQuery) {
                                    $seasonQuery->where('status', 1)
                                        ->whereNull('deleted_at')
                                        ->whereHas('episodes', function ($episodeQuery) {
                                            $episodeQuery->where('status', 1)
                                                ->whereNull('deleted_at');
                                        });
                                });
                        });
                });
            } else {
                $contentList->where('entertainments.type', $type);
                // For TV shows, only show those that have at least one season with at least one episode
                if ($type === 'tvshow') {
                    $contentList->whereHas('season', function ($seasonQuery) {
                        $seasonQuery->where('status', 1)
                            ->whereNull('deleted_at')
                            ->whereHas('episodes', function ($episodeQuery) {
                                $episodeQuery->where('status', 1)
                                    ->whereNull('deleted_at');
                            });
                    });
                }
            }
        }

        // Filter by actor_id if provided
        if ($actorId) {
            $contentList->whereHas('entertainmentTalentMappings', function ($q) use ($actorId) {
                $q->where('talent_id', $actorId);
            });
        }

        // Filter by director_id if provided
        if ($directorId) {
            $contentList->whereHas('entertainmentTalentMappings', function ($q) use ($directorId) {
                $q->where('talent_id', $directorId);
            });
        }

        // Filter by access type
        if (in_array($accessType, ['pay-per-view', 'purchased'])) {
            $contentList->where('entertainments.movie_access', 'pay-per-view');
        }

        // Filter for purchased content if access_type is 'purchased'
        if ($accessType === 'purchased' && auth()->check()) {
            $userId = auth()->id();
            $contentList->whereExists(function ($subQuery) use ($userId) {
                $subQuery->select(DB::raw(1))
                    ->from('pay_per_views')
                    ->whereColumn('pay_per_views.movie_id', 'entertainments.id')
                    ->where('pay_per_views.user_id', $userId);
            });
        }

        // Apply other filters
        isset($request->is_restricted) && $contentList = $contentList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $contentList = $contentList->where('is_restricted',0);

        $contentList = $contentList->where('entertainments.status', 1)
            ->where(function ($query) {
                $query->whereDate('release_date', '<=', Carbon::now())
                      ->orWhereNull('release_date');
            });

        // Search filter
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $contentList->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%");
            });
        }

        // Language filter
        if ($request->filled('language')) {
            $contentList->where('entertainments.language', $request->language);
        }

        $contents = $contentList->whereNull('entertainments.deleted_at')
            ->orderBy('entertainments.id', 'desc')
            ->paginate($perPage);


        $userId = auth()->id() ?? $request->user_id;
        if ($userId) {
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $contents->getCollection()->transform(function ($content) use ($userId, $profile_id) {
                $isInWatchList = WatchList::where('entertainment_id', $content->id)
                    ->where('user_id', $userId)
                    ->where('type', $content->type)
                    ->where('profile_id', $profile_id)
                    ->exists();
                $content->is_watch_list = (int) $isInWatchList;

                // Preserve previously aliased fields for downstream consumers
                $content->e_id = $content->id;
                $content->poster_image = $content->poster_url;
                $content->poster_tv_image = $content->poster_tv_url;
                $content->thumbnail_image = $content->thumbnail_url;
                $content->base_url = $content->trailer_url;
                $content->plan_level = optional($content->plan)->level ?? null;

                return $content;
            });
        } else {
            $contents->getCollection()->transform(function ($content) {
                // Preserve previously aliased fields for downstream consumers
                $content->e_id = $content->id;
                $content->poster_image = $content->poster_url;
                $content->poster_tv_image = $content->poster_tv_url;
                $content->thumbnail_image = $content->thumbnail_url;
                $content->base_url = $content->trailer_url;
                $content->plan_level = optional($content->plan)->level ?? null;
                return $content;
            });
        }

        $responseData = commonContentResourceV3::collection($contents);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';
            foreach ($responseData->toArray($request) as $contentData) {
                if(isenablemoduleV2($contentData['type']) == 1 ) {
                    if($contentData['type'] == 'movie'){
                        $html .= view('frontend::components.card.card_movie', ['values' => [$contentData]])->render();
                    }elseif($contentData['type'] == 'tvshow'){
                        $html .= view('frontend::components.card.card_tvshow', ['values' => [$contentData]])->render();
                    }
                }
            }

            $hasMore = $contents->hasMorePages();

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.movie_list'),
                'hasMore' => $hasMore,
            ], 200);
        }

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.movie_list'),
        ], 200);
    }

    public function contentDetailsV3(Request $request)
    {

        $id = $request->id;
        $device_type = getDeviceType($request);

        $cacheKey = 'common_content_detail_v3_'.$id . '_' . $request->profile_id . '_' . $request->type;


         $responseData = cacheApiResponse($cacheKey, 10, function () use ($request, $id,$device_type) {


            $user_id = isset($request->user_id) ? $request->user_id : 0;
            $profile_id = isset($request->profile_id) ? $request->profile_id : 0;
            $device_id = isset($request->device_id) ? $request->device_id : 0;

            $userId = $request->user_id ?? auth()->id() ;
            $getDeviceTypeData = Subscription::checkPlanSupportDevice($userId,$device_type);
            $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true); // Decode to associative array
            $isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
            $userPlanLevel = 0;
            if ($userId) {
                $userLevel = Subscription::select('plan_id')
                    ->where(['user_id' => $userId, 'status' => 'active'])
                    ->latest()
                    ->first();
                $userPlanLevel = $userLevel->plan_id ?? 0;

            }

           switch ($request->type) {
                case 'movie':
                    $content = Entertainment::select('id','type')->where('id', $id)->first();

                    if (!$content) {
                        return [
                            'status' => false,
                            'message' => 'Content not found.'
                        ];
                    }

                    // Check type
                    if ($content->type !== $request->type) {
                        return [
                            'status' => false,
                            'message' => 'Content type mismatch. Requested type: '.$request->type.', actual type: '.$content->type
                        ];
                    }

                    $movie = Entertainment::get_movie($id, $user_id, $profile_id, $device_id)
                        ->with(['clips' => function ($q) use ($request) { $q->select('id', 'content_id', 'title', 'content_type','type', 'url','poster_url','tv_poster_url')
                        ->where('content_type', $request->type);
                    }, 'entertainmentGenerMappings.genre'])->first();

                    if (!$movie) {
                        return [
                            'status' => false,
                            'message' => 'Movie not found.'
                        ];
                    }

                    $videoQualities = EntertainmentStreamContentMapping::where('entertainment_id', $id)
                        ->select('id', 'type as url_type', 'url', 'quality')->get();

                    $videoDefaultQuality = [
                        'quality' => "default_quality",
                        'url' => $movie['video_upload_type'] == 'Local' ? setBaseUrlWithFileName(trim($movie['video_url_input']),'video', $request->type ) : trim($movie['video_url_input']),
                        'url_type' => trim($movie['video_upload_type']),
                    ];

                    $downloadMappingsQuery = EntertainmnetDownloadMapping::where('entertainment_id', $id);

                    if ($request->download_quality) {
                        $downloadMappingsQuery->where('quality', $request->download_quality);
                    }

                    $downloadMappings = $downloadMappingsQuery->get();
                    // trailer_data
                    $watched_time =   ContinueWatch::where('entertainment_id', $id)->where('profile_id', $profile_id)->where('user_id', $user_id) ->value('watched_time');
                   // $movie =  $movie->with('entertainmentGenerMappings.genre')->find($id);
                    $genre_ids = !empty($movie->genre_ids) ? explode(',', $movie->genre_ids) : [];

                    $genre_data = $movie->entertainmentGenerMappings
                        ->pluck('genre.name')
                        ->filter()
                        ->toArray();

                    $genre_ids = $movie->entertainmentGenerMappings
                        ->pluck('genre.id')
                        ->filter()
                        ->toArray();

                    $movie['genre_data'] = $genre_data;
                    if($genre_ids){
                        $entertaintment_ids = EntertainmentGenerMapping::whereIn('genre_id', $genre_ids)->pluck('entertainment_id')->toArray();
                        $more_items = Entertainment::whereIn('id', $entertaintment_ids)
                        ->where('type', 'movie');

                        // Apply filters
                        if(isset(request()->is_restricted)) {
                            $more_items = $more_items->where('is_restricted', request()->is_restricted);
                        }

                        if(!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
                            $more_items = $more_items->where('is_restricted',0);
                        }

                        // Only released items if requested
                        if ($request->has('is_released') && !empty($request->is_released)) {
                            $more_items = $more_items->where('release_date', '<=', Carbon::now());
                        }

                        $more_items = $more_items->where('status',1)
                            ->where('deleted_at',null)
                            ->limit(10)
                            ->get();

                        // Exclude current movie if it has an ID
                        if ($movie && isset($movie->id)) {
                            $more_items = $more_items->except($movie->id);
                        }
                            // dd($more_items);

                        // Apply setContentAccess to each item in the collection
                        $userPlanLevel = 0;

                        if ($userId) {
                            $userLevel = Subscription::select('plan_id')
                                ->where(['user_id' => $userId, 'status' => 'active'])
                                ->latest()
                                ->first();
                            $userPlanLevel = $userLevel->plan_id ?? 0;

                        }

                        $more_items = $more_items->map(function($item) use ($userId, $userPlanLevel, $isDeviceSupported,$device_type) {

                            $access = in_array($item->type, ['movie', 'tvshow']) ? $item->movie_access : $item->access;
                            $itemArray = [
                                'id' => $item->id,
                                'access' => $access,
                                'plan_id' => $item->plan_id,
                                'type' => $item->type,
                            ];

                            $itemArray = setContentAccess($itemArray, $userId, $userPlanLevel);

                            // Set the access control properties on the model
                            $item->has_content_access = $itemArray['has_content_access'];
                            $item->required_plan_level = $itemArray['required_plan_level'];
                            $item->isDeviceSupported = $isDeviceSupported;
                            $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                            return $item;
                        });
                    }else{
                        $more_items =[];
                    };
                    $movie['more_items'] = $more_items;
                    $actors = collect();
                    $directors = collect();

                    foreach ($movie->entertainmentTalentMappings ?? [] as $mapping) {
                        if($mapping->talentprofile)
                        {
                            if ($mapping->talentprofile->type === 'actor') {
                                $actors[] = $mapping->talentprofile;
                            } elseif ($mapping->talentprofile->type === 'director') {
                                $directors[] = $mapping->talentprofile;
                            }
                        }
                    }

                    $movie['actors'] = $actors;
                    $movie['directors'] = $directors;
                    $seasonData = $movie->type === 'tvshow'
                    ? $movie->season->filter(function ($season) {
                        return $season->status == 1 && is_null($season->deleted_at);
                    })->map(function ($season) {
                        return [
                            'id'            => $season->id,
                            'name'          => $season->name,
                            'season_id'     => $season->id,
                            'total_episode' => $season->episodes()->count(),
                        ];
                    })->values()
                    : null;

                    // dd($movie->type,$seasonData);

                     $movie['season_data'] = $seasonData ;
                      $movie['access'] = $movie->movie_access ;
                      $movie['review'] = Entertainment::getReviewData($id, $user_id);

                    break;

                case 'tvshow':

                    $content = Entertainment::select('id','type')->where('id', $id)->first();

                    if (!$content) {
                        return [
                            'status' => false,
                            'message' => 'Content not found.'
                        ];
                    }

                    // Check type
                    if ($content->type !== $request->type) {
                        return [
                            'status' => false,
                            'message' => 'Content type mismatch. Requested type: '.$request->type.', actual type: '.$content->type
                        ];
                    }

                    $movie = Entertainment::get_tvshow($id, $user_id, $profile_id, $device_id)
                        ->with(['clips' => function ($q) use ($request) { $q->select('id', 'content_id','title', 'content_type','type', 'url','poster_url','tv_poster_url')
                        ->where('content_type','tv_show');
                    }, 'entertainmentGenerMappings.genre'])->first();

                    if (!$movie) {
                        return [
                            'status' => false,
                            'message' => 'TV Show not found.'
                        ];
                    }

                    $videoQualities = EntertainmentStreamContentMapping::where('entertainment_id', $id)
                        ->select('id', 'type as url_type', 'url', 'quality')->get();

                    $videoDefaultQuality = [
                        'quality' => "default_quality",
                        'url' => $movie['video_upload_type'] == 'Local' ? setBaseUrlWithFileName(trim($movie['video_url_input']),'video', $request->type ) : trim($movie['video_url_input']),
                        'url_type' => trim($movie['video_upload_type']),
                    ];

                    $downloadMappingsQuery = EntertainmnetDownloadMapping::where('entertainment_id', $id);

                    if ($request->download_quality) {
                        $downloadMappingsQuery->where('quality', $request->download_quality);
                    }

                    $downloadMappings = $downloadMappingsQuery->get();
                    // trailer_data
                    $watched_time =   ContinueWatch::where('entertainment_id', $id)->where('profile_id', $profile_id)->where('user_id', $user_id) ->value('watched_time');
                   // $movie =  $movie->with('entertainmentGenerMappings.genre')->find($id);
                    $genre_ids = !empty($movie->genre_ids) ? explode(',', $movie->genre_ids) : [];

                    $genre_data = $movie->entertainmentGenerMappings
                        ->pluck('genre.name')
                        ->filter()
                        ->toArray();

                    $movie['genre_data'] = $genre_data;

                    if($genre_ids){
                        $entertaintment_ids = EntertainmentGenerMapping::whereIn('genre_id', $genre_ids)->pluck('entertainment_id')->toArray();
                        $more_items = Entertainment::whereIn('id', $entertaintment_ids)
                        ->where('type',  'tvshow');

                        if(isset(request()->is_restricted)) {
                            $more_items = $more_items->where('is_restricted', request()->is_restricted);
                        }

                        if(!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
                            $more_items = $more_items->where('is_restricted',0);
                        }

                        if ($request->has('is_released') && !empty($request->is_released)) {
                            $more_items = $more_items->where('release_date', '<=', Carbon::now());
                        }

                        // Execute query and get collection
                        $more_items = $more_items->where('status',1)
                            ->where('deleted_at',null)
                            ->limit(10)
                            ->get();

                        // Exclude current movie if it has an ID
                        if ($movie && isset($movie->id)) {
                            $more_items = $more_items->except($movie->id);
                        }
                            // dd($more_items);

                        // Apply setContentAccess to each item in the collection
                        $userPlanLevel = 0;

                        if ($userId) {
                            $userLevel = Subscription::select('plan_id')
                                ->where(['user_id' => $userId, 'status' => 'active'])
                                ->latest()
                                ->first();
                            $userPlanLevel = $userLevel->plan_id ?? 0;

                        }

                        $more_items = $more_items->map(function($item) use ($userId, $userPlanLevel, $isDeviceSupported,$device_type) {

                            $access = in_array($item->type, ['movie', 'tvshow']) ? $item->movie_access : $item->access;
                            $itemArray = [
                                'id' => $item->id,
                                'access' => $access,
                                'plan_id' => $item->plan_id,
                            ];

                            $itemArray = setContentAccess($itemArray, $userId, $userPlanLevel);

                            // Set the access control properties on the model
                            $item->has_content_access = $itemArray['has_content_access'];
                            $item->required_plan_level = $itemArray['required_plan_level'];
                            $item->isDeviceSupported = $isDeviceSupported;
                            $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                            return $item;
                        });
                    }else{
                        $more_items =[];
                    };
                    $movie['more_items'] = $more_items;
                    $actors = collect();
                    $directors = collect();

                    foreach ($movie->entertainmentTalentMappings ?? [] as $mapping) {
                        if($mapping->talentprofile)
                        {
                            if ($mapping->talentprofile->type === 'actor') {
                                $casts[] = $mapping->talentprofile;
                            } elseif ($mapping->talentprofile->type === 'director') {
                                $directors[] = $mapping->talentprofile;
                            }
                        }
                    }
                     $movie['actors'] = $casts;
                     $movie['casts'] = $casts;
                     $movie['directors'] = $directors;
                     $movie['review'] = Entertainment::getReviewData($id, $user_id);
                    $seasonData = $movie->type === 'tvshow'
                    ? $movie->season->filter(function ($season) {
                        return $season->status == 1 && is_null($season->deleted_at);
                    })->map(function ($season) {
                        return [
                            'id'            => $season->id,
                            'name'          => $season->name,
                            'season_id'     => $season->id,
                            'total_episode' => $season->episodes()->count(),
                        ];
                    })->values()
                    : null;

                    // dd($movie->type,$seasonData);

                     $movie['season_data'] = $seasonData ;
                      $movie['access'] = $movie->movie_access ;

                    break;


                case 'episode':

                    $content = Episode::select('id')->where('id', $id)->first();

                    if (!$content) {
                        return [
                            'status' => false,
                            'message' => 'Episode not found.'
                        ];
                    }


                    $movie = Episode::get_episode($id, $user_id, $profile_id, $device_id)
                     ->with('seasondata.episodes','entertainmentdata')
                        ->first();
                        $genres = $movie->entertainmentdata->entertainmentGenerMappings;
                        foreach($genres as $genre){
                            $genre_data[] = $genre->genre;
                        }

                        $genre_ids = $genres->pluck('genre_id')->toArray();
                    if(!$movie){
                        return [
                            'status' => false,
                            'message' => 'Episode not found.'
                        ];
                    }
                    if($genre_ids){

                        $entertaintment_ids = EntertainmentGenerMapping::whereIn('genre_id', $genre_ids)->pluck('entertainment_id')->toArray();

                        $more_items = Entertainment::whereIn('id', $entertaintment_ids)
                        ->whereIn('type', ['tvshow']);


                        // Apply filters
                        if(isset(request()->is_restricted)) {
                            $more_items = $more_items->where('is_restricted', request()->is_restricted);
                        }

                        if(!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
                            $more_items = $more_items->where('is_restricted',0);
                        }

                        if ($request->has('is_released') && !empty($request->is_released)) {
                            $more_items = $more_items->where('release_date', '<=', Carbon::now());
                        }

                        // Execute query and get collection
                        $more_items = $more_items->where('status',1)
                            ->where('deleted_at',null)
                            ->limit(10)
                            ->get();

                        // Exclude current movie if it has an ID
                        if ($movie && isset($movie->id)) {
                            $more_items = $more_items->except($movie->id);
                        }
                            // dd($more_items);

                        // Apply setContentAccess to each item in the collection


                        $more_items = $more_items->map(function($item) use ($userId, $userPlanLevel, $isDeviceSupported,$device_type) {
                            $access = in_array($item->type, ['movie', 'tvshow']) ? $item->movie_access : $item->access;
                            $itemArray = [
                                'id' => $item->id,
                                'access' => $access,
                                'plan_id' => $item->plan_id,
                            ];

                            $itemArray = setContentAccess($itemArray, $userId, $userPlanLevel);

                            // Set the access control properties on the model
                            $item->has_content_access = $itemArray['has_content_access'];
                            $item->required_plan_level = $itemArray['required_plan_level'];
                            $item->isDeviceSupported = $isDeviceSupported;
                            $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                            return $item;
                        });
                    }else{
                        $more_items =[];
                    };
                    $movie['access'] = $movie->access ?? null;
                    $movie['more_items'] = $more_items;
                    $videoQualities = EpisodeStreamContentMapping::where('episode_id', $id)
                     ->select('id', 'type as url_type', 'url', 'quality')->get();
                    $videoDefaultQuality = [
                        'quality' => "default_quality",
                        'url' => $movie['video_upload_type'] == 'Local' ? setBaseUrlWithFileName(trim($movie['video_url_input']),'video', $request->type ) : trim($movie['video_url_input']),
                        'url_type' => $movie['video_upload_type'],
                    ];

                    $downloadMappingsQuery = EpisodeDownloadMapping::where('episode_id', $id);

                    if ($request->download_quality) {
                        $downloadMappingsQuery->where('quality', $request->download_quality);
                    }

                    $downloadMappings = $downloadMappingsQuery->get();
                    $watched_time =   ContinueWatch::where('entertainment_id', $id)->where('profile_id', $profile_id)->where('user_id', $user_id) ->value('watched_time');
                    $movie['type'] = 'episode';

                    $seasonCollection = collect();
                    if ($movie->entertainmentdata && $movie->entertainmentdata->season) {
                        $seasonCollection = $movie->entertainmentdata->season
                            ->filter(function ($season) {
                                return $season->status == 1 && is_null($season->deleted_at);
                            })
                            ->map(function ($season) {
                                $episodeCount = $season->relationLoaded('episodes')
                                    ? $season->episodes->count()
                                    : $season->episodes()->count();
                                return [
                                    'id' => $season->id,
                                    'name' => $season->name,
                                    'season_id' => $season->id,
                                    'total_episode' => $episodeCount,
                                ];
                            })
                            ->values();
                    }
                    if ($movie->seasondata) {
                        $tv_show_data = [
                            'id' => $movie->entertainmentdata->id ?? null,
                            'name' => $movie->entertainmentdata->name ?? null,
                            'season_id' => $movie->season_id ?? null,
                            'total_episode' => $movie->seasondata->episodes->count() ?? null,
                        ];
                    } else {
                        $tv_show_data = null; // no season linked
                    }

                    $movie['season_data'] = $seasonCollection;

                    $movie['tv_show_data'] = $tv_show_data;
                    break;

                case 'video':
                    $content = Video::select('id')->where('id', $id)->first();

                    if (!$content) {
                        return [
                            'status' => false,
                            'message' => 'Video not found.'
                        ];
                    }
                    $movie = Video::get_video($id, $user_id, $profile_id, $device_id)
                       ->with(['clips' => function ($q) use ($request) { $q->select('id', 'content_id', 'content_type','title','type', 'url','poster_url','tv_poster_url')
                        ->where('content_type', $request->type);
                    }])->first();

                    if(!$movie){
                        return [
                            'status' => false,
                            'message' => 'Video not found.'
                        ];
                    }
                    $movie = setContentAccess($movie, $user_id, $userPlanLevel);
                    $movie['access'] = $movie->access ?? null;
                    $videoQualities = VideoStreamContentMapping::where('video_id', $id)
                     ->select('id', 'type as url_type', 'url', 'quality')->get();

                     $more_items = Video::where('status', 1)->where('deleted_at', null)
                     ->when(request()->has('is_restricted'), function ($query) {
                         $query->where('is_restricted', request()->is_restricted);
                     })
                     ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                         $query->where('is_restricted', 0);
                     })
                     ->when($request->has('is_released') && !empty($request->is_released), function ($query) {
                        $query->where('release_date', '<=', Carbon::now());
                    })
                     ->where('id', '!=', $movie->id)  // exclude current video by ID here
                     ->take(6)
                     ->get();
                     $more_items = $more_items->map(function($item) use ($userId, $userPlanLevel, $isDeviceSupported,$device_type) {
                        $itemArray = setContentAccess($item, $userId, $userPlanLevel);
                        $item->isDeviceSupported = $isDeviceSupported;
                        $item->type = 'video';
                        $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url,'image','video') : setBaseUrlWithFileName($item->poster_url,'image','video');

                        return $item;
                     });
                     $more_items = $more_items->except($movie->id);

                    $movie['more_items'] = $more_items;

                    $videoDefaultQuality = [
                        'quality' => "default_quality",
                        'url' => $movie['video_upload_type'] == 'Local' ? setBaseUrlWithFileName(trim($movie['video_url_input']),'video', $request->type ) : trim($movie['video_url_input']),
                        'url_type' => trim($movie['video_upload_type']),
                    ];
                    $downloadMappingsQuery = VideoDownloadMapping::where('video_id', $id);

                    if ($request->download_quality) {
                        $downloadMappingsQuery->where('quality', $request->download_quality);
                    }

                    $downloadMappings = $downloadMappingsQuery->get();
                    $watched_time =   ContinueWatch::where('entertainment_id', $id)->where('profile_id', $profile_id)->where('user_id', $user_id) ->value('watched_time');
                    $movie['type'] = 'video';

                    break;

                case 'actor':
                case 'director':
                    $content = CastCrew::select('id', 'type')->where('id', $id)->first();

                    if (!$content) {
                        return [
                            'status' => false,
                            'message' => 'Cast/Crew not found.'
                        ];
                    }

                    // Check type
                    if ($content->type !== $request->type) {
                        return [
                            'status' => false,
                            'message' => 'Content type mismatch. Requested type: '.$request->type.', actual type: '.$content->type
                        ];
                    }

                    $castCrew = CastCrew::with('entertainmentTalentMappings')
                        ->where('id', $id)
                        ->where('type', $request->type)
                        ->first();

                    if (!$castCrew) {
                        return [
                            'status' => false,
                            'message' => ucfirst($request->type) . ' not found.'
                        ];
                    }

                    // Get movie and TV show counts
                    $movieCount = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($id) {
                        $query->where('talent_id', $id);
                    })->where('type', 'movie')->where('status', 1)->count();

                    $tvshowCount = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($id) {
                        $query->where('talent_id', $id);
                    })->where('type', 'tvshow')->where('status', 1)->count();

                    // Get average rating
                    $averageRating = \Modules\Entertainment\Models\Review::whereHas('entertainment.entertainmentTalentMappings', function ($query) use ($id) {
                        $query->where('talent_id', $id);
                    })->avg('rating');

                    // Get top genres
                    $topGenres = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($id) {
                        $query->where('talent_id', $id);
                    })->where('status', 1)->with(['entertainmentGenerMappings.genre:id,name'])->get()
                        ->pluck('entertainmentGenerMappings')->flatten()->pluck('genre.name')
                        ->filter()
                        ->countBy()->sortDesc()->take(3)->keys()->implode(', ');

                    // Get related content (movies and TV shows)
                    $relatedContent = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($id) {
                        $query->where('talent_id', $id);
                    })
                    ->where('status', 1)
                    ->where('deleted_at', null)
                    ->when(isset($request->is_restricted), function ($q) {
                        $q->where('is_restricted', request()->is_restricted);
                    })
                    ->when(!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0, function ($q) {
                        $q->where('is_restricted', 0);
                    })
                    ->limit(10)
                    ->get()
                    ->map(function($item) use ($userId, $userPlanLevel, $isDeviceSupported, $device_type) {
                        $access = in_array($item->type, ['movie', 'tvshow']) ? $item->movie_access : $item->access;
                        $itemArray = [
                            'id' => $item->id,
                            'access' => $access,
                            'plan_id' => $item->plan_id,
                        ];
                        $itemArray = setContentAccess($itemArray, $userId, $userPlanLevel);
                        $item->has_content_access = $itemArray['has_content_access'];
                        $item->required_plan_level = $itemArray['required_plan_level'];
                        $item->isDeviceSupported = $isDeviceSupported;
                        $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                        return $item;
                    });

                    $movie = [
                        'id' => $castCrew->id,
                        'name' => $castCrew->name,
                        'type' => $castCrew->type,
                        'bio' => $castCrew->bio,
                        'dob' => $castCrew->dob,
                        'place_of_birth' => $castCrew->place_of_birth,
                        'profile_image' => $castCrew->file_url ? setBaseUrlWithFileName($castCrew->file_url, 'image', 'castcrew') : null,
                        'rating' => round($averageRating, 1),
                        'top_genres' => $topGenres,
                        'movie_count' => $movieCount,
                        'tvshow_count' => $tvshowCount,
                        'more_items' => $relatedContent,
                    ];

                    break;

                default:
                    // fallback
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid type'
                    ], 400);
            }

                // Skip common processing for actor/director types as they don't have video/content properties
                if (!in_array($request->type, ['actor', 'director'])) {
                    $today = Carbon::now()->toDateString();
                    $placement = ['player','banner'];
                    $movie['posterImage'] = $device_type == 'tv' ? setBaseUrlWithFileName($movie->poster_tv_url ?? null) : setBaseUrlWithFileName($movie->poster_url ?? null);
                    $userId = $request->user_id ?? auth()->id() ;
                    $getDeviceTypeData = Subscription::checkPlanSupportDevice($userId,$device_type);
                    $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true); // Decode to associative array
                    $movie['isDeviceSupported'] = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;


                    $currentPlan = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();
                    $userPlanLevel = $currentPlan->plan_id ?? 0;

                    // Use helper function to set content access
                    $movie = setContentAccess($movie, $user_id, $userPlanLevel);
                    if(isset($movie['access']) && $movie['access']  == 'pay-per-view'){
                       $rental = [
                            'price' => (float)$movie['price'],
                            'discount' => (int)$movie['discount'],
                            'access_duration' => $movie['access_duration'],
                            'availability_days' => $movie['available_for'],
                            'access' => $movie['purchase_type'],
                       ];
                       if ($rental['price'] > 0 && $rental['discount'] > 0) {
                            $rental['discounted_price'] = round(
                                $rental['price'] - ($rental['price'] * $rental['discount'] / 100),
                                2
                            );
                        } else {
                            $rental['discounted_price'] = $rental['price'];
                        }
                       $movie['rental'] = $rental;
                    }else{
                        $movie['rental'] = [];
                    };

                    // Initialize trailer_data array
                    $trailer_data = [];

                    if(isset($movie['trailer_url_type']) && isset($movie['trailer_url'])) {
                        $trailer_data[] =  [
                            'id'=> $movie['id'],
                            'title'    => 'default trailer',
                            'url_type' => trim($movie['trailer_url_type']),
                            'url' =>  $movie['trailer_url_type'] == 'Local' ? setBaseUrlWithFileName(trim($movie['trailer_url']),'video', $request->type) : trim($movie['trailer_url']),
                            'poster_image' =>isset($movie['poster_url']) ? setBaseUrlWithFileName($movie['poster_url'],'image', $request->type) : null,
                        ];
                    }

                    if (isset($movie->clips) && $movie->relationLoaded('clips') && $movie->clips->count() > 0) {
                        foreach ($movie->clips as $clip) {
                            $clipData = [
                                'id'           => $clip->id,
                                'title'        => $clip->title,
                                'url_type'     => $clip->type,
                                'url'          => $clip->type == 'Local' ? setBaseUrlWithFileName(trim($clip->url),'video',$request->type) : trim($clip->url),
                                'poster_image' =>  $device_type == 'tv' ? setBaseUrlWithFileName($clip->tv_poster_url ?? null ,'image',$request->type) : setBaseUrlWithFileName($clip->poster_url ?? null,'image',$request->type),
                            ];
                            $trailer_data[] = $clipData;
                        }
                    };
                    $movie['trailer_data'] = $trailer_data;
                    // Fetch active custom ads
                    $customAds = CustomAdsSetting::where('status', 1)
                        ->whereIn('placement', $placement)
                        ->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today)
                        ->whereJsonContains('target_categories', (int) $id)
                        ->get(['type','media','redirect_url','placement'])->map(function($ad) {
                            return [
                                'type' => $ad->type,
                                'placement' => $ad->placement,
                                'url' => $ad->media ? setBaseUrlWithFileName(trim($ad->media) ,$ad->type,'ads') : null,
                                'redirect_url' => $ad->redirect_url,
                            ];
                        });

                        $vastAds = VastAdsSetting::where('status', 1)
                        ->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today)
                        ->whereJsonContains('target_selection', (int) $id)
                        ->where('target_type', $request->type =='episode' ? 'tvshow' : $request->type)
                      ->get(['type', 'url']);
                    // Prepare vast_ads array
                    $vastAdsData = [
                        'pre_role_ad_url' => $vastAds->where('type', 'pre-roll')->pluck('url')->toArray(),
                        'mid_role_ad_url' => $vastAds->where('type', 'mid-roll')->pluck('url')->toArray(),
                        'post_role_ad_url' => $vastAds->where('type', 'post-roll')->pluck('url')->toArray(),
                        'overlay_ad_url' => $vastAds->where('type', 'overlay')->pluck('url')->toArray(),
                    ];

                if($request->type != 'video'){
                    $movie['reviews'] = isset($movie->entertainmentReviews) ? $movie->entertainmentReviews : null;
                }

                $movie['subtitles'] = isset($movie->subtitles) ? $movie->subtitles : null;

                if(isset($videoQualities)) {
                    $videoQualities = $videoQualities->map(function($quality) use ($request) {
                        return [
                            'id' => $quality->id,
                            'quality' => trim($quality->quality),
                            'url_type' =>trim($quality->url_type),
                            'url' => $quality->url_type == 'Local' ? setBaseUrlWithFileName(trim($quality->url), 'video', $request->type) : trim($quality->url),
                        ];
                    });

                    $movie['video_qualities'] = array_merge([$videoDefaultQuality],$videoQualities->toArray() );
                }

                $defaultDownload = [];

                if (!empty($movie->download_type) || !empty($movie->download_url)) {
                    $defaultDownload[] = [
                        'id'        => $movie->id,
                        'url_type'  => $movie->download_type ?? null,
                        'url'       => ($movie->download_type === 'Local')
                                        ? setBaseUrlWithFileName($movie->download_url ?? null, 'video', $request->type)
                                        : ($movie->download_url ?? null),
                        'quality'   => 'default_quality',
                    ];
                }

                $mappingDownloads = [];

                if (!empty($downloadMappings)) {
                    foreach ($downloadMappings as $mapping) {
                        $mappingDownloads[] = [
                            'id'        => $mapping->id,
                            'url_type'  => $mapping->type,
                            'url'       => ($mapping->type === 'Local')
                                            ? setBaseUrlWithFileName($mapping->url, 'video', $request->type)
                                            : $mapping->url,
                            'quality'   => $mapping->quality,
                        ];
                    }
                }

                $mergedDownloads = array_merge($defaultDownload, $mappingDownloads);

                $movie['download_data'] = [
                    'download_enable' => $movie->download_status ?? 0,
                    'download_quality' => $mergedDownloads
                ];


                $movie['customAds'] = isset($customAds) ? $customAds : [];
                $movie['vast_ads'] = isset($vastAdsData) ? $vastAdsData : [];
                $movie['watched_time'] = isset($watched_time) ? $watched_time : null;
                } else {
                    // For actor/director, set minimal required properties
                    $movie['isDeviceSupported'] = $isDeviceSupported;
                    $movie['trailer_data'] = [];
                    $movie['video_qualities'] = [];
                    $movie['download_data'] = ['download_enable' => 0];
                    $movie['customAds'] = [];
                    $movie['vast_ads'] = [];
                    $movie['watched_time'] = null;
                    $movie['reviews'] = null;
                    $movie['subtitles'] = null;
                }

            if ($request->has('user_id')) {

                $user_id = $request->user_id;

                $movie['user_id'] = $user_id;

                // For actor/director, skip watchlist and likes as they don't apply
                if (!in_array($request->type, ['actor', 'director'])) {
                    // $movie['is_watch_list'] =  (int) WatchList::where('entertainment_id', $request->movie_id)->where('user_id', $user_id)->where('type', 'movie')->where('profile_id', $request->profile_id)->exists();
                    $movie['is_watch_list'] = (int) WatchList::where('entertainment_id', $id)
                    ->where('user_id', $user_id)
                    ->where('type', $request->type )
                    ->where('profile_id', $request->profile_id)
                    ->exists();

                    $movie['is_likes'] = (int) Like::where('entertainment_id', $id)
                    ->where('user_id', $user_id)
                    ->where('profile_id', $request->profile_id)
                    ->where('is_like', 1)
                    ->where('type', $request->type)
                    ->exists();

                    if (isset($movie['your_review_id']) && $movie['your_review_id']) {
                        if (isset($movie['reviews']) && $movie['reviews']) {
                            $movie['reviews'] = $movie['reviews']->where('user_id', '!=', $user_id);
                        }
                    }
                } else {
                    // For actor/director, set these to null or 0
                    $movie['is_watch_list'] = 0;
                    $movie['is_likes'] = 0;
                }


            }

            // Convert array to object if needed (for actor/director types)
            if (is_array($movie)) {
                $movie = (object) $movie;
            }
                if($request->type == 'actor' || $request->type == 'director'){
                    $responseData = new ContentDetailsCastCrewV3($movie);
                }else{
                    $responseData = new CommonContentDetails($movie);
                }
            // $responseData =  new CommonContentDetails($movie);
                     return $responseData;
         });

        return response()->json([
            'status' => true,
            'data' => $responseData['data'],
            'message' => __($request->type.'.'. $request->type.'_details' ),

        ], 200);
    }

    public function contentListV3(Request $request){
        $device_type = getDeviceType($request);

        $user_id = isset($request->user_id) ? $request->user_id : 0;
        $profile_id = getCurrentProfile($user_id, $request);

        if (!$request->has('type')) {
            return response()->json([
                'status' => false,
                'message' => 'Type parameter is required'
            ], 400);
        }

        $contentType = $request->type;
        $perPage = $request->input('per_page', 10);
        // Create cache key based on all request parameters
        $cacheKey = 'v3_content_list_'.md5(json_encode($request->all()) . '_' . $device_type . '_' . $user_id . '_' . $profile_id . '_' . $perPage);

        // Use Redis caching with 5 minutes TTL
        $cachedResponse = cacheApiResponse($cacheKey, 300, function () use ($request, $contentType, $device_type, $user_id, $profile_id, $perPage) {
            $list = collect();
            try {
            switch ($contentType) {
                case 'movie':
                case 'tvshow':
                    $list = Entertainment::select([
                        'id', 'name', 'type', 'is_restricted', 'plan_id', 'release_date','IMDb_rating',
                        'poster_url', 'poster_tv_url', 'movie_access as access', 'trailer_url_type'
                    ])
                    ->with([
                        'entertainmentLike' => function($q) use ($user_id, $profile_id) {
                            $q->where('user_id', $user_id)
                              ->where('profile_id', $profile_id)
                              ->where('is_like', 1);
                        },
                        'entertainmentTalentMappings.talentprofile',
                        'season'
                    ])
                    ->where('type', $contentType)
                    ->when($request->has('is_restricted'), function($q) use ($request) {
                        $q->where('is_restricted', $request->is_restricted);
                    })
                    ->where('status', 1);

                    if ($contentType === 'tvshow') {
                        $list->whereHas('season', function ($seasonQuery) {
                            $seasonQuery->where('status', 1)
                                ->whereNull('seasons.deleted_at')
                                ->whereHas('episodes', function ($episodeQuery) {
                                    $episodeQuery->where('status', 1)
                                        ->whereNull('episodes.deleted_at');
                                });
                        });
                    }

                    if ($request->has('is_released') && !empty($request->is_released)) {
                        $list->where('release_date', '<=', Carbon::now());
                    }

                    if ($request->has('search') && !empty($request->search)) {
                        $list->where('name', 'like', "%{$request->search}%");
                    }

                    if ($request->has('language') && !empty($request->language) && $request->language != 'null') {
                        $list->where('language', $request->language);
                    }

                    if ($request->has('genre_id') && !empty($request->genre_id) && $request->genre_id != 'null') {
                        $list->whereRelation('entertainmentGenerMappings', 'genre_id', $request->genre_id);
                    }

                    if ($request->has('actor_id') && !empty($request->actor_id) && $request->actor_id != 'null') {
                        $list->whereRelation('entertainmentTalentMappings', 'talent_id', $request->actor_id);
                    }

                    if ($request->has('director_id') && !empty($request->director_id) && $request->director_id != 'null') {
                        $list->whereRelation('entertainmentTalentMappings', 'talent_id', $request->director_id);
                    }


                    $list = $list->whereNull('entertainments.deleted_at')
                        ->with(['entertainmentTalentMappings.talentprofile', 'season'])
                        ->orderBy('entertainments.id', 'desc')
                        ->paginate($perPage);
                    break;

                case 'episode':
                    $list = Episode::select([
                        'id', 'name', 'is_restricted', 'release_date', 'plan_id',
                        'poster_url', 'poster_tv_url', 'access', 'trailer_url',
                        'trailer_url_type', 'entertainment_id', 'season_id'
                    ])
                    ->with([
                        'entertainmentLike' => function($q) use ($user_id, $profile_id) {
                            $q->where('user_id', $user_id)
                              ->where('profile_id', $profile_id)
                              ->where('is_like', 1);
                        }
                    ])
                    ->when($request->has('is_restricted'), function($q) use ($request) {
                        $q->where('is_restricted', $request->is_restricted);
                    })
                    ->where('status', 1);

                    if ($request->has('is_released') && !empty($request->is_released)) {
                        $list->where('release_date', '<=', Carbon::now());
                    }

                    if ($request->has('search') && !empty($request->search)) {
                        $list->where('name', 'like', "%{$request->search}%");
                    }

                    if ($request->has('language') && !empty($request->language) && $request->language != 'null') {
                        $list->whereRelation('entertainmentdata', 'language', $request->language);
                    }

                    if ($request->has('genre_id') && !empty($request->genre_id) && $request->genre_id != 'null') {
                        $list->whereRelation('entertainmentdata.entertainmentGenerMappings', 'genre_id', $request->genre_id);
                    }

                    if ($request->has('actor_id') && !empty($request->actor_id) && $request->actor_id != 'null') {
                        $list->whereRelation('entertainmentdata.entertainmentTalentMappings', 'talent_id', $request->actor_id);
                    }

                    if ($request->has('director_id') && !empty($request->director_id) && $request->director_id != 'null') {
                        $list->whereRelation('entertainmentdata.entertainmentTalentMappings', 'talent_id', $request->director_id);
                    }
                    $list = $list->whereNull('episodes.deleted_at')
                        ->with(['entertainmentdata.entertainmentTalentMappings.talentprofile', 'seasondata'])
                        ->orderBy('episodes.id', 'desc')
                        ->paginate($perPage);

                    break;

                case 'video':
                    $list = Video::select([
                        'id', 'name', 'is_restricted', 'release_date', 'plan_id',
                        'poster_url', 'poster_tv_url', 'access', 'trailer_url', 'trailer_url_type'
                    ])
                    ->with([
                        'entertainmentLike' => function($q) use ($user_id, $profile_id) {
                            $q->where('user_id', $user_id)
                              ->where('profile_id', $profile_id)
                              ->where('is_like', 1);
                        }
                    ])
                    ->when($request->has('is_restricted'), function($q) use ($request) {
                        $q->where('is_restricted', $request->is_restricted);
                    })
                    ->where('status', 1);

                    if ($request->has('is_released') && !empty($request->is_released)) {
                        $list->where('release_date', '<=', Carbon::now());
                    }

                    if ($request->has('search') && !empty($request->search)) {
                        $list->where('name', 'like', "%{$request->search}%");
                    }

                    $list = $list->whereNull('videos.deleted_at')
                        ->orderBy('videos.id', 'desc')
                        ->paginate($perPage);

                    break;

                default:
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid content type. Supported types: movie, tvshow, episode, video'
                    ], 400);
            }

            $userId = $request->user_id ?? auth()->id();
            if($userId){
            $getDeviceTypeData = Subscription::checkPlanSupportDevice($userId, $device_type);
            $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
            $userLevel = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();

            $listData = $list->map(function($item) use ($device_type, $deviceTypeResponse, $user_id, $profile_id, $contentType, $userLevel) {
                $userPlanLevel = $userLevel->plan_id ?? 0;

                // Set is_likes based on relationship
                $item->is_likes = $item->entertainmentLike->isNotEmpty() ? 1 : 0;
                $item->e_id = $item->id;
                if ($contentType === 'video') {
                    $item->type = 'video';
                } elseif ($contentType === 'episode') {
                    $item->type = 'episode';
                }

                // Use helper function to set content access
                $item = setContentAccess($item, $user_id, $userPlanLevel);
                $item->posterImage = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$contentType) : setBaseUrlWithFileName($item->poster_url,'image',$contentType);
                $item->poster_tv_image = setBaseUrlWithFileName($item->poster_tv_url ,'image',$contentType);
                $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;

                if ($contentType === 'tvshow' && isset($item->season)) {
                    $item->season_data = $item->season->map(function ($season) {
                        return [
                            'id'            => $season->id,
                            'name'          => $season->name,
                            'season_id'     => $season->id,
                            'total_episode' => $season->episodes()->count(),
                        ];
                    })->values();
                } elseif ($contentType === 'episode' && isset($item->seasondata)) {
                    $item->season_data = [
                        'id'            => $item->seasondata->id,
                        'name'          => $item->seasondata->name,
                        'season_id'     => $item->seasondata->id,
                        'total_episode' => $item->seasondata->episodes()->count(),
                    ];
                } else {
                    $item->season_data = null;
                }


                return $item;
            });
        }else{

            $listData = $list->map(function($item) use ($device_type, $contentType) {
                // Set is_likes based on relationship
                $item->is_likes = $item->entertainmentLike->isNotEmpty() ? 1 : 0;
                $item->e_id = $item->id;
                if ($contentType === 'video') {
                    $item->type = 'video';
                } elseif ($contentType === 'episode') {
                    $item->type = 'episode';
                }

                $item->posterImage = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$contentType) : setBaseUrlWithFileName($item->poster_url,'image',$contentType);

                $item->isDeviceSupported = 0;
                $item->has_content_access = 0;
                $item->required_plan_level = $item->plan_id ?? 0;
                $item = setContentAccess($item, null, null);
                $item->posterImage = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image', $contentType) : setBaseUrlWithFileName($item->poster_url,'image',$contentType);
                $item->isDeviceSupported =  0;

                $item->poster_tv_image =  setBaseUrlWithFileName($item->poster_tv_url ,'image', $contentType);
                if ($contentType === 'tvshow' && isset($item->season)) {
                    $item->season_data = $item->season->map(function ($season) {
                        return [
                            'id'            => $season->id,
                            'name'          => $season->name,
                            'season_id'     => $season->id,
                            'total_episode' => $season->episodes()->count(),
                        ];
                    })->values();
                } elseif ($contentType === 'episode' && isset($item->seasondata)) {
                    $item->season_data = [
                        'id'            => $item->seasondata->id,
                        'name'          => $item->seasondata->name,
                        'season_id'     => $item->seasondata->id,
                        'total_episode' => $item->seasondata->episodes()->count(),
                    ];
                } else {
                    $item->season_data = null;
                }
                return $item;
            });
        }
                $responseData = CommonContentList::collection($listData);

                return [
                    'status' => true,
                    'data' => $responseData,
                    'message' => __('movie.'.$contentType.'_list'),
                ];

            } catch (\Exception $e) {
                return [
                    'status' => false,
                    'message' => 'An error occurred while fetching content: ' . $e->getMessage()
                ];
            }
        });

        // Return cached response
        return response()->json($cachedResponse['data'], $cachedResponse['data']['status'] ? 200 : 500);
    }


}
