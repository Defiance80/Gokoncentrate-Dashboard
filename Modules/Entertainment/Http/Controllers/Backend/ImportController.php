<?php

namespace Modules\Entertainment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    /**
     * Handle import for different entertainment types
     */
    public function import(Request $request)
    {
        $type = $request->input('type', 'unknown');

        try {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,xlsx,xls,txt|max:10240', // 10MB
            'type' => 'required|in:movie,tvshow,season,episode,video,genre,castcrew,tv_channel,tv_category,user'
        ]);

            $type = $request->input('type');
            $file = $request->file('import_file');

            // Validate file content matches the expected type
            $validationResult = $this->validateFileContent($file, $type);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'File validation failed',
                    'data' => [
                        'status' => 'error',
                        'message' => $validationResult['message'],
                        'type' => $type
                    ]
                ], 400);
            }

            $filename = uniqid($type . '_') . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("imports/{$type}s", $filename);

            // Validate data before dispatching job
            if ($type === 'tvshow') {
                $validationResult = \App\Jobs\ImportTvShowsJob::validateImportData($path);
                if (!$validationResult['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data validation failed',
                        'data' => [
                            'status' => 'error',
                            'message' => $validationResult['message'],
                            'errors' => $validationResult['errors'] ?? [],
                            'type' => $type
                        ]
                    ], 400);
                }
            } elseif ($type === 'movie') {
                $validationResult = \App\Jobs\ImportMoviesJob::validateImportData($path);
                if (!$validationResult['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data validation failed',
                        'data' => [
                            'status' => 'error',
                            'message' => $validationResult['message'],
                            'errors' => $validationResult['errors'] ?? [],
                            'type' => $type
                        ]
                    ], 400);
                }
            } elseif ($type === 'season') {
                $validationResult = \App\Jobs\ImportSeasonsJob::validateImportData($path);
                if (!$validationResult['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data validation failed',
                        'data' => [
                            'status' => 'error',
                            'message' => $validationResult['message'],
                            'errors' => $validationResult['errors'] ?? [],
                            'type' => $type
                        ]
                    ], 400);
                }
            } elseif ($type === 'episode') {
                $validationResult = \App\Jobs\ImportEpisodesJob::validateImportData($path);
                if (!$validationResult['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data validation failed',
                        'data' => [
                            'status' => 'error',
                            'message' => $validationResult['message'] ?? __('messages.validation_errors_found'),
                            'errors' => $validationResult['errors'] ?? [],
                            'type' => $type
                        ]
                    ], 400);
                }
            } elseif ($type === 'video') {
                $validationResult = \App\Jobs\ImportVideosJob::validateImportData($path);
                if (!$validationResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data validation failed',
                        'data' => [
                            'status' => 'error',
                            'message' => __('messages.validation_errors_found'),
                            'errors' => $validationResult['errors'] ?? [],
                            'type' => $type
                        ]
                    ], 400);
                }
            } elseif ($type === 'genre') {
                $validationResult = \App\Jobs\ImportGenresJob::validateImportData($path);
                if (!$validationResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data validation failed',
                        'data' => [
                            'status' => 'error',
                            'message' => __('messages.validation_errors_found'),
                            'errors' => $validationResult['errors'] ?? [],
                            'type' => $type
                        ]
                    ], 400);
                }
            } elseif ($type === 'castcrew') {
                $validationResult = \App\Jobs\ImportCastCrewJob::validateImportData($path);
                if (!$validationResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data validation failed',
                        'data' => [
                            'status' => 'error',
                            'message' => __('messages.validation_errors_found'),
                            'errors' => $validationResult['errors'] ?? [],
                            'type' => $type
                        ]
                    ], 400);
                }
        } elseif ($type === 'tv_channel') {
            $validationResult = \App\Jobs\ImportTVChannelJob::validateImportData($path);
            if (!$validationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data validation failed',
                    'data' => [
                        'status' => 'error',
                        'message' => __('messages.validation_errors_found'),
                        'errors' => $validationResult['errors'] ?? [],
                        'type' => $type
                    ]
                ], 400);
            }
        } elseif ($type === 'tv_category') {
            $validationResult = \App\Jobs\ImportTVCategoryJob::validateImportData($path);
            if (!$validationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data validation failed',
                    'data' => [
                        'status' => 'error',
                        'message' => __('messages.validation_errors_found'),
                        'errors' => $validationResult['errors'] ?? [],
                        'type' => $type
                    ]
                ], 400);
            }
        } elseif ($type === 'user') {
            $validationResult = \App\Jobs\ImportUsersJob::validateImportData($path);
            if (!$validationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data validation failed',
                    'data' => [
                        'status' => 'error',
                        'message' => $validationResult['message'],
                        'errors' => $validationResult['errors'] ?? [],
                        'type' => $type
                    ]
                ], 400);
            }
        }

            // Dispatch appropriate job based on type
            $this->dispatchImportJob($type, $path, auth()->user()->id);

            return response()->json([
                'success' => true,
                'message' => __('messages.import_background_started'),
                'data' => [
                    'status' => 'processing',
                    'message' => "Your {$type} file has been uploaded successfully. Import process has started in the background. You will receive an email notification once the import is complete with detailed results.",
                    'type' => $type
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error("{$type} import error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'data' => [
                    'status' => 'error',
                    'message' => "Failed to upload {$type} file. Please try again.",
                    'type' => $type
                ]
            ], 500);
        }
    }

    /**
     * Download sample file for different types
     */
    public function downloadSample(Request $request)
    {
        $type = $request->input('type', 'movie');
        $castcrewType = $request->input('castcrew_type', 'actor'); // Get actor or director from request

        $exportClass = $this->getExportClass($type);

        if (!$exportClass) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type specified'
            ], 400);
        }

        // For castcrew, pass the castcrew_type parameter to get actor or director specific data
        if ($type === 'castcrew') {
            return Excel::download(new $exportClass($castcrewType), "{$castcrewType}s_import_sample.csv", \Maatwebsite\Excel\Excel::CSV);
        } else {
            return Excel::download(new $exportClass(), "{$type}s_import_sample.csv", \Maatwebsite\Excel\Excel::CSV);
        }
    }

    /**
     * Get required columns for different types
     */
    public function getRequiredColumns(Request $request)
    {
        $type = $request->input('type', 'movie');

        $columns = $this->getColumnsForType($type);

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'columns' => $columns
            ]
        ]);
    }

    /**
     * Dispatch appropriate import job based on type
     */
    private function dispatchImportJob($type, $path, $userId)
    {
        $jobClass = $this->getJobClass($type);

        if (!$jobClass) {
            throw new \Exception("No import job found for type: {$type}");
        }

        if ($type === 'castcrew') {
            $jobClass::dispatch($path, $userId, $type);
        } elseif ($type === 'tv_channel' || $type === 'tv_category') {
            $jobClass::dispatch($path, $userId);
        } elseif ($type === 'user') {
            // Run user import synchronously for testing
            $job = new $jobClass($path, $userId);
            $job->handle();
        } else {
            $jobClass::dispatch($path, $userId);
        }
    }

    /**
     * Get job class for type
     */
    private function getJobClass($type)
    {
        $jobMap = [
            'movie' => \App\Jobs\ImportMoviesJob::class,
            'tvshow' => \App\Jobs\ImportTvShowsJob::class,
            'season' => \App\Jobs\ImportSeasonsJob::class,
            'episode' => \App\Jobs\ImportEpisodesJob::class,
            'video' => \App\Jobs\ImportVideosJob::class,
            'genre' => \App\Jobs\ImportGenresJob::class,
            'castcrew' => \App\Jobs\ImportCastCrewJob::class,
            'actor' => \App\Jobs\ImportCastCrewJob::class,
            'director' => \App\Jobs\ImportCastCrewJob::class,
            'tv_channel' => \App\Jobs\ImportTVChannelJob::class,
            'tv_category' => \App\Jobs\ImportTVCategoryJob::class,
            'user' => \App\Jobs\ImportUsersJob::class,
        ];

        return $jobMap[$type] ?? null;
    }

    /**
     * Get export class for type
     */
    private function getExportClass($type)
    {
        $exportMap = [
            'movie' => \App\Exports\ScopedMoviesSampleExport::class,
            'tvshow' => \App\Exports\ScopedTvShowsSampleExport::class,
            'season' => \App\Exports\ScopedSeasonsSampleExport::class,
            'episode' => \App\Exports\ScopedEpisodesSampleExport::class,
            'video' => \App\Exports\ScopedVideosSampleExport::class,
            'genre' => \App\Exports\ScopedGenresSampleExport::class,
            'castcrew' => \App\Exports\ScopedCastCrewSampleExport::class,
            'actor' => \App\Exports\ScopedCastCrewSampleExport::class,
            'director' => \App\Exports\ScopedCastCrewSampleExport::class,
            'tv_channel' => \App\Exports\ScopedTVChannelSampleExport::class,
            'tv_category' => \App\Exports\ScopedTVCategorySampleExport::class,
            'user' => \App\Exports\ScopedUserSampleExport::class,
        ];

        return $exportMap[$type] ?? null;
    }

    /**
     * Get required columns for each type
     */
    private function getColumnsForType($type)
    {
        $requiredColumnsList = __('messages.required_columns_list');
        return $requiredColumnsList[$type] ?? [];
    }

    /**
     * Validate file content matches the expected type
     */
    private function validateFileContent($file, $type)
    {
        try {
            $extension = $file->getClientOriginalExtension();
            $fullPath = $file->getPathname();

            // Read first few lines to check headers
            $headers = [];
            if ($extension === 'csv') {
                $handle = fopen($fullPath, 'r');
                if ($handle !== false) {
                    $headers = fgetcsv($handle);
                    fclose($handle);
                }
            } else {
                // For Excel files, we'll use a simple approach
                $excelData = \Maatwebsite\Excel\Facades\Excel::toArray([], $file);
                if (empty($excelData) || !isset($excelData[0]) || !isset($excelData[0][0])) {
                    return [
                        'valid' => false,
                        'message' => 'File appears to be empty or invalid format.'
                    ];
                }
                $headers = $excelData[0][0];
            }

            if (empty($headers)) {
                return [
                    'valid' => false,
                    'message' => 'File appears to be empty or invalid format.'
                ];
            }

            // Check if headers match expected type
            $expectedHeaders = $this->getExpectedHeaders($type);
            $headerMatch = $this->checkHeaderMatch($headers, $expectedHeaders, $type);

            if (!$headerMatch['match']) {
                if (($headerMatch['reason'] ?? '') === 'missing' && !empty($headerMatch['missing'])) {
                    return [
                        'valid' => false,
                        'message' => 'This file is missing required ' . $type . ' headers: ' . implode(', ', $headerMatch['missing']) . ". Please use a proper {$type} import file."
                    ];
                }
                return [
                    'valid' => false,
                    'message' => "This file appears to be for {$headerMatch['detected_type']} import, but you selected {$type} import. Please select the correct import type or upload the appropriate file."
                ];
            }

            // Additional validation for all types - check for specific headers
            $typeValidation = $this->validateTypeSpecificHeaders($type, $headers);
            if (!$typeValidation['valid']) {
                return [
                    'valid' => false,
                    'message' => $typeValidation['message']
                ];
            }

            return ['valid' => true];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error reading file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get expected headers for each type
     */
    private function getExpectedHeaders($type)
    {
        $headers = [
            'movie' => ['Name', 'Description', 'Type', 'Movie Access', 'Status', 'Language', 'IMDb Rating', 'Content Rating', 'Duration', 'Release Date', 'Is Restricted', 'Trailer URL Type', 'Trailer URL', 'Thumbnail URL', 'Poster URL', 'Poster TV URL', 'Video Upload Type', 'Video URL Input', 'Genres', 'Actors', 'Directors', 'Countries', 'Plan ID', 'Price', 'Purchase Type', 'Access Duration', 'Discount', 'Available For', 'Enable Quality', 'Enable Subtitle', 'Genre File URL', 'Genre Description', 'Actor Image URL', 'Actor Bio', 'Actor Date of Birth', 'Actor Place of Birth', 'Director Image URL', 'Director Bio', 'Director Date of Birth', 'Director Place of Birth'],
            'tvshow' => ['Name', 'Description', 'Type', 'Tv Show Access', 'Status', 'Language', 'IMDb Rating', 'Content Rating', 'Duration', 'Release Date', 'Is Restricted', 'Trailer URL Type', 'Trailer URL', 'Thumbnail URL', 'Poster URL', 'Poster TV URL', 'Video Upload Type', 'Video URL Input', 'Genres', 'Actors', 'Directors', 'Countries', 'Plan ID', 'Price', 'Purchase Type', 'Access Duration', 'Discount', 'Available For', 'Enable Quality', 'Enable Subtitle'],
            'season' => ['Name', 'Description', 'TV Show', 'Season Index', 'Access', 'Status', 'Trailer URL Type', 'Trailer URL', 'Poster URL', 'Poster TV URL', 'Plan ID', 'Price', 'Purchase Type', 'Access Duration', 'Discount', 'Available For'],
            'episode' => ['Name', 'Description', 'TV Show', 'Season', 'Episode Number', 'Access', 'Status', 'Trailer URL Type', 'Trailer URL', 'Poster URL', 'Poster TV URL', 'Video Upload Type', 'Video URL Input', 'Plan ID', 'IMDb Rating', 'Content Rating', 'Duration', 'Release Date', 'Is Restricted', 'Price', 'Purchase Type', 'Access Duration', 'Discount', 'Available For'],
            'video' => ['Name', 'Description', 'Access', 'Plan ID', 'Duration', 'Release Date', 'Poster URL', 'Poster TV URL', 'Video Upload Type', 'Video URL', 'Quality', 'Quality Type', 'Quality URL', 'Status'],
            'genre' => ['Name', 'Description', 'Status', 'Image URL'],
            'castcrew' => ['Name', 'Type', 'Bio', 'Place of Birth', 'Date of Birth', 'Designation', 'Image URL'],
            'tv_channel' => ['Name', 'Category', 'Description', 'Access', 'Plan', 'Poster URL', 'Poster TV URL', 'Type', 'Stream Type', 'Server URL', 'Server URL1', 'Embedded', 'Status'],
            'tv_category' => ['Name', 'Description', 'Status', 'File URL'],
            'user' => ['First Name', 'Last Name', 'Email', 'File URL', 'Gender', 'Date of Birth', 'Mobile', 'Password', 'Address']
        ];

        return $headers[$type] ?? [];
    }

    /**
     * Validate type-specific headers for all import types
     */
    private function validateTypeSpecificHeaders($type, $headers)
    {
        $headersLower = array_map('strtolower', $headers);

        // Define required headers for each type
        $typeRequirements = [
            'movie' => [
                'required' => ['name', 'description', 'type', 'movie access', 'status'],
                'forbidden' => ['tv show', 'season index', 'episode number', 'video type', 'video access']
            ],
            'tvshow' => [
                'required' => ['name', 'description', 'type', 'tv show access', 'status'],
                'forbidden' => ['season index', 'episode number', 'video type', 'video access']
            ],
            'season' => [
                'required' => ['name', 'description', 'tv show', 'season index', 'status', 'access'],
                'forbidden' => ['episode number', 'video type', 'video access', 'video upload type', 'video url input']
            ],
            'episode' => [
                'required' => ['name', 'description', 'episode number', 'season', 'tv show', 'status'],
                'forbidden' => ['season index', 'video type', 'video access']
            ],
            'video' => [
                'required' => ['name', 'description', 'access', 'duration'],
                'forbidden' => ['tv show', 'season index', 'episode number', 'movie access', 'tv show access', 'season', 'episode number']
            ],
            'genre' => [
                'required' => ['name'],
                'forbidden' => ['tv show', 'season index', 'episode number', 'movie access', 'tv show access', 'season', 'episode number', 'video type', 'video access', 'access', 'duration']
            ],
            'castcrew' => [
                'required' => ['name', 'type'],
                'forbidden' => ['tv show', 'season index', 'episode number', 'movie access', 'tv show access', 'season', 'episode number', 'video type', 'video access', 'access', 'duration']
            ],
        'tv_channel' => [
            'required' => ['name', 'category', 'description', 'access', 'status'],
            'forbidden' => ['tv show', 'season index', 'episode number', 'movie access', 'tv show access', 'season', 'episode number', 'video type', 'video access']
        ],
        'tv_category' => [
            'required' => ['name', 'description', 'status'],
            'forbidden' => ['tv show', 'season index', 'episode number', 'movie access', 'tv show access', 'season', 'episode number', 'video type', 'video access', 'category', 'access', 'plan']
        ],
        'user' => [
            'required' => ['first name', 'last name', 'email', 'mobile', 'file url', 'gender', 'password', 'date of birth', 'address'],
            'forbidden' => ['tv show', 'season index', 'episode number', 'movie access', 'tv show access', 'season', 'episode number', 'video type', 'video access', 'category', 'access', 'plan', 'name', 'description', 'type']
        ]
        ];

        $requirements = $typeRequirements[$type] ?? null;
        if (!$requirements) {
            return ['valid' => true]; // Unknown type, skip validation
        }

        // Check for missing required headers
        $missingHeaders = [];
        foreach ($requirements['required'] as $requiredHeader) {
            if (!in_array($requiredHeader, $headersLower)) {
                $missingHeaders[] = $requiredHeader;
            }
        }

        if (!empty($missingHeaders)) {
            return [
                'valid' => false,
                'message' => "This file is missing required {$type} headers: " . implode(', ', $missingHeaders) . ". Please use a proper {$type} import file."
            ];
        }

        // Check for forbidden headers (headers from other import types)
        $foundForbiddenHeaders = [];
        foreach ($requirements['forbidden'] as $forbiddenHeader) {
            if (in_array($forbiddenHeader, $headersLower)) {
                $foundForbiddenHeaders[] = $forbiddenHeader;
            }
        }

        if (!empty($foundForbiddenHeaders)) {
            // Determine what type this file actually is
            $detectedType = $this->detectFileTypeFromHeaders($headersLower);
            return [
                'valid' => false,
                'message' => "This file appears to be a {$detectedType} import file (contains: " . implode(', ', $foundForbiddenHeaders) . "). Please use a {$type} import file instead."
            ];
        }

        return ['valid' => true];
    }

    /**
     * Detect file type from headers
     */
    private function detectFileTypeFromHeaders($headersLower)
    {
        if (in_array('video type', $headersLower) && in_array('video access', $headersLower)) {
            return 'video';
        }
        if (in_array('name', $headersLower) && in_array('type', $headersLower) && !in_array('tv show', $headersLower) && !in_array('episode number', $headersLower) && !in_array('season index', $headersLower) && !in_array('movie access', $headersLower) && !in_array('tv show access', $headersLower) && !in_array('video type', $headersLower) && !in_array('video access', $headersLower) && !in_array('access', $headersLower) && !in_array('duration', $headersLower)) {
            return 'castcrew';
        }
        if (in_array('name', $headersLower) && !in_array('tv show', $headersLower) && !in_array('episode number', $headersLower) && !in_array('season index', $headersLower) && !in_array('movie access', $headersLower) && !in_array('tv show access', $headersLower) && !in_array('video type', $headersLower) && !in_array('video access', $headersLower) && !in_array('access', $headersLower) && !in_array('duration', $headersLower) && !in_array('type', $headersLower)) {
            return 'genre';
        }
        if (in_array('episode number', $headersLower) && in_array('season', $headersLower)) {
            return 'episode';
        }
        if (in_array('season index', $headersLower) && in_array('tv show', $headersLower)) {
            return 'season';
        }
        if (in_array('tv show access', $headersLower)) {
            return 'tvshow';
        }
        if (in_array('movie access', $headersLower)) {
            return 'movie';
        }
        return 'unknown';
    }

    /**
     * Check if headers match expected type
     */
    private function checkHeaderMatch($fileHeaders, $expectedHeaders, $type)
    {
        $fileHeadersLower = array_map('strtolower', $fileHeaders);
        $expectedHeadersLower = array_map('strtolower', $expectedHeaders);

        $missing = [];
        foreach ($expectedHeadersLower as $expectedHeader) {
            if (!in_array($expectedHeader, $fileHeadersLower)) {
                $missing[] = $expectedHeader;
            }
        }
        if (!empty($missing)) {
            return [
                'match' => false,
                'reason' => 'missing',
                'missing' => $missing,
                'detected_type' => $type
            ];
        }

        return ['match' => true];
    }

}
