<?php

namespace App\Jobs;

use App\Models\FileUpload;
use App\Models\Product;
use App\Events\UploadStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use DB;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileUploadId;
    protected $fileRowData;
    
    /**
     * Create a new job instance.
     */
    public function __construct($uploadId, $fileRowData = [])
    {
        $this->fileUploadId = $uploadId;
        $this->fileRowData = $fileRowData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->fileRowData)) return;

        $now = now()->toDateTimeString();
        
        $processed = 0;
        foreach ($this->fileRowData as $rowIndex => $rowValue) {
            // $rowIndex['updated_at'] = $now;
            // $rowIndex['created_at'] = $rowIndex['created_at'] ?? $now;

            $this->fileRowData[$rowIndex]['updated_at'] = $now;
            $this->fileRowData[$rowIndex]['created_at'] = $this->fileRowData[$rowIndex]['created_at'] ?? $now;
        
            $processed++;
        }

        $updateColumns = Product::FILE_COLUMNS;

        DB::table('products')->upsert($this->fileRowData, ['unique_key'], $updateColumns);

        $fileupload = FileUpload::whereId($this->fileUploadId)->update([
            'status' => 'completed',
            'processed_rows' => $processed,
            'message' => 'Processed successfully',
        ]);

        event(new UpdateFileUploadStatus($fileupload));

        return;
    }
}
