<?php

namespace Modules\Episode\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Episode\Models\Episode;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Episode\Http\Requests\EpisodeRequest;
use App\Trait\ModuleTrait;
use Modules\Constant\Models\Constant;
use Modules\Entertainment\Models\Entertainment;
use Modules\Episode\Models\EpisodeDownloadMapping;
use Modules\Episode\Models\EpisodeStreamContentMapping;
use Modules\Season\Models\Season;
use Modules\Subscriptions\Models\Plan;
use Modules\Episode\Trait\EpisodeTrait;
use Modules\Episode\Services\EpisodeService;
use App\Services\ChatGTPService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Entertainment\Models\Subtitle;
use Illuminate\Support\Facades\Storage;
class EpisodesController extends Controller
{
    protected string $exportClass = '\App\Exports\EpisodeExport';
    use EpisodeTrait;
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
        }

    protected $episodeService;
    protected $chatGTPService;


    public function __construct(EpisodeService $episodeService,ChatGTPService $chatGTPService)
    {
        $this->episodeService = $episodeService;
        $this->chatGTPService=$chatGTPService;

        $this->traitInitializeModuleTrait(
            'episode.title', // module title
            'episodes', // module name
            'fa-solid fa-clipboard-list' // module icon
        );
    }



    /**
     * Display a listing of the resource.
     *
     * @return Response
     */

    public function index(Request $request)
    {
        $filter = [
            'status' => $request->status,
        ];

        $module_action = 'List';

        $export_import = true;
        $export_columns = [
            [
                'value' => 'name',
                'text' => __('messages.name'),
            ],
            [
                'value' => 'access',
                'text' => __('episode.singular_title') . ' ' . __('movie.lbl_movie_access'),
            ],

            [
                'value' => 'entertainment_id',
                'text' => __('season.lbl_tv_shows'),
            ],


            [
                'value' => 'season_id',
                'text' => __('episode.lbl_season'),
            ],


            [
                'value' => 'IMDb_rating',
                'text' => __('movie.lbl_imdb_rating'),
            ],

            [
                'value' => 'content_rating',
                'text' => __('movie.lbl_content_rating'),
            ],

            [
                'value' => 'duration',
                'text' => __('movie.lbl_duration'),
            ],

            [
                'value' => 'release_date',
                'text' => __('movie.lbl_release_date'),
            ],


            [
                'value' => 'is_restricted',
                'text' => __('movie.lbl_age_restricted'),
            ],

            [
                'value' => 'status',
                'text' => __('plan.lbl_status'),
            ]

        ];
        $export_url = route('backend.episodes.export');


        $tvshows = Entertainment::where('type','tvshow')->get();

        $seasons=Season::where('status', 1)->get();

        $plan=Plan::where('status',1)->get();

        return view('episode::backend.episode.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url','tvshows','seasons','plan'));
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = 'Episode'; // Adjust as necessary for dynamic use
        Cache::flush();

        return $this->performBulkAction(Episode::class, $ids, $actionType, $moduleName);
    }




    public function index_data(Datatables $datatable, Request $request)
    {
        $filter = $request->filter;

        return $this->episodeService->getDataTable($datatable, $filter);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */

      public function create()
     {
        $upload_url_type = Constant::where('type', 'upload_type')->get();
        $plan = Plan::where('status', 1)->get();
        $numberOptions = collect(range(1, 10))->mapWithKeys(function ($number) {
            return [$number => $number];
        });
        $video_quality = Constant::where('type', 'video_quality')->get();
        $tvshows = Entertainment::Where('type', 'tvshow')->where('status', 1)->orderBy('id', 'desc')->get();
        $seasons = Season::where('status', 1)->orderBy('id', 'desc')->get();
        $movie_language = Constant::where('type', 'language')->get();
        $subtitle_language = Constant::where('type', 'subtitle_language')->get();

        $imported_tvshow = Entertainment::where('type', 'tvshow')
            ->where('status', 1)
            ->whereNotNull('tmdb_id')
            ->get();

        $assets = ['textarea'];
        $module_title = __('episode.add_title');
        $mediaUrls = getMediaUrls();

        return view('episode::backend.episode.create', compact(
            'upload_url_type',
            'assets',
            'plan',
            'numberOptions',
            'video_quality',
            'tvshows',
            'seasons',
            'module_title',
            'mediaUrls',
            'imported_tvshow',
            'movie_language',
            'subtitle_language',
        ));
    }

    public function store(EpisodeRequest $request)
    {
        $data = $request->all();
        if($data['access'] == "pay-per-view"){
            $data['download_status'] = 0;
        }
        $data['poster_url']= !empty($data['tmdb_id']) ? $data['poster_url'] : extractFileNameFromUrl($data['poster_url']);
        $data['poster_tv_url']= !empty($data['tmdb_id']) ? $data['poster_tv_url'] : extractFileNameFromUrl($data['poster_tv_url']);

        if (isset($data['IMDb_rating'])) {
            $data['IMDb_rating'] = round($data['IMDb_rating'], 1);
        }

        $videoUrl = $request->input('video_url_input');
        $videoType = $request->input('video_upload_type');

        // Handle video iframe content
        if ($videoType === 'Embedded') {
            $data['video_url_input'] = $request->input('embedded');
        } elseif ($videoType === 'Local') {
            $data['video_url_input'] = extractFileNameFromUrl($videoUrl);
        } else {
            $data['video_url_input'] = $videoUrl;
        }

        // Handle quality videos with embedded codes
if (!empty($data['video_quality_type'])) {
    foreach ($data['video_quality_type'] as $key => $type) {
        if ($type === 'Embedded') {
            // Store the full iframe code without modification
            $data['quality_video'][$key] = $data['quality_video_embed'][$key] ?? '';
        } elseif ($type === 'Local') {
            // For local files, extract filename
            $data['quality_video'][$key] = extractFileNameFromUrl($data['quality_video'][$key] ?? '');
        } else {
            // For URL types, store the URL directly
            $data['quality_video'][$key] = $data['quality_video_url_input'][$key] ?? '';
        }
    }
}

        // Handle trailer embed code
        if ($request->trailer_url_type === 'Embedded') {
            $data['trailer_url'] = $request->input('trailer_embedded');
        } elseif ($request->trailer_url_type === 'Local') {
            $data['trailer_url'] = extractFileNameFromUrl($request->input('trailer_video'));
        }

        $episode = $this->episodeService->create($data);

        // Handle subtitles if enabled
        if ($request->has('enable_subtitle') && $request->enable_subtitle == 1 && $request->has('subtitles')) {
            foreach ($request->subtitles as $index => $subtitle) {
                if (isset($subtitle['subtitle_file']) && $subtitle['subtitle_file']->isValid()) {
                    $language = $request->input("subtitles.$index.language");
                    $file = $subtitle['subtitle_file'];
                    $isDefault = $request->input("subtitles.$index.is_default", false);

                    $lang_arr = Constant::where('type','subtitle_language')->where('value', $language)->first();

                    if ($file) {
                        $extension = strtolower($file->getClientOriginalExtension());
                        if (!in_array($extension, ['srt', 'vtt'])) {
                            throw new \Exception('Only .srt and .vtt files are allowed');
                        }

                        $filename = time() . '_' . $key . '_' . str_replace(' ', '_', $file->getClientOriginalName());

                        // If it's an SRT file, convert it to VTT
                        if ($extension === 'srt') {
                            $srtContent = file_get_contents($file->getRealPath());
                            $vttContent = convertSrtToVtt($srtContent);

                            // Change extension to .vtt
                            $filename = pathinfo($filename, PATHINFO_FILENAME) . '.vtt';

                            // Store the VTT content
                            Storage::disk('public')->put('subtitles/' . $filename, $vttContent);
                        } else {
                            // Store original VTT file
                            $path = $file->storeAs('subtitles', $filename, 'public');
                        }

                        $episode->subtitles()->create([
                            'entertainment_id' => $episode->id,
                            'language_code' => $language,
                            'language' => $lang_arr->name ?? null,
                            'subtitle_file' => $filename,
                            'is_default' => $isDefault ? 1 : 0,
                            'type' => 'episode',
                        ]);
                    }
                }
            }
        }

        $notification_data = [
            'id' => $episode->id,
            'name' => $episode->name,
            'poster_url' => $episode->poster_url ?? null,
            'type' => 'Episode',
            'release_date' => $episode->release_date ?? null,
            'description' => $episode->description ?? null,
        ];
        sendNotifications($notification_data);

        $message = trans('messages.create_form_episode', ['type'=>'Episode']);

        return redirect()->route('backend.episodes.index')->with('success', $message);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $data = Episode::where('id', $id)
        ->with(['EpisodeStreamContentMapping', 'subtitles'])
        ->first();
        $data->poster_url =setBaseUrlWithFileName($data->poster_url);
        $data->poster_tv_url =setBaseUrlWithFileName($data->poster_tv_url);
        $tmdb_id = $data->tmdb_id;

        if($data->trailer_url_type=='Local'){
        $data->trailer_url = setBaseUrlWithFileName($data->trailer_url);
        }

        if($data->video_upload_type=='Local'){
            $data->video_url_input = setBaseUrlWithFileName($data->video_url_input);
        }

        $upload_url_type=Constant::where('type','upload_type')->get();
        $plan=Plan::where('status',1)->get();
        $numberOptions = collect(range(1, 10))->mapWithKeys(function ($number) {
            return [$number => $number];
        });
        $assets = ['textarea'];
        $video_quality=Constant::where('type','video_quality')->get();
        $subtitle_language = Constant::where('type', 'subtitle_language');
        $tvshows=Entertainment::Where('type','tvshow')->where('status', 1)->orderBy('id','desc')->get();;
        $seasons=Season::where('status', 1)->orderBy('id','desc')->get();;
        $movie_language = Constant::where('type', 'language')->get();
        $subtitle_language = Constant::where('type', 'subtitle_language')->get();
        $module_title = __('episode.edit_title');
        $mediaUrls =  getMediaUrls();

       return view('episode::backend.episode.edit', compact('data','subtitle_language','tmdb_id','assets','upload_url_type','plan','numberOptions','video_quality','tvshows','seasons','module_title','mediaUrls','movie_language','subtitle_language'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */

    public function update(EpisodeRequest $request, $id)
    {
        $requestData = $request->all();
        if($requestData['access'] == "pay-per-view"){
            $requestData['download_status'] = 0;
        }
        // Handle poster images
        $requestData['poster_url'] = !empty($requestData['tmdb_id']) ?
            $requestData['poster_url'] :
            extractFileNameFromUrl($requestData['poster_url']);

        $requestData['poster_tv_url'] = !empty($requestData['tmdb_id']) ?
            $requestData['poster_tv_url'] :
            extractFileNameFromUrl($requestData['poster_tv_url']);

        // Handle local file uploads
        if ($request->trailer_url_type == 'Local') {
            $requestData['trailer_url'] = extractFileNameFromUrl($requestData['trailer_video']);
        }

        if ($request->video_upload_type == 'Local') {
            $requestData['video_url_input'] = extractFileNameFromUrl($requestData['video_file_input']);
        }

        // Handle embedded code for video
        if ($request->video_upload_type === 'Embedded') {
            $requestData['video_url_input'] = $request->input('video_url_embedded');
        } else {
            // For regular URLs, check if it's an iframe and extract src if needed
            $videoUrl = $request->input('video_url_input');
            if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $videoUrl, $matches)) {
                $requestData['video_url_input'] = $matches[1];
            }
        }

        // Handle embedded code for trailer
        if ($request->trailer_url_type === 'Embedded') {
            $requestData['trailer_url'] = $request->input('trailer_url_embedded');
        }

        // Handle IMDb rating
        if (isset($requestData['IMDb_rating'])) {
            $requestData['IMDb_rating'] = round($requestData['IMDb_rating'], 1);
        }

        // Handle quality videos with embedded codes
        if (!empty($requestData['video_quality_type'])) {
            foreach ($requestData['video_quality_type'] as $key => $type) {
                if ($type === 'Embedded') {
                    // Use the embed input field value instead of URL input
                    if (isset($requestData['quality_video_embed_input'][$key])) {
                        $requestData['quality_video_url_input'][$key] = $requestData['quality_video_embed_input'][$key];
                    }
                } else if ($type === 'URL' || $type === 'YouTube' || $type === 'HLS' || $type === 'Vimeo' || $type === 'x265') {
                    // For URL types, extract iframe src if present
                    if (isset($requestData['quality_video_url_input'][$key])) {
                        if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $requestData['quality_video_url_input'][$key], $matches)) {
                            $requestData['quality_video_url_input'][$key] = $matches[1];
                        }
                    }
                }
            }
        }

        // Clear plan_id if access is free
        if ($requestData['access'] == 'free') {
            $requestData['plan_id'] = null;
        }

        // Update the episode
        $data = $this->episodeService->update($id, $requestData);
        if ($request->has('deleted_subtitles')) {
            $deletedIds = explode(',', $request->deleted_subtitles);
            // Delete the subtitles from the database
            Subtitle::whereIn('id', $deletedIds)->delete();
        }
        if ($request->enable_subtitle == 1 && $request->has('subtitles')) {
            foreach ($request->subtitles as $key => $subtitleData) {

                $languageCode = $subtitleData['language'] ?? null;
                $file = $subtitleData['subtitle_file'] ?? null;

                $isDefault = isset($subtitleData['is_default']) && $subtitleData['is_default'] == 1;

                if (!$languageCode) {
                    continue; // Skip if no language code
                }

                $lang_arr = Constant::where('type', 'subtitle_language')->where('value', $languageCode)->first();

                // Check if this subtitle language already exists
                $existingSubtitle = $data->subtitles()->where('language_code', $languageCode)->first();

                if ($file) {
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (!in_array($extension, ['srt', 'vtt'])) {
                        return back()
                            ->withErrors(["subtitle_file.$key" => 'Only .srt and .vtt files are allowed'])
                            ->withInput();
                    }

                    $filename = time() . '_' . $key . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                    // If it's an SRT file, convert it to VTT
                    if ($extension === 'srt') {
                        $srtContent = file_get_contents($file->getRealPath());
                        $vttContent = convertSrtToVtt($srtContent);

                        // Change extension to .vtt
                        $filename = pathinfo($filename, PATHINFO_FILENAME) . '.vtt';

                        // Store the VTT content
                        Storage::disk('public')->put('subtitles/' . $filename, $vttContent);
                    } else {
                        // Store original VTT file
                        $path = $file->storeAs('subtitles', $filename, 'public');
                     }

                    if ($existingSubtitle) {
                        $existingSubtitle->update([
                            'subtitle_file' => $filename,
                            'is_default' => $isDefault ? 1 : 0,
                            'language' => $lang_arr->name ?? null,
                        ]);
                    } else {
                        $data->subtitles()->create([
                            'entertainment_id' => $data->id,
                            'language_code' => $languageCode,
                            'language' => $lang_arr->name ?? null,
                            'subtitle_file' => $filename,
                            'is_default' => $isDefault ? 1 : 0,
                            'type' => 'episode',
                        ]);
                    }
                }

                if($file == null && $existingSubtitle){

                    $existingSubtitle->update([
                        'is_default' => $isDefault ? 1 : 0,
                        'language' => $lang_arr->name ?? null,
                    ]);
                }
            }
        }



        $message = trans('messages.update_form_episode', ['type' => "Episode"]);

        return redirect()->route('backend.episodes.index')->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */

    public function destroy($id)
    {
        $data = $this->episodeService->delete($id);
        $message = trans('messages.delete_form_episode', ['form' => 'Episode']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore($id)
    {
        $data = $this->episodeService->restore($id);
        $message = trans('messages.restore_form_episode', ['form' => 'Episode']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function forceDelete($id)
    {
        $data = $this->episodeService->forceDelete($id);
        $message = trans('messages.permanent_delete_form_episode', ['form' => 'Episode']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function update_status(Request $request, Episode $id)
    {
        $id->update(['status' => $request->status]);

        Cache::flush();

        return response()->json(['status' => true, 'message' => __('messages.status_updated_episode')]);
    }

    public function update_is_restricted(Request $request, Episode $id)
    {

        $id->update(['is_restricted' => $request->status]);

        Cache::flush();

        $message='';

        if ($request->status == 1) {
            $message = __('messages.content_added_to_restricted');
        } else {
            $message = __('messages.content_removed_from_restricted');
        }

        return response()->json(['status' => true, 'message' => $message]);
    }


    public function downloadOption(Request $request, $id){

        $data = Episode::where('id', $id)->with('episodeDownloadMappings')->first();

        $module_title = __('episode.download_episode');

        $upload_url_type=Constant::where('type','upload_type')
                                    ->whereIn('name', ['URL', 'Local'])
                                    ->get();
        $video_quality=Constant::where('type','video_quality')->get();

        return view('episode::backend.episode.download', compact('data','module_title','upload_url_type','video_quality'));


    }

    public function storeDownloads(Request $request, $id)
    {
        $data = $request->all();
        $this->episodeService->storeDownloads($data, $id);
        $message = trans('messages.set_download_url');

        Cache::flush();
        return redirect()->route('backend.episodes.index')->with('success', $message);
    }

    public function ImportSeasonlist(Request $request){

        $tvshow_id=$request->tmdb_id;

        $seasons=Season::where('status', 1)->where('tmdb_id',$tvshow_id)->get();

        return response()->json($seasons);

    }

    public function ImportEpisodelist(Request $request){

        $tvshow_id=$request->tvshow_id;
        $season_index=$request->season_id;

        $episodejson = $this->episodeService->getEpisodeList($tvshow_id,$season_index);
        $episodelist = json_decode($episodejson, true);

        while($episodelist === null) {

            $episodejson = $this->episodeService->getEpisodeList($tvshow_id,$season_index);
            $episodelist = json_decode($episodejson, true);


        }

        if (isset($episodelist['success']) && $episodelist['success'] === false) {
            return response()->json([
                'success' => false,
                'message' => $episodelist['status_message']
            ], 400);
        }

        $episodeData= [];

        if(isset($episodelist['episodes']) && is_array($episodelist['episodes'])) {

            foreach ($episodelist['episodes'] as $episode) {
                $episodedata = [
                    'name' => $episode['name'],
                    'episode_number'=>$episode['episode_number'],
                ];

                $episodeData[] = $episodedata;
            }
         }
        return response()->json($episodeData);

    }

    public function ImportEpisode(Request $request){


        $tvshow_id = $request->tvshow_id;
        $season_id = $request->season_id;
        $episode_id = $request->episode_id;

        $episode=Episode::where('tmdb_id', $tvshow_id)->where('tmdb_season',$season_id)->where('episode_number', $episode_id )->first();


        if(!empty($season)){

            $message = __('episode.already_added_episode');

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 400);

        }

        $episode_details = null;

        $configuration =$this->episodeService->getConfiguration();
        $configurationData = json_decode($configuration, true);

        while($configurationData === null) {

            $configuration =$this->episodeService->getConfiguration();
            $configurationData = json_decode($configuration, true);
        }

        if(isset($configurationData['success']) && $configurationData['success'] === false) {
            return response()->json([
                'success' => false,
                'message' => $configurationData['status_message']
            ], 400);
        }


        $episode_details = $this->episodeService->getEpisodeDetails($tvshow_id,$season_id, $episode_id);
        $EpisodeDetail = json_decode($episode_details, true);

        while($EpisodeDetail === null) {
            $episode_details = $this->episodeService->getEpisodeDetails($tvshow_id,$season_id, $episode_id);
            $EpisodeDetail = json_decode($episode_details, true);
        }

        if (isset($EpisodeDetail['success']) && $EpisodeDetail['success'] === false) {
            return response()->json([
                'success' => false,
                'message' => $EpisodeDetail['status_message']
            ], 400);
        }

        $episode_video = $this->episodeService->getEpisodevideo($tvshow_id,$season_id, $episode_id);
        $EpisodeVideoDetail = json_decode($episode_video, true);

        while($EpisodeVideoDetail === null) {

            $episode_video = $this->episodeService->getEpisodevideo($tvshow_id,$season_id, $episode_id);
            $EpisodeVideoDetail = json_decode($episode_video, true);
        }

        if (isset($EpisodeVideoDetail['success']) && $EpisodeVideoDetail['success'] === false) {
            return response()->json([
                'success' => false,
                'message' => $EpisodeVideoDetail['status_message']
            ], 400);
        }


        $trailer_url_type=null;
        $trailer_url=null;
        $episode_video_list=[];

        $video_url_type=null;
        $video_url=null;

        if(isset($EpisodeVideoDetail['results']) && is_array($EpisodeVideoDetail['results'])) {

            foreach ($EpisodeVideoDetail['results'] as $video) {

                if($video['type'] == 'Trailer' ||  $video['type'] == 'Clip' ){

                    $trailer_url_type= $video['site'];
                    $trailer_url='https://www.youtube.com/watch?v='.$video['key'];

                }else{


                     $video_url_type=$video['site'];

                     $video_url='https://www.youtube.com/watch?v='.$video['key'];


                    $episode_video_list[]=[

                       'video_quality_type'=>$video['site'],
                       'video_quality'=>$video['size'],
                       'quality_video'=>'https://www.youtube.com/watch?v='.$video['key'],
                    ];

                }

            }
        }

        $enable_quality=false;

        if(!empty($episode_video_list)){

            $enable_quality=true;

        }


        function formatDuration($minutes) {
            $hours = floor($minutes / 60);
            $minutes = $minutes % 60;
            return sprintf('%02d:%02d', $hours, $minutes);
        }

        $tvshows = Entertainment::where('tmdb_id',$tvshow_id)->first();
        $season = Season::where('tmdb_id',$tvshow_id)->where('season_index',$season_id)->first();

        $data = [

            'poster_url' => $configurationData['images']['secure_base_url'] . 'original' . $EpisodeDetail['still_path'],
            'poster_tv_url' => $configurationData['images']['secure_base_url'] . 'original' . $EpisodeDetail['still_path'],
            'trailer_url_type'=>$trailer_url_type,
            'trailer_url'=>$trailer_url,
            'name' => $EpisodeDetail['name'],
            'description' => $EpisodeDetail['overview'],
            'duration' => formatDuration($EpisodeDetail['runtime']),
            'is_restricted' => 0,
            'release_date' => $EpisodeDetail['air_date'],
            'access'=>'free',
            'enable_quality'=>$enable_quality,
            'entertainment_id'=>$tvshows->id ?? null,
            'season_id'=>$season->id ?? null,
            'episode_number'=>$episode_id,
            'tmdb_id'=>$tvshow_id,
            'tmdb_season'=>$season_id,
            'video_url_type'=> $video_url_type ?? 'Local',
            'video_url'=> $video_url,
            'episodeStreamContentMappings'=>$episode_video_list,

        ];

             return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);

        }


        public function generateDescription(Request $request)
        {
            $name = $request->input('name');
            $description = $request->input('description');
            $tvshow=$request->input('tvshow');
            $season=$request->input('season');
            $type=$request->input('type');

            $tvshows=Entertainment::Where('id',$tvshow)->first();

            $season=Season::Where('id',$season)->first();

            if( $tvshows && $tvshows){

               $name= $name.'of season'.$season->name. 'of Tvshow of'.$tvshows->name;
            }

            $result = $this->chatGTPService->GenerateDescription($name, $description, $type);

            $result =json_decode( $result, true);

            if (isset($result['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']['message'],
                ], 400);
            }

            return response()->json([

                'success' => true,
                'data' => isset($result['choices'][0]['message']['content']) ? $result['choices'][0]['message']['content'] : null,
            ], 200);
        }

        public function details($id)
    {
        $data = Episode::with([
            'entertainmentdata',
            'seasondata',
            'episodeDownloadMappings',
            'EpisodeStreamContentMapping',
            'plan',

        ])->findOrFail($id);

        $data->poster_url =setBaseUrlWithFileName($data->poster_url);
        $data->formatted_release_date = Carbon::parse($data->release_date)->format('d M, Y');
        $module_title = __('episode.title');
        $show_name = $data->name;
        $route = 'backend.episodes.index';
        return view('episode::backend.episode.details', compact('data','module_title','show_name','route'));
    }

    public function getAccessType(Request $request)
    {
        $tvshow = Entertainment::find($request->tvshow_id);
        $season = Season::find($request->season_id);

        return response()->json([
            'tvshow_access' => $tvshow ? $tvshow->movie_access : null,
            'season_access' => $season ? $season->access : null,
        ]);
    }


}
