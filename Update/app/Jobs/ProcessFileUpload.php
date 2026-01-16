<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Filemanager\Models\Filemanager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $filemanager;
    public $filePath;
    public $diskType;
    public $originalName;
    public $page_type;
    public $fileType;
    /**
     * Create a new job instance.
     */
    public function __construct(Filemanager $filemanager, $filePath, $diskType, $originalName, $page_type, $fileType)
    {
        $this->filemanager = $filemanager;
        $this->filePath = $filePath;
        $this->diskType = $diskType;
        $this->originalName = $originalName;
        $this->page_type = $page_type;
        $this->fileType = $fileType;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {

            Log::info($this->filePath );

            Log::info($this->filePath );


            if (!Storage::exists($this->filePath)) {
                Log::info("File does not exist at path: {$this->filePath}");
                throw new \Exception("File does not exist at path: {$this->filePath}");
            }

            if($this->page_type == 'season' ) {
                $this->page_type = 'tvshow/season';
            }

            if($this->page_type == 'episode' ) {
                $this->page_type = 'tvshow/episode';
            }



            $file = Storage::get($this->filePath);

            if ($this->diskType === 'local') {

                $folderPath = 'public/' . $this->page_type . '/'. $this->fileType . '/' . $this->filemanager->file_name;

                $directoryPath = 'public/' . $this->page_type . '/' . $this->fileType;

                if(!Storage::disk('local')->exists($directoryPath)) {
                    Log::info("Directory does not exist at path: {$directoryPath}");
                    $absoluteDirectoryPath = storage_path('app/' . $directoryPath);
                    File::makeDirectory($absoluteDirectoryPath, 0775, true, true);
                }

                Storage::disk('local')->put($folderPath, $file);

                $fullPath = storage_path('app/' . $folderPath);
                if (file_exists($fullPath)) {
                    chmod($fullPath, 0664);

                    $dirPath = dirname($fullPath);
                    if (is_dir($dirPath)) {
                        chmod($dirPath, 0775);
                    }
                }
            } else {
                $folderPath =  $this->page_type . '/' . $this->fileType . '/' . $this->filemanager->file_name;
                Storage::disk($this->diskType)->put($folderPath, $file);
            }

            $this->filemanager->save();

            // Delete the unique file (with ID)
            if (Storage::exists($this->filePath)){
                $deleted = Storage::disk('local')->delete($this->filePath);
            }

            // Also delete original filename if it exists in temp/uploads
            if($this->originalName) {
                $originalTempPath = 'temp/uploads/' . $this->originalName;
                if (Storage::disk('local')->exists($originalTempPath)) {
                    Storage::disk('local')->delete($originalTempPath);
                }
            }

            Artisan::call('config:clear');
            Artisan::call('cache:clear');
        } catch (\Exception $e) {

            Log::info("Error processing file upload: " . $e->getMessage());

            throw $e;
        }
    }


}
