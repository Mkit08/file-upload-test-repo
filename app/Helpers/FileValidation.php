<?php

namespace App\Helpers;

use App\Events\UpdateFileUploadStatus;
use App\Models\FileUpload;
use Illuminate\Support\Str;
use Illuminate\Support\LazyCollection;

class FileValidation
{
    protected $fileUpload;
    protected $status;
    protected $validationMsg;
    protected $validatedFileRow;

    public function __construct(FileUpload $fileUpload) {
        $this->fileUpload = $fileUpload;
    }

    public function setStatus($status = 200)
    {
        $this->status = $status;
    }

    public function setValidationMessage($validationMessage = '')
    {
        $this->validationMsg = $validationMessage;
    }

    public function setValidatedFileRow($validatedFileRow = [])
    {
        $this->validatedFileRow = $validatedFileRow;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getValidationMessage()
    {
        return $this->validationMsg;
    }

     public function returnParams()
    {
        return [
            'status' => $this->status,
            'message' => $this->validationMsg,
            'file_row_data' => $this->validatedFileRow,
        ];
    }

    public function validateFileInput()
    {
        $path = storage_path('app/public/' . $this->fileUpload->file_path);

        $fileRow = [];
        $failedRows = [];
        if (! file_exists($path)) {
            $this->fileUpload->update([
                'status' => 'failed',
                'message' => "File not found at path: {$this->fileUpload->file_path}"
            ]);

            $this->setStatus(500);
            $this->setValidationMessage("File not found at path: {$this->fileUpload->file_path}");
            $this->setValidatedFileRow($fileRow);

            event(new UpdateFileUploadStatus($this->fileUpload));
            
            $returnData = $this->returnParams();

            return $returnData;
        }

        $processed = 0;
        $errors = 0;
        $failedRows = [];

        $raw = file_get_contents($path);
        $raw = preg_replace('/^\\xEF\\xBB\\xBF/', '', $raw);
        $utf8 = @iconv('UTF-8', 'UTF-8//IGNORE', $raw);
        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'upload_clean_' . uniqid() . '.csv';
        file_put_contents($tmpPath, $utf8);

        // try {
            $stream = fopen($tmpPath, 'r');
            
            if ($stream === false) {
                $this->fileUpload->update([
                    'status' => 'failed',
                    'message' => 'Unable to open temporary cleaned CSV file',
                ]);

                $this->setStatus(500);
                $this->setValidationMessage('Unable to open temporary cleaned CSV file');
                $this->setValidatedFileRow($fileRow);

                event(new UpdateFileUploadStatus($this->fileUpload));
                
                $returnData = $this->returnParams();

                return $returnData;
            }
            
            $header = fgetcsv($stream, 0, ',');
            if ($header === false) {
                $this->fileUpload->update([
                    'status' => 'failed',
                    'message' => 'CSV header could not be read or file is empty.',
                ]);

                $this->setStatus(500);
                $this->setValidationMessage('CSV header could not be read or file is empty.');
                $this->setValidatedFileRow($fileRow);

                event(new UpdateFileUploadStatus($this->fileUpload));
                
                $returnData = $this->returnParams();

                return $returnData;
            }

            $normalizedHeader = array_map(function ($h) {
                return Str::of($h)->trim()->upper()->__toString();
            }, $header);

            $uniqueColIndex = null;
            foreach ($normalizedHeader as $i => $col) {
                if ($col === 'UNIQUE_KEY') {
                    $uniqueColIndex = $i;
                    break;
                }
            }

            if ($uniqueColIndex === null) {
                fclose($stream);

                $this->fileUpload->update([
                    'status' => 'failed',
                    'message' => 'UNIQUE_KEY column not found in CSV header. Please include a column named UNIQUE_KEY.',
                ]);

                $this->setStatus(500);
                $this->setValidationMessage('UNIQUE_KEY column not found in CSV header. Please include a column named UNIQUE_KEY.');
                $this->setValidatedFileRow($fileRow);

                event(new UpdateFileUploadStatus($this->fileUpload));

                $returnData = $this->returnParams();

                return $returnData;
            }

            $indexToHeader = [];
            foreach ($header as $i => $h) {
                $h =  preg_replace('/[^a-zA-Z0-9_ ]/', '', $h);

                $indexToHeader[$i] = Str::of($h)->trim()->lower()->__toString();
            }

            fclose($stream);

            $rows = new LazyCollection(function() use ($tmpPath) {
                $f = fopen($tmpPath, 'r');
                fgetcsv($f, 0, ',');
                
                while (!feof($f)) {
                    $row = fgetcsv($f, 0, ',');
                    if ($row === false) continue;
                    yield $row;
                }

                fclose($f);
            });

            foreach ($rows as $rowIndex => $csvRow) {
                if ($csvRow === null || (count($csvRow) === 1 && trim($csvRow[0]) === '')) {
                    continue;
                }

                $assoc = [];
                foreach ($indexToHeader as $i => $colName) {
                    $value = $csvRow[$i] ?? null;
                    
                    if (is_string($value)) {
                        $value = preg_replace('/\\p{C}+/u', '', $value);
                        $value = trim($value);
                    }
                    
                    $assoc[$colName] = $value;
                }

                $uniqueKey = $assoc['unique_key'] ?? null;
                if (empty($uniqueKey)) {
                    // $failedRows[] = [
                    //     'row' => $rowIndex + 2, 
                    //     'index' => 'UNIQUE_KEY',
                    // ];

                    $failedRows[] = $rowIndex + 2;

                    $errors++;
                    
                    continue;
                } else {
                    $productRow = [
                        'unique_key' => $uniqueKey,
                        'product_title' => $assoc['product_title'],
                        'product_description' => $assoc['product_description'],
                        'style' => $assoc['style'],
                        'name' => $assoc['name'] ?? null,
                        'sanmar_mainframe_color' => $assoc['sanmar_mainframe_color'] ?? null,
                        'size' => $assoc['size'] ?? null,
                        'piece_price' => (float) $assoc['pierce_price'],
                        // 'meta' => json_encode(array_diff_key($assoc, array_flip(['unique_key','sku','name','piece_price','quantity'])))
                    ];

                    $fileRow[] = $productRow;
                }
               
                $processed++;

                // if (count($fileRow) >= $this->chunkSize) {
                //     $this->performUpsert($$fileRow);
                //     $fileRow = [];
                // }
            }

            if (! empty($failedRows)) {
                $failedRowList = implode(',', $failedRows);
                
                $this->fileUpload->update([
                    'status' => 'failed',
                    'message' => "Missing UNIQUE_KEY column value in row $failedRowList.",
                ]);

                $this->setStatus(500);
                $this->setValidationMessage("Missing UNIQUE_KEY column value in row $failedRowList");
                $this->setValidatedFileRow($fileRow);

                event(new UpdateFileUploadStatus($this->fileUpload));
                $returnData = $this->returnParams();

                return $returnData;
            }

            $this->setStatus(200);
            $this->setValidationMessage("");
            $this->setValidatedFileRow($fileRow);

            event(new UpdateFileUploadStatus($this->fileUpload));
            $returnData = $this->returnParams();

            return $returnData;
        // } catch (\Exception $e) {
        //     $this->fileUpload->update([
        //         'status' => 'failed',
        //         'message' => $e->getMessage()
        //     ]);
            
        //     $this->setStatus(500);
        //     $this->setValidationMessage($e->getMessage());

        //     $returnData = $this->returnParams();

        //     return $returnData;

        //     // record a generic import log for failure
        //     // ImportLog::create([
        //     //     'upload_id' => $this->fileUpload->id,
        //     //     'row_number' => null,
        //     //     'payload' => null,
        //     //     'reason' => 'Import failed: ' . $e->getMessage()
        //     // ]);
        // } finally {
        //     @unlink($tmpPath);
        // }
    }
}
