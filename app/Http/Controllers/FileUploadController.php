<?php

namespace App\Http\Controllers;

use App\Helpers\FileValidation;
use App\Http\Resources\FleUploadResource;
use App\Models\FileUpload;
use App\Models\Product;
use App\Jobs\ProcessFileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

use DB;

class FileUploadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $uploads = FileUpload::where('user_id', auth()->id())
        // DB::table('file_uploads')->truncate(); 
        // $products = Product::orderBy('id', 'desc')->get();
        // dd('products', $products);
        // $uplpads = FileUpload::orderBy('id', 'desc')->get();
        // dd('FileUpload', $uplpads);

        $now = now()->toDateTimeString();

        $uploads = FileUpload::orderBy('created_at', 'desc')
                            ->take(50)
                            ->get();
        
        foreach ($uploads as $upload) {
            $createdAt = $upload->created_at->format('Y-m-d H:i:s');

            // $timeDiffeence = now()->diffInMinutes($createdAt);
            $upload->time_difference = ceil($upload->created_at->diffInMinutes(now()));
        }
        
        $uploads = FleUploadResource::collection($uploads);

        return view('uploads.upload', [
            'uploads' => $uploads,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function save(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        // $userId = $user->id;

        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt|max:10240'
        ]);

        if ($validator->fails()) {
            return redirect()->route('file.upload.index')->withErrors(['error' => 'Invalid file extension uploaded']);
        }

        // Store the file in the 'public' disk and returns its path
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        $fullPath = Storage::disk('public')->putFileAs(
            'uploads', // Directory within 'storage/app/public'
            $file,
            $file->getClientOriginalName() // Use original filename
        );

        // $path = $request->file('file')->store('uploads', 'public');

        // $fullPath = base_path('app/' . $path);
        $url = Storage::disk('public')->url($fullPath);

        // $fileHash = md5_file('sha256', $fullPath);

        // $existing = FileUpload::where('file_hash', $fileHash)
        //     ->where('status', 'completed')
        //     ->first();

        $upload = FileUpload::create([
            'user_id' => $userId ?? 1,
            'file_name' => $fileName,
            'file_path' => $fullPath,
            'status' => 'pending'
        ]);

        $storeMessage = null;

        // to perform file validation 
        $fileValidation = new FileValidation($upload);
        $validation = $fileValidation->validateFileInput();

        if ($validation['status'] == 200) {
            $storeMessage = 'File uploaded (and queued) successfully.';

            $insertedRow = $validation['file_row_data'];
            
            $upload->update([
                'status' => 'processing',
                'message' => "File uploaded (and queued) successfully.",
            ]);
// dd('upload', $upload->id, $upload);
            // to trigger job to perform file upload
            ProcessFileUpload::dispatch($upload->id, $insertedRow);
        } else {
            $storeMessage = $validation['message'];
            
            $upload->update([
                'status' => 'failed',
                'processed_rows' => $existing->processed_rows,
                'message' => $storeMessage,
            ]);
        }

        return redirect()->route('file.upload.index')->withErrors(['error', $storeMessage]);
    }

    /**
     * Display the specified resource.
     */
    public function show(FileUpload $fileUpload)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FileUpload $fileUpload)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FileUpload $fileUpload)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FileUpload $fileUpload)
    {
        //
    }
}
