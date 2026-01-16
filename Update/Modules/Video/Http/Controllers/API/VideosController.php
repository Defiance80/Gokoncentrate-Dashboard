<?php

namespace Modules\Video\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Video\Models\Video;
use Modules\Entertainment\Models\Watchlist;
use Modules\Video\Transformers\VideoResource;
use Modules\Video\Transformers\VideoDetailResource;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Models\Like;
use Modules\Entertainment\Models\EntertainmentDownload;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Modules\Video\Transformers\Backend\VideoResourceV3;


class VideosController extends Controller
{
    public function videoList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
         $videoList = Video::where(function ($query) {
            $query->whereDate('release_date', '<=', Carbon::now())
                  ->orWhereNull('release_date');
        })
        ->where('status', 1)
        ->with('VideoStreamContentMappings', 'plan');

        isset($request->is_restricted) && $videoList = $videoList->where('is_restricted', $request->is_restricted);

        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $videoList = $videoList->where('is_restricted',0);

        $videoData = $videoList->orderBy('updated_at', 'desc')->paginate($perPage);

        $responseData = VideoResource::collection($videoData);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';
            foreach ($responseData->toArray($request) as $videosData) {
                $userId = auth()->id();
                if ($userId) {
                    $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
                    $isInWatchList = WatchList::where('entertainment_id', $videosData['id'])
                        ->where('user_id', $userId)
                        ->where('type', 'video')
                        ->where('profile_id', $profile_id)
                        ->exists();

                    // Set the flag in the video data
                    $videosData['is_watch_list'] = $isInWatchList ? true : false;
                }
                $html .= view('frontend::components.card.card_video', ['data' => $videosData])->render();
            }

            $hasMore = $videoData->hasMorePages();

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
            'message' => __('video.video_list'),
        ], 200);
    }

    public function videoListV3(Request $request)
{
    $perPage = $request->input('per_page', 10);

    $videoList = Video::query()
        ->where(function ($query) {
            $query->whereDate('release_date', '<=', now())
                  ->orWhereNull('release_date');
        })
        ->where('status', 1)
        ->with(['plan']);

    if (isset($request->is_restricted)) {
        $videoList->where('is_restricted', $request->is_restricted);
    }

    if (!empty(getCurrentProfileSession('is_child_profile'))) {
        $videoList->where('is_restricted', 0);
    }

    $videos = $videoList->orderByDesc('updated_at')->paginate($perPage);

    $videoData = VideoResourceV3::collection($videos)->toArray($request);


    if ($request->boolean('is_ajax')) {
        $html = '';
       if(!empty($videoData)){

          $html .= view('frontend::components.card.card_video', ['values' => $videoData])->render();

       }


        return response()->json([
            'status' => true,
            'html' => $html,
            'message' => __('video.video_list'),
            'hasMore' => $videos->hasMorePages(),
        ], 200);
    }

    return response()->json([
        'status' => true,
        'data' => $responseData,
        'message' => __('video.video_list'),
    ], 200);
}


  public function videoDetails(Request $request){

            $video = Video::with('VideoStreamContentMappings','plan','subtitles')->where('id', $request->video_id)->first();

            if($request->has('user_id')){
                $user_id = $request->user_id;
                $continueWatch = ContinueWatch::where('entertainment_id', $video->id)->where('user_id', $user_id)->where('entertainment_type', 'video')->first();
                $video['continue_watch'] = $continueWatch;
                $video['user_id'] = $user_id;
                $video['is_watch_list'] = WatchList::where('entertainment_id',$request->video_id )->where('user_id', $user_id)->where('profile_id', $request->profile_id)
                ->where('type', 'video')->exists();
                $video['is_likes'] = Like::where('entertainment_id', $request->video_id)->where('type', 'video')->where('user_id', $user_id)->where('profile_id', $request->profile_id)
                ->where('is_like', 1)->exists();
                $video['is_download'] = EntertainmentDownload::where('entertainment_id', $request->video_id)->where('device_id',$request->device_id)->where('user_id', $user_id)
                ->where('entertainment_type', 'video')->where('is_download', 1)->exists();
            }

            $responseData = new VideoDetailResource($video);


      return response()->json([
          'status' => true,
          'data' => $responseData,
          'message' => __('video.video_details'),
      ], 200);
  }
}
