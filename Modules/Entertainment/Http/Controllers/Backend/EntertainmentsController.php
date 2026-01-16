<?php

namespace Modules\Entertainment\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\Entertainment\Models\Entertainment;
use Illuminate\Http\Request;
use Modules\Entertainment\Http\Requests\EntertainmentRequest;
use App\Trait\ModuleTrait;
use Modules\Constant\Models\Constant;
use Modules\Subscriptions\Models\Plan;
use Modules\Genres\Models\Genres;
use Modules\CastCrew\Models\CastCrew;
use Modules\Entertainment\Services\EntertainmentService;
use Modules\World\Models\Country;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Entertainment\Models\Subtitle;
use Illuminate\Support\Facades\Storage;

class EntertainmentsController extends Controller
{
    protected string $exportClass = '\App\Exports\EntertainmentExport';


    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
        }

        protected $entertainmentService;

        public function __construct(EntertainmentService $entertainmentService)
        {
            $this->entertainmentService = $entertainmentService;

            $this->traitInitializeModuleTrait(
                'castcrew.castcrew_title',
                'castcrew',

                'fa-solid fa-clipboard-list'
            );
        }


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
                'text' => ' Name',
            ]
        ];
        $export_url = route('backend.entertainments.export');

        return view('entertainment::backend.entertainment.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url'));
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = 'Entertainment'; // Adjust as necessary for dynamic use

        Cache::flush();


        return $this->performBulkAction(Entertainment::class, $ids, $actionType, $moduleName);
    }

    public function store(EntertainmentRequest $request)
    {
        // dd($request->all());
        $videoUrl = $request->input('video_url_input');
        $videoType = $request->input('video_upload_type');

        $data = $request->all();
        if($data['movie_access'] == "pay-per-view"){
            $data['download_status'] = 0;
        }
        // Handle video quality embedded codes
        if (!empty($data['video_quality_type'])) {
            foreach ($data['video_quality_type'] as $key => $type) {
                if ($type === 'Embedded') {
                    // Use the embed input field value instead of URL input
                    if (isset($data['quality_video_embed_input'][$key])) {
                        $data['quality_video_url_input'][$key] = $data['quality_video_embed_input'][$key];
                    }
                } else if ($type === 'URL' || $type === 'YouTube' || $type === 'HLS' || $type === 'Vimeo' || $type === 'x265') {
                    // For URL types, extract iframe src if present
                    if (isset($data['quality_video_url_input'][$key])) {
                        if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $data['quality_video_url_input'][$key], $matches)) {
                            $data['quality_video_url_input'][$key] = $matches[1];
                        }
                    }
                }
            }
        }

        // Handle iframe content differently
        if ($videoType === 'Embedded') {
            // Save the embed code from the correct field
            $data['video_url_input'] = $request->input('embedded');
        } else {
            // Extract URL if it's an iframe, otherwise use as is
            if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $videoUrl, $matches)) {
                $data['video_url_input'] = $matches[1];
            }
        }

        // Handle trailer embed code
        if ($request->trailer_url_type === 'Embedded') {
            $data['trailer_url'] = $request->input('trailer_embedded');
        }

        $data['thumbnail_url'] = !empty($data['tmdb_id']) ? $data['thumbnail_url'] :extractFileNameFromUrl($data['thumbnail_url']);
        $data['poster_url']= !empty( $data['tmdb_id']) ?  $data['poster_url'] : extractFileNameFromUrl($data['poster_url']);
        $data['poster_tv_url']= !empty( $data['tmdb_id']) ?  $data['poster_tv_url'] : extractFileNameFromUrl($data['poster_tv_url']);

            if (isset($data['IMDb_rating'])) {
                $data['IMDb_rating'] = round($data['IMDb_rating'], 1);
            }

            if($request->trailer_url_type == 'Local'){
                $data['trailer_video'] = extractFileNameFromUrl($data['trailer_video']);
            }
            if($request->video_upload_type == 'Local'){
                $data['video_file_input'] = extractFileNameFromUrl($data['video_file_input']);
            }

            $entertainment = $this->entertainmentService->create($data);
              $type = $entertainment->type;
              $message = trans('messages.create_form_movie', ['type' =>ucfirst($type)]);

            // Handle multiple subtitles
            if ($request->enable_subtitle && $request->has('subtitles')) {
                foreach ($request->file('subtitles') as $index => $subtitleInput) {
                    $language = $request->input("subtitles.$index.language");
                    $file = $subtitleInput['subtitle_file'] ?? null;
                    $isDefault = $request->input("subtitles.$index.is_default", false);

                    $lang_arr = Constant::where('type','subtitle_language')->where('value', $language)->first();


                    if ($file) {
                        $extension = strtolower($file->getClientOriginalExtension());
                        if (!in_array($extension, ['srt', 'vtt'])) {
                            throw new \Exception('Only .srt and .vtt files are allowed');
                        }

                        $filename = time().'_'.$index. '_' . str_replace(' ', '_', $file->getClientOriginalName());

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

                        $entertainment->subtitles()->create([
                            'entertainment_id' => $entertainment->id,
                            'language_code' => $language,
                            'language' => $lang_arr->name ?? null,
                            'subtitle_file' => $filename,
                            'is_default' => $isDefault ? 1 : 0,
                            'type' => 'movie',
                        ]);
                    }
                }
            }

            DB::commit();

            $type = $entertainment->type;
            $message = trans('messages.create_form', ['type' => ucfirst($type)]);

            Cache::flush();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => $type == 'movie' ? route('backend.movies.index') : route('backend.tvshows.index')
                ]);
            }

            if($type=='movie'){
                return redirect()->route('backend.movies.index')->with('success', $message);
            }else{
                return redirect()->route('backend.tvshows.index')->with('success', $message);
            }

        }


    public function update_status(Request $request, Entertainment $id)
    {
        $id->update(['status' => $request->status]);

        Cache::flush();

        return response()->json(['status' => true, 'message' => __('messages.status_updated_movie')]);
    }


    public function update_is_restricted(Request $request, Entertainment $id)
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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
// dd('hello');
        $data = Entertainment::where('id', $id)
            ->with([
                'entertainmentGenerMappings',
                'entertainmentCountryMappings',
                'entertainmentStreamContentMappings',
                'entertainmentTalentMappings',
                'subtitles'
            ])
            ->first();


        $tmdb_id = $data->tmdb_id;
        $data->thumbnail_url = setBaseUrlWithFileName($data->thumbnail_url);
        $data->poster_url =setBaseUrlWithFileName($data->poster_url);

        $data->poster_tv_url =setBaseUrlWithFileName($data->poster_tv_url);
        if($data->trailer_url_type =='Local'){

            $data->trailer_url = setBaseUrlWithFileName($data->trailer_url);
        }

        if($data->video_upload_type =='Local'){

            $data->video_url_input = setBaseUrlWithFileName($data->video_url_input);
        }


        $constants = Constant::whereIn('type', ['upload_type', 'movie_language', 'video_quality','subtitle_language'])->get();
        $upload_url_type = $constants->where('type', 'upload_type');
        $movie_language = $constants->where('type', 'movie_language');
        $video_quality = $constants->where('type', 'video_quality');
        $subtitle_language = $constants->where('type', 'subtitle_language');



        $plan = Plan::where('status', 1)->get();
        $genres = Genres::where('status', 1)->get();
        $actors = CastCrew::where('type', 'actor')->get();
        $directors = CastCrew::where('type', 'director')->get();
        $countries = Country::where('status', 1)->get();
        $mediaUrls = getMediaUrls();
        $assets = ['textarea'];

        if ($data->type === 'tvshow') {
            $module_title = __('tvshow.edit_title');
        } else {
            $module_title = __('movie.edit_title');
        }


        $numberOptions = collect(range(1, 10))->mapWithKeys(function ($number) {
            return [$number => $number];
        });

        $data['genres_data'] = $data->entertainmentGenerMappings->pluck('genre_id')->toArray();
        $data['countries'] = $data->entertainmentCountryMappings->pluck('country_id')->toArray();
        $data['actors'] = $data->entertainmentTalentMappings->pluck('talent_id')->toArray();
        $data['directors'] = $data->entertainmentTalentMappings->pluck('talent_id')->toArray();



        return view('entertainment::backend.entertainment.edit', compact(
            'data',
            'tmdb_id',
            'upload_url_type',
            'plan',
            'movie_language',
            'genres',
            'numberOptions',
            'actors',
            'directors',
            'countries',
            'video_quality',
            'mediaUrls',
            'assets',
            'module_title',
            'subtitle_language'

        ));
    }


    public function update(EntertainmentRequest $request, $id)
    {
        $request_data = $request->all();
        if($request_data['movie_access'] == "pay-per-view"){
            $request_data['download_status'] = 0;
        }
        // Handle video quality embedded codes
        if (!empty($request_data['video_quality_type'])) {
            foreach ($request_data['video_quality_type'] as $key => $type) {
                if ($type === 'Embedded') {
                    // Use the embed input field value instead of URL input
                    if (isset($request_data['quality_video_embed_input'][$key])) {
                        $request_data['quality_video_url_input'][$key] = $request_data['quality_video_embed_input'][$key];
                    }
                } else if ($type === 'URL' || $type === 'YouTube' || $type === 'HLS' || $type === 'Vimeo' || $type === 'x265') {
                    // For URL types, extract iframe src if present
                    if (isset($request_data['quality_video_url_input'][$key])) {
                        if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $request_data['quality_video_url_input'][$key], $matches)) {
                            $request_data['quality_video_url_input'][$key] = $matches[1];
                        }
                    }
                }
            }
        }


        // Handle trailer embed code
                if ($request->trailer_url_type === 'Embedded') {
                    $request_data['trailer_url'] = $request->input('trailer_embedded');
                }

        // Handle video embed code and iframe extraction
             $videoUrl = $request->input('video_url_input');
             $videoType = $request->input('video_upload_type');
             if ($videoType === 'Embedded') {
                 $request_data['video_url_input'] = $request->input('video_embedded');
             } else {
                 // Extract URL if it's an iframe, otherwise use as is
                 if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $videoUrl, $matches)) {
                     $request_data['video_url_input'] = $matches[1];
                 }
             }

           $request_data['thumbnail_url'] = !empty($request_data['tmdb_id']) ? $request_data['thumbnail_url'] : extractFileNameFromUrl($request_data['thumbnail_url']);
           $request_data['poster_url'] = !empty($request_data['tmdb_id']) ? $request_data['poster_url'] : extractFileNameFromUrl($request_data['poster_url']);
           $request_data['poster_tv_url'] = !empty($request_data['tmdb_id']) ? $request_data['poster_tv_url'] : extractFileNameFromUrl($request_data['poster_tv_url']);
           $request_data['trailer_video'] = extractFileNameFromUrl($request_data['trailer_video']);
           $request_data['video_file_input'] = isset($request_data['video_file_input']) ? extractFileNameFromUrl($request_data['video_file_input']) : null;

           if (isset($request_data['IMDb_rating'])) {
               $request_data['IMDb_rating'] = round($request_data['IMDb_rating'], 1);
           }

           $entertainment = $this->entertainmentService->getById($id);

           if($request->has('deleted_subtitles')) {
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
                $existingSubtitle = $entertainment->subtitles()->where('language_code', $languageCode)->first();

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
                        $entertainment->subtitles()->create([
                            'entertainment_id' => $entertainment->id,
                            'language_code' => $languageCode,
                            'language' => $lang_arr->name ?? null,
                            'subtitle_file' => $filename,
                            'is_default' => $isDefault ? 1 : 0,
                            'type' => 'movie',
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


        // Handle Poster Image
          if ($request->input('remove_image') == 1) {
              $requestData['poster_url'] = setDefaultImage($request_data['poster_url']);
          } elseif ($request->hasFile('poster_url')) {
              $file = $request->file('poster_url');
              StoreMediaFile($entertainment, $file, 'poster_url');
              $requestData['poster_url'] = $entertainment->getFirstMediaUrl('poster_url');
          } else {
              $requestData['poster_url'] = $entertainment->poster_url;
          }

        // Handle Poster Image
        if ($request->input('remove_image_tv') == 1) {
            $requestData['poster_tv_url'] = setDefaultImage($request_data['poster_tv_url']);

        } elseif ($request->hasFile('poster_tv_url')) {
            $file = $request->file('poster_tv_url');
            StoreMediaFile($entertainment, $file, 'poster_tv_url');
            $requestData['poster_tv_url'] = $entertainment->getFirstMediaUrl('poster_tv_url');
        } else {
            $requestData['poster_tv_url'] = $entertainment->poster_tv_url;
        }

        // Handle Thumbnail Image
        if ($request->input('remove_image_thumbnail') == 1) {
            $requestData['thumbnail_url'] = setDefaultImage($request_data['thumbnail_url']);
        } elseif ($request->hasFile('thumbnail_url')) {
            $file = $request->file('thumbnail_url');
            StoreMediaFile($entertainment, $file, 'thumbnail_url');
            $requestData['thumbnail_url'] = $entertainment->getFirstMediaUrl('thumbnail_url');
        } else {
            $requestData['thumbnail_url'] = $entertainment->thumbnail_url;
        }
        $data = $this->entertainmentService->update($id, $request_data);

        Cache::flush();

        $type = $entertainment->type;
        $message = trans('messages.update_form_movie', ['Form' =>ucfirst($type)]);

        if ($type == 'movie') {
            return redirect()->route('backend.movies.index')
                ->with('success', $message);
        } else if ($type == 'tvshow') {
            return redirect()->route('backend.tvshows.index')
                ->with('success', $message);
        }

  }



    public function destroy($id)
    {
       $entertainment = $this->entertainmentService->getById($id);
       $type=$entertainment->type;
       $entertainment->delete();
       $message = trans('messages.delete_form_movie', ['form' => $type]);
       Cache::flush();
       return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore($id)
    {
        $entertainment = $this->entertainmentService->getById($id);
        $type=$entertainment->type;
        $entertainment->restore();
        $message = trans('messages.restore_form_movie', ['form' =>$type]);
        Cache::flush();
        return response()->json(['message' => $message, 'status' => true], 200);

    }

    public function forceDelete($id)
    {
        $entertainment = $this->entertainmentService->getById($id);
        $type=$entertainment->type;
        $entertainment->forceDelete();
        $message = trans('messages.permanent_delete_form_movie', ['form' =>$type]);
        Cache::flush();
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function downloadOption(Request $request, $id){

        $data = Entertainment::where('id',$id)->with('entertainmentDownloadMappings')->first();

        $module_title =__('messages.download_movie');

        $upload_url_type=Constant::where('type','upload_type')
                            ->whereIn('name', ['URL', 'Local'])
                            ->get();
        $video_quality=Constant::where('type','video_quality')->get();
        Cache::flush();

        return view('entertainment::backend.entertainment.download', compact('data','module_title','upload_url_type','video_quality'));

    }


   public function storeDownloads(Request $request, $id)
    {
        $data = $request->all();
        $this->entertainmentService->storeDownloads($data, $id);
        $message = trans('messages.set_download_url');
        Cache::flush();

        return redirect()->route('backend.movies.index')->with('success', $message);
    }


    public function details($id)
    {
        $data = Entertainment::with([
            'entertainmentGenerMappings',
            'entertainmentStreamContentMappings',
            'entertainmentTalentMappings',
            'entertainmentReviews',
            'season',

        ])->findOrFail($id);


       foreach ($data->entertainmentTalentMappings as $talentMapping) {
    $talentProfile = $talentMapping->talentprofile;

    if ($talentProfile) {
        if (in_array($talentProfile->type, ['actor', 'director'])) {
            $talentProfile->file_url =  setBaseUrlWithFileName($talentProfile->file_url);
        }
    }
}
        $data->poster_url =setBaseUrlWithFileName($data->poster_url);

        $data->formatted_release_date = Carbon::parse($data->release_date)->format('d M, Y');
        if($data->type == "movie"){
            $module_title = __('movie.title');
            $show_name = $data->name;
            $route = 'backend.movies.index';
        }else{
            $module_title = __('tvshow.title');
            $show_name = $data->name;
            $route = 'backend.tvshows.index';
        }

        return view('entertainment::backend.entertainment.details', compact('data','module_title','show_name','route'));
    }



}
