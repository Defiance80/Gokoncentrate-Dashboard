<?php

namespace Modules\Entertainment\Services;

use Modules\Entertainment\Repositories\EntertainmentRepositoryInterface;
use  Modules\Genres\Repositories\GenreRepositoryInterface;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Clip;


class EntertainmentService
{
    protected $entertainmentRepository;
    protected $genresRepository;

    public function __construct( EntertainmentRepositoryInterface $entertainmentRepository, GenreRepositoryInterface $genresRepository)
    {
        $this->entertainmentRepository = $entertainmentRepository;
        $this->genresRepository = $genresRepository;
    }

    public function getAll()
    {
        return $this->entertainmentRepository->all();
    }

    public function getById($id)
    {
        return $this->entertainmentRepository->find($id);
    }

    public function create(array $data)
    {

        $cacheKey1 = 'movie_';
        $cacheKey2 = 'tvshow_';

        Cache::flush();

        $data['trailer_url'] = ($data['trailer_url_type'] == 'Local') ? $data['trailer_video'] : $data['trailer_url'];



        if($data['type']=='movie'){

            $data['video_url_input'] = ($data['video_upload_type'] == 'Local') ? $data['video_file_input'] : $data['video_url_input'];

        }else{
            $data['video_url_input']=null;
        }


        $entertainment = $this->entertainmentRepository->create($data);

        if(env('ACTIVE_STORAGE') == 'bunny'){
            $pullBase = rtrim(env('BUNNY_PULL_ZONE', 'https://streamit-demo.b-cdn.net'), '/');

            if (($data['video_upload_type'] ?? null) == 'Local' && !empty($data['video_url_input'])) {
                $filename = basename(parse_url($data['video_url_input'], PHP_URL_PATH));
                $sourceUrl = $pullBase.'/movie/video/'.$filename;
                $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $filename);
                if ($m3u8) { $entertainment->update(['bunny_video_url' => $m3u8]); }
            }

            if (($data['trailer_url_type'] ?? null) == 'Local' && !empty($data['trailer_url'])) {
                $tfile = basename(parse_url($data['trailer_url'], PHP_URL_PATH));
                $type = ($entertainment->type == 'movie') ? 'movie' : 'tvshow';
                $sourceUrl = $pullBase . '/' . $type . '/video/' . $tfile;
                $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $tfile);
                if ($m3u8) { $entertainment->update(['bunny_trailer_url' => $m3u8]); }
            }
        }

        if (!empty($data['genres'])) {
            $this->entertainmentRepository->saveGenreMappings($data['genres'], $entertainment->id);
        }
        if (!empty($data['countries'])) {
            $this->entertainmentRepository->saveCountryMappings($data['countries'], $entertainment->id);
        }

        if (!empty($data['actors'])) {
            $this->entertainmentRepository->saveTalentMappings($data['actors'], $entertainment->id);
        }

        if (!empty($data['directors'])) {
            $this->entertainmentRepository->saveTalentMappings($data['directors'], $entertainment->id);
        }

        if (isset($data['enable_quality']) && $data['enable_quality'] == 1) {
            // Check if the keys are set to avoid undefined key errors
            $videoQuality = isset($data['video_quality']) ? $data['video_quality'] : [];
            $qualityVideoUrlInput = isset($data['quality_video_url_input']) ? $data['quality_video_url_input'] : [];
            $videoQualityType = isset($data['video_quality_type']) ? $data['video_quality_type'] : [];
            $qualityVideo = isset($data['quality_video']) ? $data['quality_video'] : [];

            $this->entertainmentRepository->saveQualityMappings(
                $entertainment->id,
                $videoQuality,
                $qualityVideoUrlInput,
                $videoQualityType,
                $qualityVideo
            );
        }

        // Handle Clips storage
        $clipTypes = isset($data['clip_upload_type']) && is_array($data['clip_upload_type']) ? $data['clip_upload_type'] : [];
        $clipUrls = isset($data['clip_url_input']) && is_array($data['clip_url_input']) ? $data['clip_url_input'] : [];
        $clipFiles = isset($data['clip_file_input']) && is_array($data['clip_file_input']) ? $data['clip_file_input'] : [];
        $clipEmbeds = isset($data['clip_embedded']) && is_array($data['clip_embedded']) ? $data['clip_embedded'] : [];
        $clipPosterUrls = isset($data['clip_poster_url']) && is_array($data['clip_poster_url']) ? $data['clip_poster_url'] : [];
        $clipTvPosterUrls = isset($data['clip_tv_poster_url']) && is_array($data['clip_tv_poster_url']) ? $data['clip_tv_poster_url'] : [];
        $clipTitles = isset($data['clip_title']) && is_array($data['clip_title']) ? $data['clip_title'] : [];

         if (!empty($clipTypes)) {
            $max = max(
                count($clipTypes),
                count($clipUrls),
                count($clipFiles),
                count($clipEmbeds),
                count($clipPosterUrls),
                count($clipTvPosterUrls),
                count($clipTitles)
            );

            for ($i = 0; $i < $max; $i++) {
                $type = $clipTypes[$i] ?? null;
                if (!$type) { continue; }

                $url = null;
                if ($type === 'Local') {
                    $val = $clipFiles[$i] ?? null;
                    if ($val) {
                        $url = function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($val,$entertainment->type) : $val;
                    }
                } elseif ($type === 'Embedded' || $type === 'Embed') {
                    $url = $clipEmbeds[$i] ?? null;
                } else {
                    $val = $clipUrls[$i] ?? null;
                    if ($val) {
                        if (preg_match('/<iframe[^>]+src=[\'\"][^\'\"]+[\'\"]/i', $val, $m)) {
                            if (preg_match('/src=[\'\"][^\'\"]+[\'\"]/i', $m[0], $m2)) {
                                $url = trim(str_replace(['src="','src=\'','"','\''], '', $m2[0]));
                            }
                        }
                        if (!$url) { $url = $val; }
                    }
                }

                if ($url) {
                    $posterUrl = $clipPosterUrls[$i] ?? null;
                    $tvPosterUrl = $clipTvPosterUrls[$i] ?? null;
                    $title = $clipTitles[$i] ?? null;

                    $posterUrl = isset($posterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($posterUrl,$entertainment->type) : $posterUrl;
                    $tvPosterUrl = isset($tvPosterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($tvPosterUrl,$entertainment->type) : $tvPosterUrl;

                    Clip::create([
                        'content_id' => $entertainment->id,
                        'content_type' => $entertainment->type == 'movie' ? 'movie' : 'tv_show',
                        'type' => $type,
                        'url' => $url,
                        'poster_url' => $posterUrl,
                        'tv_poster_url' => $tvPosterUrl,
                        'title' => $title,
                    ]);
                }
            }
        }


        return $entertainment;
    }


    public function update($id, array $data)
    {
        $entertainment = $this->entertainmentRepository->find($id);

        if($entertainment->type=='movie'){

            $cacheKey = 'movie_'.$id;
            Cache::flush();

            $data['trailer_url'] = ($data['trailer_url_type'] == 'Local') ? $data['trailer_video'] : $data['trailer_url'];

            $data['video_url_input'] = ($data['video_upload_type'] == 'Local') ? $data['video_file_input'] : $data['video_url_input'];
          }else{

            $cacheKey = 'tvshow_'.$id;
            Cache::flush();

            $data['trailer_url'] = ($data['trailer_url_type'] == 'Local') ? $data['trailer_video'] : $data['trailer_url'];

          }

        $updated = $this->entertainmentRepository->update($id, $data);

        if(env('ACTIVE_STORAGE') == 'bunny'){
            $pullBase = rtrim(env('BUNNY_PULL_ZONE', 'https://streamit-demo.b-cdn.net'), '/');

            if (($data['video_upload_type'] ?? null) == 'Local' && !empty($data['video_url_input'])) {
                $filename = basename(parse_url($data['video_url_input'], PHP_URL_PATH));
                $sourceUrl = $pullBase.'/movie/video/'.$filename;
                $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $filename);
                if ($m3u8) {
                    $entertainment->update(['bunny_video_url' => $m3u8]);
                }

            }

            if (($data['trailer_url_type'] ?? null) == 'Local' && !empty($data['trailer_url'])) {
                $tfile = basename(parse_url($data['trailer_url'], PHP_URL_PATH));
                $type = ($entertainment->type == 'movie') ? 'movie' : 'tvshow';
                $sourceUrl = $pullBase . '/' . $type . '/video/' . $tfile;
                $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $tfile);
                if ($m3u8) {
                    $entertainment->update(['bunny_trailer_url' => $m3u8]);
                }

            }
        }

        // Handle Clips update (upsert + delete removed)
        $clipIds = isset($data['clip_id']) && is_array($data['clip_id']) ? $data['clip_id'] : [];
        $clipTypes = isset($data['clip_upload_type']) && is_array($data['clip_upload_type']) ? $data['clip_upload_type'] : [];
        $clipUrls = isset($data['clip_url_input']) && is_array($data['clip_url_input']) ? $data['clip_url_input'] : [];
        $clipFiles = isset($data['clip_file_input']) && is_array($data['clip_file_input']) ? $data['clip_file_input'] : [];
        $clipEmbeds = isset($data['clip_embedded']) && is_array($data['clip_embedded']) ? $data['clip_embedded'] : [];
        $clipPosterUrls = isset($data['clip_poster_url']) && is_array($data['clip_poster_url']) ? $data['clip_poster_url'] : [];
        $clipTvPosterUrls = isset($data['clip_tv_poster_url']) && is_array($data['clip_tv_poster_url']) ? $data['clip_tv_poster_url'] : [];
        $clipTitles = isset($data['clip_title']) && is_array($data['clip_title']) ? $data['clip_title'] : [];

        $existingClips = Clip::where('content_id', $entertainment->id)
            ->where('content_type', $entertainment->type == 'movie' ? 'movie' : 'tv_show')
            ->get()
            ->keyBy('id');

        $touchedIds = [];
        if (!empty($clipTypes)) {
            $max = max(
                count($clipTypes),
                count($clipUrls),
                count($clipFiles),
                count($clipEmbeds),
                count($clipPosterUrls),
                count($clipTvPosterUrls),
                count($clipTitles)
            );

            for ($i = 0; $i < $max; $i++) {
                $typeClip = $clipTypes[$i] ?? null;
                if (!$typeClip) { continue; }

                $url = null;
                if ($typeClip === 'Local') {
                    $val = $clipFiles[$i] ?? null;
                    if ($val) {
                        $url = function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($val,$entertainment->type) : $val;
                    }
                } elseif ($typeClip === 'Embedded' || $typeClip === 'Embed') {
                    $url = $clipEmbeds[$i] ?? null;
                } else {
                    $val = $clipUrls[$i] ?? null;
                    if ($val) {
                        if (preg_match('/<iframe[^>]+src=[\'\"][^\'\"]+[\'\"]/i', $val, $m)) {
                            if (preg_match('/src=[\'\"][^\'\"]+[\'\"]/i', $m[0], $m2)) {
                                $url = trim(str_replace(['src=\"','src=\'','\"','\''], '', $m2[0]));
                            }
                        }
                        if (!$url) { $url = $val; }
                    }
                }

                if (!$url) { continue; }

                $existingId = $clipIds[$i] ?? null;
                if ($existingId && isset($existingClips[$existingId])) {
                    $posterUrl = $clipPosterUrls[$i] ?? null;
                    $tvPosterUrl = $clipTvPosterUrls[$i] ?? null;
                    $title = $clipTitles[$i] ?? null;

                    $posterUrl = isset($posterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($posterUrl,$entertainment->type) : $posterUrl;
                    $tvPosterUrl = isset($tvPosterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($tvPosterUrl,$entertainment->type) : $tvPosterUrl;

                    $existingClips[$existingId]->update([
                        'type' => $typeClip,
                        'url' => $url,
                        'poster_url' => $posterUrl,
                        'tv_poster_url' => $tvPosterUrl,
                        'title' => $title,
                    ]);
                    $touchedIds[] = (int)$existingId;
                } else {
                    $posterUrl = $clipPosterUrls[$i] ?? null;
                    $tvPosterUrl = $clipTvPosterUrls[$i] ?? null;
                    $title = $clipTitles[$i] ?? null;

                    $posterUrl = isset($posterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($posterUrl,$entertainment->type) : $posterUrl;
                    $tvPosterUrl = isset($tvPosterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($tvPosterUrl,$entertainment->type) : $tvPosterUrl;

                    $new = Clip::create([
                        'content_id' => $entertainment->id,
                        'content_type' => $entertainment->type == 'movie' ? 'movie' : 'tv_show',
                        'type' => $typeClip,
                        'url' => $url,
                        'poster_url' => $posterUrl,
                        'tv_poster_url' => $tvPosterUrl,
                        'title' => $title,
                    ]);
                    $touchedIds[] = (int)$new->id;
                }
            }
        }

        $toDelete = $existingClips->keys()->diff($touchedIds);
        if ($toDelete->count() > 0) {
            Clip::whereIn('id', $toDelete)->delete();
        }

        return $updated;
    }

    public function delete($id)
    {
         $entertainment = $this->entertainmentRepository->find($id);

        if($entertainment->type=='movie'){

            $cacheKey = 'movie_'.$id;
            Cache::flush();

          }else{

            $cacheKey = 'tvshow_'.$id;
            Cache::flush();

          }

        return $this->entertainmentRepository->delete($id);
    }

    public function restore($id)
    {
        $entertainment = $this->entertainmentRepository->find($id);

        if($entertainment->type=='movie'){

            $cacheKey = 'movie_'.$id;
            Cache::flush();

          }else{

            $cacheKey = 'tvshow_'.$id;
            Cache::flush();

          }

        return $this->entertainmentRepository->restore($id);
    }

    public function forceDelete($id)
    {
        $entertainment = $this->entertainmentRepository->find($id);

        if($entertainment->type=='movie'){

            $cacheKey = 'movie_'.$id;
            Cache::flush();

          }else{

            $cacheKey = 'tvshow_'.$id;
            Cache::flush();

          }

        return $this->entertainmentRepository->forceDelete($id);
    }


 public function getDataTable(Datatables $datatable, $filter, $type)
    {
        $query = $this->getFilteredData($filter, $type)
            ->withCount([
                'entertainmentLike' => function ($query) use ($type) {
                    $query->where('is_like', 1)->where('type', $type);
                },
                'entertainmentView' => function ($query) {
                    // Add custom logic here if needed
                }
            ]);

    return $datatable->eloquent($query)
        ->editColumn('thumbnail_url', function ($data) {
            $genres = $this->entertainmentRepository->movieGenres($data->id);
            $countries = $this->entertainmentRepository->moviecountries($data->id);
            $type = 'movie';
            $releaseDate = $data->release_date ? formatDate($data->release_date) : '';
            $imageUrl = setBaseUrlWithFileName($data->thumbnail_url, 'image', 'movie');
            return view('components.media-item', [
                'thumbnail' => $imageUrl,
                'name' => $data->name,
                'genre' => implode(', ', $genres->toArray()),
                'country' => implode(', ', $countries->toArray()),
                'releaseDate' => $releaseDate,
                'type' => $type
            ])->render();
        })
        ->addColumn('like_count', function ($data) {
            return $data->entertainment_like_count > 0 ? $data->entertainment_like_count : '-';
        })
        ->orderColumn('like_count', 'entertainment_like_count $1')
        ->addColumn('watch_count', function ($data) {
            return $data->entertainment_view_count > 0 ? $data->entertainment_view_count : '-';
        })
        ->orderColumn('watch_count', 'entertainment_view_count $1')
        ->filterColumn('thumbnail_url', function ($query, $keyword) {
            if (!empty($keyword)) {
                $query->where(function($q) use ($keyword) {
                    // Search by movie name
                    $q->where('name', 'like', '%' . $keyword . '%')
                      // Search by genre names
                      ->orWhereHas('entertainmentGenerMappings.genre', function ($genreQuery) use ($keyword) {
                          $genreQuery->where('name', 'like', '%' . $keyword . '%');
                      })
                      // Search by language
                      ->orWhere('language', 'like', '%' . $keyword . '%')
                      // Search by access type (paid, free, pay-per-view)
                      ->orWhere('movie_access', 'like', '%' . $keyword . '%')
                      // Search by plan name
                      ->orWhereHas('plan', function ($planQuery) use ($keyword) {
                          $planQuery->where('name', 'like', '%' . $keyword . '%');
                      });
                });
            }
        })
        ->editColumn('plan_id', function ($data) {
            return $data->movie_access === 'pay-per-view' ? '-' : optional($data->plan)->name ?? '-';
        })
        ->filterColumn('plan_id', function ($query, $keyword) {
            if (!empty($keyword)) {
                $query->whereHas('plan', function ($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                });
            }
        })
        ->addColumn('check', function ($data) {
            return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-' . $data->id . '" name="datatable_ids[]" value="' . $data->id . '" data-type="entertainment" onclick="dataTableRowCheck(' . $data->id . ',this)">';
        })
        ->addColumn('action', function ($data) {
            return view('entertainment::backend.entertainment.action', compact('data'));
        })
        ->editColumn('status', function ($row) {
            $checked = $row->status ? 'checked="checked"' : '';
            $disabled = $row->trashed() ? 'disabled' : '';

            return '
                <div class="form-check form-switch">
                    <input type="checkbox" data-url="' . route('backend.entertainments.update_status', $row->id) . '"
                        data-token="' . csrf_token() . '" class="switch-status-change form-check-input"
                        id="datatable-row-' . $row->id . '" name="status" value="' . $row->id . '" ' . $checked . ' ' . $disabled . '>
                </div>';
        })
        ->editColumn('is_restricted', function ($row) {
            $checked = $row->is_restricted ? 'checked' : '';
            $disabled = $row->trashed() ? 'disabled' : '';

            return '
                <div class="form-check form-switch">
                    <input type="checkbox"
                        class="switch-status-change form-check-input"
                        data-id="' . $row->id . '"
                        data-url="' . route('backend.entertainments.update_is_restricted', $row->id) . '"
                        data-token="' . csrf_token() . '"
                        ' . $checked . ' ' . $disabled . '>
                </div>';
        })
        ->editColumn('updated_at', fn($data) => formatUpdatedAt($data->updated_at))
        ->orderColumns(['id'], '-:column $1')
        ->rawColumns(['action', 'status', 'check', 'thumbnail_url', 'is_restricted'])
        ->toJson();
}
    public function getFilteredData($filter, $type)
    {
        $query = $this->entertainmentRepository->query();

        if($type!=null){

            $query = $query->where('type',$type);
        }

        if (isset($filter['movie_name']) && !empty($filter['movie_name'])) {
            $searchTerm = $filter['movie_name'];
            $query->where(function($q) use ($searchTerm) {
                // Search by movie name
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  // Search by genre names
                  ->orWhereHas('entertainmentGenerMappings.genre', function ($genreQuery) use ($searchTerm) {
                      $genreQuery->where('name', 'like', '%' . $searchTerm . '%');
                  })
                  // Search by language
                  ->orWhere('language', 'like', '%' . $searchTerm . '%')
                  // Search by access type (paid, free, pay-per-view)
                  ->orWhere('movie_access', 'like', '%' . $searchTerm . '%')
                  // Search by plan name
                  ->orWhereHas('plan', function ($planQuery) use ($searchTerm) {
                      $planQuery->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }


        if (isset($filter['plan_id']) && !empty($filter['plan_id'])) {
            $query->where('plan_id', $filter['plan_id']);
        }

        if (isset($filter['movie_access']) && !empty($filter['movie_access'])) {
            $query->where('movie_access', $filter['movie_access']);
        }

        if (isset($filter['language']) && !empty($filter['language'])) {
            $query->where('language', $filter['language']);
        }

        if (isset($filter['gener']) && !empty($filter['gener'])) {
            $query->whereHas('entertainmentGenerMappings', function ($q) use ($filter) {
                $q->where('genre_id', $filter['gener']);
            });
        }

        if (isset($filter['actor_id']) && !empty($filter['actor_id'])) {
            $query->whereHas('entertainmentTalentMappings', function ($q) use ($filter) {
                $q->where('talent_id', $filter['actor_id'])
                  ->whereHas('talentprofile', function ($subQuery) {
                      $subQuery->where('type', 'actor');
                  });
            });
        }

        if (isset($filter['director_id']) && !empty($filter['director_id'])) {
            $query->whereHas('entertainmentTalentMappings', function ($q) use ($filter) {
                $q->where('talent_id', $filter['director_id'])
                  ->whereHas('talentprofile', function ($subQuery) {
                      $subQuery->where('type', 'director');
                  });
            });
        }

        if (isset($filter['column_status'])) {
            $query->where('status', $filter['column_status']);
        }

        return $query;
    }

    public function storeDownloads(array $data, $id)
    {
        return $this->entertainmentRepository->storeDownloads($data, $id);
    }



    public function getEntertainmentList($perPage, $searchTerm = null)
    {
        return $this->entertainmentRepository->list($perPage, $searchTerm);
    }

}
